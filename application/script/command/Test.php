<?php
/**
 * 订单利息
 */

namespace app\script\command;

use app\common\model\CashLog;
use app\common\model\Income;
use app\common\model\RechargeCycle;
use app\common\model\TokenDetail;
use think\config\driver\Xml;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\common\model\UserWallet;
use app\common\model\UserBind;
use app\common\model\User;
use app\common\model\Config as ConfigModel;
use app\common\library\Wallet as WalletLib;
use math\BCMath;
use think\Exception;
use think\Facade\Config;
use think\Db;
use think\facade\Cache;


class Test extends Command {
	public static $interestRate = [

		'child' => [
			1 => 0.500000000000,
			2 => 0.250000000000,
			3 => 0.100000000000,
			4 => 0.100000000000,
			5 => 0.100000000000,
			6 => 0.100000000000,
			7 => 0.100000000000
		],
		'parent' => [
			1 => 0.300000000000,
			2 => 0.200000000000,
			3 => 0.100000000000
		],

	];
	//public static $sysRate = 1;

	public $disableAddress  = [

	];
	/**
	 * 配置
	 */
	protected function configure() {
		$this->setName('Test')
			->addArgument('name', Argument::OPTIONAL, 'your name')
			->addOption('option', null, Option::VALUE_REQUIRED, 'option name')
			->setDescription('Order Test Script');
	}

	/**
	 * 订单利息计算
	 */
	public function execute(Input $input, Output $output) {
		if (!ps_qty_limit('think Test', 10)) {
			common_log('进程限制');
			return false;
		}

		$output->writeln('start interest record');
		$indexKey = 'one_interest_Test';
		$lastId = Cache::get($indexKey);
		$lastId = ((int)$lastId > 0) ? $lastId : 0;
		$now = strtotime(date('Y-m-d'));
		$t_1001 = mktime(23,59,59,9,29,2019);
		$interest = model('common/Config')->getConfig(ConfigModel::KEY_INTEREST);
		$disableUser = User::where('origin_address','in',$this->disableAddress)->column('user_id');
		$cycleList =
			RechargeCycle::where('recharge_amount', '>', 0)
			->where('cycle_id', '>', $lastId)
			->where('create_time', '>', $t_1001)
			->whereNotIn('user_id',$disableUser)
			->where('status', RechargeCycle::STATUS_PENDING)
			->order('cycle_id asc')
			->limit(100)
			->select();
		/**
		 *  $cycleList 未出局的周期充值订单
		 */
		$cycleList = $cycleList ? $cycleList->toArray() : [];
		if (!$cycleList) {
			common_log('订单为空');
			Cache::set($indexKey, 0);
			return true;
		} else {
			$lastData = end($cycleList);
			Cache::set($indexKey, $lastData['cycle_id']);

			foreach ($cycleList as $cycleInfo) {
				common_log($cycleInfo['cycle_id']);
				//判断利息是否已经转过
				$map = [
					//'user_id' => $cycleInfo['user_id'],
					'foreign_id' => $cycleInfo['cycle_id'],
					'type' => $cycleInfo['type'],
					'day' => date('Y-m-d'),
					//'to_user_id'=>$cycleInfo['user_id']
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
				//$profit = BCMath::mul($profit,self::$sysRate,12);
				/*$ACGG = BCMath::mul($profit,BCMath::sub(1,self::$sysRate),12);
				Db::startTrans();
				$token=[
					'user_id'=>$cycleInfo['user_id'],
					'type'=>3,
					'foreign_id'=>0,
					'amount'=>$ACGG,
					'create_time'=>time()
				];

				$ret = Db::name('token_detail')->insert($token);
				if(!$ret){
					Db::rollback();
				}
				$ret = Db::name('user_wallet')->where(['user_id'=>$cycleInfo['user_id']])->inc('token_amount',$ACGG)->update(['update_time'=>time()]);
				if(!$ret){
					Db::rollback();
				}
				Db::commit();*/
				$rewardList[$cycleInfo['user_id']] = [
					'cycle_id' => $cycleInfo['cycle_id'],
					'type' => $cycleInfo['type'],
					'profit' => $profit
				];
				$directCount = User::where(['parent_id' => $cycleInfo['user_id']])->count();
				common_log($cycleInfo['user_id'].'的直推人数'.$directCount);
				$childWhere = ['parent_id'=>$cycleInfo['user_id']];
				$parentWhere= ['user_id'=>$cycleInfo['user_id']];
				if($directCount>=4){
					//查找出用户下3代 上7代
					$childrenIds = UserBind::where($childWhere)->where('level', 'elt', 7)->where('user_id', '<>', 1)->order('level ASC')->select();
					$parentIds = UserBind::where($parentWhere)->where('level', 'elt', 3)->where('parent_id', '<>', 1)->order('level ASC')->select();
				}elseif($directCount>0&&$directCount<4){
					$childrenIds = UserBind::where($childWhere)->where('level', 'elt', $directCount)->where('user_id', '<>', 1)->order('level ASC')->select();
					$parentIds = UserBind::where($parentWhere)->where('level', 'elt', $directCount)->where('parent_id', '<>', 1)->order('level ASC')->select();
				}else{
					$childrenIds = [];
					$parentIds=[];
				}
				$childrenIds = $childrenIds ? $childrenIds->toArray() : [];
				$parentIds = $parentIds ? $parentIds->toArray() : [];
				$rewardIds = [];
				/**
				 *  $rewardIds  这笔订单上3 下7 带的基本信息
				 */
				if(!empty($childrenIds)){
					foreach ($childrenIds as $key => $value) {
						$rewardIds[] = [
							'type' => 'child',
							'level' => $value['level'],
							'user_id' => $value['user_id'],
						];
					}
				}
				if(!empty($parentIds)){
					foreach ($parentIds as $key => $value) {
						$rewardIds[] = [
							'type' => 'parent',
							'level' => $value['level'],
							'user_id' => $value['parent_id'],
						];
					}
				}

				//当前用户是否有另一种模式的订单
				/*$otherCycleInfo = RechargeCycle::where('user_id', $cycleInfo['user_id'])
					->where('status', '=',RechargeCycle::STATUS_PENDING)
					->where('type', '<>', $cycleInfo['type'])
					->find();*/
				/*if($cycleInfo['cycle_id']==$testCyid){
					file_put_contents('public/ids.json',json_encode($rewardIds));
				}*/
				foreach ($rewardIds as $rInfo){
					$rType = $rInfo['type'];
					$rLevel = $rInfo['level'];
					$rUserId = $rInfo['user_id'];
					$rCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING])->where('recharge_amount','>',0)->find();

					//$no_money =[];
					if(empty($rCycleInfo)){
						/*$rCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING])->where('recharge_amount','>',0)->where('type','<>',$cycleInfo['type'])->find();
						if(empty($rCycleInfo)){
							//Cache::set('no_money',array_push($no_money,$rUserId));
							common_log($rUserId . ' 无投资');
							continue ;
						}*/
						//Cache::set('no_money',array_push($no_money,$rUserId));
						common_log($rUserId . ' 无投资');
						continue ;
					}
					/*else{
						$orCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING])->where('recharge_amount','>',0)->where('type','<>',$cycleInfo['type'])->find();
					}*/
					//如果当前用户有另外一种投资模式 ???
					/*if(!empty($otherCycleInfo)){
						$rCount = RechargeCycle::where('user_id', $rUserId)->where('status', RechargeCycle::STATUS_PENDING)->count();
						if ($rCount == 1) {
							//充值用户有两种投资 获益用户只有一种投资
							if ($cycleInfo['recharge_amount'] < $otherCycleInfo->recharge_amount) {
								continue ;
							}
						}
					}*/
					/*if($cycleInfo['cycle_id']==$testCyid){
						$tempList[] = $rCycleInfo;
						file_put_contents('public/temp.json',json_encode($tempList));
					}*/
					$parentAmount = $rCycleInfo['recharge_amount'];
					$rCycleInfo = empty($rCycleInfo)?[]:$rCycleInfo->toArray();
					//$orCycleInfo = empty($orCycleInfo)?[]:$orCycleInfo->toArray();
					/*if(!empty($orCycleInfo)){
						$parentAmount = BCMath::add($rCycleInfo['recharge_amount'],$orCycleInfo['recharge_amount'],12);
					}*/

					/*
					 * 烧伤判断
					 */
					if($parentAmount <= $cycleInfo['recharge_amount']){
						$uProfit = BCMath::mul(BCMath::mul($parentAmount, $interest, 12), self::$interestRate[$rType][$rLevel], 12);
						//$uProfit = BCMath::mul(BCMath::mul($parentAmount, BCMath::mul($interest,self::$sysRate,12), 12), self::$interestRate[$rType][$rLevel], 12);
					}else{
						$uProfit = BCMath::mul($profit, self::$interestRate[$rType][$rLevel], 12);
					}
					//$uProfit = BCMath::mul(BCMath::mul($parentAmount, $interest, 12), self::$interestRate[$rType][$rLevel], 12);
					if($uProfit<=0){
						continue;
					}
					$rewardList[$rUserId] = [
						'cycle_id' => $rCycleInfo['cycle_id'],
						'type' => $rCycleInfo['type'],
						'profit' => $uProfit,
						'parentAmount'=>$parentAmount,
						'cycleChargeAmount'=>$cycleInfo['recharge_amount']
					];
				}
				common_log(json_encode($rewardList));
				common_log('用户直推数据'.json_encode($rewardIds));
				/*if($cycleInfo['cycle_id']==$testCyid){
					file_put_contents('public/list.json',json_encode($rewardList));

				}*/
				$totalInviteProfit=0;
				$totalReward = 0;
				/**
				 * 用户没有直推人的情况,直接拿自身静态收益率
				 */

				if(empty($rewardIds)){
					Db::startTrans();
					common_log('当前用户没有直推');
					try{
						$incomeData = [
							'user_id'     => $cycleInfo['user_id'],
							'foreign_id'  => $cycleInfo['cycle_id'],
							'type'        => $cycleInfo['type'],
							'amount'      => $profit,
							'day'         => date('Y-m-d'),
							'create_time' => time(),
							'to_user_id'  =>$cycleInfo['user_id']
						];
						$incomeId = Db::name('income')->insertGetId($incomeData);
						common_log('新增收益账单ID'.$incomeId);
						if(!$incomeId){
							Db::rollback();
							continue;
						}
						$result = RechargeCycle::where('cycle_id', $cycleInfo['cycle_id'])->inc('reward_amount', $profit)->update(['update_time' => time()]);
						if(!$result){
							Db::rollback();
							continue;
						}

						if ($cycleInfo['type'] == RechargeCycle::TYPE_DESERT) {
							$result = UserWallet::where('user_id', $cycleInfo['user_id'])->inc('desert_profit', $profit)->update(['update_time' => time()]);
						} else {
							$result = UserWallet::where('user_id', $cycleInfo['user_id'])->inc('oasis_profit', $profit)->update(['update_time' => time()]);
						}
						if(!$result){
							//common_log($result);
							Db::rollback();
							continue;
						}

						$address = $this->getUserAddress($cycleInfo['user_id']);

						/*if($cycleInfo['cycle_id']==$testCyid){
							file_put_contents('public/address.json',json_encode([$address,$profit]));
						}*/
						//发送利息
						$result = WalletLib::transfer($address, $profit,1);

						if(!$result){
							Db::rollback();
							continue;
						}

						Db::commit();
					}catch (Exception $e){
						common_log($e);
						continue;
					}

				}

				Db::startTrans();
				common_log('用户有直推');
				foreach ($rewardList as $uId=>$item){
					try {
						$incomeData = [
							'user_id'     => $uId,
							'foreign_id'  => $item['cycle_id'],
							'type'        => Income::TYPE_INVITE,
							'amount'      => $item['profit'],
							'day'         => date('Y-m-d'),
							'create_time' => time(),
							'to_user_id'  =>$cycleInfo['user_id']
						];
						//用户自身
						if ($uId == $cycleInfo['user_id']) {
							$incomeData['type'] = $item['type'];
							$totalReward+=$item['profit'];
							if ($item['type'] == RechargeCycle::TYPE_DESERT) {
								$result = Db::name('user_wallet')->where('user_id', $uId)->inc('desert_profit', $item['profit'])->update(['update_time' => time()]);
							} else {
								$result = Db::name('user_wallet')->where('user_id', $uId)->inc('oasis_profit', $item['profit'])->update(['update_time' => time()]);
							}
							if (!$result) {
								common_log('222');
								Db::rollback();
								continue 2;
							}
						} else {
							$totalInviteProfit += $item['profit'];
							$incomeData['foreign_id'] = $item['cycle_id'];
						}

						/*if($cycleInfo['cycle_id']==$testCyid){
							$temp[]=$incomeData;
							file_put_contents('public/income.json',json_encode($temp));
						}*/
						$incomeId = Db::name('income')->insertGetId($incomeData);
						if (!$incomeId) {
							common_log(11);
							Db::rollback();
							continue 2;
						}
					} catch (Exception $e) {
						common_log(333);
						common_log($e);
						Db::rollback();
						continue 2;
					}
				}

				// $rewardAmount 奖励金额 出局判断  已出局金额 + 当前出局金额$totalReward(动态加静态金额)
				$orderOut = false;
				$rewardAmount =BCMath::add($cycleInfo['reward_amount'], $totalReward, 12) ;
				if ($cycleInfo['type'] == RechargeCycle::TYPE_DESERT) {
					if ($rewardAmount >= BCMath::mul($cycleInfo['recharge_amount'], 4, 12)) {
						$orderOut = true;
					}
				} else {
					if ($rewardAmount >= BCMath::mul($cycleInfo['recharge_amount'], 6, 12)) {
						$orderOut = true;
					}
				}
				if($orderOut){
					$result =Db::name('recharge_cycle')->where(['cycle_id'=>$cycleInfo['cycle_id']])->update(['status'=>RechargeCycle::STATUS_COMPLETE]);//出局状态修改
					if (!$result) {
						Db::rollback();
						continue ;
					}
				}
				$result = Db::name('user_wallet')->where('user_id', $cycleInfo['user_id'])->inc('invite_profit', $totalInviteProfit)->update(['update_time' => time()]);
				if (!$result) {
					Db::rollback();
					continue ;
				}
				$cycleData = ['update_time' => time()];
				$totalReward += $totalInviteProfit;
				$result = Db::name('recharge_cycle')->where('cycle_id', $cycleInfo['cycle_id'])->inc('reward_amount', $totalReward)->update($cycleData);
				if (!$result) {
					common_log('fail');
				}

				//用户转币地址查询
				$address = $this->getUserAddress($cycleInfo['user_id']);
				/*if($cycleInfo['cycle_id']==$testCyid){
					file_put_contents('public/address.json',json_encode([$address,$totalReward,$cycleInfo['user_id']]));
				}*/
				//发送利息
				$result = WalletLib::transfer($address, $totalReward,1);
				if (!$result) {
					Db::rollback();
					continue;
				}

				Db::commit();
				common_log('success');
			}
		}
		common_log('end!!!');
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
		$user_info = $user->where('user_id=' . intval($user_id))->field('origin_address')->find();
		if (!$user_info) {
			common_log('465465');
			return false;
		}

		$user_info = $user_info->toArray();
		return $user_info['origin_address'];
	}

}
