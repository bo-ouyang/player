<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
	'upgrade'  => 'app\script\command\Upgrade',
	'order'    => 'app\script\command\Order',
    'interest' => 'app\script\command\OrderInterest',
    'team'     => 'app\script\command\TeamReward',
    'redress'  => 'app\script\command\Redress',



	'test' => 'app\script\command\Test',
	'orderfake' => 'app\script\command\OrderFake',
	'upinvest' => 'app\script\command\UpUserInvest',


	'ACGG' => 'app\script\command\ACGG',
];
