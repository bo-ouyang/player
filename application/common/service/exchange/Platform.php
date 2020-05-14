<?php
/**
 * 交易平台服务父类
 */
namespace app\common\service\exchange;
use think\facade\Cache;
use think\facade\Config;
use Workerman\Connection\AsyncTcpConnection;

class Platform
{
    /**
     * 缓存前缀
     * @var string
     */
    public static $prefix = 'amd-exchange:general_';

    /**
     * 异步实例
     * @var null
     */
    private static $_asyncClient = NULL;

    /**
     * 币种转换 把标准币种转换为特殊币种
     * @param $standardSymbol
     * @return string
     */
    public static function standardToSpecial($standardSymbol)
    {
        $symbols = Cache::get(static::$prefix . 'symbols');
        if (isset($symbols[$standardSymbol])) {
            return $symbols[$standardSymbol];
        }

        return '';
    }

    /**
     * 币种转换 把特殊币种转换为标准币种
     * @param $specialSymbol
     * @return string
     */
    public static function specialToStandard($specialSymbol)
    {
        $symbol = strtolower($specialSymbol);

        $symbols = Cache::get(static::$prefix . 'symbols');
        $symbols = array_flip($symbols);
        if (isset($symbols[$symbol])) {
            return $symbols[$symbol];
        }

        return '';
    }

    /**
     * 判断是否参考时间
     */
    public static function isInitTime(string $platform, $nowTime): bool
    {
        // 参考价:火币 0点[北京时间]  币安 8点[北京时间]  Okex 0点[北京时间]
        $platform = strtolower($platform);

        $timeRange = [];
        foreach (range(0, 9) as $qty) {
            $timeRange[] = date('Y-m-d') . ' 00:0' . $qty;
            $timeRange[] = date('Y-m-d') . ' 00:1' . $qty;
        }

        // 毫秒转成秒
        $exchangeTime = date('Y-m-d H:i', $nowTime);
        if (in_array($exchangeTime, $timeRange)) {
            return true;
        }

        return false;
    }

    /**
     * 发送异步请求,通知前台
     */
    public static function noticeFront(array $data): void
    {
        // 与远程task服务建立异步连接，ip为远程task服务的ip，如果是本机就是127.0.0.1，如果是集群就是lvs的ip
        if (is_null(self::$_asyncClient)) {
            self::$_asyncClient = new AsyncTcpConnection(Config::get('app.websocket_inner'));
        }
        // var_dump($data);
        // 发送数据
        $result = self::$_asyncClient->send(json_encode($data) . PHP_EOL);
        // if (!$result) {
        //     // 重新发送
        //     self::$_asyncClient->reconnect();
        //     self::$_asyncClient->send(json_encode($data) . PHP_EOL);
        // }

        // 异步获得结果[$taskResult]
        $taskConnection = self::$_asyncClient;
        self::$_asyncClient->onMessage = function($taskConnection, $taskResult)
        {
            // 获得结果后关闭异步连接
            // $taskConnection->close();
        };

        // 执行异步连接
        self::$_asyncClient->connect();
    }
}
