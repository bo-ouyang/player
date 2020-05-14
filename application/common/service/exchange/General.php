<?php
/**
 * 汇率行情查询
 */
namespace app\common\service\exchange;
use think\facade\Log;
use platform\Platform as PlatformBase;
use think\facade\Cache;
use math\BCMath;
use http\Http;
use think\facade\Config;
use Exception;
use app\common\model\ExchangeSymbol;
use app\common\model\SymbolQuote;

class General extends Platform
{
    /**
     * 接口
     * @var null
     */
    protected static $generalApi = null;

    /**
     * 初始化
     */
    public function __construct()
    {
        if (is_null(self::$generalApi)) {
            self::$generalApi = Config::get('app.exchange_api');
        }
    }

    /**
     * 获取通用行情数据
     * @return bool
     */
    public function ticker($symbol)
    {
        try {
            $tmpList = explode('/', $symbol);
            $url = sprintf(self::$generalApi, $tmpList[0], $tmpList[1]);

            $rawdata = Http::get($url);
            $rawdata = json_decode($rawdata, true);
            if ($rawdata['success'] == '1' && $rawdata['result']['rate']) {
                $price = $rawdata['result']['rate'];

                $nowTime = time();
                $map = [
                    'symbol' => $symbol,
                ];

                $attribute = [
                    'price'      => $price,
                    'quote_time' => $nowTime,
                ];

                // 判断接口返回时间戳
                if (self::isInitTime('general', $nowTime)) {
                    // 当前时间是否为0点[缓存行情]
                    $attribute['init_price'] = $price;
                }

                $exists = SymbolQuote::where($map)->find();
                if ($exists) {
                    SymbolQuote::where($map)->update($attribute);
                } else {
                    $newId = SymbolQuote::create(array_merge($map, $attribute));
                    if (!$newId->quote_id) {
                        throw new Exception('add database error');
                    }
                }

                // 异步通知websocket服务端
                // self::noticeFront(['symbol' => $symbol, 'channel' => 'quote']);
            }
        } catch (Exception $e){
            Log::record('缓存通用行情价格数据出错');
        }

        return true;
    }
}
