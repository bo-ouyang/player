<?php
/**
 * 汇率通用接口
 */
namespace app\common\service;
use Cache;
use math\BCMath;
use Log;
use Config;
use app\common\model\SymbolQuote;

class Exchange
{
    use \app\common\traits\Instance;

    /**
     * 获取行情
     * @param $symbol 品种代码
     * @return mixed
     */
    public function getPrice(string $symbol, $price = null)
    {
        if (!$price) {
            $quoteData = SymbolQuote::where('symbol', $symbol)->find();
            $price = $quoteData ? $quoteData['price'] : false;
        }

        if (!$price) {
            $price = 0;
        }

        return $price;
    }

    /**
     * 获取行情[包含涨跌幅]
     * @param  string  $symbol  品种代码
     * @param  boolean $cnyFlag 是否需要人民币价格
     */
    public function getDetail(string $symbol)
    {
        if (!$symbol) {
            return false;
        }

        // 获取前缀
        $quoteData = SymbolQuote::where('symbol', $symbol)->find();
        if (!$quoteData) {
            return false;
        }

        $nowPrice = $quoteData['price'];
        // 参考价格
        $initPrice = (isset($quoteData['init_price']) && (float)$quoteData['init_price'] > 0) ? $quoteData['init_price'] : $nowPrice;

        $extent = $this->getPercent($nowPrice, $initPrice);
        $data = [
            'symbol'     => $symbol,
            'init_price' => BCMath::convertScientificNotationToString($initPrice),
            'price'      => BCMath::convertScientificNotationToString($nowPrice),
            'percent'    => $extent . '%'
        ];

        return $data;
    }

    /**
     * 获取涨跌幅
     */
    public function getPercent($nowPrice, $initPrice)
    {
        // 算法：[（当前价格-参考价）÷参考价]×100%
        $diffPrice = (($nowPrice - $initPrice) / $initPrice) * 100;
        if (floor($diffPrice) != $diffPrice) {
            // 小数
            $extent = round($diffPrice, 2);
        } else {
            // 整数
            $extent = $diffPrice;
        }

        return BCMath::convertScientificNotationToString($extent);
    }

    /**
     * 构造子类方法
     */
    public static function createClass(string $name): string
    {
        return '\\app\\common\\service\\exchange\\' . ucwords($name);
    }

    /**
     * 根据批量获取行情数据
     * @param  string  $symbols 品种代码
     */
    public function getBatchDetail($symbols)
    {
        if (!$symbols) {
            return false;
        }

        $symbolData = SymbolQuote::where('symbol', 'in', $symbols)->select()->toArray();
        if (!$symbolData) {
            return false;
        }

        $result = [];
        foreach ($symbolData as $info) {
            if (empty($info['price'])) {
                continue;
            }

            $symbol   = $info['symbol'];
            $nowPrice = $info['price'];
            // 参考价格
            $initPrice = (isset($info['init_price']) && (float)$info['init_price'] > 0) ? $info['init_price'] : $nowPrice;

            $extent = $this->getPercent($nowPrice, $initPrice);
            $result[$symbol] = [
                'symbol'     => $symbol,
                'init_price' => BCMath::convertScientificNotationToString($initPrice),
                'price'      => BCMath::convertScientificNotationToString($nowPrice),
                'percent'    => $extent . '%'
            ];
        }

        return $result;
    }
}
