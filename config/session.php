<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
    'id'             => '',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // SESSION 前缀
    'prefix'         => 'one',
    // 驱动方式 支持redis memcache memcached
    'type'           => '',
    // 是否自动开启 SESSION
    'auto_start'     => true,
    // 重置session_name
    // 'name'           => 'PHPSESSID',
    // session作用域
    'domain'         => '.' . SITE_TOP_DOMAIN,
    // 只允许通过HTTP协议访问
    'httponly'       => true,
    // 是否仅通过安全连接发送
    'secure'         => IS_HTTPS,
];
