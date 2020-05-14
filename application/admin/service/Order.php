<?php
/**
 * 换汇订单服务类
 * @author chat
 * @version 1.0
 */
namespace app\admin\service;

use app\common\library\Wallet as WalletLib;
use app\common\model\Egg;
use app\common\model\EggOrder;
use app\common\model\EggReward;
use app\common\model\Income;
use app\common\model\RechargeOrder;
use app\common\model\SystemStatistic;
use app\common\model\TokenDetail;
use app\common\model\TokenOrder;
use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use math\BCMath;
use think\Exception;
use think\facade\Config;
use app\common\exception\Order as OrderException;
use think\Db;
class Order
{

	public function sendLog($inviteCode, $params){
		$model =Db::name('send_log');
		if (!empty($params['to'])) {
			$model = $model->where('to', '=', $params['to']);
		}
		if (!empty($params['hash'])) {
			$model = $model->where('hash', '=', $params['hash']);
		}
		if (!empty($params['create_time'])) {
			$day = strtotime(date('Y-m-d',strtotime($params['create_time'])));
			$model = $model->whereBetweenTime('create_time', $day, $day+(3600*24));
		}
		$list = $model->order('create_time DESC')
			->page($params['page'], Config::get('paginate.list_rows'))
			->select();
		return ['list' => $list, 'total' => $model->count()];
	}
    /**
     * token列表
     * @param $inviteCode
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function tokenList($inviteCode, $params)
    {
        $model = model('common/TokenDetail')->alias('o')
            ->join('one_user u', 'o.user_id = u.user_id');
        $time = mktime(23,59,59,9,29,2019);
        if (!empty($params['address'])) {
            $model = $model->where('origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['type'])) {
            $model = $model->where('type', $params['type']);
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
	    $model->where('o.create_time','>',$time);
        $list = $model->field('u.origin_address,o.*')->order('o.token_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$value) {
            if ($value['type'] == TokenDetail::TYPE_BUY) {
                $value['price'] = TokenOrder::getFieldByOrderId($value['foreign_id'], 'price');
            } else {
                $value['price'] = EggOrder::where('user_id', $value['user_id'])->where('egg_id', $value['foreign_id'])->sum('amount');
            }
        }
        return ['list' => $list, 'total' => $model->count()];
    }


    //根据用户ID查询地址
    private function getUserAddress($user_id){
        $userModel = new User();
        $user = $userModel->where('user_id='.$user_id)->field('address')->find();
        if (!$user) {
            return false;
        }

        $user = $user->toArray();
        return $user['address'];
    }

    //根据地址查询ID
    private function getUserID($address) {
        $userModel = new User();
        $user = $userModel->where('origin_address='."'$address'")->field('user_id,origin_address,address')->find();
        if (!$user) {
            return false;
        }

        $user = $user->toArray();
        if (strtolower($user['origin_address']) != strtolower($user['address'])) {
            return false;
        }

        return $user['user_id'];
    }

    /**
     * 提现订单列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCashOrder($inviteCode, $params)
    {
        $model = model('common/CashLog')->alias('o')
            ->join('one_user u', 'o.user_id = u.user_id')
            ->join('one_recharge_cycle c', 'c.cycle_id = o.cycle_id');
        if (!empty($params['address'])) {
            $model = $model->where('origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['type'])) {
            $model = $model->where('c.type', $params['type']);
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
        $list = $model->field('u.origin_address,c.type,o.*')->order('o.log_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $model->count()];
    }


    /**
     * 动态收益
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRewardIncome($inviteCode, $params)
    {
        $model = model('common/Income')->alias('i')
            ->join('one_user u', 'i.to_user_id = u.user_id', 'left')
           //->join('one_income in', 'i.foreign_id = in.income_id', 'left')
            ->join('one_user us', 'i.user_id = us.user_id', 'left')
            ->join('one_recharge_cycle c', 'c.cycle_id = i.foreign_id', 'left')
            ->where('i.type', Income::TYPE_INVITE);
        if (!empty($params['address'])) {
            $model = $model->where('u.origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['pay_address'])) {
            $model = $model->where('us.origin_address', 'LIKE', "%{$params['pay_address']}%");
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
        $list = $model->field('u.origin_address as address,us.origin_address as pay_address,c.recharge_amount,i.*')->order('i.income_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];
        return ['list' => $list, 'total' => $model->count()];
    }


    /**
     * 投资利息
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInvestIncome($inviteCode, $params)
    {
        $model = model('common/Income')->alias('i')
            ->join('one_user u', 'i.user_id = u.user_id')
            ->join('one_recharge_cycle c', 'c.cycle_id = i.foreign_id');
        if (!empty($params['address'])) {
            $model = $model->where('origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['type'])) {
            $model = $model->where('i.type', $params['type']);
        } else {
            $model = $model->where('i.type', 'in', [Income::TYPE_DESERT, Income::TYPE_OASIS]);
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
        $list = $model->field('u.origin_address,c.recharge_amount,i.*')->order('income_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $model->count()];
    }


    /**
     * 投资列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function investOrder($inviteCode, $params)
    {
        $model = model('common/RechargeOrder')->alias('o')
            ->join('one_user u', 'o.user_id = u.user_id');
        if (!empty($params['address'])) {
            $model = $model->where('origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['type'])) {
            $model = $model->where('type', $params['type']);
        }
        if (!empty($params['system_recharge'])) {
            $model = $model->where('o.is_true', $params['system_recharge']);
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
	    $time = mktime(23,59,59,9,28,2019);
        $list = $model->where('o.create_time','>',$time)
	        ->field('u.origin_address,o.*')->order('o.order_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 错误列表
     * @param $params
     * @return array
     */
    public function errorList($page)
    {
        $model = model('common/ErrorInfo');

        $list = $model->order('info_id desc')->page($page, Config::get('paginate.list_rows'))->select()->each(function ($item,$value){
	        $item['content'] = json_decode($item['content'],true);
        });
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $model->count()];
    }


    /**
     * 团队收益
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTeamAmount($inviteCode, $params)
    {
        $model = model('common/Income')->alias('i')
            ->join('one_user u', 'i.user_id = u.user_id')
            ->where('type', Income::TYPE_TEAM);
        if (!empty($params['address'])) {
            $model = $model->where('origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['grade'])) {
            $model = $model->where('u.grade', $params['grade']);
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
        $list = $model->field('u.origin_address,u.grade,i.*')->order('income_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];
        $performance = [];
        foreach ($list as &$value) {
            if (!isset($performance[$value['day']])) {
                $endTime = strtotime($value['day']);
                $beginTime = $endTime - (3600 * 24);
                $performance[$value['day']] = RechargeOrder::where('is_validate', RechargeOrder::VALIDATE_COMPLETE)->whereBetweenTime('create_time', $beginTime, $endTime)->sum('amount');
            }
            $value['performance'] = $performance[$value['day']];
        }
        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 超级节点奖励
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function superReward($inviteCode, $params)
    {
        $model = model('common/Income')->alias('i')
            ->join('one_user u', 'i.user_id = u.user_id')
            ->join('one_recharge_order r', 'i.foreign_id = r.order_id')
            ->join('one_user us', 'r.user_id = us.user_id')
            ->where('i.type', Income::TYPE_SUPER);
        if (!empty($params['address'])) {
            $model = $model->where('u.origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($params['pay_address'])) {
            $model = $model->where('us.origin_address', 'LIKE', "%{$params['pay_address']}%");
        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            $model = $model->where('u.user_id', $userId);
        }
        $list = $model->field('u.origin_address as address,us.origin_address as pay_address,r.amount as recharge_amount,i.*')->order('i.income_id DESC')->page($params['page'], Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];
        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 彩蛋订单
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eggOrder($params)
    {
        $eggInfo = Egg::where('status', Egg::STATUS_PENDING)->where('type', $params['type'])->find();
        $eggId = $eggInfo->egg_id ?? 0;
        $number = model('common/EggOrder')->distinct('user_id')->where('egg_id', $eggId)->where('is_validate', EggOrder::VALIDATE_COMPLETE)->count();
        $model = model('common/EggOrder')->alias('o')
            ->join('one_user u', 'o.user_id = u.user_id')
            ->where('type', $params['type'])
            ->where('is_validate', EggOrder::VALIDATE_COMPLETE);
        if (!empty($params['address'])) {
            $model = $model->where('u.origin_address', 'LIKE', "%{$params['address']}%");
        }
        if (!empty($eggId) && $params['status'] == 1) { //status 1不显示 2显示
            $model = $model->where('egg_id', $eggId);
        }
        $list = $model->field('u.origin_address,o.*')->order('order_id DESC')->page($params['page'], $params['page_size'] ?? Config::get('paginate.list_rows'))->select();
        $list = $list ? $list->toArray() : [];
        return ['list' => $list, 'total' => $model->count(), 'amount' => $eggInfo->amount ?? 0, 'quota' => $eggInfo->quota ?? 0, 'number' => $number, 'egg_id' => $eggId];
    }

    /**
     * 设置开奖
     * @param $eggId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function drawSetting($eggId)
    {
        $virtualUser = User::where('is_true', 2)->field('user_id,origin_address')->select();
        $virtualUser = $virtualUser ? $virtualUser->toArray() : [];
        $eggInfo = Egg::get($eggId);
        return [
            'list'   => $virtualUser,
            'amount' => $eggInfo->amount,
            'quota'  => $eggInfo->quota
        ];
    }

    /**
     * 开奖
     * @param $data
     * @return bool
     * @throws Exception
     * @throws OrderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function draw($data)
    {
        $time = time();
        $eggId = $data['egg_id'];
        $eggInfo = Egg::where('egg_id', $eggId)->where('status', Egg::STATUS_PENDING)->find();
        if (empty($eggInfo)) {
            throw new OrderException(OrderException::getErrorMsg(OrderException::E_ORDER_STATUS_ERROR), OrderException::E_ORDER_STATUS_ERROR);
        }
        $rechargeAmount = 0;
        foreach ($data['order'] as $value) {
            $rechargeAmount = BCMath::add($rechargeAmount, $value['amount'], 12);
            $data['luck_id'][] = $value['user_id'];
        }
        if (BCMath::add($rechargeAmount, $eggInfo->amount, 12) < $eggInfo->quota) {
            throw new OrderException(OrderException::getErrorMsg(OrderException::E_ORDER_UNMET_QUOTA), OrderException::E_ORDER_UNMET_QUOTA);
        }
        //生成假订单
        $order = $data['order'];
        foreach ($order as $key => $value) {
            service('index/Order')->eggOrder($value['user_id'], ['egg_type' => $eggInfo->type, 'amount' => $value['amount'], 'is_true' => 2]);
            Egg::where('egg_id', $eggId)->inc('amount', $value['amount'])->update(['update_time' => $time]);
            UserWallet::where('user_id', $value['user_id'])->inc('egg_amount', $value['amount'])->update(['update_time' => $time]);
            model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_TOTAL_EGG_AMOUNT, $value['amount']);
        }

        //开奖
        $list = model('common/EggOrder')->where('egg_id', $eggId)->field('user_id,sum(amount) total_amount')->group('user_id')->select();
        $list = $list ? $list->toArray() : [];
        if (empty($list)) {
            return false;
        }

        //未中奖获得token的用户id
        $eggUserIds = [];
        if (count($list) <= 10) {
            $rewardIds = array_column($list, 'user_id');
        } else {
            $totalWeight = 0;
            foreach ($list as $key => $value) {
                $list[$key]['pre_weight'] = $totalWeight;
                $totalWeight += BCMath::mul($value['total_amount'], 10, 0);
                $list[$key]['weight'] = $totalWeight;
            }
            $i = 0;
            $rewardIds = $data['luck_id'];
            $rewardIds = array_unique($rewardIds);
            if (count($rewardIds) > 10) {
                return false;
            }
            while (count($rewardIds) < 10 && count($list) > count($rewardIds)) {
                $random = mt_rand(0, $totalWeight);
                foreach ($list as $key => $value) {
                    if (in_array($value['user_id'], $rewardIds)) {
                        continue;
                    }
                    if ($random >= $value['pre_weight'] + 1 && $random <= $value['weight']) {
                        $rewardIds[] = $value['user_id'];
                        unset($list[$key]);
                    }
                }
                if ($i > 100000) {
                    return false;
                }
            }
            foreach ($list as $key => $value) {
                if ($value['total_amount'] >= 10 && !in_array($value['user_id'], $rewardIds)) {
                    $eggUserIds[] = $value['user_id'];
                }
            }
        }

        EggOrder::startTrans();
        try {
            $result = Egg::where('egg_id', $eggId)->update(['status' =>  Egg::STATUS_COMPLETE]);
            if (!$result) {
                EggOrder::rollback();
                return false;
            }

            //发放奖励
            if (!empty($rewardIds)) {
                foreach ($rewardIds as $rewardId) {
                    $result = model('common/EggReward')->reward($rewardId, 9, $eggId, EggReward::TYPE_ETH);
                    if (!$result) {
                        EggOrder::rollback();
                        return false;
                    }
                    $virtualUser = User::where('is_true', 2)->column('user_id');
                    if (!in_array($rewardId, $virtualUser)) {
                        $address = model('common/User')->getUserAddress($rewardId);
                        $result = WalletLib::transfer($address, 9,1);
                        if (!$result) {
                            RechargeOrder::rollback();
                            return false;
                        }
                    }
                }
            }
            if (!empty($eggUserIds)) {
                //发放token
                foreach ($eggUserIds as $rewardId) {
                    $result = model('common/EggReward')->reward($rewardId, 1, $eggId, EggReward::TYPE_TOKEN);
                    if (!$result) {
                        EggOrder::rollback();
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            EggOrder::rollback();
            return false;
        }
        EggOrder::commit();
        return true;
    }
}
