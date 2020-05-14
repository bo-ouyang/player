<?php
namespace app\admin\controller;

use think\facade\Config;
use think\facade\Hook;
use think\facade\Session;
use app\admin\library\Auth;
use app\common\library\Jwt;
use think\exception\HttpResponseException;
use app\common\exception\System as SystemException;
use app\common\exception\User as UserException;
use app\common\controller\Common as CommonController;
use app\common\library\Output;

class Common extends CommonController
{
	/**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    protected $needSuperRight = [];

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

	/**
	 * 初始化
	 */
	protected function initialize()
	{
		parent::initialize();
		$modulename     = $this->request->module();
		$controllername = strtolower($this->request->controller());
		$actionname     = strtolower($this->request->action());

        $this->auth = Auth::instance();

        // 通过JWT初始化用户
        $jwtToken = $this->request->header('Authorization');
        $token = NULL;
        if (!$jwtToken) {
        	if (!$this->auth->match($this->noNeedLogin, $actionname)) {
        		// 未登录
	        	$response = Output::error(UserException::E_NOT_LOGIN, UserException::$messageList[UserException::E_NOT_LOGIN]);
				throw new HttpResponseException($response);
        	}
        } else {
        	$token = Jwt::validateToken($jwtToken);
	        if (!$token && !$this->auth->match($this->noNeedLogin, $actionname)) {
	        	// 已过期
	    		$response = Output::error(UserException::E_NOT_LOGIN, UserException::$messageList[UserException::E_NOT_LOGIN]);
				throw new HttpResponseException($response);
	        }
        }
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录

        if (!$this->auth->match($this->noNeedLogin, $actionname)) {

            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $response = Output::error(UserException::E_NOT_LOGIN, UserException::$messageList[UserException::E_NOT_LOGIN]);
				throw new HttpResponseException($response);
            }

            // 判断是否需要验证权限
            /*if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $response = Output::error(UserException::E_NO_PURVIEW, UserException::$messageList[UserException::E_NO_PURVIEW]);
					throw new HttpResponseException($response);
                }

            }*/

            if ($this->auth->match($this->needSuperRight, $actionname)) {
                if ($this->auth->admin_user_id != 1) {
                    $response = Output::error(UserException::E_NO_PURVIEW, UserException::$messageList[UserException::E_NO_PURVIEW]);
                    throw new HttpResponseException($response);
                }
            }

        } else {
            // 不需要登录,如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }
	}
    /**
     * 用户权限列表
     * @date: 2018年6月13日 下午1:15:52
     * @author: tangzhiwei
     * @param
     * @return
     * @throws Exception
     */
    public function unlimitedForLayer ($cate, $name = 'child', $pid = 0) {
        $arr = array();
        foreach ($cate as $v) {
            if ($v['pid'] == $pid) {
                $v[$name] = self::unlimitedForLayer($cate, $name, $v['id']);
                $arr[] = $v;
            }
        }
        return $arr;

    }

    /**
     *
     *   菜单
     *@date: 2018年6月13日 下午2:07:09
     *@author: tangzhiwei
     * @param
     * @return
     * @throws Exception
     */

    public function getAllMenu()
    {
        $model = model('admin/AuthRule');  //从数据库读取菜单
        $cate  = $model->getMenu(1);
        //读取用作菜单显示的
        $menu = self::unlimitedForLayer($cate);
        return $menu;
    }
    /**
     * 用户全部节点
     *@date: 2018年6月14日 下午6:21:56
     *@author: tangzhiwei
     * @param
     * @return
     * @throws Exception
     */
    public function  getNode()
    {
        $auth_id  = Session::get('admin_id');
        $nodeList = $this->getAllMenu();
        $ids = [];
        $auth =  new Auth();

        $authList = $auth->getRuleList($auth_id);
        $rule = [];
        foreach ($authList as $auth){
            $rule[] = $auth['name'];
        }

        //获取用户需要验证的所有有效规则列表
        $map['name'] = $rule;
        $AuthRuleModel = model('admin/AuthRule');
        $roleRight  =  $AuthRuleModel->rightMenu($map);
        foreach ($roleRight as $key ){
            $ids[] =$key['id'];
        }
        foreach($nodeList as $k=>$v){
            foreach ($nodeList[$k]['child'] as $kk => $vv){
                if(!in_array($vv['id'], $ids)){
                    unset($nodeList[$k]['child'][$kk]);
                }
            }
            if(!in_array($v['id'], $ids)){
                unset($nodeList[$k]);
            }

        }
        return $nodeList;
    }
}
