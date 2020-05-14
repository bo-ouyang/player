<?php


namespace app\script\command;


use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserInvest;
use app\common\model\UserWallet;
use math\BCMath;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\facade\Cache;

/**
 * Class UpUserInvest 更新用户业绩
 * @package app\script\command
 */

class UpUserInvest extends Command{
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
		if (!ps_qty_limit('think upinvest', 10)) {
			common_log('进程限制');
			return false;
		}

		$output->write('start update user invest');
		$indexKey = 'upUserInvest';
		$lastId = Cache::get($indexKey);
		$lastId = ((int)$lastId > 0) ? $lastId : 0;
		$time = mktime(23,59,59,9,28,2019);
		$userIds = User::where('create_time','>',$time)->where(['is_fake'=>0])->where('user_id','>',$lastId)->column('user_id');
		$lastData = end($userIds);
		Cache::set($indexKey, $lastData['cycle_id']);
		foreach ($userIds as $pid){
			common_log($pid);
			$childIds = UserBind::where(['parent_id'=>$pid])->column('user_id');
			if(empty($childIds)){

			}
			$childIds[] = $pid;
			$childInvest = UserWallet::where('user_id','in',$childIds)->field('sum(desert_amount) + sum(oasis_amount) as total_invest')->select()->toArray();
			$userInfo = UserInvest::where(['user_id'=>$pid])->value('id');
			if(!empty($userInfo)){
				$result = UserInvest::where(['user_id'=>$pid])->update(['user_invest'=>$childInvest[0]['total_invest']]);
				if(!$result){
					common_log($pid.'无新增业绩');
				}
			}else{
				$result = UserInvest::create(['user_id'=>$pid,'user_invest'=>$childInvest[0]['total_invest']]);
			}
		}

	}
}
