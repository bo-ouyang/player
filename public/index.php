<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;
// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 获取sessionid
if(!empty($_SERVER['Bcxgame-Id'])) {
	// App端传递sessionid,微信端不传
	session_id($_SERVER['Bcxgame-Id']);
}
// 执行应用并响应
Container::get('app')->run()->send();
