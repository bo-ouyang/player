<?php
/**
 * 通用API接口类
 */
namespace platform\driver\general;

use Log;
use platform\driver\Driver;
use Config;
use http\Http;

class General extends Driver
{
    /**
     * rest api接口地址
     * @var string
     */
    private $api = '';

    /**
     * 通用接口Code
     * @var string
     */
    private $appCode = '';

    /**
     * 初始化
     */
    public function __construct($param = [])
    {
    	$config = Config::get('app.general_api');
    	if (!empty($param)) {
    		$config = array_merge($config, $param);
    	}

        $this->api     = $config['basic_url'];
        $this->appCode = $config['app_code'];
    }

    /**
     * 批量行情数据
     * @param $symbols
     * @return bool
     */
    public function ticker(...$data)
    {
    	list($symbols) = $data;
    	if (is_array($symbols)) {
    		$symbols = join(',', $symbols);
    	}

    	$result = [];
        try {
        	$params  = ['symbols' => $symbols];
            $headers = ['Authorization:APPCODE ' . $this->appCode];

            $result = Http::get($this->api . '/query/comrms', $params, [], $headers);
            $result = json_decode($result, true);
        } catch (\Exception $e) {
            Log::record('获取通用行情数据失败' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 获取K线数据
     * @param $period
     * @param $symbol
     * @return bool
     */
    public function kline(...$data)
    {
    	// 单个币种
    	list($symbol, $period) = $data;

    	$result = [];
        try {
        	$params  = [
				'date'   => date('Y-m-d'),
				'period' => $period,
				'symbol' => $symbol
        	];

            $headers = ['Authorization:APPCODE ' . $this->appCode];

            $result = Http::get($this->api . '/query/comkm4', $params, [], $headers);
            $result = json_decode($result, true);
        } catch (\Exception $e) {
            Log::record('获取通用K线数据失败' . $e->getMessage());
        }

        return $result;
    }
}