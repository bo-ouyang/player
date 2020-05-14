<?php
/**
 * 用户升级
 */
namespace app\script\command;

use PHPMailer\PHPMailer\Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use app\common\model\RewardIncome;
use app\common\model\RegularOrder;
use math\BCMath;
use think\facade\Config;
use app\common\library\Wallet as WalletLib;

class Upgrade extends Command
{
    use \app\common\traits\MqOperate;
    /**
     * 配置
     */
    protected function configure()
    {
        $this->setName('upgrade')
            ->addArgument('name', Argument::OPTIONAL, 'your name')
            ->addOption('option', null, Option::VALUE_REQUIRED, 'option name')
            ->setDescription('Game Script');
    }

    /**
     * 执行命令
     * php think upgrade
     */
    protected function execute(Input $input, Output $output)
    {
        if (!ps_qty_limit('think upgrade', 1)) {
            common_log('进程限制');
            return false;
        }

        $this->_setCallback('userUpgrade');
        $this->_receive(config('mq.user_upgrade'));
        //$this->userUpgrade(['user_id' => 80,'order_id' => "50",'amount' => '1.000000000000', 'is_true' => 1]);
    }

    /**
     * 用户升级
     */
    public function userUpgrade($data)
    {
        common_log(json_encode($data));
        $userId  = $data['user_id'];
        $orderId = $data['order_id'];
        $amount  = $data['amount'];
        $isTrue  = $data['is_true'] ?? 1;
        //$type    = $data['type'] ?? 2; //1活期订单不计算团队业绩
        $orderExist = RegularOrder::where('order_id', $orderId)->find();
        if ($orderExist) {
            if ($orderExist->is_calc == RegularOrder::CALC_COMPLETE) {
                // 已经计算团队奖励
                common_log('order already calculate team income');
                return true;
            }

            if ($orderExist->is_validate != RegularOrder::VALIDATE_COMPLETE) {
                $mqConfig = config('mq.user_upgrade');
                $this->publish($mqConfig, $data);

                common_log('order hash no validate');
                return true;
            }

            $userIds = UserBind::where('user_id', $userId)->order('level ASC')->column('parent_id');
            $userIds = $userIds ?? [];
            array_unshift($userIds, $userId);
            common_log(json_encode($userIds));
            $grade = Config::get('reward.grade');
            $upgradeConfig = Config::get('reward.upgrade_amount');
            //循环判断每个用户是否升级
            $upgradeList = [];
            $reward = [];
            $rechargeUser = '';
            $userData = [];
            $directParent = ''; //直接上级
            $maxGrade = 0;
            $maxGradeKey = 0;
            $maxCompare = [];
            foreach ($userIds as $key => $userId) {
                common_log($userId);
                $childrenData = UserBind::where('parent_id', $userId)->field('user_id,level')->order('level DESC')->select();
                $childrenData = $childrenData ? $childrenData->toArray() : [];
                $childrenIds = array_column($childrenData, 'user_id');
                array_unshift($childrenIds, $userId);
                $userInfo = User::where('user_id', $userId)->find();
                $userData[$userId] = $userInfo;
                if ($key == 0) {
                    $rechargeUser = $userInfo;
                }
                //获取直接下级
                $childrenList = array_column($childrenData, null, 'user_id');
                $directChildren = $this->getDirectChildren($childrenList);
                $directChildren = $directChildren ?? [];
                //计算升级
                if ($userInfo->group == 0) {
                    $totalAmount = UserWallet::where('user_id', 'in', $childrenIds)->sum('regular_amount');
                    if ($totalAmount >= $upgradeConfig['amount']) {
                        if (empty($directChildren)) {
                            //没有下级则直接升级
                            $upgradeList[$userId] = $grade['adviser'];
                        } else {
                            $directAmount = [];
                            foreach ($directChildren as $childId) {
                                $grandson = UserBind::where('parent_id', $childId)->column('user_id');
                                array_unshift($grandson, $childId);
                                $directAmount[] = UserWallet::where('user_id', 'in', $grandson)->sum('regular_amount');
                            }
                            if (count($directAmount) == 1) {
                                //只有一个分支则不去除业绩最大的分支的业绩
                                $upgradeList[$userId] = $grade['adviser'];
                            } else {
                                asort($directAmount);
                                $maxAmount = end($directAmount);
                                if ($totalAmount - $maxAmount >= $upgradeConfig['amount']) {
                                    $upgradeList[$userId] = $grade['adviser'];
                                }
                            }
                        }
                    }
                }

                //最高5级 自身充值不会触发顾问以上的等级升级
                if ($userInfo->group < $grade['director'] && $key > 0) {
                    $directData = User::where('user_id', 'in', $directChildren)->select();
                    $directData = $directData ? $directData->toArray() : [];
                    $gradeCount = 0;
                    $minDirectGroupArr = [];
                    foreach ($directData as $directItem) {
                        if (isset($upgradeList[$directItem['user_id']]) && $upgradeList[$directItem['user_id']] >= $userInfo->group && $directItem['group'] != 0) {
                            $minDirectGroupArr[] = $upgradeList[$directItem['user_id']];
                            $gradeCount++;
                        } elseif ($directItem['group'] >= $userInfo->group && $directItem['group'] != 0) {
                            $minDirectGroupArr[] = $directItem['group'];
                            $gradeCount++;
                        }
                    }

                    if ($gradeCount >= $upgradeConfig['number']) {
                        rsort($minDirectGroupArr);
                        $minDirectGroup = end($minDirectGroupArr);
                        if ($userInfo->group < $minDirectGroup + 1 && $minDirectGroup + 1 <= $grade['director']) {
                            $upgradeList[$userId] = $minDirectGroup + 1;
                        }
                    }
                }

                //计算奖励
                if ($userInfo->group >0 && $isTrue == 1 && $key != 0) {
                    //获得直接上级
                    if (empty($directParent)) {
                        $directParent = $userInfo;
                    }
                    $rewardConfig = Config::get('reward.team_income');

                    //直接上级拿全部团队奖励
                    if ($userInfo->user_id == $directParent->user_id) {
                        $reward[$userId] = BCMath::mul($rewardConfig[$userInfo->group], $amount, 12);
                    }

                    //级差奖励
                    if ($userInfo->group > $maxGrade //大于当前最大等级
                        && $userInfo->user_id != $directParent->user_id) {
                        $reward[$userId] = BCMath::mul(($rewardConfig[$userInfo->group] - $rewardConfig[$maxGrade]), $amount, 12); //此处的$maxGrade肯定大于0
                    }

                    //平级奖励
                    if ($userInfo->group == $maxGrade //等于当前最大级
                        && $userInfo->user_id != $directParent->user_id) {
                        //计算各等级平级次数
                        if (isset($maxCompare[$userInfo->group])) {
                            $maxCompare[$userInfo->group]++;
                        } else {
                            $maxCompare[$userInfo->group] = 1;
                        }
                        if (in_array($maxCompare[$userInfo->group], [1,2])) {
                            $percent = $rewardConfig[0][$maxCompare[$userInfo->group]];
                            $reward[$userId] = BCMath::mul($percent, $reward[$maxGradeKey], 12);
                        }
                    }
                }
                //更新最大级
                if ($userInfo->group > $maxGrade  && $key != 0) {
                    $maxGrade = $userInfo->group;
                    $maxGradeKey = $userId;
                }
            }

            User::startTrans();
            if (!empty($upgradeList)) {
                common_log('用户升级');
                common_log(json_encode($upgradeList));
                foreach ($upgradeList as $key => $value) {
                    $userUpdate = [
                        'group' => $value,
                        'group_time' => time()
                    ];
                    $result = model('common/User')->where('user_id', $key)->update($userUpdate);
                    if (!$result) {
                        User::rollback();
                        throw new Exception();
                    }
                }
            }

            if (!empty($reward)) {
                common_log('保存收益');
                common_log(json_encode($reward));
                foreach ($reward as $key => $value) {
                    $rewardData = [
                        'user_id'     => $key,
                        'order_id'    => $orderId,
                        'invite_id'   => $rechargeUser->user_id,
                        'type'        => RewardIncome::TYPE_TEAM,
                        'amount'      => $value,
                        'day'         => date('Y-m-d', time()),
                        'create_time' => time(),
                        'order_type'  => 2,
                    ];
                    $result = RewardIncome::insert($rewardData);
                    if (!$result) {
                        User::rollback();
                        throw new Exception();
                    }
                    $result = UserWallet::where('user_id', $key)->setInc('invite_interest', $value);
                    if (!$result) {
                        User::rollback();
                        throw new Exception();
                    }
                    $userDetail = $userData[$key];
                    common_log('转账 ' . $userDetail->address . ' ' . $value);
                    $result = WalletLib::transfer($userDetail->address, $value, 1);
                    if (!$result) {
                        common_log('转账失败');
                        User::rollback();
                        throw new Exception();
                    }
                    common_log('转账成功');
                }
            }

            // 修改is_calc
            RegularOrder::where('order_id', $orderId)->update(['is_calc' => RegularOrder::CALC_COMPLETE]);
            User::commit();
        } else {
            common_log('order no exist!!!');
        }
        common_log('end!!!');
    }

    /**
     * 取得直接下级
     * @param $children
     * @return mixed
     */
    private function getDirectChildren($children)
    {
        $children = $this->getLevelUser($children);
        ksort($children);
        return current($children);
    }

    /**
     * 根据等级组装用户父级或者子级id
     * @param $childrenData
     * @return array
     */
    private function getLevelUser($list)
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[$value['level']][] = $value['user_id'];
        }
        return $data;
    }
}
