<?php
/**
 * 订单hash验证
 */
namespace app\script\command;

use app\common\model\RechargeOrder;
use app\common\model\Config as ConfigModel;
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

class Order extends Command
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
        if (!in_array($name, ['recharge', 'egg', 'token'])) {
            $output->writeln('Error argument!');
            return false;
        }

        if (!ps_qty_limit('think order ' . $name, 5)) {
            $output->writeln('process limit!');
            return false;
        }

        $output->writeln('start process order!');
        if ($name == 'recharge') {
            $orderModel = model('common/RechargeOrder');
        }
        if ($name == 'egg') {
            $orderModel = model('common/EggOrder');
        }
        if ($name == 'token') {
            $orderModel = model('common/TokenOrder');
        }

        $indexKey = 'one_validate_hash_' . $name;
        $lastId = Cache::get($indexKey);
        $lastId = ((int)$lastId > 0) ? $lastId : 0;
        $output->writeln('last id ' . $lastId);
	    //$t_928 = mktime(23,59,59,9,28,2019);
        // 获取hash未验证的真实订单
        $list = $orderModel
	        ->where('is_validate', RechargeOrder::VALIDATE_UNDONE)
	        ->where('is_true', 1)
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

        $expire = 7200;// 20分钟
        foreach ($list as $order) {
            sleep(5);
            $diffTime = time() - $order['create_time'];
            //if (false) {
            if ($diffTime > $expire) {
                // 验证超时,删除订单
                $this->_deleteOrder($orderModel, $order, $name);
                $output->writeln('order ' . $order['order_id'] .' is validate expire!');
            } else {
                // 未超时,验证订单
                $order['amount'] = $order['amount'] ?? $order['price'];
                $amount = BCMath::mul($order['amount'], pow(10, 18));
                // 获取用户地址
                $userInfo = model('common/User')->field('user_id, address')->where('user_id', $order['user_id'])->find()->toArray();
                $address  = $userInfo['address'];

                if ($name == 'recharge') {
                    $contract = strtolower(Config::get('app.contract_address'));
                    $status = WalletLib::validHash($order['hash'], $address, $amount, $contract);
                }elseif ($name == 'egg') {
                    $contract = strtolower(Config::get('app.egg_address'));
                    $status = WalletLib::validHash($order['hash'], $address, $amount, $contract);
                } elseif ($name == 'token') {
                    $contract = strtolower(Config::get('app.token_address'));
                    $status = WalletLib::validHash($order['hash'], $address, $amount, $contract);
                } else {
                    $output->writeln('参数错误');
                    return false;
                }
                //if (1) {
                $systemRechargeId = model('common/Config')->getConfig(ConfigModel::KEY_SYSTEM_RECHARGE_ID);
                $systemRechargeId = explode(',', $systemRechargeId);
                //$output->writeln($status.in_array($order['order_id'], $systemRechargeId));
                if ($status||$systemRechargeId==2501) {
                    if ($name == 'recharge') {
                        $result =  service('index/Order')->validRechargeOrder($order);
                    }
                    if ($name == 'egg') {
                        $result =  service('index/Order')->validEggOrder($order);
                    }
                    if ($name == 'token') {
                        $result =  service('index/Order')->validTokenOrder($order);
                    }

                    if ($result) {
                        $output->writeln('order ' . $order['order_id'] .' hash validate success!');
                    } else {
                        $output->writeln('帆帆帆帆');
                        $output->writeln('order ' . $order['order_id'] .' hash validate faliure, continue!');
                    }
                } else {

                    // 未超时,跳过
                    $output->writeln('order ' . $order['order_id'] .' hash validate faliure, continue!');
                }
            }
        }

        $output->writeln('end process order!');
    }

    /**
     * 删除订单
     */
    protected function _deleteOrder($model, $order, $type)
    {
        $attribute = [
            'content'     => json_encode($order),
            'type'        => \app\common\model\ErrorInfo::TYPE_HASH,
            'create_time' => time(),
        ];

        \app\common\model\ErrorInfo::create($attribute);
        // 删除订单,撤销钱包表数据
        $nowTime = time();
        $model->where('order_id', $order['order_id'])->delete();
    }
}
