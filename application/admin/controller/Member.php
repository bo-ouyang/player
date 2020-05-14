<?php
namespace app\admin\controller;

use app\common\exception\System as SystemException;
use app\common\exception\User as UserException;
use app\common\exception\AdminUser as AdminUserException;
use app\admin\validate\AdminUser as UserValidate;
use Exception;
use app\common\library\Output;
use app\common\library\Wallet;

class Member extends Common
{
    /**
     * 超级管理员才拥有的权限
     * @var array
     */
    protected $needSuperRight = [];

    /**
     * 会员列表
     * @return object
     * @throws Exception
     */
    public function list()
    {
        $param = [
            'address'     => $this->request->post('address'),
            'is_super'    => $this->request->post('is_super'),
            'invite_code' => $this->request->post('invite_code'),
            'page'        => $this->request->post('page', 1),
        ];

        try {
            $list = service('admin/Member')->list($this->auth->invite_code, $param);
        } catch (AdminUserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);

    }

    /**
     * 级别修改
     * @return object
     * @throws Exception
     */
    public function groupEdit()
    {
        $userId = $this->request->post('user_id', 0);
        $group = $this->request->post('is_super');
        try {
            $result = service('admin/Member')->groupEdit($userId, $group);
        } catch (AdminUserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
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
     * 变更用户上级
     * @return object
     * @throws Exception
     */
    public function parentChange()
    {
        $userId = $this->request->post('user_id', 0);
        $inviteCode = $this->request->post('invite_code');
        try {
            $result = service('admin/Member')->parentChange($userId, $inviteCode);
        } catch (AdminUserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        if ($result) {
            return Output::success();
        } else {
            return Output::error();
        }
    }

    public function bindRedress()
    {
        $userId         = $this->request->post('user_id');
        $originParentId = $this->request->post('origin_parent_id');
        $parentId       = $this->request->post('parent_id');
        try {
            $result = service('admin/Member')->bindRedress($userId, $originParentId, $parentId);
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
     * 后台添加用户
     * @return object
     * @throws Exception
     */
    public function memberAdd()
    {
        $data = [
            'invite_code' => $this->request->post('invite_code'),
            'address'     => $this->request->post('address'),
            'is_true'     => $this->request->post('is_true', 1),
        ];
        try {
            $result = service('admin/Member')->memberAdd($data);
        } catch (UserException $e) {
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
}
