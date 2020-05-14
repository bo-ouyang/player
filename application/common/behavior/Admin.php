<?php
/**
 * 后台操作钩子
 */
namespace app\common\behavior;
use think\facade\Env;
use think\facade\Request;
use app\admin\model\AdminLog;

class Admin
{
    /**
     * 操作日志
     */
    public function adminOperateLog($data)
    {
        $data['url']         = Request::url();
        $data['ip']          = Request::ip();
        $data['user_agent']  = Request::header('User-Agent');
        $data['create_time'] = time();
        $data['content']     = json_encode($data['content']);
        $success = AdminLog::create($data);
        if (!$success->id) {
            return false;
        }

        return true;
    }
}
