<?php
/**
 * OKex接口类
 */
namespace platform\driver\okex;

use Log;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use platform\driver\Driver;

class Okex extends Driver
{
    /**
     * rest api接口地址
     * @var string
     */
    private $api = '';

    /**
     * webSocket接口地址
     * @var string
     */
    private $stream = '';

    /**
     * API 访问密钥
     * @var string
     */
    private $accessKey = '';

    /**
     * 签名密钥
     * @var string
     */
    private $secretKey = '';

    /**
     * Okex接口配置
     * @var array
     */
    private $config = [];

    public function __construct($param = [])
    {
        $this->config = file_get_contents(dirname(__FILE__) . '/config.json');

        $this->config = json_decode($this->config, true);
        $this->api = $this->config['api'];

        $this->stream = $this->config['stream'];

        if (isset($param['access_key']) && isset($param['secret_key'])) {
            $this->accessKey = $param['access_key'];
            $this->secretKey = $param['secret_key'];
        } else {
            $this->accessKey = $this->config['access_key'];
            $this->secretKey = $this->config['secret_key'];
        }
    }

    /**
     * webSocket订阅ticker数据
     * @param $symbols
     * @param $callback
     * @return bool
     */
    public function ticker(...$data)
    {
        list($symbols, $callback) = $data;

        $param = [];
        foreach ($symbols as $symbol) {
            $param[] = [
                'event'   => 'addChannel',
                'channel' => 'ok_sub_spot_' . $symbol . '_ticker'
            ];
        }

        $this->webSocketClient($param, $callback);
        return true;
    }

    /**
     * webSocket接口统一请求客户端
     * @param $param
     * @param $callback
     * @return bool
     */
    protected function webSocketClient($param, $callback)
    {
        try {
            $con = new AsyncTcpConnection($this->stream);
        } catch (\Exception $e) {
            Log::record('请求webSocket接口出错 ' . $this->stream . $e->getMessage());
        }
        $con->transport = 'ssl'; // 设置以ssl加密方式访问，使之成为wss
        $con->onConnect = function($con) use ($param) {
            if (!empty($param)) {
                if (is_array($param)) {
                    foreach ($param as $value) {
                        $con->send(json_encode($value));
                    }
                } else {
                    $con->send(json_encode($param));
                }
            }
            //设置定时器向服务器发送心跳数据
            $con->timerId = Timer::add(30, function() use ($con) {
                $heartBeatData = json_encode(['event' => 'ping']);
                $con->send($heartBeatData);
            });
        };

        $con->onMessage = function($con, $data) use ($callback) {
            $data = json_decode(gzinflate($data), true);
            if (!isset($data['event'])) {
                call_user_func_array($callback, array($data));
            }
        };

        $con->connect();
        return true;
    }

    /**
     * 订阅depth数据[订阅10条]
     * @param $symbols
     * @param $callback
     * @return bool
     */
    public function depth(array $symbols, callable $callback): bool
    {
        $param = [];
        foreach ($symbols as $symbol => $value) {
            $param[] = [
                'event'   => 'addChannel',
                'channel' => 'ok_sub_spot_' . $symbol . '_depth_10'
            ];
        }

        $this->webSocketClient($param, $callback);
        return true;
    }

    /**
     * 订阅合约K线数据[订阅10条]
     * @param $symbols
     * @param $callback
     * @return bool
     */
    public function kline(...$data)
    {
        list($symbols, $callback) = $data;

        $param = [];
        foreach ($symbols as $symbol) {
            foreach (['1min', '5min', '15min', '1hour', 'day'] as $timer) {
                $param[] = [
                    'event'   => 'addChannel',
                    'channel' => 'ok_sub_spot_' . $symbol . '_kline_' . $timer
                ];
            }
        }

        $this->webSocketClient($param, $callback);
        return true;
    }

    /**
     * REST方式获取行情
     * @return array
     */
    public function restTicker($symbol)
    {
        $data = [];
        try {
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
            ];

            $data = curl($this->api . '/api/v1/ticker.do?symbol=' . $symbol, [], 'GET', $headers);
            $data = json_decode($data, true);
        } catch (\Exception $e) {
            Log::record('初始化OKEx请求行情数据出错' . $e->getMessage());
        }

        return $data;
    }

    /**
     * REST方式获取深度
     * @return array
     */
    public function restDepth($symbol)
    {
        $data = [];
        try {
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
            ];

            $data = curl($this->api . '/api/v1/depth.do?symbol=' . $symbol, [], 'GET', $headers);
            $data = json_decode($data, true);
        } catch (\Exception $e) {
            Log::record('初始化OKEx请求深度数据出错' . $e->getMessage());
        }

        return $data;
    }
}