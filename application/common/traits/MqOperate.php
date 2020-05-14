<?php
/**
 * MQ消费类,可复制
 *
 * @author   amd
 * @version  1.0
 */
namespace app\common\traits;
use Exception;
use Debug;
use Throwable;
use app\common\library\RabbitMQ;

trait MqOperate
{
	/**
     * 当前队列配置
     * @var array
     */
    private static $_mqConfig = [];

    /**
     * 消费统计
     * @var integer
     */
    private static $_count = 0;

    /**
     * 当前MQ实例
     * @var null
     */
    private static $_mq = NULL;

    /**
     * 直接确认ack的错误代码[自定义]
     * @var array
     */
    private static $_errorCode = [];

    /**
     * 日志容器
     * @var array
     */
    private static $_log = [];

    /**
     * 设置MQ消费函数
     * @var null
     */
	private static $_callbackFunc = NULL;

	/**
     * 设置回调处理函数
     * @return array
     */
	private static function _setCallback(string $funcName)
    {
		if (is_null(self::$_callbackFunc)) {
			self::$_callbackFunc = $funcName;
		}
    }

    /**
     * 设置错误码
     * @return array
     */
    private static function _setErrorCode($errorCode)
    {
		if (!in_array($errorCode, self::$_errorCode)) {
			self::$_errorCode[] = $errorCode;
		}
    }

    /**
     * 获取MQ配置
     * @return array
     */
    private static function _getConfig($config)
    {
        if (empty(self::$_mqConfig)) {
            return self::$_mqConfig = $config;
        } elseif (!is_array(self::$_mqConfig)) {
            return self::$_mqConfig = $config;
        }

        return self::$_mqConfig;
    }

    /**
     * 获取MQ连接实例
     */
    private static function _getMq(string $vhost)
    {
        if (!$vhost) {
            return false;
        }

        if (empty(self::$_mq) || !is_object(self::$_mq)) {
            self::$_mq = new RabbitMQ($vhost);
        }

        return self::$_mq;
    }

    /**
     * 判断给定的字符串是否json格式
     */
    private static function _isJson($string): bool
    {
        $decode = json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 消费确认
     */
    private static function _consumerAck($msgObj)
    {
        try {
            $mq = self::_getMq(self::$_mqConfig['vhost']);
            if (empty($mq) || !is_object($mq)) {
                exception('获取MQ实例失败!');
            }

            $mq->basicAck($msgObj);
        } catch (Throwable $e) {
            // 错误上报
            echo $e->getMessage() . PHP_EOL;
        } catch (Exception $e) {
            // 错误上报
            echo $e->getMessage() . PHP_EOL;
        }

        Debug::remark('ack', microtime(true));
        echo '第'.self::$_count.'个 耗时'.Debug::getRangeTime('callback', 'ack').'s'.PHP_EOL;
    }

    /**
     * 消费计数
     */
    private static function _setCount()
    {
        self::$_count++;
    }

    /**
     * 队列数据消费
     */
    private function _receive($mqConfig, $callbackObj = '')
    {
        try {
            $config = self::_getConfig($mqConfig);
            $mq = self::_getMq($config['vhost']);

            if (empty($mq) || !is_object($mq)) {
                exception('获取MQ实例失败!');
            }

            // 设置每个消费者必须消费完数据才能进行下一条
            $mq->setQos(1);

            if (!empty($callbackObj)) {
                $mq->basicReceive($config['queue'], array($callbackObj, 'callback'));
            } else {
                $mq->basicReceive($config['queue'], array($this, 'callback'));
            }
        } catch (Throwable $e) {
            echo $e->getMessage() . PHP_EOL;
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 消费：回调
     */
    public function callback($msgObj)
    {
        // 回调起始时间点
        Debug::remark('callback', microtime(true));
        try {
            if (!self::_isJson($msgObj->body)) {
                exception('非JSON格式的数据');
            }

            $data = json_decode($msgObj->body, true);
            if (!isset($data)) {
                exception('未知的数据格式');
            }

            $funcName = self::$_callbackFunc;

            //消费
            if (is_callable([$this, $funcName])) {
                $this->$funcName($data);
            }

            //消费计数
            self::_setCount();
            //确认ACK
            self::_consumerAck($msgObj);
        } catch (Exception $e) {
            //错误上报
            echo $e->getMessage() . PHP_EOL;

            //直接确认ack的错误
            if (in_array($e->getCode(), self::$_errorCode)) {
                self::_consumerAck($msgObj);
            }
        }
    }

    /**
     * 统计未消费的数量
     * @return mixed
     */
    private function _msgCount($mqConfig, $queue)
    {
        if (!$mqConfig || !$queue) {
            return false;
        }

        $connArgs = array(
            'host'     => $mqConfig[0],
            'port'     => $mqConfig[3],
            'login'    => $mqConfig[1],
            'password' => $mqConfig[2],
            'vhost'    => $mqConfig[4]
        );

        $conn = new \AMQPConnection($connArgs);
        $conn->connect();

        $channel = new \AMQPChannel($conn);
        $q = new \AMQPQueue($channel);
        $q->setName($queue);
        $q->setFlags(AMQP_PASSIVE);

        $messageCount = 0;
        $messageCount = $q->declare();
        $conn->disconnect();

        return $messageCount;
    }

    /**
     * 队列消息发布
     * @param array  $mqConfig 队列配置[rmq_queue]
     * @param mixed  $message  需要发布的消息[array|string]
     * $message格式：
     *     ['user_id' => '56', 'group_id' => 2] ['12','56','25'] 数组格式
     *     {"user_id" : "25", "group_id" => "2"} ["23", "46", "58"] json字符串格式
     *     12  单个标量格式（不能有逗号等标点）
     */
    public function publish($mqConfig, $message)
    {
        if (!$mqConfig || !$message) {
            return false;
        }

        try {
            if (is_scalar($message)) {
                if (self::_isJson($message)) {
                    $message = json_decode($message, true);
                    if (!is_array($message)) {
                        $message = json_encode([(string)$message]);
                    }
                } else {
                    $message = json_encode([(string)$message]);
                }
            }

            if (is_array($message)) {
                $message = json_encode($message);
            }

            // 获取MQ实例
            $mq = self::_getMq($mqConfig['vhost']);
            if (empty($mq) || !is_object($mq)) {
                exception('获取MQ实例失败!');
            }

            if (!empty($mqConfig['routekey'])) {
                // topic类型发布
                $mq->topicPublish($mqConfig['exchange'], $message, $mqConfig['routekey']);
            } else {
                // basic发布
                $mq->basicPublish($mqConfig['exchange'], $message);
            }
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}