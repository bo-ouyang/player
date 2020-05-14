<?php
/**
 * 外汇币种
 */
namespace app\common\model;
use think\Model;

class ExchangeSymbol extends Model
{
    // 主键
    protected $pk = 'symbol_id';
	// 表名
    protected $name = 'exchange_symbol';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    /**
     * 获取品种信息
     */
    public function getSymbolList($status = null, $field = null)
    {
        $model = is_null($status) ? $this : self::where('status', $status);
        if ($field) {
            $list = model_field_query($model, $field);
        } else {
            $list = $model->select();
        }

        if ($list) {
            $list = !is_array($list) ? $list->toArray() : $list;
            return $list;
        }

        return [];
    }
}
