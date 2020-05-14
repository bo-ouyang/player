<?php


namespace app\index\service;


use app\common\exception\Order as OrderException;
use app\common\model\Config as ConfigModel;
use app\common\model\EggOrder;
use app\common\model\RechargeCycle;
use app\common\model\RechargeOrder;
use app\common\model\TokenOrder;
use app\common\model\User;
use app\common\model\User as UserModel;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use Exception;
use math\BCMath;
use think\Db;
use think\facade\Config;
use think\facade\Log;

class OrderFake {
//充值订单
	const ORDER_RECHARGE = 1;
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
	public function createFake($data) {

		if ($data['type'] == self::ORDER_RECHARGE) {
			if (strtolower($data['receive_address']) != strtolower(Config::get('app.contract_address'))) {
				throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
			}
		}else {
			throw new OrderException(OrderException::getErrorMsg(OrderException::E_CONTRACT_ADDRESS_ERROR), OrderException::E_CONTRACT_ADDRESS_ERROR);
		}

		$data['is_true'] = $data['is_true'] ?? 1;
		if (empty($data['hash'])) {
			throw_new(OrderException::class, OrderException::E_ADDRESS_HASH_ERROR);
		}
		// 查询hash是否已经存在
		$oneExists = RechargeOrder::where('hash', $data['hash'])->find();
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
		$userInfo = User::where(['origin_address' => $address])->find();
		$userId = 0;
		if (!empty($userInfo)) {
			$userId = $userInfo['user_id'];
		}

		if (empty($userId)) {
			$parentInfo = User::where('invite_code', $data['invite_code'] ?? '')->find();
			if (empty($parentInfo)) {
				throw_new(OrderException::class, OrderException::E_INVALID_CODE);
			}
			$data['parent_id'] = $parentInfo->user_id;
			$data['invite_code'] = $parentInfo->invite_code;
			//$data['is_new_user'] = 2; //新用户
			$userId = $this->userAddFake($data);//添加新用户,并添加上下级关系
		}

		if ($data['type'] == self::ORDER_RECHARGE) {
			$result = $this->rechargeFakeOrder($userId, $data);
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
	public function rechargeFakeOrder($userId, $data) {
		$time = time();
		$rechargeQuota = model('common/Config')->getConfig(ConfigModel::KEY_RECHARGE_QUOTA);
		$type = $data['amount'] <= $rechargeQuota ? RechargeOrder::TYPE_DESERT : RechargeOrder::TYPE_OASIS;//判断是1还是2
		$cycle = RechargeCycle::where(['user_id' => $userId, 'type' => $type])->find();
		Db::startTrans();
		if (empty($cycle)) {
			//每笔订单都是一个新的周期
			$cycleData = [
				'user_id' => $userId,
				'type' => $type,
				'create_time' => $time,
				'update_time' => $time,
				'status'=>2
			];
			$cycleId = RechargeCycle::insertGetId($cycleData);
			if (!$cycleId) {
				Db::rollback();
				return false;
			}
		}else{
			common_log('FOrder gen fail');
			return false;
		}
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
			'system_recharge' => $data['system_recharge'] ?? 1,
			'fake_validate'=>1
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
	 * 新增会员
	 * @param array $data
	 * @return bool|int|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function userAddFake(array $data)
	{

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
				'is_true'     => $data['is_true'] ?? 1,
				'is_fake'     =>1
			]);
			if (!$userId->user_id) {
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

}
