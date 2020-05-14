<?php
/**
 * 彩蛋订单
 */
namespace app\common\model;
use think\Model;

class EggReward extends Model
{
    // 主键
    protected $pk = 'reward_id';
	// 表名
    protected $name = 'egg_reward';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const TYPE_ETH   = 1;
    const TYPE_TOKEN = 2;

    /**
     * 彩蛋开奖获得奖励
     * @param $userId
     * @param $amount
     * @param $eggId
     * @param $type
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function reward($userId, $amount, $eggId, $type)
    {
        $time = time();
        $rewardData = [
            'user_id'     => $userId,
            'egg_id'      => $eggId,
            'type'        => $type,
            'amount'      => $amount,
            'create_time' => $time,
        ];
        $result = EggReward::insert($rewardData);
        if (!$result) {
            return false;
        }
        $model = UserWallet::where('user_id', $userId);
        if ($type == self::TYPE_ETH) {
            $model = $model->inc('egg_profit', $amount);
        }
        if ($type == self::TYPE_TOKEN) {
            $model = $model->inc('token_amount', $amount);
            $res = TokenDetail::insert([
                    'user_id'     => $userId,
                    'type'        => TokenDetail::TYPE_GIFT,
                    'foreign_id'  => $eggId,
                    'amount'      => $amount,
                    'create_time' => $time,
                ]);
            if (!$res) {
                return false;
            }
        }
        $result = $model->update(['update_time' => $time]);
        if (!$result) {
            return false;
        }

        return true;
    }
}
