<?php
/**
 * @author one
 * @version 1.0
 */
namespace app\admin\service;

use app\common\model\CashLog;
use app\common\model\Config as ConfigModel;
use app\common\model\EggOrder;
use app\common\model\EggReward;
use app\common\model\Income;
use app\common\model\RechargeOrder;
use app\common\model\SystemStatistic;
use app\common\model\TokenOrder;
use app\common\model\User;
use app\common\model\UserBind;
use math\BCMath;
use think\db\Where;
use think\Exception;
use think\Facade\Config;
use think\Db;



class Home
{
    /**
     * 首页
     * @return array
     */

    public function index($inviteCode)
    {
        //$today = strtotime(date('Y-m-d'));
        //$yesterday = strtotime(date('Y-m-d', strtotime('-1 day')));
        //$tomorrow = strtotime(date('Y-m-d', strtotime('+1 day')));

		$time = mktime(29,59,59,9,29,2019);
		$validate = ['is_validate'=>RechargeOrder::VALIDATE_COMPLETE];
            $userNumber        = User::where('create_time','>',$time)->count();
            $performance       = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_PERFORMANCE);
	        $performanceFake       = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_FAKE_TOTAL_PERFORMANCE);
            $totalEgg          = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_EGG_AMOUNT);
            $totalToken        = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_TOKEN_AMOUNT);

            $totalCash         = CashLog::sum('amount');
            $totalIncome       = Income::sum('amount');
            $totalEggReward    = EggReward::sum('amount');
            $systemRecharge    = RechargeOrder::where('create_time','>',$time)->where($validate)->where('system_recharge', RechargeOrder::SYSTEM_RECHARGE_YES)->sum('amount');

            $todayRecharge     = RechargeOrder::whereTime('create_time','today')->where($validate)->sum('amount');
            $todayEgg          = EggOrder::whereTime('create_time','today')->where($validate)->sum('amount');
            $todayToken        = TokenOrder::whereTime('create_time','today')->where($validate)->sum('price');

            $yesterdayRecharge = RechargeOrder::whereTime('create_time','yesterday')->where($validate)->sum('amount');
            $yesterdayEgg      = EggOrder::whereTime('create_time','yesterday')->where($validate)->sum('amount');
            $yesterdayToken    = TokenOrder::whereTime('create_time','yesterday')->where($validate)->sum('price');

            $todayCash         = CashLog::whereTime('create_time','today')->sum('amount');
            $todayIncome       = Income::whereTime('create_time','today')->sum('amount');
            $todayEggReward    = EggReward::whereTime('create_time','today')->sum('amount');

            $yesterdayCash     = CashLog::whereTime('create_time','yesterday')->sum('amount');
            $yesterdayIncome   = Income::whereTime('create_time','yesterday')->sum('amount');
            $yesterdayEggReward = EggReward::whereTime('create_time','yesterday')->sum('amount');

        $totalIn      = BCMath::add(BCMath::add(BCMath::add($performance,$performanceFake),$totalEgg,12), $totalToken, 12);
        $totalOut     = BCMath::add(BCMath::add($totalCash, $totalIncome, 12), $totalEggReward, 12);
	    //$totalOut     = $totalIncome;
        $todayIn      = BCMath::add(BCMath::add($todayRecharge,$todayToken,12),$todayEgg,12);
        $yesterdayIn  = BCMath::add(BCMath::add($yesterdayRecharge,$yesterdayEgg,12), $yesterdayToken, 12);
        $todayOut     = BCMath::add(BCMath::add($todayCash, $todayIncome, 12), $todayEggReward, 12);
        $yesterdayOut = BCMath::add(BCMath::add($yesterdayCash,$yesterdayEggReward,12), $yesterdayIncome, 12);
        return [
            'user_number'   => $userNumber,
            'total_in'      => $totalIn,
            'total_out'     => $totalOut,
            'today_in'      => $todayIn,
            'today_out'     => $todayOut,
            'yesterday_in'  => $yesterdayIn,
            'yesterday_out' => $yesterdayOut,
            'system_recharge' => $systemRecharge
        ];
    }

    /**
     * 参数设置
     * @return mixed
     */
    public function config()
    {
        $list = ConfigModel::select()->toArray();

        $totalEggAmount = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_EGG_AMOUNT);

	    $jackpot = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_STATIC);
	    $jackpotFake = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_FAKE_TOTAL_STATIC);
	    $performance = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_PERFORMANCE);
	    $performanceFake = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_FAKE_TOTAL_PERFORMANCE);
        $beginTime = strtotime(date('Y-m-d', strtotime('-1 day')));
        $endTime = $beginTime + (3600 * 24);
        $yPerformance = RechargeOrder::where('is_validate', RechargeOrder::VALIDATE_COMPLETE)->where('is_true','=',1)->whereBetweenTime('create_time', $beginTime, $endTime)->sum('amount');
        $todayPerformance = RechargeOrder::where('is_validate', RechargeOrder::VALIDATE_COMPLETE)->where(['is_true'=>1])->where('create_time', '>', $endTime)->sum('amount');
        $o_1001 = mktime(0,0,0,10,1,2019);
        $superUser = model('common/User')->where('is_super', User::SUPER_YES)->where('create_time','>',$o_1001)->count();
        $tokenNumber = TokenOrder::where('create_time', '>', strtotime(date('Y-m-d')))->where('is_validate', TokenOrder::VALIDATE_COMPLETE)->sum('number');
	    //$list['base_jackpot'] = $jackpotFake+$jackpot;
	   // $list['base_performance'] = $performanceFake+$performance;

	    foreach ($list as &$value){
	    	if($value['key']=='base_jackpot'){
			    $value['value'] = $jackpotFake+$jackpot;
		    }
	    	if($value['key']=='base_performance'){
			    $value['value'] = $performanceFake+$performance;
		    }
	    }

	    return [
            'list'                  => $list,
            'performance'           => $performance,
            'yesterday_performance' => $yPerformance,
            'super_node_number'     => $superUser,
            'total_jackpot'         => $jackpot,
            'total_egg_amount'      => $totalEggAmount,
            'token_number'          => $tokenNumber,
            'today_performance'     => $todayPerformance,
        ];
    }

    /**
     * 配置修改
     * @param $key
     * @param $value
     * @return int|string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function configEdit($key, $value)
    {
        return ConfigModel::where('key', $key)->update(['value' => $value, 'update_time' => time()]);
    }
}
