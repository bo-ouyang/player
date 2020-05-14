<?php
/**
 * 活期订单
 */
namespace app\common\model;
use think\Model;

class RechargeCycle extends Model
{
    // 主键
    protected $pk = 'cycle_id';
	// 表名
    protected $name = 'recharge_cycle';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const TYPE_DESERT = 1;
    const TYPE_OASIS  = 2;
    public static $statusLabels = [
        self::TYPE_DESERT => '沙漠',
        self::TYPE_OASIS  => '绿洲',
    ];

    const STATUS_PENDING   = 1;
    const STATUS_COMPLETE = 2;
    public static $validLabels = [
        self::STATUS_PENDING  => '进行中',
        self::STATUS_COMPLETE => '结束',
    ];

    const CASH_NO  = 1;
    const CASH_YES = 2;
}
