<?php
/**
 * 活期订单
 */
namespace app\common\model;
use think\Model;

class RechargeOrder extends Model
{
    // 主键
    protected $pk = 'order_id';
	// 表名
    protected $name = 'recharge_order';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const TYPE_DESERT = 1;
    const TYPE_OASIS  = 2;
	//const TYPE_NEW    = 3;
    public static $statusLabels = [
        self::TYPE_DESERT => '沙漠',
        self::TYPE_OASIS  => '绿洲',
	    //self::TYPE_NEW  =>   '新增Type3',
    ];

    // 是否验证hash
    const VALIDATE_UNDONE   = 1;
    const VALIDATE_COMPLETE = 2;
    public static $validLabels = [
        self::VALIDATE_UNDONE   => '未验证',
        self::VALIDATE_COMPLETE => '已验证',
    ];

    const SYSTEM_RECHARGE_NO  = 1;
    const SYSTEM_RECHARGE_YES = 2;
}
