<?php
/**
 * token订单
 */
namespace app\common\model;
use think\Model;

class TokenOrder extends Model
{
    // 主键
    protected $pk = 'order_id';
	// 表名
    protected $name = 'token_order';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    // 是否验证hash
    const VALIDATE_UNDONE   = 1;
    const VALIDATE_COMPLETE = 2;
    public static $validLabels = [
        self::VALIDATE_UNDONE   => '未验证',
        self::VALIDATE_COMPLETE => '已验证',
    ];
}
