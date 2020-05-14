<?php
/**
 * 订单hash验证
 */
namespace app\script\command;

use app\common\model\Income;
use app\common\model\RechargeOrder;
use app\common\model\Config as ConfigModel;
use app\common\model\SystemStatistic;
use app\common\model\UpgradeLog;
use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use math\BCMath;
use think\facade\Config;
use app\common\library\Wallet as WalletLib;

use think\Db;
use Exception;
use think\facade\Cache;

class OrderFake extends Command
{
	/**
	 * 配置
	 */
	protected function configure()
	{
		$this->setName('order')
			->addArgument('name', Argument::OPTIONAL, 'your name')
			->addOption('option', null, Option::VALUE_REQUIRED, 'option name')
			->setDescription('Game Script');
	}

	/**
	 * 执行命令
	 * php think order recharge 充值订单
	 * php think order egg      彩蛋订单
	 * php think order token    token订单
	 */
	protected function execute(Input $input, Output $output)
	{
		$name = trim($input->getArgument('name'));
		if (!ps_qty_limit('think orderfake ' . $name, 5)) {
			$output->writeln('process limit!');
			return false;
		}
		$output->writeln('start process order!');
		$indexKey = 'one_validate_hash_' . $name;
		$lastId = Cache::get($indexKey);
		$lastId = ((int)$lastId > 0) ? $lastId : 0;
		$output->writeln('last id ' . $lastId);
		//$t_928 = mktime(23,59,59,9,28,2019);
		// 获取hash未验证的真实订单
		$list = RechargeOrder::
			 where('is_validate', RechargeOrder::VALIDATE_COMPLETE)
			->where('is_true', 2)
			->where('fake_validate', 1)
			->where('order_id', '>', $lastId)
			// ->where('create_time','>',$t_928)
			->order('order_id asc')
			->limit(100)->select()
			->toArray();
		if (empty($list)) {
			Cache::set($indexKey, 0);
			$output->writeln('no undone validate order!');
			return true;
		} else {
			$lastData = end($list);
			Cache::set($indexKey, $lastData['order_id']);
		}
		foreach ($list as $order) {
			sleep(5);
				// 未超时,验证订单
				$order['amount'] = $order['amount'] ?? $order['price'];
				$result =  $this->validFakeRechargeOrder($order);
					if ($result) {
						$output->writeln('Fakeorder ' . $order['order_id'] .' hash validate success!');
					} else {
						$output->writeln('Fakorder ' . $order['order_id'] .' hash validate faliure, continue!');
					}
			}
		$output->writeln('end process order!');
	}
	public function validFakeRechargeOrder($order) {
		$time = time();
		$userId = $order['user_id'];
		$orderId = $order['order_id'];
		$cycleId = $order['cycle_id'];
		$type = $order['type'];
		$amount = $order['amount'];
		Db::startTrans();
		try {
			$result = Db::name('recharge_cycle')->where('cycle_id', $cycleId)->inc('recharge_amount', $amount)->update(['update_time' => $time,'status'=>2]);
			if (!$result) {
				Db::rollback();
				return false;
			}
			$result = Db::name('recharge_order')->where('cycle_id', $cycleId)->update(['update_time' => $time,'fake_validate'=>2]);
			if (!$result) {
				Db::rollback();
				return false;
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
			//系统总业绩
			$result = model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_FAKE_TOTAL_PERFORMANCE, $amount);
			if (!$result) {
				RechargeOrder::rollback();
				return false;
			}
			//系统奖池
			$result = model('common/SystemStatistic')->StatisticInc(SystemStatistic::KEY_FAKE_TOTAL_STATIC, BCMath::mul($amount, 0.75, 12));
			if (!$result) {

				Db::rollback();
				return false;
			}
		} catch (\think\Exception $e) {
			common_log($e);
			Db::rollback();
			return false;
		}

		Db::commit();
		return true;
	}
}
