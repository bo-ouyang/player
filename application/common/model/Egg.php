<?php
/**
 * 彩蛋周期
 */
namespace app\common\model;
use think\Model;

class Egg extends Model
{
    // 主键
    protected $pk = 'egg_id';
	// 表名
    protected $name = 'egg';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];


    const SYS_FEE = 0.1;//彩蛋手续费 进入静态池

    const TYPE_COPPER_EGG = 1;
    const TYPE_JADE_EGG = 2;
    const TYPE_CRYSTAL_EGG = 3;
    const TYPE_SUPER_EGG = 4;
    public static $quotaKeyMap = [
        self::TYPE_COPPER_EGG  => 'copper_egg_quota',
        self::TYPE_JADE_EGG    => 'jade_egg_quota',
        self::TYPE_CRYSTAL_EGG => 'crystal_egg_quota',
        self::TYPE_SUPER_EGG   => 'super_egg_quota'
    ];
	public static $quotaKeyUser = [
		self::TYPE_COPPER_EGG  => 20,
		self::TYPE_JADE_EGG    => 18,
		self::TYPE_CRYSTAL_EGG => 45,
		self::TYPE_SUPER_EGG   => 60
	];
	public static $luckUserPrice = [
		self::TYPE_COPPER_EGG  => 2.25,
		self::TYPE_JADE_EGG    => 5,
		self::TYPE_CRYSTAL_EGG => 10,
		self::TYPE_SUPER_EGG   => 15
	];
    public static $quotaLabel = [
        self::TYPE_COPPER_EGG  => '铜钥匙',
        self::TYPE_JADE_EGG    => '翠玉钥匙',
        self::TYPE_CRYSTAL_EGG => '水晶钥匙',
        self::TYPE_SUPER_EGG   => '超级彩蛋'
    ];

    const STATUS_PENDING  = 1;
    const STATUS_COMPLETE = 2;
}
