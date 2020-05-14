<?php
/**
 * 股东收益
 */
namespace app\script\command;

use app\common\model\Income;
use app\common\model\RechargeOrder;
use app\common\model\Config as ConfigModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use math\BCMath;
use think\facade\Config;
use think\facade\Cache;
use app\common\library\Wallet as WalletLib;

class TeamReward extends Command
{
    /**
     * 配置
     */
    protected function configure()
    {
        $this->setName('team')
            ->addArgument('name', Argument::OPTIONAL, 'your name')
            ->addOption('option', null, Option::VALUE_REQUIRED, 'option name')
            ->setDescription('Game Script');
    }

    /**
     * 执行命令
     * php think TeamReward
     */
    protected function execute(Input $input, Output $output)
    {
        if (!ps_qty_limit('think team', 10)) {
            common_log('进程限制');
            return false;
        }


        $output->writeln('start TeamReward');

        //昨日业绩
        $beginTime = strtotime(date('Y-m-d', strtotime('-1 day')));
        $endTime = $beginTime + (3600 * 24);
        $performance = RechargeOrder::where(['is_validate'=>RechargeOrder::VALIDATE_COMPLETE,'is_true'=>1])->whereBetweenTime('create_time', $beginTime, $endTime)->sum('amount');
        //$performance = model('common/Config')->getConfig(ConfigModel::KEY_TODAY_PERFORMANCE);
        if (empty($performance)) {
            common_log('昨日无业绩', true);
        }
        common_log('昨日业绩 ' . $performance);
        $map = [
            'type'       => Income::TYPE_TEAM,
            'day'        => date('Y-m-d'),
        ];
        $incomeCount = Income::where($map)->count();
        if ($incomeCount) {
            common_log('今日已发利息', true);
        }

	    $time=mktime(23,59,59,9,28,2019);
        $pCount = User::where('grade', User::GRADE_PRIMARY)->where('create_time','>',$time)->count();
        $mCount = User::where('grade', User::GRADE_MIDDLE)->where('create_time','>',$time)->count();
        $hCount = User::where('grade', User::GRADE_HIGH)->where('create_time','>',$time)->count();
        $sCount = User::where('grade', User::GRADE_SUPER)->where('create_time','>',$time)->count();
        $oCount = User::where('grade', User::GRADE_ONE)->where('create_time','>',$time)->count();
        $gradeCount = [
            1 => $pCount + $mCount + $hCount + $sCount + $oCount,
            2 => $mCount + $hCount + $sCount + $oCount,
            3 => $hCount + $sCount + $oCount,
            4 => $sCount + $oCount,
            5 => $oCount
        ];
	    $rate = [
		    1 => 0.02,
		    2 => 0.03,
		    3 => 0.04,
		    4 => 0.05,
		    5 => 0.06,
	    ];
        $reward = [];
	    /**
	     * @reward 每个用户等级下,没有用户的平均分润(低等级包含高等级)
	     */
        foreach ($rate as $grade => $rateValue) {
            if ($gradeCount[$grade] == 0) {
                continue;
            }
            $reward[$grade] = BCMath::div(BCMath::mul($performance, $rateValue, 12), $gradeCount[$grade], 12);
            common_log('人数'.$gradeCount[$grade].'-等级:'.$grade . '-金额:' . $reward[$grade]);
        }
		$time=mktime(23,59,59,9,28,2019);
        $userList = User::where('grade', '>', 0)
            ->where('user_id','<>', 1)
            ->where('status', User::STATUS_ABLE)
	        ->where('create_time','>',$time)
            ->field('user_id,grade')
            ->order('user_id ASC')
            ->select();

        $userList = $userList ? $userList->toArray() : [];
        if(!$userList){
	        common_log('没有股东');
            return true;
        }

        $userReward = [];
	    /**
	     * @userReward 每个用户全球业绩的分利
	     */
        foreach ($userList as $userInfo) {
            $rewardAmount = 0;
            for ($i = 1;$i <= $userInfo['grade'];$i++) {
                if ($reward[$i] > 0) {
                    $rewardAmount += $reward[$i];
                }
            }
            common_log("{$userInfo['user_id']} 收益 {$rewardAmount}");
            $userReward[$userInfo['user_id']] = $rewardAmount;
        }
	    /**
	     *  开始插入 Income 分润表
	     */
        foreach ($userList as $userInfo) {
            if (empty($userReward[$userInfo['user_id']])) {
                continue;
            }
            $map = [
                'user_id'    => $userInfo['user_id'],
                'type'       => Income::TYPE_TEAM,
                'day'        => date('Y-m-d'),
            ];
            $incomeCount = Income::where($map)->count();
            if ($incomeCount) {
                $output->writeln('今日已发利息');
                continue;
            }
            Income::startTrans();
            try {
                $result = model('common/Income')->incomeChange($userInfo['user_id'], $userReward[$userInfo['user_id']], Income::TYPE_TEAM, 0);
                if (!$result) {
                    Income::rollback();
                    continue;
                }
                //用户转币地址查询
                $address = model('common/User')->getUserAddress($userInfo['user_id']);
                //发送利息
                $result = WalletLib::transfer($address, $userReward[$userInfo['user_id']],1);
                if (!$result) {
                    Income::rollback();
                    continue;
                }

                Income::commit();
            } catch (\Exception $e) {
                Income::rollback();
                continue;
            }
        }

        //修改昨日业绩
        model('common/Config')->where('key', ConfigModel::KEY_BASE_Y_PERFORMANCE)->update(['value' => $performance]);
        model('common/Config')->where('key', ConfigModel::KEY_BASE_PERFORMANCE)->setInc('value', $performance);
        common_log('结束', true);
    }
}
