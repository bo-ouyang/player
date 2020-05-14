<?php
/**
 * 彩蛋订单
 */
namespace app\common\model;
use think\Model;

class TokenDetail extends Model
{
    // 主键
    protected $pk = 'token_id';
	// 表名
    protected $name = 'token_detail';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const TYPE_BUY   = 1;
    const TYPE_GIFT = 2;
	const TYPE_STATIC = 3;
    public static $typeLabel = [
        self::TYPE_BUY  => '购买',
        self::TYPE_GIFT => '彩蛋奖励',
	    self::TYPE_STATIC => '静态分红'
    ];
}
