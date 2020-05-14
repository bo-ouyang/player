<?php
/**
 * Websocket服务端,发布数据到前台
 * @version 1.0
 */
namespace app\script\controller;

use Workerman\Worker;
use Workerman\Lib\Timer;
use think\facade\Config;
use app\common\service\Exchange;
use math\BCMath;
use think\facade\Response;
use random\Random;
use token\Token;
use think\facade\Cache;
use think\worker\Server as WorkerServer;
use app\common\model\Kline;

class Server extends WorkerServer
{
    /**
     * Worker实例
     * @var object
     */
    protected $worker;

    /**
     * websocket服务端地址
     * @var string
     */
    protected $socket;

    /**
     * 内部通讯地址
     * @var string
     */
    protected $innerSocket;

    /**
     * 进程数
     * @var integer
     */
    protected $processes = 4;

    /**
     * 是否开启端口复用
     * @var boolean
     */
    protected $reusePort = true;

    /**
     * 心跳间隔时间,默认25秒
     * @var integer
     */
    protected $heartbeatTime = 25;

    /**
     * 定时器,默认1秒运行一次
     * @var integer
     */
    protected $timing = 1;

    /**
     * 限制访问频率
     * @var integer
     */
    protected $limitTime = 1;

    /**
     * 平台实例
     * @var null
     */
    protected static $_exchange = NULL;

    /**
     * redis前缀
     * @var null
     */
    protected static $_redisPrefix = NULL;

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * 当前允许的请求类型
     * @var array
     */
    protected $_channel = ['quote', 'kline'];

    /**
     * 客户端时间和服务器时间差值
     * @var int
     */
    protected $_diffTime = 300;

    /**
     * 首页分组ID
     * @var integer
     */
    protected $_homeGroup = 1;

    /**
     * 开机启动 [php server.php start -d]
     * 常驻进程
     * 监控进程
     * 10分钟删除一次日志
     * @access public
     */
    public function __construct(Request $request = null)
    {
        $this->request = is_null($request) ? Request::instance() : $request;
        // 初始化
        $this->initialize();

        $this->socket      = Config::get('worker_server.protocol') . '://' . Config::get('worker_server.host') . ':' . Config::get('worker_server.port');
        $this->innerSocket = Config::get('app.websocket_inner');

        // 实例化 Websocket 服务
        $this->worker = new Worker($this->socket);

        // 实例名称
        $this->worker->name = 'Amd-Exchange';
        // 设置进程数
        $this->worker->count = $this->processes;
        // worker连接句柄容器
        $this->worker->uidConnections = [];
        // 所有ID
        $this->worker->requestIds = [];

        // 设置回调
        foreach (['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerStop', 'onWorkerReload'] as $event) {
            if (method_exists($this, $event)) {
                $this->worker->$event = [$this, $event];
            }
        }
        // Run worker
        Worker::runAll();
    }

    /**
     * 初始化操作
     */
    private function initialize()
    {
        set_time_limit(0);// 执行时间无限
        ini_set('default_socket_timeout', 120);// 不超时

        if (is_null(self::$_exchange)) {
            self::$_exchange = service('common/Exchange');
        }

        if (is_null(self::$_redisPrefix)) {
            $config = Config::get('cache.redis');
            self::$_redisPrefix = $config['prefix'];
        }
    }

    /**
     * 系统级参数检测
     */
    private function _systemParamValidate(array $data): bool
    {
        $paramKey = ['event', 'channel', 'symbol', 'token', 'timestamp', 'sign', 'type', 'group', 'page', 'user'];
        if (!empty(array_diff(array_keys($data), $paramKey))) {
            return false;
        }

        if (empty($data['event']) || !in_array($data['event'], ['search', 'init', 'ping'])) {
            return false;
        }

        if (empty($data['token'])) {
            return false;
        }

        if (!empty($data['channel']) && !in_array($data['channel'], $this->_channel)) {
            return false;
        }

        if (!empty($data['channel']) && $data['channel'] == 'kline' && (empty($data['type']) || empty($data['symbol']))) {
            return false;
        }

        if (!empty($data['type']) && !in_array($data['type'], array_keys(Kline::$typeLabels))) {
            return false;
        }

        if (!empty($data['symbol']) && !empty($data['group'])) {
            return false;
        }

        if (!empty($data['channel']) && $data['channel'] == 'kline' && !empty($data['group'])) {
            return false;
        }

        return true;
    }

    /**
     * token，时间戳，签名验证
     */
    private function _tokenValidate($connection, $data)
    {
        // token
        $token  = $data['token'];
        $origin = Token::get($token);
        if ($connection->token != $token || (!empty($origin) && $origin['token'] != $token)) {
            return false;
        }

        // 客户端ID随机变动，无法匹配
        // $origin = Token::get($token);
        // if (empty($origin) || $connection->id != $origin['user_id']) {
        //     return false;
        // }

        // 时间戳
        $clientTime = floor($data['timestamp']/1000);
        if (abs(time() - $clientTime) > $this->_diffTime) {
            return false;
        }

        // 签名
        if (sha1($data['token'] . $data['timestamp']) != $data['sign']) {
            return false;
        }

        return true;
    }

    /**
     * 针对单个客户端发送数据
     */
    private function _sendData($uid, $message)
    {
        if (isset($this->worker->uidConnections[$uid])) {
            $connection = $this->worker->uidConnections[$uid];

            $cnt = 0;
            while ($cnt < 3 && ($flag = $connection->send($message)) === false) {
                $cnt++;
            }

            return true;
        }

        return false;
    }

    /**
     * 收到信息
     * ['event' => 'ping', 'token' => 'xxxxx', 'timestamp' => 1523265748, 'sign' => 'xxxxxx'] 心跳
     * ['event' => 'init', 'channel' => 'quote', 'token' => 'xxxx', 'timestamp' => 1523265748, 'sign' => 'xxxxxx'] 首页第一页行情初始化[首页组]
     * ['event' => 'search', 'channel' => 'quote', 'symbol' => 'btc_usdt', 'token' => 'xxxx', 'timestamp' => 1523265748, 'sign' => 'xxxxxx'] 单个品种行情搜索
     * ['event' => 'search', 'channel' => 'quote', 'group' => '2', 'page'=> '1', 'token' => 'xxxx', 'timestamp' => 1523265748, 'sign' => 'xxxxxx'] 按照组ID搜索行情[分组行情]
     * ['event' => 'search', 'channel' => 'quote', 'user' => '2', 'page'=> '1', 'token' => 'xxxx', 'timestamp' => 1523265748, 'sign' => 'xxxxxx'] 按照用户ID搜索行情[用户自选]
     * ['event' => 'search', 'channel' => 'kline', 'symbol' => 'btc_usdt', 'token' => 'xxxx', 'timestamp' => 1523265748, 'sign' => 'xxxxxx', 'type' => 'one|five|fifteen|hour|day'] K线
     */
    public function onMessage($connection, $data)
    {
        $uid = $connection->token;
        $dataSign = crc32($data);
        $currTime = time();
        // 限制客户端访问频率
        if (!empty($connection->lastMessageSign) && ($connection->lastMessageSign == $dataSign) && !empty($connection->lastMessageTime) && ($currTime - $connection->lastMessageTime) < $this->limitTime) {
            $connection->send(self::_output(['message' => 'request too frequently']));
            return;
        }

        // 设置最后一次数据时间
        $connection->lastMessageTime = $currTime;
        $connection->lastMessageSign = $dataSign;

        $message = json_decode($data, true);

        // 检测数据格式
        if (!$message || json_last_error() != JSON_ERROR_NONE) {
            $connection->close();
            return;
        }

        // 系统级别参数验证
        if (!$this->_systemParamValidate($message)) {
            $connection->close();
            return;
        }

        // token验证
        if (!$this->_tokenValidate($connection, $message)) {
            $connection->close();
            return;
        }

        // token验证通过,存入容器
        if (!isset($this->worker->uidConnections[$uid])) {
            $this->worker->uidConnections[$uid] = $connection;
        }

        if (isset($message['channel'])) {
            if (!isset($this->worker->requestIds[$message['channel']])) {
                $this->worker->requestIds[$message['channel']] = [];
            }

            // eth_usdt
            switch ($message['event']) {
                case 'init':
                    $clientSymbol = 'group_' . $this->_homeGroup;// 首页分组ID
                    break;
                case 'search':
                    if (!empty($message['group'])) {
                        $clientSymbol = 'group_' . $message['group'];// 分组页面ID
                    } elseif (!empty($message['user'])) {
                        $clientSymbol = 'user_' . $message['user'];// 用户ID
                    } else {
                        $clientSymbol = $message['symbol'] ?? '';// 品种代码
                    }

                    break;
            }

            // 没有type,默认等于channel[行情]
            $type = $message['type'] ?? $message['channel'];

            // btc_usdt#quote  btc_usdt#five
            // all#quote  all#five
            if (!array_key_exists($uid, $this->worker->requestIds[$message['channel']])) {
                $this->worker->requestIds[$message['channel']][$uid] = $clientSymbol . '#' . $type;
            } else {
                // 同一个客户端,切换不同的搜索条件
                if ($this->worker->requestIds[$message['channel']][$uid] != $clientSymbol . '#' . $type) {
                    $this->worker->requestIds[$message['channel']][$uid] = $clientSymbol . '#' . $type;
                }
            }
        }

        // 1.首页第一页init,首页第二页开始和分组页面按照分组ID搜索[分组搜索没有symbol]
        // 2.K线按照单个品种搜索
        // 当前客户端ID
        switch ($message['event']) {
            // 客户端回应服务端的心跳[单个客户端],25秒一次
            case 'ping':
                $this->_sendData($uid, self::_output(['event' => 'pong']));
                break;
            case 'search':
                # 前台发送来的搜索请求,行情|K线
                $symbol = !empty($message['symbol']) ? $message['symbol'] : '';
                $group  = !empty($message['group']) ? $message['group'] : '';
                $user   = !empty($message['user']) ? $message['user'] : '';
                $page   = !empty($message['page']) ? $message['page'] : '';

                $result = $this->_buildData($message['channel'], $symbol, $type, $group, $user, $page, true);
                $this->_sendData($uid, self::_output($result, $message['channel']));
                break;
            case 'init':
                # 前台浏览器数据初始化,首页组默认显示
                $result = [];
                $result = $this->_initQuoteData();

                $this->_sendData($uid, self::_output($result, $message['channel']));
                break;
        }
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        $connection->onWebSocketConnect = function($connection, $httpHeader)
        {
            // 访问来源控制
            if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false) {
                $connection->close();
            }
        };

        // 发送token
        $token = Random::uuid();
        Token::set($token, $connection->id);
        // 设置自定义属性
        $connection->token = $token;
        // 连接后发送给客户端
        $connection->send(self::_output(['token' => $token]));
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        // 删除token
        $token = NULL;
        if ($connection->token) {
            $token = $connection->token;
            Token::delete($connection->token);
            $connection->token = NULL;
        }

        if (empty($this->worker->uidConnections)) {
            return;
        }

        if (isset($this->worker->uidConnections[$token])) {
            // 连接断开时删除映射
            $this->worker->uidConnections[$token] = NULL;
            unset($this->worker->uidConnections[$token]);
        }

        foreach ($this->_channel as $channel) {
            if (!empty($this->worker->requestIds[$channel]) && array_key_exists($token, $this->worker->requestIds[$channel])) {
                $this->worker->requestIds[$channel][$token] = NULL;
                unset($this->worker->requestIds[$channel][$token]);
            }
        }

        unset($token);
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        $connection->send(self::_output([], '', $code, $msg));
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        Timer::add($this->timing, function() use($worker) {
            $nowTime = time();

            foreach ($worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $nowTime;
                    continue;
                }

                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($nowTime - $connection->lastMessageTime > $this->heartbeatTime) {
                    if (!empty($connection->token) && isset($this->worker->uidConnections[$connection->token])) {
                        $this->worker->uidConnections[$connection->token] = NULL;
                        unset($this->worker->uidConnections[$connection->token]);
                    }

                    $connection->close();
                }
            }
        });

        // 开启一个内部端口，供内部系统推送数据，Text协议格式 文本+换行符[线上开启9777端口防火墙]
        $innerWorker = new Worker($this->innerSocket);
        // 异步任务进程数
        // $innerWorker->count = 10;
        // 允许多进程端口复用
        $innerWorker->reusePort = $this->reusePort;
        $innerWorker->onMessage = function($innerConnect, $data)
        {
            if (empty($data)) {
                return;
            }

            // 接受后端推送过来的数据
            $message = json_decode($data, true);
            unset($data);
            if (!empty($message['symbol']) && !empty($message['channel'])) {
                // 接受脚本请求,广播消息给所有客户端
                $result = $this->_broadcast($message);
                // 返回推送结果
                $innerConnect->send($result ? 'ok' : 'fail');
            }
        };

        // $innerWorker->onClose = function($innerConnect)
        // {
        //     $innerConnect->close();
        // };

        // 执行监听
        $innerWorker->listen();
    }

    /**
     * 推送数据到所有已验证的客户端
     */
    private function _broadcast($data)
    {
        if (!$data['channel']) {
            return false;
        }

        if (!isset($this->worker->requestIds[$data['channel']]) || empty($this->worker->requestIds[$data['channel']])) {
            return true;
        }

        $type = !empty($data['type']) ? $data['type'] : '';
        // 获取当前币种对应的行情
        $buildData = $this->_buildData($data['channel'], $data['symbol'], $type);

        $message = self::_output($buildData, $data['channel']);

        // 判断当前品种属于哪个分组
        if ($data['channel'] == 'quote') {
            $group = model('common/SymbolConfig')->getGroupBySymbol($data['symbol']);
        }

        $userSymbolModel = model('common/UserSymbol');
        // $symbol格式：eth_usdt#one或eth_usdt#quote
        foreach ($this->worker->requestIds[$data['channel']] as $connId => $symbol) {
            // 获取币种
            $realData = explode('#', $symbol);
            $realSymbol = $realData[0];// 品种代码[btc_usdt或者all]或者分组ID
            $realType   = $realData[1];// K线类型[five或者quote]

            if ($data['channel'] == 'quote') {
                // 分组搜索
                if (strpos($realSymbol, 'group_') !== false) {
                    if ($realSymbol == 'group_' . $group['config_id'] && array_key_exists($connId, $this->worker->uidConnections)) {
                        $this->worker->uidConnections[$connId]->send($message);
                    }
                }

                // 用户自选
                if (strpos($realSymbol, 'user_') !== false) {
                    // 获取用户自选品种
                    $tmpList = explode('_', $realSymbol);
                    $userSymbol = $userSymbolModel->getUserSymbol($tmpList[1]);
                    if (!empty($userSymbol) && in_array($data['symbol'], $userSymbol) && array_key_exists($connId, $this->worker->uidConnections)) {
                        unset($userSymbol);
                        $this->worker->uidConnections[$connId]->send($message);
                    }
                }

                if (strpos($realSymbol, '_') === false) {
                    if (array_key_exists($connId, $this->worker->uidConnections) && in_array($realSymbol, [$data['symbol'], 'all'])) {
                        $this->worker->uidConnections[$connId]->send($message);
                    }
                }
            } else {
                if (array_key_exists($connId, $this->worker->uidConnections) && in_array($realSymbol, [$data['symbol'], 'all']) && $data['type'] == $realType) {
                    $this->worker->uidConnections[$connId]->send($message);
                }
            }
        }

        return true;
    }

    /**
     * 组装前台消息[行情|K线]
     */
    private function _buildData($channel, $symbol = null, $type = null, $group = null, $user = null, $page = null, $signFlag = false)
    {
        if (!$channel) {
            return false;
        }

        $result = [];
        switch ($channel) {
            case 'quote':
                // 行情
                if (!empty($group)) {
                    $result = self::$_exchange->getDetailByGroup($group, $page, true);
                }

                if (!empty($user)) {
                    $result = self::$_exchange->getDetailByUser($user, $page, true);
                }

                if (!empty($symbol)) {
                    $symbolData = self::$_exchange->getDetail($symbol, true);
                    $result['symbol'] = $symbol;
                    $result['data']   = $signFlag ? [$symbolData] : $symbolData;
                }

                break;
            case 'kline':
                $result['symbol'] = $symbol;
                $result['data']   = self::$_exchange->getKline($symbol, $type);

                $result = array_merge($result, self::$_exchange->getDetail($symbol, true));
                break;
        }

        return $result;
    }

    /**
     * 输出字符串
     */
    private static function _output(array $data, $channel = '', $code = '', $msg = ''): string
    {
        $code = !empty($code) ? $code : \app\common\exception\System::E_SUCCESS;
        $msg  = !empty($msg) ? $msg : 'request success';
        if ($code == \app\common\exception\System::E_SUCCESS) {
            $output = success_return($data, $code, $msg);
        } else {
            $output = error_return($code, $msg);
        }

        if (!empty($channel)) {
            $output = array_merge(['channel' => $channel], $output);
        }

        return json_encode($output);
    }

    /**
     * 获取首页组的行情数据
     */
    private function _initQuoteData()
    {
        return self::$_exchange->getDetailByGroup($this->_homeGroup, 1, true);
    }
}
