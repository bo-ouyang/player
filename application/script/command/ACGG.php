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


class ACGG extends Command {
	public static $interestRate = [

		'child' => [
			1 => 1.000000000000,
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
	public static $ETHRate = 0.5;
	public static $ACGGRate= 0.5;
	public static $ACGGScale=1;

	public $disableAddress  = [

	];
	/**
	 * 配置
	 */
	protected function configure() {
		$this->setName('ACGG')
			->addArgument('name', Argument::OPTIONAL, 'your name')
			->addOption('option', null, Option::VALUE_REQUIRED, 'option name')
			->setDescription('Order Test Script');
	}

	/**
	 * 订单利息计算
	 */
	public function execute(Input $input, Output $output) {
		if (!ps_qty_limit('think ACGG', 10)) {
			common_log('进程限制');
			return false;
		}
		//$income = Income::all();
		//dump($income);
		//die();
		$output->writeln('start interest record');
		$indexKey = 'one_interest_Test';
		$lastId = Cache::get($indexKey);
		$lastId = ((int)$lastId > 0) ? $lastId : 0;
		$now = strtotime(date('Y-m-d'));
		$testCyid=60246;
		$t_1001 = mktime(23,59,59,9,29,2019);
		$interest = model('common/Config')->getConfig(ConfigModel::KEY_INTEREST);
		$disableUser = User::where('origin_address','in',$this->disableAddress)->column('user_id');
		$cycleList =
			RechargeCycle::where('recharge_amount', '>', 0)
				//->where('cycle_id', '>', $lastId)
				->where('cycle_id', '=', $testCyid)
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
				common_log('静态总利益:'.$profit);

				$ACGG= BCMath::mul($profit, self::$ACGGRate, 12);
				$ETH = BCMath::mul($profit, self::$ETHRate, 12);

				//比例换算
				$ACGG= BCMath::mul($ACGG, self::$ACGGScale, 12);
				$rewardList[$cycleInfo['user_id']] = [
					'cycle_id' => $cycleInfo['cycle_id'],
					'type' => $cycleInfo['type'],
					'profit' => $ETH,
					'acgg_profit'=>$ACGG
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
				if(!empty($rewardIds)){
					foreach ($rewardIds as $rInfo){
						$rType = $rInfo['type'];
						$rLevel = $rInfo['level'];
						$rUserId = $rInfo['user_id'];
						$rCycleInfo = RechargeCycle::where(['user_id'=>$rUserId,'status'=>RechargeCycle::STATUS_PENDING])->where('recharge_amount','>',0)->find();
						if(empty($rCycleInfo)){
							common_log($rUserId . ' 无投资');
							continue ;
						}
						$parentAmount = $rCycleInfo['recharge_amount'];
						$rCycleInfo = empty($rCycleInfo)?[]:$rCycleInfo->toArray();

						/*
						 * 烧伤判断
						 */

						if($parentAmount <= $cycleInfo['recharge_amount']){//无烧伤
							$parentStaticProfit = BCMath::mul($parentAmount, $interest,12);;
							$ETHProfit = BCMath::mul(BCMath::mul($parentStaticProfit,self::$ETHRate,12), self::$interestRate[$rType][$rLevel], 12);
							$ACGGProfit = BCMath::mul(BCMath::mul($parentStaticProfit,self::$ACGGRate,12),self::$interestRate[$rType][$rLevel], 12);
						}else{
							//有烧商  拿自身静态收益的百分比
							$ETHProfit = BCMath::mul(BCMath::mul($profit,self::$ETHRate,12), self::$interestRate[$rType][$rLevel], 12);
							$ACGGProfit = BCMath::mul(BCMath::mul($profit,self::$ACGGRate,12), self::$interestRate[$rType][$rLevel], 12);
						}

						if($ETHProfit<=0||$ACGGProfit<=0){
							continue;
						}
						//比例换算
						$ACGGProfit=BCMath::mul($ACGGProfit,self::$ACGGScale,12);


						$rewardList[$rUserId] = [
							'cycle_id' => $rCycleInfo['cycle_id'],
							'type' => $rCycleInfo['type'],
							'profit' => $ETHProfit,
							'parentAmount'=>$parentAmount,
							'cycleChargeAmount'=>$cycleInfo['recharge_amount'],
							'acgg_profit'=>$ACGGProfit,
							'rlev'=>$rLevel,
							'rtype'=>$rType
						];
					}
				}

				common_log(json_encode($rewardList));
				common_log('用户直推数据'.json_encode($rewardIds));
				if($cycleInfo['cycle_id']==$testCyid){
					file_put_contents('public/list.json',json_encode($rewardList));

				}
				$selfStaticProfit=0;
				$totalInviteETHProfit = 0;
				$totalInviteACGGProfit=0;
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
							'amount'      => $ETH,
							'day'         => date('Y-m-d'),
							'create_time' => time(),
							'to_user_id'  => $cycleInfo['user_id'],
							'acgg_amount' => $ACGG
						];
						$incomeId = Db::name('income')->insertGetId($incomeData);
						common_log('新增收益账单ID'.$incomeId);
						if(!$incomeId){
							Db::rollback();
							continue;
						}


						//出局金额
						$result = RechargeCycle::where('cycle_id', $cycleInfo['cycle_id'])->inc('reward_amount', $profit)->update(['update_time' => time()]);
						if(!$result){
							Db::rollback();
							continue;
						}
						//acgg 分利
						$result = UserWallet::where('user_id', $cycleInfo['user_id'])->inc('token_amount', $ACGG)->update(['update_time' => time()]);
						if(!$result){
							Db::rollback();
							continue;
						}
						if ($cycleInfo['type'] == RechargeCycle::TYPE_DESERT) {
							$result = UserWallet::where('user_id', $cycleInfo['user_id'])->inc('desert_profit', $ETH)->update(['update_time' => time()]);
						} else {
							$result = UserWallet::where('user_id', $cycleInfo['user_id'])->inc('oasis_profit', $ETH)->update(['update_time' => time()]);
						}
						if(!$result){
							//common_log($result);
							Db::rollback();
							continue;
						}

						$address = $this->getUserAddress($cycleInfo['user_id']);
						if($cycleInfo['cycle_id']==$testCyid){
							file_put_contents('public/address.json',json_encode([$address,$profit]));
						}
						//发送利息
						$result = WalletLib::transfer($address, $ETH,1);
						if(!$result){
							Db::rollback();
							continue;
						}
						//发送利息
						$result = WalletLib::transferAcgg($address, $ACGG,3);
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
							'to_user_id'  =>$cycleInfo['user_id'],
							'acgg_amount' =>$item['acgg_profit']
						];
						//用户自身
						if ($uId == $cycleInfo['user_id']) {//用户自身

							common_log('用户自身ETH收益:'.$item['profit']);
							common_log('用户自身ACGG收益:'.$item['acgg_profit']);
							$incomeData['type'] = $item['type'];
							//acgg 分利
							$result = UserWallet::where('user_id', $cycleInfo['user_id'])->inc('token_amount', $item['acgg_profit'])->update(['update_time' => time()]);
							if(!$result){
								Db::rollback();
								continue;
							}
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
							$totalInviteETHProfit +=$item['profit'];
							$totalInviteACGGProfit += $item['acgg_profit'];
							$incomeData['foreign_id'] = $item['cycle_id'];
						}
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
				$totalReward = BCMath::add(BCMath::add($totalInviteETHProfit,BCMath::div($totalInviteACGGProfit,self::$ACGGScale,12),12),$profit,12);

				$rewardAmount =BCMath::add($cycleInfo['reward_amount'], BCMath::add($totalInviteETHProfit,$totalInviteACGGProfit,12), 12) ;
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

				$result = Db::name('user_wallet')->where('user_id', $cycleInfo['user_id'])->inc('invite_profit', $totalInviteETHProfit)->update(['update_time' => time()]);
				if (!$result) {
					Db::rollback();
					continue ;
				}
				$result = Db::name('user_wallet')->where('user_id', $cycleInfo['user_id'])->inc('token_amount', $totalInviteACGGProfit)->update(['update_time' => time()]);
				if (!$result) {
					Db::rollback();
					continue ;
				}
				$cycleData = ['update_time' => time()];
				$result = Db::name('recharge_cycle')->where('cycle_id', $cycleInfo['cycle_id'])->inc('reward_amount', $totalReward)->update($cycleData);
				if (!$result) {
					common_log('fail');
				}

				//用户转币地址查询
				$address = $this->getUserAddress($cycleInfo['user_id']);
				//发送利息
				$result = WalletLib::transfer($address,BCMath::add($totalInviteETHProfit,$ETH,12) ,1);

				if (!$result) {
					Db::rollback();
					continue;
				}
				//发送acgg
				$result = WalletLib::transferAcgg($address, BCMath::add($totalInviteACGGProfit,$ACGG,12),3);
				if($cycleInfo['cycle_id']==$testCyid){
					file_put_contents('public/address.json',json_encode([$address,$totalReward,$totalInviteETHProfit,$totalInviteACGGProfit]));
				}
				if(!$result){
					Db::rollback();
					continue;
				}
				Db::commit();
				common_log('success');
			}
		}
		common_log('end!!!');
	}


	public function transNoRecommed(){

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
