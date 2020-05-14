<?php
namespace app\common\model;
use think\Model;

class Config extends Model
{
	// 表名
    protected $name = 'config';
    // 主键
    protected $pk = 'config_id';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [];

    /**
     * 用户升级额度
     * @var array
     */
    public static $upGradeQuota = [
        1 => self::KEY_PRIMARY_GRADE_QUOTA,
        2 => self::KEY_MIDDLE_GRADE_QUOTA,
        3 => self::KEY_HIGH_GRADE_QUOTA,
        4 => self::KEY_SUPER_GRADE_QUOTA,
        5 => self::KEY_ONE_GRADE_QUOTA
    ];

    // 键名配置
    const KEY_TOTAL_TOKEN      = 'total_token';// 令牌发行数
    const KEY_TOTAL_DESTROY    = 'token_destroy';// 令牌销毁数
    const KEY_BASE_JACKPOT     = 'base_Jackpot';// 基础奖次金额
    const KEY_BASE_PERFORMANCE = 'base_performance';// 基础全球业绩
    const KEY_BASE_EGG         = 'base_egg';// 基础彩蛋金额
    const KEY_RECHARGE_QUOTA   = 'recharge_quota';// 充值额度
    const KEY_TOKEN_PRICE       = 'token_price';// 令牌价格
    const KEY_VOLUME            = 'volume';// 平台容积
    const KEY_PRIMARY_GRADE_QUOTA   = 'primary_grade_quota';// 初级玩家升级额度
    const KEY_MIDDLE_GRADE_QUOTA    = 'middle_grade_quota';// 中级玩家升级额度
    const KEY_HIGH_GRADE_QUOTA      = 'high_grade_quota';// 高级玩家升级额度
    const KEY_SUPER_GRADE_QUOTA     = 'super_grade_quota';// 超级玩家升级额度
    const KEY_ONE_GRADE_QUOTA       = 'one_grade_quota';// 头号玩家升级额度
    const KEY_SUPER_NODE_QUOTA      = 'super_node_quota';// 超级节点额度

	const KEY_COPPER_EGG_QUOTA  = 'copper_egg_quota';// 铜彩蛋额度
	const KEY_JADE_EGG_QUOTA    = 'jade_egg_quota';// 玉彩蛋额度
	const KEY_CRYSTAL_EGG_QUOTA = 'crystal_egg_quota';// 水晶彩蛋额度
	const KEY_SUPER_EGG_QUOTA   = 'super_egg_quota';// 超级彩蛋额度


    const KEY_INTEREST              = 'interest';// 静态收益率
    const KEY_BASE_Y_PERFORMANCE    = 'base_y_performance';// 基础昨日全球业绩
    const KEY_SUPER_NODE_NUMBER     = 'super_node_number';// 超级节点数量
    const KEY_SYSTEM_RECHARGE_ID    = 'system_recharge_id';// 后台充值id
    const KEY_TOKEN_LIMIT           = 'token_limit';// token每日购买限制
    const KEY_TODAY_PERFORMANCE     = 'today_performance';// 今日全球总业绩

    /**
     * 获取相应配置
     */
    public  function getConfig($key, $type = null)
    {
        $model = self::where('key', $key);
        if ($type) {
            $model = $model->where('type', $type);
        }

        return $model->value('value');
    }

    /*
     * 所有配置
     */
    public  function  configList()
    {
        return self::select()->toArray();
    }

    /**
     * 获得用户升级额度
     * @return array
     */
    public function getUserUpgradeQuota()
    {
        $quotaMap = self::$upGradeQuota;
        $dara = [];
        foreach ($quotaMap as $grade => $key) {
            $dara[$grade] = $this->getConfig($key);
        }

        return $dara;
    }
}
