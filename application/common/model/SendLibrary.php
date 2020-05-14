<?php
/**
 * 执行地址
 */
namespace app\common\model;
use think\Model;

class SendLibrary extends Model
{
    // 主键
    protected $pk = 'library_id';
	// 表名
    protected $name = 'send_library';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const STATUS_UNDONE   = 1;
    const STATUS_COMPLETE = 2;
    public static $statusLabels = [
        self::STATUS_UNDONE   => '未执行',
        self::STATUS_COMPLETE => '已执行',
    ];

    const TYPE_INCOME = 1;
    const TYPE_CASH   = 2;
    public static $typeLabels = [
        self::TYPE_INCOME => '收益',
        self::TYPE_CASH   => '提现',
    ];
}
