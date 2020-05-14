<?php
/**
 * 对象创建类[保持单一入口]
 *
 * @author   amd
 * @version  1.0
 */
namespace app\common\traits;
use think\facade\Log;
use App;
use think\facade\Config;

trait Instance
{
	/**
     * @var array 缓存平台实例
     */
    public static $subclass = [];

    /**
     * @var object 操作句柄
     */
    public static $handler = NULL;

    /**
     * 当前实例
     * @var null
     */
    protected static $instance = null;

    /**
     * 获取单例
     * @return object
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance) || !(self::$instance instanceof static)) {
            if (!empty($options)) {
                self::$instance = new static($options);
            } else {
                self::$instance = new static;
            }
        }

        return self::$instance;
    }

    /**
     * 连接驱动
     * @access public
     * @param string $name    平台名称[支持命名空间和平台名称写法]
     * @param array  $options 平台类初始化参数
     */
    public static function connect(string $name, array $options = [])
    {
        if (!isset(self::$subclass[$name]))
        {
            // 命名空间小写，类名首字母大写
            $class = (false === strpos($name, '\\')) ? static::createClass($name) : $name;

            // 记录初始化信息
            Config::get('app.app_debug') && Log::record('[ Make ] INIT ' . $name, 'info');
            if (!empty($options)) {
                self::$subclass[$name] = new $class($options);
            } else {
                self::$subclass[$name] = new $class();
            }
        }

        return self::$subclass[$name];
    }

    /**
     * 构造子类对象
     */
    public static function make(string $name, array $options = [])
    {
        self::$handler = self::connect($name, $options);
        return self::$handler;
    }

    /**
     * 获取对子类的支持
     */
    public function __call($method, $args)
    {
        // 调用缓存类型自己的方法
        if (method_exists(self::$handler, $method))
        {
            return call_user_func_array(array(self::$handler, $method), $args);
        }
        else
        {
            throw new \BadMethodCallException('Could not find ' . get_class(self::$handler) . 'method ' . $method);
            return;
        }
    }

    /**
     * 获取驱动属性
     */
    public function __get($name)
    {
        return self::$handler->$name;
    }

    /**
     * 设置驱动属性
     */
    public function __set($name, $value)
    {
        return self::$handler->$name = $value;
    }
}
