<?php
/**
 * RabbitMQ操作类
 *
 * @author   chat
 * @version  1.0
 */

namespace app\common\library;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Config;

class RabbitMQ
{
    /**
     * 频道
     * @var source
     */
    private $ch;

    /**
     * 队列消息
     */
    private $msg;

    /**
     * 连接句柄
     * @var source
     */
    private $conn;

    /**
     * MQ配置信息
     * @var array
     */
    private $system;

    /**
     * 消费者标识符
     * @var string
     */
    private $consumer_tag;

    /**
     * 是否需要重推消息
     * @var boolean
     */
    private $isRePublish = false;

    /**
     * 重推消息ID
     * @var string
     */
    private $mid;

    /**
     * 定义回调函数
     * @var callback
     */
    private $callback;

    /**
     * 初始化构造函数
     * @param string $system MQ配置系统名称
     */
    public function __construct($system)
    {
        if (!empty($system)) {
            $this->connection($system);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        if (is_object($this->ch)) {
            $this->ch->close();
        }

        if (is_object($this->conn)) {
            $this->conn->close();
        }
    }

    /**
     * 根据key获取MQ详细配置
     * @param string $system 系统名称
     * @return mixed
     */
    private static function _getConfig($system)
    {
        // 连接RabbitMQ虚拟机
        $systemList = Config::get('app.mq_vhost');
        return $systemList[$system] ?? false;
    }

    /**
     * 实例化后rabbitMQ连接
     * @param string $system 系统名称
     * @return bool
     */
    public function connection($system)
    {
        if (!$system) {
            return false;
        }

        if (empty($this->conn) || ($system != $this->system)) {
            return $this->_resetConnection($system);
        }

        return true;
    }

    /**
     * 强制重置rabbitMQ连接
     * @param  string $system 系统名称
     * @return bool
     */
    private function _resetConnection($system)
    {
        $this->system = $system;
        $rmqConfig = self::_getConfig($system);

        if ($rmqConfig) {
            // 关闭之前的通道和连接
            if (is_object($this->ch)) {
                $this->ch->close();
            }

            if (is_object($this->conn)) {
                $this->conn->close();
            }

            list($host, $user, $password, $port, $vhost) = $rmqConfig;
            // 捕获异常
            try {
                $this->conn = new AMQPConnection($host, $port, $user, $password, $vhost);
            } catch (\Throwable $e) {
                return $e->getMessage();
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            if (!$this->conn->isConnected()) {
                return false;
            }

            $this->consumer_tag = 'consumer' . getmypid();
            $this->ch           = $this->conn->channel();
            $this->system       = $system;
            return true;
        }

        return false;
    }

    /**
     * 设置消息体大小限制
     * @param string|int $bytes 字节数
     */
    private function setBodySizeLimit($bytes = 0)
    {
        $this->ch->setBodySizeLimit($bytes);
    }

    /**
     * 添加交换器
     * @param string $ename 交换器名称
     * @param string $type 交换器的消息传递方式 可选:'fanout','direct','topic','headers'
     * 'fanout':不处理(忽略)路由键，将消息广播给绑定到该交换机的所有队列
     * 'diect':处理路由键，对路由键进行全文匹配。对于路由键为"xzy_rain"的消息只会分发给路由键绑定为"xzy_rain"的队列,不会分发给路由键绑定为"xzy_music"的队列
     * 'topic':处理路由键，按模式匹配路由键。模式符号 "#" 表示一个或多个单词，"*" 仅匹配一个单词。如 "xzy.#" 可匹配 "xzy.rain.music"，但 "xzy.*" 只匹配 "xzy.rain"和"xzy.music"。只能用"."进行连接，键长度不超过255字节
     * @param boolean $durable 是否持久化
     * @param boolean $autoDelete 当所有绑定队列都不再使用时，是否自动删除该交换机
     */
    public function addExchange($ename, $type = 'fanout', $durable = true, $autoDelete = false)
    {
        return $this->ch->exchange_declare($ename, $type, false, $durable, $autoDelete);
    }

    /**
     * 添加队列
     * @param string $qname 队列名称
     * @param boolean $durable 是否持久化
     * @param boolean $exclusive 仅创建者可以使用的私有队列，断开后自动删除
     * @param boolean $autoDelete 当所有消费客户端连接断开后，是否自动删除队列
     * @return int 该队列的ready消息数量
     */
    public function addQueue($qname, $durable = true, $exclusive = false, $autoDelete = false)
    {
        return $this->ch->queue_declare($qname, false, $durable, $exclusive, $autoDelete);
    }

    /**
     * 绑定队列和交换器
     * @param string $qname 队列名称
     * @param string $ename 交换器名称
     * @param string $routing_key 路由键 注:在fanout的交换器中路由键会被忽略
     */
    public function bind($qname, $ename, $routingKey = '')
    {
        $this->ch->queue_bind($qname, $ename, $routingKey);
    }

    /**
     * 设置消费者预取消息数量
     * @param string|int $count 预取消息数量
     */
    public function setQos($count = 1)
    {
        $this->ch->basic_qos(null, $count, null);
    }

    /**
     * 基础模型之消息发布
     * @param string $exchange 交换器名称
     * @param string|array $msg 发布内容
     * @param string $mqtype 发布消息的类型
     * @return bool
     */
    public function basicPublish($exchange, $msg)
    {
        try {
            $tosend = new AMQPMessage(
                is_array($msg) ? json_encode($msg) : $msg,
                array('content_type'  => 'text/plain', 'delivery_mode' => 2)
            );

            if (empty($this->ch)) {
                throw new \Exception('ERROR:Func[basicPublish] Exchange['.$exchange.'] this->ch Empty');
            }

            return $this->ch->basic_publish($tosend, $exchange);
        } catch (\Exception $e) {
            //保存现场
            $this->_saveTmpMsg();
            return $e->getMessage();
        }
    }

    /**
     * 基础模型之消息接受
     * @param string $exchange
     * @param string $queue
     * @param array $callback
     * @param string $mqtype
     * @return string
     */
    public function basicReceive($queue, $callback = array('RabbitMQ', 'process_message'))
    {
        $this->ch->basic_consume($queue, $this->consumer_tag, false, false, false, false, $callback);
        while (count($this->ch->callbacks)) {
            $read   = array($this->conn->getSocket());
            $write  = NULL;
            $except = NULL;

            //预防僵尸进程
            if (false == ($numChangedStreams = stream_select($read, $write, $except, 50))) {
                echo '################################'.PHP_EOL;
                echo '['.date('Y-m-d H:i:s').'] func:basicReceive socket error!'.PHP_EOL;
                echo '################################'.PHP_EOL;
                exit;
            } elseif ($numChangedStreams > 0) {
                $this->ch->wait();
            }
        }
    }

    /**
     * Pub/Sub 之消息发布
     * @param string $exchange 交换器名称
     * @param string|array $msg 发布内容
     * @param string $mqtype 发布消息的类型
     * @return bool
     */
    public function queuePublish($exchange, $msg)
    {
        $this->ackHandler();
        $tosend = new AMQPMessage(json_encode($msg), array('content_type' => 'text/plain', 'delivery_mode' => 2));
        $this->ch->basic_publish($tosend, $exchange);
        $this->waitAck();
    }

    /**
     * Pub/Sub 之消息接受
     * @param string $exchange 交换器名称
     * @param string $queue 队列名称
     * @param string $callback 注册回调函数
     * @param string|array $msg 发布内容
     * @param string $mqtype 发布消息的类型
     * @return bool
     */
    public function queueSubscribe($queue, $callback = array('RabbitMQ', 'process_message'))
    {
        $this->ch->basic_consume($queue, $this->consumer_tag, false, false, false, false, $callback);
        while(count($this->ch->callbacks)){
            $read   = array($this->conn->getSocket());
            $write  = NULL;
            $except = NULL;

            //预防僵尸进程
            if (false == ($numChangedStreams = stream_select($read, $write, $except, 50))) {
                echo '################################'.PHP_EOL;
                echo '['.date('Y-m-d H:i:s').'] func:basicReceive socket error!'.PHP_EOL;
                echo '################################'.PHP_EOL;
                exit;
            } elseif ($numChangedStreams > 0) {
                $this->ch->wait();
            }
        }
    }

    /**
     * 根据主题进行消息推送
     * @param string $exchange 交换器名称
     * @param string|array $msg 发布内容
     * @param string $routingKey 路由键 注:在fanout的交换器中路由键会被忽略
     * @return bool
     */
    public function topicPublish($exchange, $msg, $routingKey)
    {
        try {
            $msg    = is_array($msg) ? json_encode($msg) : $msg;
            $tosend = new AMQPMessage(
                $msg,
                array(
                    'content_type' => 'text/plain',
                    'delivery_mode' => 2
                )
            );

            //异常
            if (empty($this->ch)) {
                throw new \Exception('ERROR:Func[topicPublish] Exchange['.$exchange.'] this->ch Empty');
            }

            $this->ch->basic_publish($tosend, $exchange, $routingKey);
            $this->waitAck();
        } catch (\Exception $e) {
            //保存现场
            $this->_saveTmpMsg();
            return $e->getMessage();
        }
    }

    /**
     * 根据主题进行消息消费
     * @param string  $queue 队列名称
     * @param string  $callback 注册回调函数
     * @param boolean $noAck 是否不需要发送ACK
     * @return bool
     */
    public function topicSubscribe($queue, $callback, $noAck = false)
    {
        $this->callback = $callback;
        $this->ch->basic_consume($queue, $this->consumer_tag, false, $noAck, false, false, [$this,'process_message']);
        while (count($this->ch->callbacks)) {
            $read   = array($this->conn->getSocket());
            $write  = NULL;
            $except = NULL;

            //预防僵尸进程
            if (false == ($numChangedStreams = stream_select($read, $write, $except, 50))) {
                echo '################################'.PHP_EOL;
                echo '['.date('Y-m-d H:i:s').'] func:basicReceive socket error!'.PHP_EOL;
                echo '################################'.PHP_EOL;
                exit;
            } else if($numChangedStreams > 0) {
                $this->ch->wait();
            }
        }
    }

    /**
     * Pub/Sub 之批量消息接受，默认接受200条数据
     * @param string $exchange 交换器名称
     * @param string $queue 队列名称
     * @param int $limit 返回条数
     * @param bool $extral 返回数据类型， true为json_decode， false为json
     * @return array
     */
    public function queueSubscribeLimit($exchange, $queue, $limit = 200, $extral = true, $mqtype = 'fanout')
    {
        $messageCount = $this->ch->queue_declare($queue, false, true, false, false);
        $this->ch->queue_bind($queue, $exchange);
        $i        = 0;
        $max      = $limit < 200 ? $limit : 200;
        $orderids = array();
        while ($i < $messageCount[1] && $i < $max) {
            $this->msg = $this->ch->basic_get($queue);
            $this->ch->basic_ack($this->msg->delivery_info['delivery_tag']);
            if ($extral === false) {
                array_push($orderids, $this->msg->body);
            } else {
                array_push($orderids, json_decode($this->msg->body, true));
            }

            $i++;
        }

        return $orderids;
    }

    /**
     * 重推消息
     * @param string|int $mid 重推消息id
     * @param string $exchange 交换器名称
     * @param string|array $msg 发布内容
     * @param string $routingKey 路由键 注:在fanout的交换器中路由键会被忽略
     * @return bool
     */
    public function rePublish($mid,$exchange, $msg, $routingKey = '')
    {
        $this->isRePublish = true;
        $this->mid = $mid;
        $msg = is_array($msg) ? json_encode($msg) : $msg;
        $tosend = new AMQPMessage($msg, array('content_type' => 'text/plain', 'delivery_mode' => 2));
        $this->ch->basic_publish($tosend, $exchange, $routingKey);
        $this->waitAck();
        $this->isRePublish = false;//为了防止之后调用其他推送方法出现异常
    }

    /**
     * 销毁队列中的数据
     * @param $msgObj
     * @return bool
     */
    public function basicAck($msgObj){
        $this->ch->basic_ack($msgObj->delivery_info['delivery_tag']);
    }

    /**
     * 推送回调处理
     */
    public function ackHandler()
    {
        $this->ch->set_ack_handler(function (AMQPMessage $message) {
            if ($this->isRePublish) {
                $funcInfo = ['id'=>$this->mid,'status'=>1];
            }
        });

        $this->ch->set_nack_handler(function (AMQPMessage $message) {
            if ($this->isRePublish) {
                $funcInfo = ['id'=>$this->mid];
            } else {
                $funcInfo = $this->getPublishInfo();
            }
        });

        $this->ch->confirm_select();
    }

    /**
     * 等待Ack确认消息
     */
    public function waitAck()
    {
        $this->ch->wait_for_pending_acks();
    }

    /**
     * 默认回调函数
     * @param object $msgObj
     * @return bool
     */
    public function process_message($msgObj)
    {
        $rerult = call_user_func($this->callback, $msgObj);
        if (($rerult['ack'])) {
            $this->basicAck($msgObj);
        }
    }

    /**
     * 关闭消费者
     * @param $msgObj
     * @return array
     */
    public function cancelConsumer($msgObj)
    {
        $msgObj->delivery_info['channel']->basic_cancel($msgObj->delivery_info['consumer_tag']);
    }

    /**
     * 获取消息推送失败时的快照信息，以便之后再次推送
     * @return array
     */
    public function getPublishInfo()
    {
        $backtraceList = debug_backtrace();//TODO 个人觉得还有其他方法,这个也不错
        $funcInfo  = [];
        foreach ($backtraceList as $bt) {
            $function = $bt['function'];
            $args     = $bt['args'];
            if (substr($function, -7) == 'Publish') {
                $funcInfo['function'] = $function;
                $funcInfo['exchange'] = $args[0];
                $funcInfo['msg']      = is_array($args[1]) ? json_encode($args[1]) : $args[1];
                $funcInfo['routeKey'] = $args[2];
                $funcInfo['system']   = $this->system;
                break;
            }
        }

        return $funcInfo;
    }

    /**
     * 保存消息推送失败时的现场
     */
    private function _saveTmpMsg()
    {
        if (!Config::get('mq_tmp_msg_save_path')) {
            return false;
        }

        $list   = $this->getPublishInfo();
        $file   = Config::get('mq_tmp_msg_save_path') . 'tmp_' . date('Ymd') . '.bak';
        $de     = '<-*##*->'; // 分隔符
        return file_put_contents($file, json_encode($list).$de, FILE_APPEND); // 追加
    }

    /**
     * 测试是否链接
     * @return boolean
     */
    public function isConnected()
    {
        if (!$this->conn->isConnected()) {
            return false;
        }

        return true;
    }
}
