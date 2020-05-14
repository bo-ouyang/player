<?php
/**
 * 活期转定期日志
 */
namespace app\common\model;
use think\Model;

class TurnLog extends Model
{
    // 主键
    protected $pk = 'log_id';
	// 表名
    protected $name = 'turn_log';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];


}
