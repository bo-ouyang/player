<?php
namespace app\admin\controller;

use app\common\exception\AdminUser as AdminUserException;
use app\common\exception\User as UserException;
use Exception;
use app\common\library\Output;
use app\common\library\Wallet;
use app\common\exception\Order as OrderException;

class Order extends Common
{
    /**
     * 超级管理员才拥有的权限
     * @var array
     */
    protected $needSuperRight = ['errorList', 'eggOrder', 'drawSetting', 'draw', 'create'];

	/**
	 * 初始化
	 */
	protected function initialize()
    {
        parent::initialize();
    }

    /**
     * Alibo列表
     * @return object
     * @throws Exception
     */
    public function tokenList()
    {
        $param = [
            'address' => $this->request->post('address'),
            'type'    => $this->request->post('type'),
            'page'    => $this->request->post('page', 1),
        ];
        try {
            $list = service('admin/Order')->tokenList($this->auth->invite_code, $param);
        } catch (AdminUserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }


    /**
     * @return mixed
     * @throws Exception
     *
     * 提现订单列表
     */
    public function cashOrder()
    {
        $param = [
            'address' => $this->request->post('address'),
            'type'    => $this->request->post('type'),
            'page'    => $this->request->post('page', 1),
        ];

        $list = service('admin/Order')->getCashOrder($this->auth->invite_code, $param);

        return Output::success($list);
    }

    /**
     * 动态收益
     * @return object
     * @throws Exception
     */
    public function rewardIncome()
    {
        $params = [
            'pay_address' => $this->request->post('pay_address'),
            'address'     => $this->request->post('address'),
            'page'        => $this->request->post('page', 1)
        ];
        try {
            $list = service('admin/Order')->getRewardIncome($this->auth->invite_code, $params);
        }catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

	/**
	 * 动态收益
	 * @return object
	 * @throws Exception
	 */
	public function sendLog()
	{
		$params = [
			'to' => $this->request->post('to'),
			'hash'     => $this->request->post('hash'),
			'create_time'     => $this->request->post('create_time'),
			'page'        => $this->request->post('page', 1)
		];
		try {
			$list = service('admin/Order')->SendLog($this->auth->invite_code, $params);
		}catch (Exception $e) {
			throw $e;
		}

		return Output::success($list);
	}

    /**
     * @return mixed
     *
     * 信托派息
     */
    public function investIncome()
    {
        $param = [
            'address' => $this->request->post('address'),
            'page'    => $this->request->post('page', 1),
            'type'    => $this->request->post('type'),
        ];

        $list = service('admin/Order')->getInvestIncome($this->auth->invite_code, $param);

        return Output::success($list);
    }



    /**
     * 投资订单列表
     * @return object
     * @throws Exception
     */
    public function investOrder()
    {
        $params = [
            'address' => $this->request->post('address', ''),
            'page'    => $this->request->post('page', 1),
            'type'    => $this->request->post('type'),
            'system_recharge'    => $this->request->post('system_recharge'),
        ];

        try {
            $list = service('admin/Order')->investOrder($this->auth->invite_code, $params);
        } catch (AdminUserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

    /**
     * 错误列表
     */
    public function errorList()
    {
        try {
            $list = service('admin/Order')->errorList($this->request->post('page', 1));
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

    /**
     * 团队收益
     * @return object
     * @throws Exception
     */
    public function getTeamAmount()
    {
        $param = [
            'address' => $this->request->post('address'),
            'page'    => $this->request->post('page', 1),
            'grade'   => $this->request->post('grade'),
        ];
        try {
            $list = service('admin/Order')->getTeamAmount($this->auth->invite_code, $param);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

    /**
     * 超级节点收益
     * @return object
     * @throws Exception
     */
    public function superReward()
    {
        $params = [
            'pay_address' => $this->request->post('pay_address'),
            'address'     => $this->request->post('address'),
            'page'        => $this->request->post('page', 1)
        ];
        try {
            $list = service('admin/Order')->superReward($this->auth->invite_code, $params);
        }catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

    /**
     * 彩蛋订单
     * @return object
     * @throws Exception
     */
    public function eggOrder()
    {
        $params = [
            'address' => $this->request->post('address'),
            'type'    => $this->request->post('type'),
            'status'  => $this->request->post('status'),
            'page'    => $this->request->post('page', 1),
            'page_size' => $this->request->post('page_size')
        ];
        try {
            $list = service('admin/Order')->eggOrder($params);
        }catch (Exception $e) {
            throw $e;
        }
        return Output::success($list);
    }

    /**
     * 开奖设置
     * @return object
     * @throws Exception
     */
    public function drawSetting()
    {
        $eggId = $this->request->post('egg_id');
        try {
            $data = service('admin/Order')->drawSetting($eggId);
        }catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 开奖设置
     * @return object
     * @throws Exception
     */
    public function draw()
    {
        try {
            $result = service('admin/Order')->draw($this->request->post());
        } catch (OrderException $e) {
            return Output::error($e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        if ($result) {
            return Output::success();
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
        $data = $this->request->post();
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
     * 获取合约地址
     */
    public function contractAddress()
    {
        try {
            $data = service('index/Home')->getContractAddress();
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

}
