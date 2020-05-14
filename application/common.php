<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Request;
use think\facade\Config;
use think\facade\Cache;

// 应用公共文件
/**
 * /**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param string $url 请求URL
 * @param array $params 请求参数
 * @param string $method 请求方法GET/POST
 * @param array $header
 * @return array  $data   响应数据
 * @throws \Exception
 */
function curl($url, $params = [], $method = 'GET', $header = [])
{
    $opts = [
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header
    ];

    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            if (!empty($params)) {
                $url .=  '?' . http_build_query($params);
            }
            $opts[CURLOPT_URL] = $url;
            break;
        case 'POST':
            //判断是否传输文件
            //$params                   = $params;
            $opts[CURLOPT_URL]        = $url;
            $opts[CURLOPT_POST]       = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new \Exception('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        throw new \Exception('请求发生错误：' . $error);
    }
    return $data;
}

/**
 * 实例化服务类
 * service('common/Order'); 实例化common模块的Order服务类
 * @param  string  $name 格式 [模块名]/接口名
 */
function service($name, $layer = '')
{
    if (!$name) {
        return false;
    }

    static $_service = [];

    $array     = explode('/', $name);
    $classname = array_pop($array);
    $module    = $array ? array_pop($array) : 'common'; // 默认是common分组下的service
    $class     = '\\app\\' . $module . '\\service\\' . $classname . ucfirst($layer);
    if (isset($_service[$class]) && is_object($_service[$class])) {
        return $_service[$class];
    }

    if (class_exists($class)) {
        $_service[$class] = new $class();
        return $_service[$class];
    }

    return false;
}

/**
 * 当前进程数限制
 * @param string $search 进程名称
 * @param int    $maxLimit 最大限制数[默认2]
 * @return boolean
 */
function ps_qty_limit($search, $maxLimit = 2): bool
{
    $result = '';
    exec("ps aux | grep '" . $search . "' | grep -v grep | awk '{ print  }' | head -50", $result);
    if (count($result) - 1 >= $maxLimit) {
        return false;
    } else {
        return true;
    }
}

/**
 * 删除文件夹
 * @param string $dirname 目录
 * @param bool $withself 是否删除自身
 * @return boolean
 */
function rmdirs($dirname, $withself = true)
{
    if (!is_dir($dirname))
        return false;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    if ($withself) {
        @rmdir($dirname);
    }
    return true;
}

/**
 * 复制文件夹
 * @param string $source 源文件夹
 * @param string $dest 目标文件夹
 */
function copydirs($source, $dest)
{
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    foreach (
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item
    ) {
        if ($item->isDir()) {
            $sontDir = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if (!is_dir($sontDir)) {
                mkdir($sontDir, 0755, true);
            }
        } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
    }
}

/**
 * 获取多媒体Url[包含域名]
 */
function medias_url($file): string
{
    if (empty($file)) {
        return $file;
    }

    $schema = Config::get('is_https') ? 'https://' : 'http://';
    return $schema . Config::get('app.media_domain') . $file;
}

/**
 * 生成订单号
 */
function generage_order_sn()
{
    $yCode = range('A', 'Z');
    $randIndex = array_rand($yCode, 1);
    $orderSn = $yCode[$randIndex] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));

    return $orderSn;
}

/**
 * 模型字段查询
 */
function model_field_query($model, $field, $page = 0, $sort = '')
{
    if (!$model || !$field) {
        return false;
    }

    $pageSize = Config::get('paginate.list_rows');
    $list = false;
    if (is_array($field)) {
        $firstKey = array_keys($field)[0];
        if ($firstKey != '0') {
            // ['gift_id' => 'name']
            $key = array_values($field)[0];
            $value = array_keys($field)[0];

            if ($sort) {
                $model = $model->order($sort);
            }

            $list = $page > 0 ? $model->page($page, $pageSize)->column($key, $value) : $model->column($key, $value);
        } else {
            // ['gift_id', 'name', 'icon']
            $model = $model->field($field);
            if ($sort) {
                $model = $model->order($sort);
            }

            $list = $page > 0 ? $model->page($page, $pageSize)->select() : $model->select();
        }
    } else {
        if (strpos($field, ',') === false) {
            // 'name'
            if ($sort) {
                $model = $model->order($sort);
            }

            $list = $page > 0 ? $model->page($page, $pageSize)->column($field) : $model->column($field);
        } else {
            // 'gift_id,name,icon'
            $model = $model->field($field);
            if ($sort) {
                $model = $model->order($sort);
            }

            $list = $page > 0 ? $model->page($page, $pageSize)->select() : $model->select();
        }
    }

    return $list;
}

/**
 * Log记录
 * @param string $message 输出信息
 * @return void
 */
function common_log($message, $exit = false)
{
    $niceMsg = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    if (!Request::isCli()) {
        $niceMsg .= '<br/>';
    }
    echo $niceMsg;

    if ($exit) {
        exit();
    }
}

/**
 * 抛出异常
 * throw_new();
 * throw_new(SystemException::class, SystemException::E_DEFAULT);
 * throw_new(SystemException::class, SystemException::E_DEFAULT, $msg = '', $cover = false);
 */
function throw_new($exception = '', $code = 0, $message = '', $cover = false)
{
    // 默认系统异常
    $exception = $exception ? '\\' . $exception : '\app\common\exception\System';
    $code = !empty($code) ? $code : \app\common\exception\System::E_DEFAULT;

    $message = $exception::getErrorMsg($code, $message, $cover);
    throw new $exception($message, $code);
}

/**
 * 随机字符串
 */
function GetRandStr($len = 4)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );

    $charsLen = count($chars) - 1;
    shuffle($chars);
    $output = "";
    for ($i=0; $i<$len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }

    return $output;
}

/**
 * 控制器错误输格式化方法
 * @param $code
 * @param $msg
 * @param string $data
 * @return array
 */
function error_return($code, $msg, $data = '')
{
    return ['status' => 'error', 'code' => $code, 'msg' => $msg, 'data' => $data];
}

/**
 * 控制器正常输格式化方法
 * @param $data
 * @param string $code
 * @param string $msg
 * @return array
 */
function success_return($data, $code = '', $msg = '')
{
    return ['status' => 'success', 'code' => $code, 'msg' => $msg, 'data' => $data];
}


/**
 * 创建目录
 */
function directory($dir)
{
   return is_dir($dir) or directory(dirname($dir)) and  mkdir($dir, 0777);
}


/**
 * 获取redis数据源对象
 * @return object
 */
function get_redis_obj()
{
    static $redisObj;
    // 动态配置参数

    if (is_null($redisObj) || !is_object($redisObj)) {
        $redisObj = Cache::store('redis')->handler();
    }

    if (Request::isCli()) {
        try {
            $result = $redisObj->ping();
            $exist = strpos($result, 'PONG');
            if ($exist === false) {
                exception('Redis timeout and reconnecting!');
            }
        } catch (\Exception $e) {
            // 错误上报
            $redisObj = Cache::store('redis')->handler();
        }
    }

    return $redisObj;
}

/**
 * 获取毫秒时间戳
 */
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 计算两个日期相差的天数
 */
function days_count($day1, $day2)
{
    // $day1 = "2018-07-25";
    // $day2 = "2018-08-04";
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }

    return ($second1 - $second2) / 86400;
}

/**
 * 判断字符串是否base64编码
 */
function is_base64($str)
{
    return $str == base64_encode(base64_decode($str)) ? true : false;
}

function listToTree(array $list, $pk = 'id', $pid = 'parent_id', $child = 'child', $root = 0) {
    $tree = [];
    $refer = [];
    foreach ($list as $key => $data) {
        $refer[$data[$pk]] = &$list[$key];
    }

    foreach ($list as $key => $data) {
        $parentId = $data[$pid];
        if ($root == $parentId) {
            $tree[$data[$pk]] = &$list[$key];
        } else {
            if (isset($refer[$parentId])) {
                $parent = &$refer[$parentId];
                //$parent[$child][$data[$pk]] = &$list[$key];
                $parent[$child][] = &$list[$key];
            }
        }
    }
    return $tree;
}

function OutLev($OrderAmount){
	if($OrderAmount>1&&$OrderAmount<=20){
		return 1;
	}elseif ($OrderAmount>21&&$OrderAmount<100){
		return 2;
	}elseif ($OrderAmount>=100){
		return 3;
	}
}
