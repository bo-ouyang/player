<?php
/**
 * 短信发送钩子
 */
namespace app\common\behavior;
use think\facade\Config;
use think\facade\Env;
use app\common\model\User as UserModel;
use app\common\library\Jwt;

class User
{
	/**
	 * 前台用户退出
	 */
	public function userLogout(object $user): bool
	{
		if (!$user) {
			return false;
		}

		// 登录失败次数
		$user->token = '';
		$user->save();
		return true;
	}

	/**
	 * 前台用户登录失败
	 */
	public function userLoginFailure(object $user): bool
	{
		if (!$user) {
			return false;
		}

		// 登录失败次数
		$user->login_failure_date = strtotime(date('Y-m-d'));
		$user->login_failure      = $user->login_failure+1;
		$user->save();
		return true;
	}

	/**
	 * 用户唯一设备登录
	 */
	public function userLoginUnique(array $data): bool
	{
		// 获取token
		$loginToken = Jwt::getColumn($data['token'], 'uid');
		$updateId = UserModel::where('user_id', $data['user_id'])->update(['token' => $loginToken]);

		return ($updateId === false) ? false : true;
	}
}
