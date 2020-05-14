<?php
/**
 * 订单模块
 */
namespace app\index\controller;

use app\common\model\RechargeCycle;
use app\common\model\UserWallet;
use think\Db;
use think\facade\Config;
use app\common\library\Output;
use app\common\exception\Order as OrderException;
use app\common\exception\User as UserException;

class Order extends Common
{


	public function test(){
		Db::name('recharge_cycle')->where('recharge_amount','>=',21)->update(['type'=>2]);
	}

	public function del(){
		$input = input();
		if(isset($input['user_id'])){
			Db::name('recharge_cycle')->where(['user_id'=>$input['user_id']])->delete();
			Db::name('recharge_order')->where(['user_id'=>$input['user_id']])->delete();
			Db::name('user')->where(['user_id'=>$input['user_id']])->delete();
			Db::name('user_wallet')->where(['user_id'=>$input['user_id']])->delete();
		}else{
			echo '请输入id';
		}
	}


	/**
	 * 定时生成假订单
	 * @return object
	 */
	public function fake(){
		$amountDes = mt_rand(3,10).'000000000000000000';
		$amountOas = mt_rand(30,80).'000000000000000000';
		$len = mt_rand(40,44);
		$order = [
			'address'=> "0x".GetRandStr($len),
			'type'=>1,
			'hash'=>"0xFake".hash("sha256",GetRandStr(4)),
			'amount'=>$amountOas,
			'is_true'=>2,
			'receive_address'=>'0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593',
			'invite_code'=>342526
		];
		//dump($order);
		//exit();
		try {
			$inviteCode = service('index/OrderFake')->createFake($order);
		} catch (UserException $e) {
			return Output::error($e->getCode(), $e->getMessage());
		} catch (OrderException $e) {
			return Output::error($e->getCode(), $e->getMessage());
		} catch (Exception $e) {
			throw $e;
		}
		if ($inviteCode) {
			return Output::success(['invite_code' => $inviteCode]);
		} else {
			return Output::error();
		}
	}
    /**
     * 充值订单
     * @return object
     */
    public function create()
    {
        $data = input();
        try {
            $inviteCode = service('index/Order')->create($data);
        } catch (UserException $e) {
            return Output::error($e->getCode(), $e->getMessage());
        } catch (OrderException $e) {
            return Output::error($e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        if ($inviteCode) {
            return Output::success(['invite_code' => $inviteCode]);
        } else {
            return Output::error();
        }
    }

    /**
     * 定期或者活期列表
     */
    public function investList()
    {
        try {
            $data = [
                'invite_code' => $this->request->post('invite_code'),
                'type'        => $this->request->post('type'),
                'page'        => $this->request->post('page'),
            ];
            $result = service('index/Order')->getInvestList($data);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (OrderException $orderE) {
            return Output::error($orderE->getCode(), $orderE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($result);
    }

    /**
     * 保存失败数据
     */
    public function saveInfo()
    {
        try {
            $result = service('index/Order')->saveErrorInfo($this->request->post());
        } catch (Exception $e) {
            throw $e;
        }

        if ($result) {
            return Output::success();
        } else {
            return Output::error();
        }
    }
}
