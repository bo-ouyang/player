<?php
/**
 * 设置当前环境变量
 */
define('SITE_ENVIROMENT', 'productive'); //developer,devcloud,sandbox,sandbox_t,productive

// 是否开启https
define('IS_HTTPS', true);
require_once(dirname(__FILE__) . '/domain.php');
