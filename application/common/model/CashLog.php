<?php
/**
 * 提取本金
 */
namespace app\common\model;
use think\Model;

class CashLog extends Model
{
    // 主键
    protected $pk = 'log_id';
	// 表名
    protected $name = 'cash_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];


    const  SYS_CASH_FEE = 0.4;//提现手续费
    const SYS_CASH_RETA = 1 - self::SYS_CASH_FEE;
    const STATUS_UNDONE   = 1;
    const STATUS_COMPLETE = 2;
    public static $statusLabels = [
        self::STATUS_UNDONE   => '未打款',
        self::STATUS_COMPLETE => '已打款',
    ];
}
