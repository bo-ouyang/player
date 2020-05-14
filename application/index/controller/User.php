<?php
/**
 * 用户模块
 */
namespace app\index\controller;
use think\facade\Session;
use Exception;
use think\facade\Hook;
use think\facade\Config;
use random\Random;
use think\facade\Env;
use app\common\exception\User as UserException;
use app\common\exception\System as SystemException;
use app\common\library\Output;
use app\common\validate\User as UserValidate;
use app\common\exception\Order as OrderException;

class User extends Common
{
    /**
     * 获取用户详情
     */
    public function detail()
    {
        try {
            $inviteCode = $this->request->post('invite_code') ?: Config::get('app.default_invite_code');
            $data = service('index/User')->getUserDetail($inviteCode);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * @return object
     * @throws Exception
     *
     * 每日分红
     */
    public function getDayBonus()
    {
        try {
            $inviteCode = $this->request->post('invite_code') ?: Config::get('app.default_invite_code');
            $page = $this->request->post('page') ?: 1;
            $limit = $this->request->post('limit') ?: Config::get('paginate.list_rows');
            $data = service('index/User')->getDayBonus($inviteCode, $page, $limit);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 投资总金额
     */
    public function investList()
    {
        try {
            $inviteCode = $this->request->post('invite_code') ?: Config::get('app.default_invite_code');
            $data = service('index/User')->getUserAmount($inviteCode);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 判断邀请码是否存在
     */
    public function codeExists()
    {
        try {
            $inviteCode = $this->request->post('invite_code');
            $data = service('index/User')->codeExists($inviteCode);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 提现
     */
    public function cash()
    {
        try {
            $data = [
                'cycle_id'    => $this->request->post('cycle_id'),
                'invite_code' => $this->request->post('invite_code')
            ];

            $result = service('index/User')->cashAmount($data);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (OrderException $orderE) {
            return Output::error($orderE->getCode(), $orderE->getMessage());
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
     * @return object
     * @throws Exception
     *
     * 推广奖励
     */
    public function getExtensionBonus()
    {
        try {
            $inviteCode = $this->request->post('invite_code') ?: Config::get('app.default_invite_code');
            $page = $this->request->post('page') ?: 1;
            $limit = $this->request->post('limit') ?: Config::get('paginate.list_rows');
            $data = service('index/User')->getExtensionBonus($inviteCode, $page, $limit);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }



    /***
     * @return object
     * @throws Exception
     *
     * 团队明细
     */
    public function teamList()
    {
        try {
            $inviteCode = $this->request->post('invite_code') ?: Config::get('app.default_invite_code');
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->teamList($inviteCode, $page, $listRows);
        } catch (UserException $e) {
            return Output::error($e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    public function userBind()
    {
        try {
            $parentId = $this->request->post('parent_id');
            $userId = $this->request->post('user_id');
            service('index/User')->userBind($userId, $parentId);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success();
    }

    /**
     * 奖励
     * @return object
     * @throws Exception
     */
    public function getBonus()
    {
        try {
            $inviteCode = $this->request->post('invite_code') ?: Config::get('app.default_invite_code');
            $page = $this->request->post('page') ?: 1;
            $limit = $this->request->post('limit') ?: Config::get('paginate.list_rows');
            $data = service('index/User')->getBonus($inviteCode, $page, $limit);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 投资明细
     * @return object
     * @throws Exception
     */
    public function investDetail()
    {
        try {
            $inviteCode = input('invite_code') ?: Config::get('app.default_invite_code');
            $data = service('index/User')->investDetail($inviteCode);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 充值列表
     * @return object
     * @throws Exception
     */
    public function rechargeList()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->rechargeList($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 静态收益明细
     * @return object
     * @throws Exception
     */
    public function staticProfit()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->staticProfit($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 邀请收益明细
     * @return object
     * @throws Exception
     */
    public function inviteProfit()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->inviteProfit($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 股东收益
     * @return object
     * @throws Exception
     */
    public function teamProfit()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->teamProfit($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * X1明细
     * @return object
     * @throws Exception
     */
    public function tokenList()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->tokenList($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 超级节点奖励
     * @return object
     * @throws Exception
     */
    public function superReward()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/User')->superReward($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 用户升级明细
     * @return object
     * @throws Exception
     */
    public function upgradeLog()
    {
        try {
            $inviteCode = $this->request->post('invite_code', Config::get('app.default_invite_code'));
            $data = service('index/User')->upgradeLog($inviteCode);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }
}
