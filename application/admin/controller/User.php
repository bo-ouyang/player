<?php
namespace app\admin\controller;

use app\common\exception\System as SystemException;
use app\common\exception\User as UserException;
use app\common\exception\AdminUser as AdminUserException;
use app\admin\validate\AdminUser as UserValidate;
use Exception;
use app\common\library\Output;

class User extends Common
{
	/**
	 * 不需要登录
	 * @var array
	 */
	protected $noNeedLogin = ['login'];

	/**
	 * 不需要鉴权
	 * @var array
	 */
	protected $noNeedRight = ['*'];

    /**
     * 超级管理员才拥有的权限
     * @var array
     */
    protected $needSuperRight = ['register', 'userList', 'updateUser', 'deleteUser', 'userStatus'];

	/**
	 * 初始化
	 */
	protected function initialize()
    {
        parent::initialize();
    }

	/**
	 * 登录
	 */
	public function login()
	{
		if ($this->auth->admin_user_id) {
			return Output::error(UserException::E_IS_LOGIN, UserException::$messageList[UserException::E_IS_LOGIN]);
		}

		if ($this->request->header('ChatId')) {
			// 重新生成App端sessionid
			// Session::regenerate();
		}

		$data = [
			'username' => $this->request->post('username'),
			'password' => $this->request->post('password'),
		];

		$this->validateData(new UserValidate, 'login', $data);

        try {
        	$message = service('admin/User')->login($data, $this->auth);
        } catch (AdminUserException $userE) {
        	return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($message, SystemException::E_SUCCESS, '登录成功');
	}

	/**
	 * 添加系统管理员
	 */
	public function register()
	{
		$data = [
			'username'    => $this->request->post('username'),
            'invite_code' => $this->request->post('invite_code'),
			'password'    => $this->request->post('password')
		];

        $this->validateData(new UserValidate, 'register', $data);

		try {
        	$message = service('admin/User')->register($data, $this->auth);
        } catch (AdminUserException $userE) {
        	return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success('', SystemException::E_SUCCESS, '添加管理员成功');
	}

    /**
     * 管理员列表
     * @return object
     * @throws Exception
     */
	public  function userList()
    {
        $params = input('post.');
        try {
            $message = service('admin/User')->userList($params);
        } catch (AdminUserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($message, SystemException::E_SUCCESS);
    }

    /**
     * 编辑管理员
     * @return object
     * @throws Exception
     */
	public  function  updateUser()
    {
        $data = [
            'username' => $this->request->post('username'),
            'password' => $this->request->post('password'),
            'admin_user_id' => $this->request->post('admin_user_id')
        ];

        $this->validateData(new UserValidate, 'update', $data);

        try {
            $message = service('admin/User')->updateUser($data);

        } catch (Exception $e) {
            throw $e;
        }
        if($message === false){
            return Output::success('', SystemException::E_SUCCESS, '修改失败');
        }else{
            return Output::success('', SystemException::E_SUCCESS, '修改成功');
        }
    }

    /**
     * 删除管理员
     * @return object
     * @throws Exception
     */
    public  function  deleteUser()
    {
        $data = [

            'admin_user_id' => $this->request->post('admin_user_id')
        ];
        $this->validateData(new UserValidate, 'delete', $data);

        try {
            $message = service('admin/User')->deleteUser($data);

        } catch (Exception $e) {
            throw $e;
        }
        if($message === false){
            return Output::success('', SystemException::E_SUCCESS, '删除失败');
        }else{
            return Output::success('', SystemException::E_SUCCESS, '删除成功');
        }
    }

    /**
     * 管理员启用/禁用
     * @return object
     * @throws Exception
     */
    public function userStatus()
    {
        $userId = $this->request->post('admin_user_id', 0);
        try {
            $result = service('admin/User')->userStatus($userId);
        } catch (Exception $e) {
            throw $e;
        }
        if($result){
            return Output::success();
        }else{
            return Output::success();
        }
    }

	/**
	 * 退出
	 */
	public function logout()
	{
		if (!$this->auth->admin_user_id) {
			return Output::error(UserException::E_NOT_LOGIN, UserException::$messageList[UserException::E_NOT_LOGIN]);
		}
        try {
        	$this->auth->logout();
        } catch (Exception $e) {
        	throw $e;
        }

        return Output::success(NULL, SystemException::E_SUCCESS, '退出成功');
	}

    /**
     * 管理员详情
     * @throws Exception
     */
	public function adminDetail()
    {
        try {
            $data = service('admin/User')->adminDetail($this->auth->admin_user_id);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }
}
