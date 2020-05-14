<?php
/**
 * 用户钱包表
 */
namespace app\common\model;
use think\Model;

class UserWallet extends Model
{
    // 主键
    protected $pk = 'wallet_id';
	// 表名
    protected $name = 'user_wallet';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [];
}
