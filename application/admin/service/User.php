<?php
/**
 * 用户服务类
 * @author chat
 * @version 1.0
 */
namespace app\admin\service;

use app\admin\library\Auth;
use app\admin\model\AdminUser;
use app\common\exception\AdminUser as AdminUserException;
use app\common\library\Jwt;
use app\admin\model\AdminUser as AdminUserModel;
use app\admin\model\AuthGroupAccess;
use app\admin\model\AuthGroup;
use app\common\model\FlowRecord;
use app\common\model\UserWallet;
use app\common\model\User as UserModel;
use app\common\model\UserGroup;
use think\facade\Config;
use think\facade\Session;
use think\facade\Hook;
use think\Db;
use think\Exception;
use app\common\exception\User as UserException;

class User
{
	/**
	 * 用户注册
	 */
	public function register($data, $auth = NULL)
	{
		if (!$data) {
			return false;
		}

		if (is_null($auth)) {
			$auth = Auth::instance();
		}

		// 检测用户名或邮箱、手机号是否存在
        if (AdminUserModel::get(['username' => $data['username']])) {
            exception(AdminUserException::getErrorMsg(AdminUserException::E_NAME_EXISTS), AdminUserException::E_NAME_EXISTS, '\\' . AdminUserException::class);
		}

		$userId = $auth->register($data['username'], $data['password'], $data['invite_code']);
		if (!$userId) {
			exception(AdminUserException::getErrorMsg(AdminUserException::E_ADD_FAIL), AdminUserException::E_ADD_FAIL, '\\' . AdminUserException::class);
		}
        $logData['title']   = '添加用户';
        $logData['content'] = ['add_user_id' => $userId, 'user_id'=> Session::get('admin_id')];
        Hook::listen('admin_operate_log', $logData);
		return $userId;
	}

    /**
     * 编辑管理员
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     */
	public  function  updateUser($params)
    {
        $adminInfo =  AdminUserModel::getByAdminUserId($params['admin_user_id']);
        $auth = new Auth();
        $data = [
            'username'    => $params['username'],
            'update_time' => time()
            ];
        if(!empty($params['password'])){
            $data['password'] = $auth->getEncryptPassword($params['password'],$adminInfo['salt']);
        }
        $user = adminUserModel::where(['admin_user_id' =>$params['admin_user_id']])->update($data);
        if($user !== false){
            $logData['title']   = '修改用户';
            $logData['content'] = ['update_user_id' => $params['admin_user_id'], 'user_id'=>Session::get('admin_id')];
            Hook::listen('admin_operate_log', $logData);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除管理员
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public  function deleteUser($params)
    {
        $userId = $params['admin_user_id'];
        if ($userId == 1) {
            return false;
        }
        $user  = adminUserModel::where(['admin_user_id' =>$userId])->delete();
        if($user !== false){
            $logData['title']   = '删除用户';
            $logData['content'] = ['delete_user_id' => $params['admin_user_id'], 'user_id'=>Session::get('admin_id')];
            Hook::listen('admin_operate_log', $logData);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 启用/禁用管理员
     * @param $userId
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function userStatus($userId)
    {
        $status = adminUserModel::getFieldByAdminUserId($userId, 'status');
        $logData = [];
        if ($status == adminUserModel::STATUS_ENABLE) {
            $status = adminUserModel::STATUS_DISABLE;
            $logData['title']   = '禁用用户';
        } else {
            $status = adminUserModel::STATUS_ENABLE;
            $logData['title']   = '启用用户';
        }
        $result = adminUserModel::where(['admin_user_id' => $userId])->update(['status' => $status, 'update_time' => time()]);
        if($result){
            $logData['content'] = ['admin_user_id' => $userId, 'user_id'=> Session::get('admin_id')];
            Hook::listen('admin_operate_log', $logData);
            return true;
        }else{
            return false;
        }
    }

	/**
	 * 用户登录
	 */
	public function login($data, $auth = NULL)
	{
		if (!$data) {
			return false;
		}

		if (is_null($auth)) {
			$auth = Auth::instance();
		}

		// 查询最大登录次数
		if ($auth->admin_user_id) {
			$userInfo = $auth->getUser();
		} else {
			$userInfo = AdminUserModel::get(['username' => $data['username']]);
		}

		if (!$userInfo) {
			exception(AdminUserException::getErrorMsg(AdminUserException::E_PASSWORD), AdminUserException::E_PASSWORD, '\\' . AdminUserException::class);
		}

		// 对比日期
		if (strtotime(date('Y-m-d')) == $userInfo->login_failure_date && $userInfo->login_failure > Config::get('max_login_failures')) {
			exception(AdminUserException::getErrorMsg(AdminUserException::E_MAX_LOGIN), AdminUserException::E_MAX_LOGIN, '\\' . AdminUserException::class);
		}

		$userId = $auth->login($data['username'], $data['password']);
		if (!$userId) {
			exception(AdminUserException::getErrorMsg(AdminUserException::E_PASSWORD), AdminUserException::E_PASSWORD, '\\' . AdminUserException::class);
		}

		// 生成JWT
		$token = Jwt::createToken($userId);
		if (!$token) {
			exception(AdminUserException::getErrorMsg(AdminUserException::E_JWT_ERROR), AdminUserException::E_JWT_ERROR, '\\' . AdminUserException::class);
		}

		return ['auth' => $token, 'user_id' => $userId, 'is_super' => $userId == 1 ? 1 : 0];
	}

    /**
     * 管理员列表
     * @param $params
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function  userList($params)
    {
        $adminUserModel = model('adminUser');
        if(!empty($params['page'])){
            $page = $params['page']  ;
        }else{
            $page = 1;
        }
        $adminUserList = $adminUserModel->userList($page, 'admin_user_id, username, invite_code, status, create_time');
        return $adminUserList;
    }

    /**
     * 管理员信息
     * @param $adminUserId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function adminDetail($adminUserId)
    {
        $data = AdminUser::where('admin_user_id', $adminUserId)->field('admin_user_id, username, invite_code, status')->find();
        $data ? $data->toArray() : [];
        $data['is_super'] = $data['admin_user_id'] == 1 ? 1 : 0;
        return $data;
    }
}
