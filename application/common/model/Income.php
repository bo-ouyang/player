<?php
/**
 * 投资收益表[定期+活期]
 */
namespace app\common\model;
use think\Model;

class Income extends Model
{
    // 主键
    protected $pk = 'income_id';
	// 表名
    protected $name = 'income';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 追加属性
    protected $append = [];

    const TYPE_DESERT = 1;
    const TYPE_OASIS  = 2;
    const TYPE_INVITE = 3;
    const TYPE_TEAM   = 4;
    const TYPE_SUPER  = 5;
    public static $typeLabels = [
        self::TYPE_DESERT => '1~20',
        self::TYPE_OASIS  => '21~',
        self::TYPE_INVITE => '分享',
        self::TYPE_TEAM   => '股东',
        self::TYPE_SUPER  => '节点'
    ];

    /**
     * 收益
     * @param $userId
     * @param $amount
     * @param $type
     * @param $foreignId
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function incomeChange($userId, $amount, $type, $foreignId)
    {
        if (empty($userId) || empty($amount) || empty($type)) {
            return false;
        }

        $time = time();
        $result = self::insert([
            'user_id'     => $userId,
            'foreign_id'  => $foreignId,
            'type'        => $type,
            'amount'      => $amount,
            'day'         => date('Y-m-d', $time),
            'create_time' => $time,
            'to_user_id'  =>$userId
        ]);
        if (!$result) {

            return false;
        }
        $model = UserWallet::where('user_id', $userId);
        //common_log('test'.$userId.':'.$amount);
        switch ($type) {
            case self::TYPE_DESERT :
                $model->inc('desert_profit', $amount);
                break;
            case self::TYPE_OASIS :
                $model->inc('oasis_profit', $amount);
                break;
            case self::TYPE_INVITE :
                $model->inc('invite_profit', $amount);
                break;
            case self::TYPE_TEAM :
                $model->inc('team_profit', $amount);
                break;
            case self::TYPE_SUPER :
                $model->inc('super_profit', $amount);
                break;
            default :
                return false;
        }

        $result = $model->update(['update_time' => $time]);
        if (!$result) {
            common_log(858);
            return false;
        }

        return true;
    }
}
