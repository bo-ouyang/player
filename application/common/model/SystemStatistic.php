<?php
/**
 * 系统统计
 */
namespace app\common\model;
use think\Model;

class SystemStatistic extends Model
{
    // 主键
    protected $pk = 'statistic_id';
	// 表名
    protected $name = 'system_statistic';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    // 键名配置
    const KEY_TOTAL_PERFORMANCE   = 'total_performance';//平台总业绩
    const KEY_TOTAL_EGG_AMOUNT    = 'total_egg_amount';//彩蛋总业绩
    const KEY_TOTAL_STATIC       = 'total_static';//奖池总额
    const KEY_TOTAL_TOKEN_AMOUNT  = 'total_token_amount';//A1ibo token入金总额


	const KEY_FAKE_TOTAL_STATIC       = 'total_fake_static';//奖池总额
	const KEY_FAKE_TOTAL_PERFORMANCE   = 'total_fake_performance';//平台总业绩
    /**
     * 统计数据增加
     * @param $key
     * @param $amount
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function StatisticInc($key, $amount)
    {
        return self::where('key', $key)->inc('value', $amount)->update(['update_time' => time()]);
    }

    /**
     * 获取统计数值
     * @param $key
     * @return mixed
     */
    public function getStatistic($key)
    {
        return self::where('key', $key)->value('value');
    }
}
