<?php
/**
 * 通用数据客户端
 * @version 1.0
 */
namespace app\script\controller;

use Workerman\Worker;

use Workerman\Lib\Timer;
use app\common\service\Exchange;
use SplObjectStorage;
use think\facade\Config;


class General
{
    /**
     * Workerman实例容器
     * @var array
     */
    private static $_workerContainer = [];

    /**
     * 定时器[请求通用接口时间间隔]
     * @var integer
     */
    protected $_timeInterval = 480;

    /**
     * 交易对简码
     * @var null
     */
    protected $_symbolList = null;

    /**
     * 批量实例化workerman实例
     */
    public function makeWorker(array $workers): void
    {
        self::$_workerContainer = new SplObjectStorage();
        foreach ($workers as $name) {
            // 行情
            $worker = new Worker();
            // 实例名称
            $worker->name = $name;
            // 进程个数
            $worker->count = 1;
            // 加入容器
            self::$_workerContainer->attach($worker, $name);
        }
    }

    /**
     * 初始化客户端
     */
    public function __construct()
    {
        set_time_limit(0);// 执行时间无限
        ini_set('default_socket_timeout', 120);// 不超时

        // 后台更新品种配置后,需要重启进程
        if (is_null($this->_symbolList)) {
            $this->_symbolList = model('common/ExchangeSymbol')->getSymbolList(null, 'symbol');
        }

        Worker::$stdoutFile = '/dev/null';
        $this->makeWorker(['Quote']);

        self::$_workerContainer->rewind();//遍历先前所有指针
        while (self::$_workerContainer->valid()) {//当当前迭代器条目返回真时
            $worker = self::$_workerContainer->current();//当前条目

            // 设置回调
            foreach (['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerStop', 'onWorkerReload'] as $event) {
                $method = ($event == 'onWorkerStart') ? 'on' . $worker->name . 'WorkerStart' : $event;
                if (method_exists($this, $method)) {
                    $worker->$event = [$this, $method];
                }
            }

            self::$_workerContainer->next();
        }

        // Run worker
        Worker::runAll();
    }

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        $connection->send('我收到你的信息了');
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {

    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {

    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * Quote进程启动
     */
    public function onQuoteWorkerStart($worker)
    {
        // 添加行情定时器
        $time = $this->_timeInterval;
        foreach ($this->_symbolList as $index => $symbol) {
            if ($index > 0) {
                $time += rand(4, 10);
            }

            Timer::add($time, function() use($symbol) {
                // 多个币种行情
                Exchange::make('General')->ticker($symbol);
            });
        }
    }
}
