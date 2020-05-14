<?php


namespace app\index\controller;


use app\common\model\Config as ConfigModel;
use app\common\model\Income;
use app\common\model\RechargeCycle;
use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use app\common\library\Wallet as WalletLib;
use math\BCMath;
use think\Db;
use think\Exception;
use think\facade\Cache;
use think\facade\Log;

class Temp extends Common {
	//分润比例
	public static $interestRate = [
		'child' => [
			1 => 1,
			2 => 0.5,
			3 => 0.25,
			4 => 0.1,
			5 => 0.1,
			6 => 0.1,
			7 => 0.1
		],
		'parent' => [
			1 => 0.3,
			2 => 0.2,
			3 => 0.1
		],
	];

	public function temp() {
			//echo (0.045+0.03+0.015+0.5+0.075+0.5+0.015+0.0045);
			//$p3 = UserBind::where(['user_id' => $v['user_id']])->where('level', '>=', 3)->distinct(true)->count();
			//$c7 = UserBind::where(['parent_id' => 189])->where('level', 'elt', 7)->where('user_id', '<>', 1)->order('level ASC')->select();
			//echo Income::where(['user_id'=>189])->sum('amount');//21.914625 23.079625 24.244625

				/*$user = User::all();
				$t = 0;
				foreach ($user as $v){
					$c = RechargeCycle::where(['user_id'=>$v['user_id']])
						->where('recharge_amount','>',0)
						->where('recharge_amount','<',1)
						->count();
					$d = RechargeCycle::where(['user_id'=>$v['user_id']])
						->where('recharge_amount','>',1)
						->where('recharge_amount','<',30)
						->count();
					if($c>0&&$d>0){
						echo $v['user_id']."<br>";
					}
				}
				die();*/
				/*echo Income::where('user_id','in',[1287,1342,873,656,151,119,118])->sum('amount');
				exit();*/
				$json = file_get_contents('list.json');
				//echo count(json_decode($json,true));
			$jsonn = file_get_contents('list.json');
			$cc = json_decode($jsonn,true);
			//echo count(json_decode($jsonn,true));
			$ids = array_column($cc,'profit');
			echo array_sum($ids);
			//echo Db::name('income')->sum('amount');
			//echo "<br>";
			//echo Db::name('recharge_order')->sum('amount');
			//echo 16.1640525/1.598875;   0.01425 0.0175
			//1.598875

		// 189 :   257  259  271      292  424  1326  929
		//189  173  166  86
		//356
	}

	public function index() {
		echo 111;
	}

	public function share() {
		if (!ps_qty_limit('think interest2', 10)) {
			common_log('进程限制');
			return false;
		}
		common_log('start interest record');
		$indexKey = 'one_interest_index2';
		$lastId = Cache::get($indexKey);
		$lastId = ((int)$lastId > 0) ? $lastId : 0;
		$now = strtotime(date('Y-m-d'));
		$interest = model('common/Config')->getConfig(ConfigModel::KEY_INTEREST);

		$cycleList = RechargeCycle::where('recharge_amount', '>', 0)
			->where('cycle_id', '>', $lastId)
			->where('create_time', '<', $now)
			->where('status', RechargeCycle::STATUS_PENDING)
			->order('cycle_id asc')
			->limit(100)
			->select();
		/**
		 *  $cycleList 未出局的周期充值订单
		 */
		$cycleList = $cycleList ? $cycleList->toArray() : [];
		if (!$cycleList) {
			Cache::set($indexKey, 0);
			return true;
		} else {
			$lastData = end($cycleList);
			Cache::set($indexKey, $lastData['cycle_id']);
			/**
			 *  start Foreach cycleList
			 */

			foreach ($cycleList as $cycleInfo) {
				common_log($cycleInfo['cycle_id']);
				//判断利息是否已经转过
				$map = [
					'user_id' => $cycleInfo['user_id'],
					'foreign_id' => $cycleInfo['cycle_id'],
					'type' => $cycleInfo['type'],
					'day' => date('Y-m-d'),
				];
				$incomeCount = Income::where($map)->count();
				if ($incomeCount) {
					common_log('今日已发利息');
					continue;
				}
				$rewardList = [];//获利数据
				//利息计算
				$profit = BCMath::mul($cycleInfo['recharge_amount'], $interest, 12);
				/*
				 *  $rewardList 用户获利数组
				 */

				$rewardList[$cycleInfo['user_id']] = [
					'cycle_id' => $cycleInfo['cycle_id'],
					'type' => $cycleInfo['type'],
					'profit' => $profit
				];
				// $rewardAmount 奖励金额 出局判断
				$rewardAmount = BCMath::add($cycleInfo['reward_amount'], $profit, 12);
				if ($cycleInfo['type'] == RechargeCycle::TYPE_DESERT) {
					if ($rewardAmount >= BCMath::mul($cycleInfo['recharge_amount'], 5, 12)) {
						$rewardList[$cycleInfo['user_id']]['complete'] = true;
					}
				} else {
					if ($rewardAmount >= BCMath::mul($cycleInfo['recharge_amount'], 10, 12)) {
						$rewardList[$cycleInfo['user_id']]['complete'] = true;
					}
				}




				$directCount = User::where(['parent_id' => $cycleInfo['user_id']])->count();
				common_log($cycleInfo['user_id'].'的直推人数'.$directCount);
				$childWhere = ['parent_id'=>$cycleInfo['user_id']];
				$parentWhere= ['user_id'=>$cycleInfo['user_id']];
				switch ($directCount) {
					case 0:
						$childrenIds = [];
						$parentIds=[];
						break;
					case 1://直推一人 上下1代收益
						$childrenIds = UserBind::where($childWhere)->where('level', 'elt', 1)->where('user_id', '<>', 1)->order('level ASC')->select();
						$parentIds = UserBind::where($parentWhere)->where('level', 'elt', 1)->where('parent_id', '<>', 1)->order('level ASC')->select();
						break;
					case 2://直推2人 上下2代收益
						$childrenIds = UserBind::where($childWhere)->where('level', 'elt', 2)->where('user_id', '<>', 1)->order('level ASC')->select();
						$parentIds = UserBind::where($parentWhere)->where('level', 'elt', 2)->where('parent_id', '<>', 1)->order('level ASC')->select();
						break;
					case 3://直推三人 上下3代收益
						$childrenIds = UserBind::where($childWhere)->where('level', 'elt', 3)->where('user_id', '<>', 1)->order('level ASC')->select();
						$parentIds = UserBind::where($parentWhere)->where('level', 'elt', 3)->where('parent_id', '<>', 1)->order('level ASC')->select();
						break;
					case $directCount>3:
						//查找出用户下3代 上7代
						$childrenIds = UserBind::where($childWhere)->where('level', 'elt', 7)->where('user_id', '<>', 1)->order('level ASC')->select();
						$parentIds = UserBind::where($parentWhere)->where('level', 'elt', 3)->where('parent_id', '<>', 1)->order('level ASC')->select();
				}
				$childrenIds = $childrenIds ? $childrenIds->toArray() : [];
				$parentIds = $parentIds ? $parentIds->toArray() : [];
				$rewardIds = [];
				/**
				 *  $rewardIds  这笔订单上3 下7 带的基本信息
				 */

				foreach ($childrenIds as $key => $value) {
					$rewardIds[] = [
						'type' => 'child',
						'level' => $value['level'],
						'user_id' => $value['user_id'],
					];
				}
				foreach ($parentIds as $key => $value) {
					$rewardIds[] = [
						'type' => 'parent',
						'level' => $value['level'],
						'user_id' => $value['parent_id'],
					];
				}
				common_log(json_encode($rewardIds));
				$otherCycleInfo = RechargeCycle::where('user_id', $cycleInfo['user_id'])
					->where('status', RechargeCycle::STATUS_PENDING)
					->where('type', '<>', $cycleInfo['type'])
					->find();
				foreach ($rewardIds as $rInfo){
					$rType = $rInfo['type'];
					$rLevel = $rInfo['level'];
					$rUserId = $rInfo['user_id'];
					$rCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING,'type'=>$cycleInfo['type']])->find();
					if(empty($rCycleInfo)){
						$rCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING])->find();
						if(empty($rCycleInfo)){
							common_log($rUserId . ' 无投资');
							continue;
						}
					}else{
						$orCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING])->where('cycle_id','<>',$rCycleInfo->cycle_id)->find();
					}
					//如果当前用户有另外一种投资模式 ???
					if(!empty($otherCycleInfo)){
						/*$rCount = RechargeCycle::where('user_id', $rUserId)->where('status', RechargeCycle::STATUS_PENDING)->count();
						if ($rCount == 1) {
							//充值用户有两种投资 获益用户只有一种投资
							if ($cycleInfo['recharge_amount'] < $otherCycleInfo->recharge_amount) {
								continue;
							}
						}*/
					}
					$parentAmount = $rCycleInfo->recharge_amount;
					if(!empty($orCycleInfo)){
						$parentAmount = BCMath::add($rCycleInfo->recharge_amount,$orCycleInfo->recharge_amount,12);
					}
					/*
					 * 烧伤判断
					 */
					if($parentAmount<$cycleInfo['recharge_amount']){
						$uProfit = BCMath::mul(BCMath::mul($parentAmount, $interest, 12)+$profit, self::$interestRate[$rType][$rLevel], 12);
					}else{
						$uProfit = BCMath::mul($profit, self::$interestRate[$rType][$rLevel], 12);
					}

					if($uProfit<=0){
						continue;
					}
					$rewardList[$rUserId] = [
						'cycle_id' => $rCycleInfo->cycle_id,
						'type' => $rCycleInfo->type,
						'profit' => $uProfit
					];

					$rRewardAmount = BCMath::add($rCycleInfo->reward_amount, $uProfit);
					if ($rCycleInfo->type == RechargeCycle::TYPE_DESERT) {
						if ($rRewardAmount >= BCMath::mul($rCycleInfo->recharge_amount, 5, 12)) {
							$rewardList[$rUserId]['complete'] = true;
						}
					} else {
						if ($rRewardAmount >= BCMath::mul($rCycleInfo->recharge_amount, 10, 12)) {
							$rewardList[$rUserId]['complete'] = true;
						}
					}
				}

				$foreign_id = 0;
				foreach ($rewardList as $uid=>$item){
					Db::startTrans();
					$inCome = Db::name('income');
					$userWallet = Db::name('user_wallet');
					$RechargeCycle = Db::name('recharge_cycle');
					try{
						$incomeData = [
							'user_id' => $uid,
							'foreign_id' => $item['cycle_id'],
							'type' => Income::TYPE_INVITE,
							'amount' => $item['profit'],
							'day' => date('Y-m-d'),
							'create_time' => time()
						];
						if($uid==$cycleInfo['user_id']){
							$incomeData['type'] = $item['type'];
							if($item['type']==RechargeCycle::TYPE_DESERT){
								$ret = $userWallet->where(['user_id'=>$uid])->inc('desert_profit', $item['profit'])->update(['update_time' => time()]);
							}else{
								$ret = $userWallet->where('user_id', $uid)->inc('oasis_profit', $item['profit'])->update(['update_time' => time()]);

							}
							if (!$ret) {
								Db::rollback();
								continue 2;
							}

						}else{
							$incomeData['foreign_id'] = $foreign_id;
							$ret = $userWallet->where(['user_id'=>$uid])->inc('invite_profit', $item['profit'])->update(['update_time' => time()]);
							if (!$ret) {
								Db::rollback();
								continue 2;
							}
						}
						$incomeId = $inCome->insertGetId($incomeData);
						if (!$incomeId) {
							Db::rollback();
							continue 2;
						}
						$cycleData = ['update_time' => time()];
						//如果当前用户已出具则创建新的投资周期
						if (isset($item['complete'])&&$item['complete']==true) {
							/*$cycleData['status'] = RechargeCycle::STATUS_COMPLETE;
							//创建新的投资周期
							$result = RechargeCycle::insert([
								'user_id' => $uid,
								'type' => $item['type'],
								'create_time' => time(),
								'update_time' => time()
							]);
							if (!$result) {
								Income::rollback();
								continue 2;
							}*/
						}
						$result = $RechargeCycle->where('cycle_id', $cycleInfo['cycle_id'])->inc('reward_amount', $item['profit'])->update($cycleData);//??
						if (!$result) {
							Db::rollback();
							continue 2;
						}
						//用户转币地址查询
						$address = $this->getUserAddress($uid);
						//发送利息
						$result = WalletLib::transfer($address, $item['profit'], 1);
						if (!$result) {
							Db::rollback();
							continue 2;
						}

						Db::commit();
					}catch (Exception $e) {
						common_log($e);
						Db::rollback();
						continue 2;
					}
				}

			}
		}
	}
	/**
	 * @param $user_id
	 * @return mixed
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 *
	 * 根据用户ID获取用户的转币地址
	 */
	private function getUserAddress($user_id) {
		$user = new  User();
		$user_info = $user->where('user_id=' . intval($user_id))->field('address')->find();
		if (!$user_info) {
			return false;
		}

		$user_info = $user_info->toArray();
		return $user_info['address'];
	}


}
