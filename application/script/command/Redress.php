<?php
/**
 * 订单hash验证
 */
namespace app\script\command;

use app\common\model\RechargeOrder;
use app\common\model\Config as ConfigModel;
use app\common\model\User;
use app\common\model\UserBind;
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

class Redress extends Command
{
    /**
     * 配置
     */
    protected function configure()
    {
        $this->setName('redress')
            ->addArgument('name', Argument::OPTIONAL, 'your name')
            ->addOption('option', null, Option::VALUE_REQUIRED, 'option name')
            ->setDescription('Game Script');
    }

    /**
     * 执行命令
     * php think redress
     */
    protected function execute(Input $input, Output $output)
    {
        $list = User::field('user_id, parent_id')->select()->toArray();
        foreach ($list as $userInfo) {
            common_log($userInfo['user_id']);
            $this->saveParent($userInfo['user_id'], $userInfo, 1);
        }
    }

    public function saveParent($userId, $userInfo, $level)
    {
        if ($userInfo['parent_id'] == 0) {
            return true;
        }
        UserBind::insert([
            'user_id'   => $userId,
            'parent_id' => $userInfo['parent_id'],
            'level'     => $level
        ]);
        $parentInfo = User::where('user_id', $userInfo['parent_id'])->field('user_id, parent_id')->find()->toArray();
        return $this->saveParent($userId, $parentInfo, $level + 1);
    }
}
