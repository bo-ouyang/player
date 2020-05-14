<?php
/**
 * 用户关系表
 */
namespace app\common\model;
use think\Model;

class UserBind extends Model
{
    // 主键
    protected $pk = 'band_id';
	// 表名
    protected $name = 'user_bind';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [];
}
