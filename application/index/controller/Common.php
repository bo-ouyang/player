<?php
namespace app\index\controller;
use think\facade\Config;
use think\facade\Hook;
use think\facade\Session;
use think\facade\Lang;
/*use Config;
use Hook;
use Session;
use Lang;
*/
use app\common\library\Auth;
use app\common\library\Jwt;
use think\exception\HttpResponseException;
use app\common\exception\System as SystemException;
use app\common\exception\User as UserException;
use app\common\controller\Common as CommonController;
use app\common\library\Output;
use app\common\model\Language;


class Common extends CommonController
{
    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = ['*'];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['*'];

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

    	//return true;
        parent::initialize();

        // 设置多语言
        define('CURRENT_LANG', $this->request->langset());
        // 获取多语言ID
        $lang = Language::getByCode(CURRENT_LANG);
        define('CURRENT_LANG_ID', $lang->language_id);

        $modulename     = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname     = strtolower($this->request->action());

        $this->auth = Auth::instance();
        // 通过JWT初始化用户
        $jwtToken = $this->request->header('Authorization');
        $token = NULL;
        if (!$jwtToken) {
            if (!$this->auth->match($this->noNeedLogin, $actionname)) {
            	echo 111;
                // 未登录
                $response = Output::error(UserException::E_NOT_LOGIN, UserException::getErrorMsg(UserException::E_NOT_LOGIN));
                throw new HttpResponseException($response);
            }
        } else {
            $token = Jwt::validateToken($jwtToken);
            if (!$token && !$this->auth->match($this->noNeedLogin, $actionname)) {
                // 已过期
                $response = Output::error(UserException::E_NOT_LOGIN, UserException::getErrorMsg(UserException::E_NOT_LOGIN));
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
                if (Config::get('app.login_unique')) {
                    $error = $this->auth->getError();
                    if (!empty($error) && $error == UserException::getErrorMsg(UserException::E_OTHER_LOGIN)) {
                        $response = Output::error(UserException::E_OTHER_LOGIN, UserException::getErrorMsg(UserException::E_OTHER_LOGIN));
                    } else {
                        $response = Output::error(UserException::E_NOT_LOGIN, UserException::getErrorMsg(UserException::E_NOT_LOGIN));
                    }
                } else {
                    $response = Output::error(UserException::E_NOT_LOGIN, UserException::getErrorMsg(UserException::E_NOT_LOGIN));
                }

                throw new HttpResponseException($response);
            }

            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $response = Output::error(UserException::E_NO_PURVIEW, UserException::getErrorMsg(UserException::E_NO_PURVIEW));
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
}
