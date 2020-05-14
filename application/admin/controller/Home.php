<?php
namespace app\admin\controller;

use app\common\exception\System as SystemException;
use app\common\exception\User as UserException;
use app\common\exception\AdminUser as AdminUserException;
use app\admin\validate\AdminUser as UserValidate;
use Exception;
use app\common\library\Output;
use app\common\library\Wallet;

class Home extends Common
{
    /**
     * 超级管理员才拥有的权限
     * @var array
     */
    protected $needSuperRight = ['configEdit'];

    /**
     * 后台首页
     * @return object
     * @throws Exception
     */

    public function index()
    {
        try {
            $list = service('admin/Home')->index($this->auth->invite_code);
        }catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

    /**
     * 参数设置
     * @return object
     * @throws Exception
     */
    public function config()
    {
        try {
            $list = service('admin/Home')->config();
        }catch (Exception $e) {
            throw $e;
        }

        return Output::success($list);
    }

    /**
     * 配置修改
     * @return object
     * @throws Exception
     */
    public function configEdit()
    {
        $key   = $this->request->post('key');
        $value = $this->request->post('value');
        try {
            $result = service('admin/Home')->configEdit($key, $value);
        }catch (Exception $e) {
            throw $e;
        }

        if ($result) {
            return Output::success();
        } else {
            return Output::error();
        }
    }
}
