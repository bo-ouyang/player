<?php
/**
 * 币种行情
 */
namespace app\common\model;
use think\Model;

class SymbolQuote extends Model
{
    // 主键
    protected $pk = 'quote_id';
	// 表名
    protected $name = 'symbol_quote';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'quote_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];
}
