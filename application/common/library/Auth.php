<?php

/**
 * 用户鉴权
 * @author bcxgame
 * @version 1.0
 */

namespace app\common\library;

use app\common\model\User;
use app\common\exception\User as UserException;
use app\common\model\UserWallet;
use random\Random;
use token\Token;
use Exception;

class Auth
{

    protected static $instance = null;
    protected $_error = '';
    public $_code_error = '';
    protected $_logined = FALSE;
    protected $_user = NULL;
    protected $_token = '';
    //Token默认有效时长
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $options = [];
    protected $allowFields = ['user_id', 'mobile', 'status', 'device'];

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
     *
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
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
        if ($user_id > 0) {
            $user_id = intval($user_id / 520);
            $user = User::getByUserId($user_id);
            if (!$user) {
                $this->setError(UserException::getErrorMsg(UserException::E_NOT_EXISTS));
                return false;
            }

            if ($user['status'] != User::STATUS_ENABLE) {
                $this->setError(UserException::getErrorMsg(UserException::E_NOT_ENABLE));
                return false;
            }

            if (!empty($user['token']) && $user['token'] != $token) {
                $this->setError(UserException::getErrorMsg(UserException::E_OTHER_LOGIN));
                return false;
            }

            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen("user_init_successed", $this->_user);
            return true;
        }
        else {
            $this->setError(UserException::getErrorMsg(UserException::E_NOT_EXISTS));
            return false;
        }
    }

    /**
     * 注册用户
     *
     * @param string $email | 邮箱地址
     * @param string $password  密码
     * @param array  $extend    扩展参数
     * @return boolean
     */
    public function register($email, $password, $extend = [])
    {
        $salt = Random::alnum();
        //设置Token
        $token = Random::uuid();
        $nowTime = time();

        $params = [
            'email'       => $email,
            'password'    => $this->getEncryptPassword($password, $salt),
            'salt'        => $salt,
            'group'       => User::GROUP_GENERAL,
            'type'        => User::TYPE_REAL,
            'status'      => User::STATUS_ENABLE,
            'token'       => $token,
            'create_time' => $nowTime,
            'update_time' => $nowTime,
        ];

        //$params = array_merge($params, $extend);
        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $inviteCode = $extend['invite_code'];

            $userInfo = [];
            //关联推荐码账号
            if ($inviteCode == User::TOP_CODE) {
                $userInfo['admin_id'] = model('admin/AdminUser')->getCustomerService();
                $userInfo['user_id'] = 0;
            } else {
                $userInfo = User::where(['invite_code' => $inviteCode, 'group' => User::GROUP_AGENT])->find();
                if ($userInfo) {
                    $userInfo = $userInfo->toArray();
                }
            }

            if (empty($userInfo)) {
                $this->_code_error = UserException::E_CODE;
                return false;
            }

            $params['admin_id'] = $userInfo['admin_id'];
            $params['agent_id'] = $userInfo['user_id'];

            $user = User::create($params);
            if ($user) {
                $relationId = $this->relationUser($user);
                if (!$relationId) {
                    Db::rollback();
                    return false;
                }

                $inventedUser = $user->toArray();
                if (!empty($userInfo['user_id'])) {
                    $inventedUser['group']    = User::GROUP_GENERAL;
                    $inventedUser['agent_id'] = User::where(['email' => $userInfo['email'], 'type' => User::TYPE_VIRTUAL])->value('user_id');
                }

                $inventedRes = $this->createInventedUser($inventedUser);
                if (!$inventedRes) {
                    Db::rollback();
                    return false;
                }

                Db::commit();
            }

            // 此时的Model中只包含部分数据
            $this->_user = User::getByUserId($user->user_id);
            $this->_logined = true;

            //注册成功的事件
            Hook::listen("user_register_successed", $token);
            return $user->user_id;
        }
        catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 关联其他表
     */
    public function relationUser($user, $isInventedUser = false)
    {
        $nowTime = time();
        $walletData = [
            'user_id'          => $user->user_id,
            'btc_address'      => $isInventedUser ? '' : Wallet::newAddress('btc'),
            'eth_address'      => $isInventedUser ? '' : Wallet::newAddress('eth'),
            'total_amount'     => $isInventedUser ? 10000 : 0,
            'available_amount' => $isInventedUser ? 10000 : 0,
            'freeze'           => 0,
            'create_time'      => $nowTime,
            'update_time'      => $nowTime,
        ];

        $walletId = UserWallet::create($walletData);
        if (!$walletId->wallet_id) {
            return false;
        }

        $configData = [
            'user_id'  => $user->user_id,
            'recharge' => $isInventedUser ? UserConfig::RECHARGE_DISABLE : UserConfig::RECHARGE_ENABLE,
            'cash'     => $isInventedUser ? UserConfig::CASH_DISABLE : UserConfig::CASH_ENABLE,
        ];

        $configId = UserConfig::create($configData);
        if (!$configId->config_id) {
            return false;
        }

        $extendId = UserExtend::create(['user_id' => $user->user_id]);
        if (!$extendId->extend_id) {
            return false;
        }

        $type = $isInventedUser ? User::TYPE_VIRTUAL : User::TYPE_REAL;
        model('common/User')->userFid($user->agent_id, $type);
        $topIds = model('common/User')->userpId;

        if (!empty($topIds)) {
            $topIds = implode(',', $topIds);
        }

        $statData = [
            'user_id'           => $user->user_id,
            'total_profit'      => 0,
            'total_flow'        => 0,
            'unsettled_amount'  => 0,
            'popularize_amount' => 0,
            'parent_ids'        => $topIds,
        ];

        $statId = UserStatistic::create($statData);
        if (!$statId->statistic_id) {
            return false;
        }

        return true;
    }

    /**
     * 创建虚拟用户
     */
    public function createInventedUser($user)
    {
        $data = json_decode(json_encode($user), true);
        unset($data['user_id']);
        $data['type'] = User::TYPE_VIRTUAL;

        $inventedUser = User::create($data);
        if ($inventedUser->user_id) {
            return $this->relationUser($inventedUser, true);
        } else {
            return false;
        }
    }

    /**
     * 用户登录
     *
     * @param string   $account    账号,用户名、邮箱、手机号
     * @param string   $password   密码
     * @param boolean  $invented   是否切换用户类型登录
     * @return boolean
     */
    public function login($account, $password, $invented = false, $userType = User::TYPE_REAL)
    {
        $field = Validate::is($account, 'email') ? 'email' : (Validate::regex($account, '/^1\d{10}$/') ? 'mobile' : 'username');
        $user = User::where([$field => $account, 'type' => $userType])->find();
        if (!$user) {
            $this->setError(UserException::getErrorMsg(UserException::E_EXISTS));
            return false;
        }

        if ($user->status != User::STATUS_ENABLE) {
            $this->setError(UserException::getErrorMsg(UserException::E_NOT_ENABLE));
            Hook::listen("user_login_failure", $user);
            return false;
        }

        // 正常用户登录验证密码
        if (!$invented) {
            if (!$this->checkEncryptPassword($user->password, $password, $user->salt)) {
                $this->setError(UserException::getErrorMsg(UserException::E_PASSWORD));
                Hook::listen("user_login_failure", $user);
                return false;
            }
        }

        //直接登录会员
        $this->direct($user, 'object');
        return $user->user_id;
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
        //注销成功的事件
        Hook::listen("user_logout_successed", $this->_user);

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
            $user = User::getByUserId($userId);
        }
        else {
            $user = $userId;
        }

        if ($user) {
            $token = Random::uuid();
            // 记录本次登录的IP和时间
            $user->last_login_ip = Request::ip();
            $user->login_time = time();
            $user->login_failure_date = 0;
            $user->login_failure = 0;
            $user->token = $token;
            $user->save();

            $this->_user = $user;
            $this->_logined = true;

            //登录成功的事件
            Hook::listen("user_login_successed", $token);
            return true;
        }
        else {
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

        // 暂无权限验证
        return true;

        /*
          $ruleList = $this->getRuleList();
          $rules = [];
          foreach ($ruleList as $k => $v)
          {
          $rules[] = $v['name'];
          }
          $url = ($module ? $module : Request::module()) . '/' . (is_null($path) ? $this->getRequestUri() : $path);
          $url = strtolower(str_replace('.', '/', $url));
          return in_array($url, $rules) ? true : false;
         */
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
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
    public function getRuleList()
    {
        if ($this->rules)
            return $this->rules;
        $group = $this->_user->group;
        if (!$group) {
            return [];
        }

        $rules = explode(',', $group->rules);
        $this->rules = UserRule::where('status', 'normal')->where('id', 'in', $rules)->field('id,pid,name,title,ismenu')->select();
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
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        if (!$actionname) {
            $actionname = Request::action();
        }

        // 是否存在
        if (in_array(strtolower($actionname), $arr) || in_array('*', $arr)) {
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
