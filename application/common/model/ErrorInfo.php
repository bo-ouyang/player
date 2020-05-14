<?php
/**
 * 错误信息
 */
namespace app\common\model;
use think\Model;

class ErrorInfo extends Model
{
    // 主键
    protected $pk = 'info_id';
	// 表名
    protected $name = 'error_info';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const TYPE_ORDER = 1;
    const TYPE_HASH  = 2;
    public static $typeLabels = [
        self::TYPE_ORDER => '订单',
        self::TYPE_HASH  => 'hash验证',
    ];
}
