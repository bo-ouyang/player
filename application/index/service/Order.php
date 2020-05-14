<?php
/**
 * 订单服务类
 */

namespace app\index\service;

use app\common\library\Wallet as WalletLib;
//use app\common\model\Config;
use app\common\model\Egg;
use app\common\model\EggOrder;
use app\common\model\EggReward;
use app\common\model\Income;
use app\common\model\RechargeCycle;
use app\common\model\RechargeOrder;
use app\common\model\SystemStatistic;
use app\common\model\TokenDetail;
use app\common\model\TokenOrder;
use app\common\model\UpgradeLog;
use app\common\model\UserBind;
use app\common\model\UserInvest;
use think\Db;
use app\common\model\User;
use app\common\model\UserWallet;
use app\common\exception\User as UserException;
use app\common\exception\Order as OrderException;
use app\common\model\Config as ConfigModel;
use math\BCMath;
use app\common\model\CashLog;
use think\Exception;
use think\facade\Config;
use think\facade\Log;

class Order {
	use \app\common\traits\MqOperate;
	//充值订单
	const ORDER_RECHARGE = 1;
	//彩蛋订单
	const ORDER_EGG = 2;
	//token订单
	const ORDER_TOKEN = 3;

	/**
	 * 订单提交
	 * @param $data
	 * @return bool|int|string|true
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function create($data) {

		/*if ($data['type'] == self::ORDER_RECHARGE) {
			//金额小于1 判断
			if ($data['amount'] < 1) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_MIN_INVEST), OrderException::E_MIN_INVEST);
			}
			$sub = $data['amount'] - (int)$data['amount'];

			if ($sub > 0) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_AMOUNT_TYPE_ERR), OrderException::E_AMOUNT_TYPE_ERR);
			}
		}*/
      	//dump($data);
		if ($data['type'] == self::ORDER_RECHARGE) {
			if (strtolower($data['receive_address']) != strtolower(Config::get('app.contract_address'))) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
			}
		} elseif ($data['type'] == self::ORDER_EGG) {
			if (strtolower($data['receive_address']) != strtolower(Config::get('app.egg_address'))) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
			}
		} elseif ($data['type'] == self::ORDER_TOKEN) {
			if (strtolower($data['receive_address']) != strtolower(Config::get('app.token_address'))) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
			}
		} else {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
		}

		$data['is_true'] = $data['is_true'] ?? 1;
		//$isTest = $data['is_test'] ?? 1;
		//if ($data['is_true'] == 1 && $isTest == 1) {
			/*
			$result = \app\common\library\Wallet::validHash($data['hash'], $data['address'], $data['amount']);
			if (!$result) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_ADDRESS_HASH_ERROR), OrderException::E_ADDRESS_HASH_ERROR);
			}
			*/
		//}

		if (empty($data['hash'])) {
			throw_new(OrderException::class, OrderException::E_ADDRESS_HASH_ERROR);
		}

		// 查询hash是否已经存在
		$oneExists = RechargeOrder::where('hash', $data['hash'])->find();
		$twoExists = EggOrder::where('hash', $data['hash'])->find();
		$threeExists = TokenOrder::where('hash', $data['hash'])->find();
		if (!empty($oneExists) || !empty($twoExists) || !empty($threeExists)) {
			throw_new(OrderException::class, OrderException::E_ADDRESS_HASH_ERROR);
		}
		$data['amount'] = BCMath::div($data['amount'], pow(10, 18), 12);//number_format($data['amount'], 12);//
		Log::info(json_encode($data));
		$address = empty($data['address'])?'':$data['address'];
		if (!$address) {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_ADDRESS_EMPTY), OrderException::E_ADDRESS_EMPTY);
		}
		//判断用户是否存在
		$stime = mktime(23,59,59,9,28,2019);

		$userInfo = User::where(['origin_address' => $address])->where('create_time','>',$stime)->find();

		$userId = 0;
		if (!empty($userInfo)) {
			$userId = $userInfo['user_id'];
		}

		if (empty($userId)) {
			$time=mktime(23,59,59,9,28,2019);
			$parentInfo = User::where('invite_code', $data['invite_code'] ?? '')->where('create_time','>',$time)->find();
			if (empty($parentInfo)) {
				throw_new(OrderException::class, OrderException::E_INVALID_CODE);
			}
			$data['parent_id'] = $parentInfo->user_id;
			$data['invite_code'] = $parentInfo->invite_code;

			$userId = service('index/User')->userAdd($data);//添加新用户,并添加上下级关系
		}

		if ($data['type'] == self::ORDER_RECHARGE) {
			$result = $this->rechargeOrder($userId, $data);
			if (!$result) {
				return false;
			}
		} elseif ($data['type'] == self::ORDER_EGG) {
			$result = $this->eggOrder($userId, $data);
			if (!$result) {
				return false;
			}
		} elseif ($data['type'] == self::ORDER_TOKEN) {
			$result = $this->tokenOrder($userId, $data);
			if (!$result) {
				return false;
			}
		} else {
			return false;
		}

		$inviteCode = User::where('user_id', $userId)->value('invite_code');
		return $inviteCode;
	}

	/**
	 * 充值订单
	 * @param $userId
	 * @param $data
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function rechargeOrder($userId, $data) {
		//xml()
		$time = time();
		$rechargeQuota = model('common/Config')->getConfig(ConfigModel::KEY_RECHARGE_QUOTA);
		//获取充值额度
		$type = $data['amount'] <= $rechargeQuota ? RechargeOrder::TYPE_DESERT : RechargeOrder::TYPE_OASIS;//判断是1还是2
		$cycle = RechargeCycle::where(['user_id' => $userId])->find();
		Db::startTrans();
		if (empty($cycle)) {
			//每笔订单都是一个新的周期
			$cycleData = [
				'user_id' => $userId,
				'type' => $type,
				'create_time' => $time,
				'update_time' => $time
			];
			$cycleId = RechargeCycle::insertGetId($cycleData);
			if (!$cycleId) {
				Db::rollback();
				return false;
			}
		} else if($cycle->status==2){//订单出局生成新的投资周期
			$cycleData = [
				'user_id' => $userId,
				'type' => $type,
				'create_time' => $time,
				'update_time' => $time
			];
			$cycleId = RechargeCycle::insertGetId($cycleData);
			if (!$cycleId) {
				Db::rollback();
				return false;
			}
		}else{
			$cycleId = $cycle->cycle_id;
		}
		// echo $data['amount'];
		$orderSn = generage_order_sn();


		//开始生成充值订单
		$orderData = [
			'order_sn' => $orderSn,
			'user_id' => $userId,
			'cycle_id' => $cycleId,
			'amount' => $data['amount'],
			'type' => $type,
			'hash' => $data['hash'],
			'create_time' => time(),
			'update_time' => time(),
			'is_true' => $data['is_true'],
			'is_validate' => ($data['is_true'] == 1) ? RechargeOrder::VALIDATE_UNDONE : RechargeOrder::VALIDATE_COMPLETE,
			'system_recharge' => $data['system_recharge'] ?? 1
		];
		$orderId = RechargeOrder::insertGetId($orderData);
		if (!$orderId) {
			Db::rollback();
			return false;
		}

		Db::commit();
		return true;
	}

	/**
	 * 彩蛋充值
	 * @param $userId
	 * @param $data
	 * @return bool
	 * @throws \think\exception\PDOException
	 */
	public function eggOrder($userId, $data) {
		$time = time();
		$eggId = Egg::where('status', Egg::STATUS_PENDING)->where('type', $data['egg_type'])->value('egg_id');
		$orderSn = generage_order_sn();
		EggOrder::startTrans();
		$eggQuota = model('common/Config')->getConfig(Egg::$quotaKeyMap[$data['egg_type']]);
		if (empty($eggId)) {
			$eggData = [
				'type' => $data['egg_type'],
				'quota' => $eggQuota,
				'create_time' => $time,
				'update_time' => $time
			];
			$eggId = Egg::insertGetId($eggData);
			if (!$eggId) {
				EggOrder::rollback();
				return false;
			}
		}
		$orderData = [
			'order_sn' => $orderSn,
			'user_id' => $userId,
			'egg_id' => $eggId,
			'amount' => $data['amount'],
			'type' => $data['egg_type'],
			'hash' => $data['hash'] ?? '',
			'create_time' => time(),
			'update_time' => time(),
			'is_true' => $data['is_true'],
			'is_validate' => ($data['is_true'] == 1) ? EggOrder::VALIDATE_UNDONE : EggOrder::VALIDATE_COMPLETE,
		];
		$orderId = EggOrder::insertGetId($orderData);
		if (!$orderId) {
			EggOrder::rollback();
			return false;
		}

		EggOrder::commit();
		return true;
	}

	/**
	 * token订单
	 * @param $userId
	 * @param $data
	 * @return bool
	 */
	public function tokenOrder($userId, $data) {
		$tokenPrice = model('common/Config')->getConfig(ConfigModel::KEY_TOKEN_PRICE);
		$tokenLimit = model('common/Config')->getConfig(ConfigModel::KEY_TOKEN_LIMIT);
		$tokenNumber = TokenOrder::where('create_time', '>', strtotime(date('Y-m-d')))->where('is_validate', TokenOrder::VALIDATE_COMPLETE)->sum('number');
		if ($tokenNumber >= $tokenLimit) {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_TOKEN_ORDER_LIMIT), OrderException::E_TOKEN_ORDER_LIMIT);
		}
		$number = BCMath::mul($data['amount'], $tokenPrice, 12);
		$orderSn = generage_order_sn();
		$orderData = [
			'order_sn' => $orderSn,
			'user_id' => $userId,
			'number' => $number,
			'price' => $data['amount'],
			'hash' => $data['hash'],
			'create_time' => time(),
			'update_time' => time(),
			'is_true' => $data['is_true'],
			'is_validate' => ($data['is_true'] == 1) ? TokenOrder::VALIDATE_UNDONE : TokenOrder::VALIDATE_COMPLETE,
		];
		$result = TokenOrder::insert($orderData);
		if (!$result) {
			return false;
		}

		return true;
	}


	/**
	 * 充值订单hash验证通过
	 * @param $order
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function validRechargeOrder($order) {
		$time = time();
		$userId = $order['user_id'];
		$orderId = $order['order_id'];
		$cycleId = $order['cycle_id'];
		$type = $order['type'];
		$amount = $order['amount'];

		$userInfo = User::get($userId);
		//是否升级
		$upgradeList = $this->upgrade($userId, $amount);
		//更新用户业绩

		common_log(json_encode($upgradeList));
		if ($userInfo->is_super == User::SUPER_NO) {
			//是否成为超级节点
			$superNodeQuota = model('common/Config')->getConfig(ConfigModel::KEY_SUPER_NODE_QUOTA);
			if ($amount >= $superNodeQuota) {
				$isSuperNode = true;
			}
		}

		/*if (isset($isSuperNode)) {
			//提前获取超级节点计算需要的数据，避免都在事务中获取，导致事务停留时间过长，造成死锁
			$parentId = User::getFieldByUserId($userId, 'parent_id');
			if ($parentId) {
				$pIsSuper   = User::getFieldByUserId($parentId, 'is_super');
			}
			$childrenIds = User::where('parent_id', $userId)->column('user_id');
			if (!empty($childrenIds)) {
				$cIsSuper = User::where('user_id', 'in', $childrenIds)->field('user_id,is_super')->select();
				$cIsSuper = $cIsSuper ? $cIsSuper->toArray() : [];
				$cIsSuper = array_column($cIsSuper, 'is_super', 'user_id');
			}
		}*/
		//查找超级节点的上级
		$userIds = UserBind::where('user_id', $userId)->order('level ASC')->column('parent_id');
		$superParentIds = [];
		foreach ($userIds as $pUserId) {
			$isSuper = Db::name('user')->field('is_super,create_time')->where('user_id',$pUserId)->find();
			if ($isSuper['is_super'] == User::SUPER_YES&&$isSuper['create_time']>mktime(23,59,59,9,28,2019)) {
				$superParentIds[] = $pUserId;
			}
		}
		Db::startTrans();
		try {

			$result = Db::name('recharge_order')->where('order_id', $orderId)->update(['is_validate' => RechargeOrder::VALIDATE_COMPLETE, 'update_time' => $time]);
			if (!$result) {
				Db::rollback();
				return false;
			}
			$result = Db::name('recharge_cycle')->where('cycle_id', $cycleId)->inc('recharge_amount', $amount)->update(['update_time' => $time]);
			if (!$result) {
				Db::rollback();
				return false;
			}
			$result = Db::name('recharge_cycle')->where(['user_id'=>$userId,'cycle_id'=>$cycleId])->value('recharge_amount');
			if ($result>=21) {
				$result = Db::name('recharge_cycle')->where(['user_id'=>$userId,'cycle_id'=>$cycleId])->update(['type'=>2]);
				if($result){
					Db::rollback();
					return false;
				}

			}
			if ($type == RechargeOrder::TYPE_DESERT) {
				$result = UserWallet::where('user_id', $userId)->inc('desert_amount', $amount)->update(['update_time' => $time]);
			} else {
				$result = UserWallet::where('user_id', $userId)->inc('oasis_amount', $amount)->update(['update_time' => $time]);
			}
			if (!$result) {
				Db::rollback();
				return false;
			}
			//上级超级节点奖励
			if (!empty($superParentIds)) {
				common_log('该用户的上级超级节点' . json_encode($superParentIds));
				foreach ($superParentIds as $v) {
					$result = model('common/Income')->incomeChange($v, BCMath::mul($amount, 0.05, 12), Income::TYPE_SUPER, $orderId);
					if (!$result) {
						Db::rollback();
						return false;
					}
					$address = model('common/User')->getUserAddress($v);
					$result = WalletLib::transfer($address, BCMath::mul($amount, 0.05, 12), 1);
					if (!$result) {
						Db::rollback();
						return false;
					}
				}
			}
			//系统总业绩
			$result = model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_TOTAL_PERFORMANCE, $amount);
			if (!$result) {
				RechargeOrder::rollback();
				return false;
			}
			//系统奖池
			$result = model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_TOTAL_STATIC, BCMath::mul($amount, 0.75, 12));
			if (!$result) {

				Db::rollback();
				return false;
			}
			//用户升级
			foreach ($upgradeList as $uUserId => $info) {
				$result = User::where('user_id', $uUserId)->update(['grade' => $info['grade'], 'update_time' => $time]);
				if (!$result) {
					Db::rollback();
					return false;
				}
				$result = UpgradeLog::insert([
					'user_id' => $uUserId,
					'grade' => $info['grade'],
					'amount' => $info['amount'],
					'create_time' => $time
				]);
				if (!$result) {
					Db::rollback();
					return false;
				}
			}

			//升级超级节点
			if (isset($isSuperNode)) {
				$result = User::where('user_id', $userId)->update(['is_super' => User::SUPER_YES, 'update_time' => $time]);
				if (!$result) {
					RechargeOrder::rollback();
					return false;
				}
				//下级成为超级节点奖励上级30eth
				/*if (isset($pIsSuper) && $parentId != 0 && $parentId != 1) {
					$result = model('common/Income')->incomeChange($parentId, 30, Income::TYPE_SUPER, $orderId);
					if (!$result) {
						RechargeOrder::rollback();
						return false;
					}
					$address = model('common/User')->getUserAddress($parentId);
					$result = WalletLib::transfer($address, 30,1);
					if (!$result) {
						RechargeOrder::rollback();
						return false;
					}
				}*/

				/*if (isset($cIsSuper)) {
					$childNumber = count($cIsSuper);
					$result = model('common/Income')->incomeChange($userId, 30 * $childNumber, Income::TYPE_SUPER, $orderId);
					if (!$result) {
						RechargeOrder::rollback();
						return false;
					}
					$address = model('common/User')->getUserAddress($userId);
					$result = WalletLib::transfer($address, 30 * $childNumber,1);
					if (!$result) {
						RechargeOrder::rollback();
						return false;
					}
				}*/
			}
		} catch (Exception $e) {
			common_log($e);
			Db::rollback();
			return false;
		}

		Db::commit();
		return true;
	}

	/**
	 * 计算充值用户和上级是否升级
	 * @param $userId
	 * @param $amount
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function upgrade($userId, $amount) {
		$userIds = UserBind::where('user_id', $userId)->where('parent_id', '<>', 1)->where('parent_id','>',2313)->order('level ASC')->column('parent_id');
		$userIds = $userIds ?? [];//当前user_id 的所有 上级

		array_unshift($userIds, $userId); //
		common_log('用户和用户上级' . json_encode($userIds));
		$upgradeConfig = model('common/Config')->getUserUpgradeQuota();//获取用户升级额度
		//循环判断每个用户是否升级
		$upgradeList = [];
		foreach ($userIds as $key => $pUserId) {
			common_log('开始循环');
			$childrenData = UserBind::where('parent_id', $pUserId)->field('user_id,level')->order('level DESC')->select();//拿到子集
			$childrenData = $childrenData ? $childrenData->toArray() : [];
			$childrenIds = array_column($childrenData, 'user_id');//多有 子集 ID
			array_unshift($childrenIds, $pUserId);
			$grade = User::where('user_id', $pUserId)->value('grade');
			if ($grade >= 5) {
				continue;
			}
			//获取直接下级
			$childrenList = array_column($childrenData, null, 'user_id');
			$directChildren = $this->getDirectChildren($childrenList);
			$directChildren = empty($directChildren) ? [] : $directChildren;
			if(count($directChildren)<10){
				common_log('ID为'.$pUserId.'的用户推荐人数不够,无法升级');
				continue;
			}
			common_log('用户' . $pUserId . '直推' . json_encode($directChildren));
			//计算升级
			$totalAmount = UserWallet::where('user_id', 'in', $childrenIds)->value('sum(desert_amount) + sum(oasis_amount) as total');
			$totalAmount = BCMath::add($totalAmount, $amount, 12);
			arsort($upgradeConfig);
			foreach ($upgradeConfig as $uGrade => $gradeAmount) {
				if ($uGrade <= $grade) {
					break;
				}
				common_log($totalAmount . ':直推的所有金额' . '-升级金额' . $gradeAmount);
				if ($totalAmount >= $gradeAmount) {
					if (empty($directChildren)) {
						//没有下级则直接升级
						$upgradeList[$pUserId] = [
							'grade' => $uGrade,
							'amount' => $totalAmount,
						];
						break;
					} else {
						$directAmount = [];
						foreach ($directChildren as $childId) {
							$grandson = UserBind::where('parent_id', $childId)->column('user_id');
							array_unshift($grandson, $childId);
							$damount = UserWallet::where('user_id', 'in', $grandson)->value('sum(desert_amount) + sum(oasis_amount) as total');
							if (in_array($userId, $grandson)) {
								$damount = BCMath::add($damount, $amount, 12);
							}
							$directAmount[] = $damount;

						}
						/*if (count($directAmount) == 1) {
							//只有一个分支则不去除业绩最大的分支的业绩
							$upgradeList[$pUserId] = [
								'grade'  => $uGrade,
								'amount' => $totalAmount,
							];
							break;
						} else {

						}*/
						//
						asort($directAmount);
						$maxAmount = end($directAmount);
						$totalChildAmount = array_sum($directAmount);
						//common_log('下级最大金额' . $maxAmount);
						//common_log('总金额' . $totalChildAmount);
						$realAmount = BCMath::sub($totalAmount, $maxAmount, 12);
						if ($realAmount >= $gradeAmount) {
							$upgradeList[$pUserId] = [
								'grade' => $uGrade,
								'amount' => $realAmount,
							];
							break;
						}
					}
				}
			}
		}

		return $upgradeList;
	}

	/**
	 * 取得直接下级
	 * @param $children
	 * @return mixed
	 */
	private function getDirectChildren($children) {
		$children = $this->getLevelUser($children);
		ksort($children);
		return current($children);
	}

	/**
	 * 根据等级组装用户父级或者子级id
	 * @param $childrenData
	 * @return array
	 */
	private function getLevelUser($list) {
		$data = [];
		foreach ($list as $key => $value) {
			$data[$value['level']][] = $value['user_id'];
		}
		return $data;
	}

	/**
	 * 开奖
	 * @param $eggId
	 * @return array|bool
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function luckDraw($eggId) {
		$list = model('common/EggOrder')->where('egg_id', $eggId)->field('user_id,sum(amount) total_amount')->group('user_id')->select();
		$list = $list ? $list->toArray() : [];
		$eggType = Egg::where(['egg_id' => $eggId])->value('type');
		$luckUserNum = EGG::$quotaKeyUser[$eggType];
		$luckUserPrice = EGG::$luckUserPrice[$eggType];
		if (empty($list)) {
			return false;
		}
		$rewardIds = [];
		//未中奖获得token的用户id
		$eggUserIds = [];
		$eggLeft = 0;
		//业绩前十名
		$Invest = UserInvest::field('user_id,user_invest')->order('user_invest','desc')->limit(10)->column('user_id');

		if (count($list) <= $luckUserNum) {//人数不够
			//$rewardIds = array_column($list, 'user_id','user_id');
			foreach ($list as $key => $value) {
				$rewardIds[$value['user_id']] = $luckUserPrice;
			}
			$conf = new ConfigModel();
			$eggQuota = $conf->getConfig(EGG::$quotaKeyMap[$eggType]);
			$eggLeft = $eggQuota - (count($list) * $luckUserPrice)-(Egg::SYS_FEE*$eggQuota);

		} else {
			$totalWeight = 0;
			foreach ($list as $key => $value) {
				$list[$key]['pre_weight'] = $totalWeight;
				$totalWeight += BCMath::mul($value['total_amount'], 10, 0);
				$list[$key]['weight'] = $totalWeight;
			}
			$i = 0;
			while (count($rewardIds) < $luckUserNum) {
				$random = mt_rand(0, $totalWeight);
				foreach ($list as $key => $value) {
					if(in_array($value['user_id'],$Invest)){
						$rewardIds[$value['user_id']] = $luckUserPrice;
					} else if ($random >= $value['pre_weight'] + 1 && $random <= $value['weight']) {
						$rewardIds[$value['user_id']] = $luckUserPrice;
					}
					unset($list[$key]);
				}
				if ($i > 100000) {
					return false;
				}
			}
			//token 用户
			foreach ($list as $key => $value) {
				if ($value['total_amount'] <= 3 && $value['total_amount'] >= 0.1) {
					$eggUserIds[$value['user_id']] = $value['total_amount'];
				}
			}
		}

		return ['reward' => $rewardIds, 'egg' => $eggUserIds, 'eggLeft' => $eggLeft];
	}
	/**
	 * 彩蛋订单hash验证通过
	 * @param $order
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function validEggOrder($order) {
		$time = time();
		$userId = $order['user_id'];
		$orderId = $order['order_id'];
		$amount = $order['amount'];
		$eggId = $order['egg_id'];

		$eggInfo = Egg::where('egg_id', $eggId)->field('egg_id, amount, quota')->find();
		$eggAmount = $eggInfo->amount + $amount;
		$quota = $eggInfo->quota;
		//开奖
		$eggOpen = false;
		$rewardIds = [];//中奖人
		$eggUserIds = [];//未中奖人发token
		$eggLeft = 0;//发奖剩余
		if ($eggAmount >= $quota) {
			$result = $this->luckDraw($eggId);
			if (!$result) {
				return false;
			}
			$rewardIds = $result['reward'];
			$eggUserIds = $result['egg'];
			$eggLeft = $result['eggLeft'];
			$eggOpen = true;
		}

		EggOrder::startTrans();
		try {
			$eggUpdate = ['update_time' => $time];
			if ($eggOpen) {
				$eggUpdate['status'] = Egg::STATUS_COMPLETE;
			}
			$result = Egg::where('egg_id', $eggId)->inc('amount', $amount)->update($eggUpdate);
			if (!$result) {
				EggOrder::rollback();
				return false;
			}

			$result = EggOrder::where('order_id', $orderId)->update(['is_validate' => EggOrder::VALIDATE_COMPLETE, 'update_time' => $time]);
			if (!$result) {
				EggOrder::rollback();
				return false;
			}

			$result = UserWallet::where('user_id', $userId)->inc('egg_amount', $amount)->update(['update_time' => $time]);
			if (!$result) {
				EggOrder::rollback();
				return false;
			}
			$result = model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_TOTAL_EGG_AMOUNT, $amount);
			if (!$result) {
				EggOrder::rollback();
				return false;
			}
			//common_log('金额足够,人数不够');
			common_log('中奖人'.json_encode($rewardIds));
			common_log('剩余金额'.$eggLeft);
			//平台抽成 10% 进入静态池 和 开奖剩余
			if ($eggOpen) {
				$HolderAddress = Config::get('app.contract_address');
				$conf = new ConfigModel();
				$eggQuota = $conf->getConfig(EGG::$quotaKeyMap[$order['type']]);
				$sysFee = $eggQuota * EGG::SYS_FEE + $eggLeft;
				//common_log('手续费:'.$eggQuota * EGG::SYS_FEE.'---'.'剩余金额:'.$eggLeft);
				$result = WalletLib::transfer($HolderAddress, $sysFee, 1);
				if (!$result) {
					RechargeOrder::rollback();
					return false;
				}
				$result = model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_TOTAL_STATIC, $sysFee);
				if (!$result) {
					EggOrder::rollback();
					return false;
				}

			}


			//发放奖励
			if (!empty($rewardIds)) {
				foreach ($rewardIds as $rewardId => $amount) {
					$result = model('common/EggReward')->reward($rewardId, $amount, $eggId, EggReward::TYPE_ETH);
					if (!$result) {
						EggOrder::rollback();
						return false;
					}
					$address = model('common/User')->getUserAddress($rewardId);
					$result = WalletLib::transfer($address, $amount, 1);
					if (!$result) {
						RechargeOrder::rollback();
						return false;
					}
				}
			}
			//发放token
			if (!empty($eggUserIds)) {
				foreach ($eggUserIds as $rewardId => $amount) {
					$result = model('common/EggReward')->reward($rewardId, $amount, $eggId, EggReward::TYPE_TOKEN);
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

	/**
	 * token订单hash验证通过
	 * @param $order
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function validTokenOrder($order) {
		$time = time();
		$userId = $order['user_id'];
		$orderId = $order['order_id'];
		$number = $order['number'];
		$price = $order['price'];

		TokenOrder::startTrans();
		$result = TokenOrder::where('order_id', $orderId)->update(['is_validate' => TokenOrder::VALIDATE_COMPLETE, 'update_time' => $time]);
		if (!$result) {
			TokenOrder::rollback();
			return false;
		}

		$result = TokenDetail::insert([
			'user_id' => $userId,
			'type' => TokenDetail::TYPE_BUY,
			'foreign_id' => $orderId,
			'amount' => $number,
			'create_time' => $time
		]);
		if (!$result) {
			TokenOrder::rollback();
			return false;
		}

		$result = UserWallet::where('user_id', $userId)->inc('token_amount', $number)->inc('token_cost', $price)->update(['update_time' => $time]);
		if (!$result) {
			TokenOrder::rollback();
			return false;
		}

		TokenOrder::commit();
		return true;
	}

	/**
	 * 获取订单列表
	 */
	public function getInvestList($data) {
		if (!$data['invite_code'] || !$data['type'] || !$data['page']) {
			throw_new(UserException::class, UserException::E_NOT_EXISTS);
		}

		$user = User::where('invite_code', $data['invite_code'])->find();
		if (!$user) {
			throw_new(UserException::class, UserException::E_NOT_EXISTS);
		}

		$pageSize = Config::get('paginate.list_rows');
		if ($data['type'] == CashLog::TYPE_CURRENT) {
			// 活期
			$model = model('common/CurrentOrder')->where(['user_id' => $user['user_id']]);
		} else {
			$model = model('common/RegularOrder')->where(['user_id' => $user['user_id']]);
		}

		$list = $model->page($data['page'], $pageSize)->select();
		if ($list) {
			$list = !is_array($list) ? $list->toArray() : $list;
			$nowTime = time();

			foreach ($list as &$order) {
				if ($data['type'] != CashLog::TYPE_CURRENT) {
					switch ($order['type']) {
						case RegularOrder::TYPE_MONTH:
							// 1个月
							$endTime = $order['create_time'] + 2592000;
							break;

						case RegularOrder::TYPE_TWO_MONTH:
							// 3个月
							$endTime = $order['create_time'] + 7776000;
							break;

						case RegularOrder::TYPE_SIX_MONTH:
							// 6个月
							$endTime = $order['create_time'] + 15552000;
							break;

						case RegularOrder::TYPE_TWELVE_MONTH:
							// 12个月
							$endTime = $order['create_time'] + 31104000;
							break;
					}

					if ($nowTime < $endTime) {
						// 不可提现 2
						$order['status'] = RegularOrder::STATUS_COMPLETE;
					} else {
						// 可提现 1
						$order['status'] = RegularOrder::STATUS_UNDONE;
					}
				}
			}

			return ['list' => $list, 'total' => $model->count()];
		}

		return [];
	}

	/**
	 * 活期和定期入队列
	 */
	public function orderQueue($data) {
		if (empty($data)) {
			return false;
		}

		if (strtolower($data['receive_address']) != strtolower(Config::get('app.contract_address'))) {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
		}

		$data['amount'] = BCMath::div($data['amount'], pow(10, 18), 12);
		if ($data['amount'] < Config::get('reward.min_invest')) {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_MIN_INVEST), OrderException::E_MIN_INVEST);
		}

		$address = $data['address'] ?? '';
		if (!$address) {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_ADDRESS_EMPTY), OrderException::E_ADDRESS_EMPTY);
		}

		$mqConfig = Config::get('mq.eth_order');
		// 获取邀请码
		$inviteCode = model('common/User')->getInviteCode();
		$data['user_invite_code'] = $inviteCode;
		$data['queue_time'] = time();
		$this->publish($mqConfig, $data);

		return $inviteCode;
	}

	/**
	 * 换汇订单
	 */
	public function exchangeQueue($data) {
		if (empty($data)) {
			return false;
		}

		if (strtolower($data['receive_address']) != strtolower(Config::get('app.contract_address'))) {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
		}

		$mqConfig = Config::get('mq.exchange_order');
		$data['queue_time'] = time();

		$this->publish($mqConfig, $data);
		return true;
	}

	/**
	 * 保存错误信息
	 */
	public function saveErrorInfo($data) {
		if (!$data) {
			return false;
		}

		$attribute = [
			'content' => json_encode($data),
			'type' => \app\common\model\ErrorInfo::TYPE_ORDER,
			'create_time' => time(),
		];

		$errorId = \app\common\model\ErrorInfo::create($attribute);
		if ($errorId->info_id) {
			return true;
		}

		return false;
	}
}
