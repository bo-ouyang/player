<?php
/**
 * 后台用户鉴权
 * @author bcxgame
 * @version 1.0
 */
namespace app\admin\library;

use app\admin\model\AdminUser;
use app\common\exception\AdminUser as AdminUserException;
use random\Random;
use Think\Db;
use token\Token;
use app\admin\model\AuthGroupAccess;
use app\admin\model\AuthGroup;
use app\admin\model\AuthRule;
use think\facade\Config;
use think\facade\Hook;
use think\facade\Request;
use Exception;
use think\facade\Session;

class Auth
{
    protected static $instance = null;
    protected $_error = '';
    protected $_logined = FALSE;
    protected $_user = NULL;
    protected $_token = '';
    //Token默认有效时长
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $options = [];
    protected $allowFields = ['admin_user_id', 'mobile', 'status', 'device'];
    protected $config = [
        'auth_on' => 1, // 权限开关
        'auth_type' => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group' => 'auth_group', // 用户组数据表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
        'auth_rule' => 'auth_rule', // 权限规则表
        'auth_user' => 'member', // 用户信息表
    ];

    /**
     * 初始化
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * 获取实例
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 获取User模型
     * @return User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 兼容调用user模型的属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_user ? $this->_user->$name : NULL;
    }

    /**
     * 根据token初始化
     *
     * @param string   $token Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined) {
            return true;
        }

        if ($this->_error) {
            return false;
        }

        $data = Token::get($token);
        if (!$data) {
            return false;
        }

        $user_id = intval($data['user_id']);
        if ($user_id > 0)
        {
            $user_id = intval($user_id/520);
            $user = AdminUser::get($user_id);
            if (!$user)
            {
                $this->setError(AdminUserException::getErrorMsg(AdminUserException::E_NOT_EXISTS));
                return false;
            }

            if ($user['status'] != AdminUser::STATUS_ENABLE)
            {
                $this->setError(AdminUserException::getErrorMsg(AdminUserException::E_NOT_ENABLE));
                return false;
            }

            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen('user_init_successed', $this->_user);
            return true;
        }
        else
        {
            $this->setError(AdminUserException::getErrorMsg(AdminUserException::E_NOT_EXISTS));
            return false;
        }
    }

    /**
     * 后台添加用户
     *
     * @param string $name      用户名
     * @param string $password  密码
     * @return boolean
     */
    public function register($name, $password, $inviteCode)
    {
        // 生成盐值
        $salt = Random::alnum();
        $nowTime = Request::server('REQUEST_TIME');
        $params = [
            'username'    => $name,
            'invite_code' => $inviteCode,
            'password'    => $this->getEncryptPassword($password, $salt),
            'salt'        => $salt,
            'status'      => AdminUser::STATUS_ENABLE,
            'create_time' => $nowTime,
            'update_time' => $nowTime,
        ];

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try
        {
            $user = AdminUser::create($params);
            Db::commit();

            return $user->admin_user_id;
        }
        catch (Exception $e)
        {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }
    }

    /**
     * 用户登录
     *
     * @param string   $name     用户名
     * @param string   $password 密码
     * @return boolean
     */
    public function login($name, $password)
    {
        $user = AdminUser::get(['username' => $name]);
        if (!$user)
        {
            $this->setError(AdminUserException::getErrorMsg(AdminUserException::E_EXISTS));
            return false;
        }

        if ($user->status != AdminUser::STATUS_ENABLE)
        {
            $this->setError(AdminUserException::getErrorMsg(AdminUserException::E_NOT_ENABLE));
            Hook::listen('user_login_failure', $user);
            return false;
        }

        if (!$this->checkEncryptPassword($user->password, $password, $user->salt))
        {
            $this->setError(AdminUserException::getErrorMsg(AdminUserException::E_PASSWORD));
            Hook::listen('user_login_failure', $user);
            return false;
        }

        // 直接登录会员
        $this->direct($user, 'object');
        return $user->admin_user_id;
    }

    /**
     * 注销
     *
     * @return boolean
     */
    public function logout()
    {
        //设置登录标识
        $this->_logined = false;
        //删除Token
        Token::delete($this->_token);
        //注销用户
        $this->_user = NULL;

        return true;
    }

    /**
     * 直接登录账号
     * @param int  $userId  用户ID或者用户对象
     * @return boolean
     */
    public function direct($userId, $type = 'id')
    {
        if ($type == 'id') {
            $user = AdminUser::get($userId);
        } else {
            $user = $userId;
        }

        if ($user)
        {
            // 记录本次登录的IP和时间
            $user->last_login_ip      = Request::ip();
            $user->login_time         = time();
            $user->login_failure_date = 0;
            $user->login_failure      = 0;
            $user->save();

            $this->_user = $user;
            $this->_logined = true;

            // 存入session
            Session::set('admin_id', $user->admin_user_id);
            Session::set('admin_user_name', $user->username);
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 检测是否是否有对应权限
     * @param string $path      控制器/方法
     * @param string $module    模块 默认为当前模块
     * @return boolean
     */
    public function check($path = NULL, $module = NULL)
    {
        if (!$this->_logined) {
            return false;
        }
        $group = AuthGroupAccess::where(['uid'=>Session::get('admin_id')])->find()->toArray();

        $groupAuth = AuthGroup::where('group_id',$group['group_id'])->find()->toArray();
        if($groupAuth['is_super_management'] == 1 ) {
            $this->rules = AuthRule::select()->toArray();
            return true;
        }

        $ruleList = $this->getRuleList(Session::get('admin_id'));
        $rules = [];
        foreach ($ruleList as $k => $v)
        {
            $rules[] = strtolower($v['name']);
        }
        $url = ($module ? $module :  $this->getRequestUri());
        return in_array($url, $rules) ? true : false;

    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined)
        {
            return true;
        }

        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取会员组别规则列表
     * @return array
     */
    public function getRuleList($userId)
    {
        if ($this->rules){
            return $this->rules;
        }
        $group = AuthGroupAccess::where(['uid'=>$userId])->find()->toArray();

        $groupAuth = AuthGroup::where('group_id',$group['group_id'])->find()->toArray();
        if($groupAuth['is_super_management'] == 1 ) {
            $this->rules = AuthRule::select()->toArray();
        }else{
            $rules = explode(',',$groupAuth['rules']);
            $this->rules = AuthRule::where('id', 'in', $rules)->where('status','1')->field('id,pid,name,title,ismenu')->select();
        }

        return $this->rules;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password  密码
     * @param string $salt      密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = ''): string
    {
        return password_hash(hash_hmac('sha512', md5($password) . $salt, $salt), PASSWORD_BCRYPT);
    }

    /**
     * 验证密码
     * @param  string $hashSign 密文
     * @param  string $password 密码
     * @param  string $salt     密码盐
     * @return boolean
     */
    public function checkEncryptPassword($hashSign, $password, $salt): bool
    {
        return password_verify(hash_hmac('sha512', md5($password) . $salt, $salt), $hashSign);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [], $actionname = '')
    {
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr)
        {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        if (!$actionname) {
            $actionname = Request::action();
        }

        // 是否存在
        if (in_array(strtolower($actionname), $arr) || in_array('*', $arr))
        {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }



    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? $this->_error : '';
    }
}
