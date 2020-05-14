<?php
/**
 * 用户服务类
 */
namespace app\index\service;

use app\common\library\Wallet as WalletLib;
use app\common\model\EggOrder;
use app\common\model\EggReward;
use app\common\model\Income;
use app\common\model\RechargeCycle;
use app\common\model\RechargeOrder;
use app\common\model\SystemStatistic;
use app\common\model\TokenDetail;
use app\common\model\TokenOrder;
use app\common\model\UpgradeLog;
use think\Facade\Config;
use think\Db;
use http\Http;
use app\common\model\User as UserModel;
use app\common\model\InvestIncome;
use app\common\model\RewardIncome;
use app\common\model\UserBind;
use app\common\exception\User as UserException;
use app\common\exception\System as SystemException;
use app\common\exception\Order as OrderException;
use Exception;
use math\BCMath;
use app\common\model\UserWallet;
use app\common\model\CurrentOrder;
use app\common\model\RegularOrder;
use app\common\model\CashLog;
use app\common\library\Wallet;

class User
{
    /**
     * 获取用户详情
     */
    public function getUserDetail($inviteCode)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $user = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->find();
        //$user = UserModel::get(['invite_code' => $inviteCode]);
        if (!$user) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }

        $user           = $user->toArray();
        //print_r($user);
        $userWallet     = model('common/UserWallet')->where('user_id', $user['user_id'])->find();
	    $t_1001         = mktime(0,0,0,10,1,2019);
		$superNums      = \app\common\model\User::where(['is_super'=>2])->where('create_time','>',$t_1001)->count();
		$superNodes     = model('common/Config')->getConfig('super_node_number');
	    $incJackpot     = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_STATIC);//奖池总额
	    $incJackpotFake = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_FAKE_TOTAL_STATIC);//奖池总额
		$data = [
            'user_id'       => $user['user_id'],
            'invite_code'   => $inviteCode,
            'grade'         => $user['grade'],
            'static_profit' => BCMath::add($userWallet['desert_profit'], $userWallet['oasis_profit'],12),
            'invite_profit' => $userWallet['invite_profit'],
            'team_profit'   => $userWallet['team_profit'],
            'token_amount'  => $userWallet['token_amount'],
	        'egg_amount'    => $userWallet['egg_amount'],
            'super_profit'  => $userWallet['super_profit'],
            'static_amount' => BCMath::add($userWallet['desert_amount'], $userWallet['oasis_amount'],12),
            'egg_profit'    => $userWallet['egg_profit'],
            'is_super'      => $user['is_super'],
	        'user_detail'   =>1,
	        'leftSuperNums' =>$superNodes-$superNums,
	       // 'leftSuperNums' =>0,
	        'total_static'=>$incJackpot+$incJackpotFake
            ];

        return $data;
    }

    /**
     * 新增会员
     * @param array $data
     * @return bool|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function userAdd(array $data)
    {
        $bindData[] = [
            'parent_id' => $data['parent_id'],
            'level'     => 1
        ];
        $parentList = UserBind::where('user_id', $data['parent_id'])->select();
        $parentList = $parentList ? $parentList->toArray() : [];
        foreach ($parentList as $key => $value) {
            $bindData[] = [
                'parent_id' => $value['parent_id'],
                'level'     => $value['level'] + 1
            ];
        }

        if (!empty($data['user_invite_code'])) {
            $inviteCode = $data['user_invite_code'];
        } else {
            $inviteCode = model('common/User')->getInviteCode();
        }

        Db::startTrans();
        try {
            $userId = UserModel::create([
                'parent_id'   => $data['parent_id'],
                'invite_code' => $inviteCode,
                'address'     => $data['address'],
                'origin_address' => $data['address'],
                'create_time' => time(),
                'update_time' => time(),
                'is_true'     => $data['is_true'] ?? 1
            ]);
            if (!$userId->user_id) {
                Db::rollback();
                return false;
            }
            foreach ($bindData as &$item) {
                $item['user_id'] = $userId->user_id;
            }
            $result = model('common/UserBind')->saveAll($bindData);
            if (!$result) {
                Db::rollback();
                return false;
            }
            $result = UserWallet::create([
                'user_id'     => $userId->user_id,
                'create_time' => time(),
                'update_time' => time()
            ]);
            if (!$result->wallet_id) {
                Db::rollback();
                return false;
            }

        } catch (Exception $e) {
            Db::rollback();
            return false;
        }

        Db::commit();
        return $userId->user_id;
    }

    /**
     * 判断邀请码是否存在
     */
    public function codeExists($inviteCode)
    {
    	$time=mktime(23,59,59,9,28,2019);
        $user = UserModel::where('create_time','>',$time)->where(['invite_code' => $inviteCode])->find();
        if (!$user) {
            return false;
        }
        return true;
    }

    /**
     * 团队明细
     * @param $inviteCode
     * @param $page
     * @param $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function teamList($inviteCode, $page, $listRows){
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw new UserException(UserException::getErrorMsg(UserException::E_INVITE_CODE), UserException::E_INVITE_CODE);
        }

        $model = model('common/UserBind')->alias('b')
            ->leftJoin('one_user u', 'b.user_id = u.user_id')
            ->where('b.parent_id', $userId);
        $list = $model->field('u.user_id,u.parent_id,invite_code,u.create_time')->select();
        $list = $list ? $list->toArray() : [];
        if (!empty($list)) {
            $list = listToTree($list, 'user_id', 'parent_id', 'child', $userId);
        }
        $list = array_values($list);
        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 提现
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashAmount($data)
    {
        if (!$data['cycle_id'] || !$data['invite_code']) {
            return false;
        }

        $user = UserModel::get(['invite_code' => $data['invite_code']]);
        if (!$user) {
            throw_new(UserException::class, UserException::E_NOT_EXISTS);
        }

        $cycle = RechargeCycle::where('cycle_id', $data['cycle_id'])->find();
        if (!$cycle) {
            throw_new(OrderException::class, OrderException::E_NOT_EXISTS);
        }
        $cycle = $cycle->toArray();
        if ($cycle['status'] == RechargeCycle::STATUS_COMPLETE) {
            throw_new(OrderException::class, OrderException::E_CANNOT_CASH);
        }
        //$cashAmount = BCMath::sub(BCMath::mul($cycle['recharge_amount'], 0.95, 12), $cycle['reward_amount'], 12);
	    $cashAmount = BCMath::sub(BCMath::mul($cycle['recharge_amount'], CashLog::SYS_CASH_RETA, 12), $cycle['reward_amount'], 12);
	    $cash = BCMath::sub(BCMath::mul($cycle['recharge_amount'], CashLog::SYS_CASH_FEE, 12), $cycle['reward_amount'], 12);

        Db::startTrans();
       try {
            // 提现记录
            $nowTime = time();
            $logData = [
                'user_id'       => $user['user_id'],
                'cycle_id'      => $cycle['cycle_id'],
                'amount'        => $cashAmount,
                'status'        => CashLog::STATUS_UNDONE,
                'create_time'   => $nowTime
            ];

            $cashLog = CashLog::create($logData);

            if (!$cashLog->log_id) {

                Db::rollback();
                throw new Exception('add database error');
            }

            $result = RechargeCycle::where('cycle_id', $data['cycle_id'])->update([
                'is_cash'     => RechargeCycle::CASH_YES,
                'status'      => RechargeCycle::STATUS_COMPLETE,
                'update_time' => $nowTime]
            );
            if (!$result) {
                Db::rollback();
                throw new Exception('add database error');
            }
            $address = model('common/User')->getUserAddress($user['user_id']);
            $result = WalletLib::transfer($address, $cashAmount,2);//提现

	        if (!$result) {
		        Db::rollback();
		        throw new Exception('user cash add database error');
	        }
	        $walletAddress = Config::get('app.contract_address');
	        $result = WalletLib::transfer($walletAddress, $cashAmount,3);//
	        if (!$result) {
		        Db::rollback();
		        throw new Exception('user cash fee add database error');
	        }
            Db::commit();
        } catch (Exception $e) {
	        Db::rollback();
        	//return json_encode($e);
	        //return json_encode($e);
            throw_new(OrderException::class, OrderException::E_CASH_ERROR);
        }

        return true;
    }

    public function userBind($userId, $parentId)
    {
        $bindData[] = [
            'parent_id' => $parentId,
            'level'     => 1
        ];
        $parentList = UserBind::where('user_id', $parentId)->select();
        $parentList = $parentList ? $parentList->toArray() : [];
        foreach ($parentList as $key => $value) {
            $bindData[] = [
                'parent_id' => $value['parent_id'],
                'level'     => $value['level'] + 1
            ];
        }

        Db::startTrans();
        try {
            $result = model('common/User')->where('user_id', $userId)->update(['parent_id' => $parentId]);
            if (!$result) {
                Db::rollback();
                return false;
            }
            foreach ($bindData as &$item) {
                $item['user_id'] = $userId;
            }
            $result = model('common/UserBind')->saveAll($bindData);
            if (!$result) {
                Db::rollback();
                return false;
            }

        } catch (Exception $e) {
            Db::rollback();
            return false;
        }
        print_r($bindData);
        Db::commit();
        return true;
    }

   /* public function getBonus($inviteCode, $page, $limit)
    {
        if (!$inviteCode) {
            return false;
        }

        $user = UserModel::get(['invite_code' => $inviteCode]);
        if (!$user) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }

        $user_id = $user['user_id'];
        $incomeData = DB::table('sp_income')->where('user_id='.$user_id)
            ->field('day, sum(amount) as amount')
            ->group('day')
            ->order('day DESC')
            ->page($page)
            ->limit($limit)
            ->select();

        $extensionData = RewardIncome::where('user_id='.$user_id)
            ->field('day, sum(amount) as amount')
            ->group('day')
            ->order('day DESC')
            ->page($page)
            ->limit($limit)
            ->select();
        $extensionData = $extensionData ? $extensionData->toArray() : [];

        $DayData = InvestIncome::where('user_id='.$user_id)
            ->field('day, sum(amount) as amount')
            ->group('day')
            ->order('day DESC')
            ->page($page)
            ->limit($limit)
            ->select();
        $DayData = $DayData ? $DayData->toArray() : [];
        $data = array_merge_recursive($incomeData, $extensionData, $DayData);
        $reward = [];
        foreach ($data as $value) {
            if (isset($reward[$value['day']])) {
                $reward[$value['day']]['amount'] = BCMath::add($reward[$value['day']]['amount'], $value['amount'], 12);
            } else {
                $reward[$value['day']] = [
                    'day'    => $value['day'],
                    'amount' => $value['amount'],
                ];
            }
        }
        $reward = $this->arraySort($reward, 'day', 'desc');
        $reward = array_slice($reward, 0, $limit);
        return $reward;
    }*/

    public function arraySort($arr, $keys, $type = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }

        $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[] = $arr[$k];
        }
        return $new_array;
    }

    /**
     * 投资明细
     * @param $inviteCode
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function investDetail($inviteCode)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $user = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->find();
        if (!$user) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $data = [];
        for ($i = 1;$i <= 2;$i++) {
            $data[$i] = [
                'name'     => RechargeCycle::$statusLabels[$i],
                'profit'   => 0,
                'amount'   => 0,
                'cycle_id' => 0
            ];
        }
        $cycleInfo = RechargeCycle::where('user_id', $user->user_id)->where('status', RechargeCycle::STATUS_PENDING)->select();
        if (empty($cycleInfo)) {
	        return $data;
        }
        $cycleInfo = $cycleInfo->toArray();
        foreach ($cycleInfo as $key => $info) {
            $profit = Income::where('to_user_id', $info['user_id'])->where('type', 'in', [$info['type'], Income::TYPE_INVITE])->sum('amount');
            //$data[$info['type']]['profit'] = $profit;
            if($info['type']==Income::TYPE_DESERT){
            	//$uw = UserWallet::where(['user_id'=>$info['user_id']])->find();
	            $data[$info['type']]['profit'] = $profit;
	            $data[$info['type']]['amount'] = BCMath::sub(BCMath::mul($info['recharge_amount'], 4,3), $info['reward_amount'], 3);
            }elseif($info['type']==Income::TYPE_OASIS){
	            //$uw = UserWallet::where(['user_id'=>$info['user_id']])->find();
	            $data[$info['type']]['profit'] = $profit;
	            $data[$info['type']]['amount'] = BCMath::sub(BCMath::mul($info['recharge_amount'], 6,3), $info['reward_amount'], 3);
            }

            $data[$info['type']]['cash_amount'] = BCMath::sub(BCMath::mul($info['recharge_amount'], CashLog::SYS_CASH_RETA, 12), $info['reward_amount'], 3);
            $data[$info['type']]['cycle_id'] = $info['cycle_id'];
        }
	    //$data['leftSuperNum'] = Db::where(['is_super'=>2])->count();
	    //$data['is_super'] = Db::where(['user_id'=>$user->user_id])->value('is_super');
        return $data;
    }


    /**
     * 投资列表
     * @param $inviteCode
     * @param $page
     * @param int $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeList($inviteCode, $page, $listRows = 20)
    {
        if (!$inviteCode) {
            return false;
        }
       //Db::name()
        //$userId = UserModel::getFieldByInviteCode($inviteCode, 'user_id');
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $model = RechargeOrder::where('user_id', $userId)->where('is_validate', RechargeOrder::VALIDATE_COMPLETE);
        $list = $model->field('amount,type,create_time')->page($page, $listRows)->order('order_id DESC')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$value) {
            $value['type_name'] = RechargeOrder::$statusLabels[$value['type']];
        }
        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 静态收益明细
     * @param $inviteCode
     * @param $page
     * @param int $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function staticProfit($inviteCode, $page, $listRows = 20)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $model = Income::where('user_id', $userId)->where('type', 'in', [Income::TYPE_DESERT, Income::TYPE_OASIS]);
        $list = $model->field('amount,type,create_time')->page($page, $listRows)->order('income_id DESC')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$value) {
            $value['type_name'] = Income::$typeLabels[$value['type']];
        }
        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 邀请收益明细
     * @param $inviteCode
     * @param $page
     * @param int $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function inviteProfit($inviteCode, $page, $listRows = 20)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
	    $list  = Income::alias('i')
		    ->leftJoin('user u','i.user_id=u.user_id')
		    ->where(['i.to_user_id'=>$userId,'i.type'=>Income::TYPE_INVITE])
		    ->field('i.user_id,u.invite_code,i.amount,i.create_time')->page($page,$listRows)->select();
	    $list = $list ? $list->toArray() : [];
	    $directCount = \app\common\model\User::where(['parent_id' =>$userId])->count();
	    $childWhere = ['parent_id'=>$userId];
	    $parentWhere= ['user_id'=>$userId];
	    if($directCount>=4){
		    //查找出用户下3代 上7代
		    $childrenIds = UserBind::where($childWhere)->where('level', 'elt', 7)->where('user_id', '<>', 1)->order('level ASC')->select();
		    $parentIds = UserBind::where($parentWhere)->where('level', 'elt', 3)->where('parent_id', '<>', 1)->order('level ASC')->select();
	    }elseif($directCount>0&&$directCount<4){
		    $childrenIds = UserBind::where($childWhere)->where('level', 'elt', $directCount)->where('user_id', '<>', 1)->order('level ASC')->select();;
		    $parentIds = UserBind::where($parentWhere)->where('level', 'elt', $directCount)->where('parent_id', '<>', 1)->order('level ASC')->select();
	    }else{
		    $childrenIds = [];
		    $parentIds=[];
	    }
        $parent = $parentIds ? $parentIds->toArray() : [];
        $parent = array_column($parent, 'level', 'parent_id');
        $child = $childrenIds ? $childrenIds->toArray() : [];
        $child = array_column($child, 'level', 'user_id');
        foreach ($list as &$value) {
            $level = 0;
            if (array_key_exists($value['user_id'], $parent)) {
                $level = -$parent[$value['user_id']];
            }
            if (array_key_exists($value['user_id'], $child)) {
                $level = $child[$value['user_id']];
            }
            $value['level'] = $level;
        }

        return ['list' => $list, 'total' => count($list)];
    }

    /**
     * 股东收益明细
     * @param $inviteCode
     * @param $page
     * @param int $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function teamProfit($inviteCode, $page, $listRows = 20)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $model = Income::alias('i')
            ->leftJoin('one_user u', 'i.to_user_id = u.user_id')
            ->where('i.to_user_id', $userId)->where('i.type', Income::TYPE_TEAM);
        $list = $model->field('grade,i.amount,i.create_time')->page($page, $listRows)->order('i.income_id DESC')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$value) {
            $value['grade_name'] = UserModel::$groupLabel[$value['grade']] ?? '无等级';
        }

        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * X1明细
     * @param $inviteCode
     * @param $page
     * @param int $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenList($inviteCode, $page, $listRows = 20)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $model = TokenDetail::where('user_id', $userId);
        $list = $model->page($page, $listRows)->order('token_id DESC')->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$value) {
            if ($value['type'] == TokenDetail::TYPE_BUY) {
                $value['price'] = TokenOrder::getFieldByOrderId($value['foreign_id'], 'price');
            } elseif($value['type'] == TokenDetail::TYPE_GIFT) {
                $eggId = EggReward::getFieldByRewardId($value['foreign_id'], 'egg_id');
                $value['price'] = EggOrder::where('egg_id', $eggId)->where('user_id', $userId)->sum('amount');
            }elseif($value['type'] == TokenDetail::TYPE_STATIC){
	            $value['price'] = 0;
            }
            $value['type_name'] = TokenDetail::$typeLabel[$value['type']];
        }

        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 超级节点奖励
     * @param $inviteCode
     * @param $page
     * @param int $listRows
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function superReward($inviteCode, $page, $listRows = 20)
    {
        if (!$inviteCode) {
            return false;
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $model = Income::alias('i')
            ->leftJoin('one_recharge_order r', 'i.foreign_id = r.order_id')
            ->leftJoin('one_user u', 'r.user_id = u.user_id')
            ->where('i.user_id', $userId)->where('i.type', Income::TYPE_SUPER);
        $list = $model->field('invite_code,r .amount as recharge_amount,i.amount,i.create_time')->page($page, $listRows)->order('i.income_id DESC')->select();
        $list = $list ? $list->toArray() : [];
        return ['list' => $list, 'total' => $model->count()];
    }

    /**
     * 升级明细
     * @param $inviteCode
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upgradeLog($inviteCode)
    {
        if (!$inviteCode) {
            return [];
        }
	    $time=mktime(23,59,59,9,28,2019);
	    $userId = UserModel::where(['invite_code' => $inviteCode])->where('create_time','>',$time)->value('user_id');
        //$userId = UserModel::getFieldByInviteCode($inviteCode, 'user_id');

        if (!$userId) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }
        $list = UpgradeLog::where('user_id', $userId)->select();
        $list = $list ? $list->toArray() : [];
        foreach ($list as &$value) {
            $value['grade_name'] = UserModel::$groupLabel[$value['grade']] ?? '';
        }
        return $list;
    }
}
