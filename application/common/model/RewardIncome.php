<?php
/**
 * 推广奖励收益表
 */
namespace app\common\model;
use think\Model;

class RewardIncome extends Model
{
    // 主键
    protected $pk = 'income_id';
	// 表名
    protected $name = 'reward_income';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    //动态收益
    const TYPE_REGULAR = 1;
    //团队收益
    const TYPE_TEAM = 2;
}
