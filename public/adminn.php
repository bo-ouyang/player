<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 后台入口文件 ]
// 使用此文件可以达到隐藏admin模块的效果
// 建议将admin.php改成其它任意的文件名，同时修改config.php中的'deny_module_list',把admin模块也添加进去
// 定义应用目录
namespace think;

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象
if (is_file(__DIR__ . '/../env_status.php')) {
    require_once(__DIR__ . '/../env_status.php');
}

// 获取sessionid
if(!empty($_SERVER['Bcxgame-Id'])) {
	// App端传递sessionid,微信端不传
	session_id($_SERVER['Bcxgame-Id']);
}

// 执行应用并响应
Container::get('app')->bind('admin')->run()->send();