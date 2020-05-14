<?php
return [
	// +----------------------------------------------------------------------
	// | 数据库设置
	// +----------------------------------------------------------------------
	'database' => [
		// 数据库类型
		'type'            => 'mysql',
		// 服务器地址
		'hostname'        => '127.0.0.1',
		// 数据库名
		//'database'        => 'ooozvwxz',
		// 用户名
		//'username'        => 'ooozvwxz',
		// 密码
		//'password'        => '9kgvub7Ms64mVD9g',
		// 数据库名
		'database'        => 'player',
		// 用户名
		'username'        => 'player',
		// 密码
		'password'        => '123456',
		//'password'        => 'G9w#3J&t$ou&6%v$L8#b&5h',
		// 端口
		'hostport'        => '3306',
		// 连接dsn
		'dsn'             => '',
		// 数据库连接参数
		'params'          => [],
		// 数据库编码默认采用utf8
		'charset'         => 'utf8',
		// 数据库表前缀
		'prefix'          => 'one_',
		// 数据库调试模式
		'debug'           => false,
		// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
		'deploy'          => 0,
		// 数据库读写是否分离 主从式有效
		'rw_separate'     => false,
		// 读写分离后 主服务器数量
		'master_num'      => 1,
		// 指定从服务器序号
		'slave_no'        => '',
		// 自动读取主库数据
		'read_master'     => false,
		// 是否严格检查字段是否存在
		'fields_strict'   => true,
		// 数据集返回类型
		'resultset_type'  => 'array',
		// 自动写入时间戳字段
		'auto_timestamp'  => false,
		// 时间字段取出后的默认时间格式
		'datetime_format' => false,
		// 是否需要进行SQL性能分析
		'sql_explain'     => false,
		// Builder类
		'builder'         => '',
		// Query类
		'query'           => '\\think\\db\\Query',
		// 是否需要断线重连
		'break_reconnect' => true,
		// 断线标识字符串
		'break_match_str' => [],
	],
	// +----------------------------------------------------------------------
	// | Token设置
	// +----------------------------------------------------------------------
	'token'                  => [
		// 驱动方式
		'type'     => 'Mysql',
		// 缓存前缀
		'key'      => 'i6d8o32wh8fvs1fvdpwygm',
		// 加密方式
		'hashalgo' => 'ripemd160',
		// 缓存有效期 0表示永久缓存
		'expire'   => 0,
		// 表名
		'table'    => 'user_token',
	],
	// +----------------------------------------------------------------------
	// | Node地址[查询充值记录]
	// +----------------------------------------------------------------------
	'node_addr' => 'http://127.0.0.1:5023/',
	// +----------------------------------------------------------------------
	// | 队列配置
	// +----------------------------------------------------------------------
	'mq_vhost' => [
		'VHOST_SDRPAY' => [
			'172.31.129.160',//ip
			'sdr_pay',//user
			'qJHQ5XHMqNQ4kJrw',//pwd
			'5672',//port
			'VHOST_SDRPAY'//vhost
		]
	],
	//0xd4D31E0ef05071C1482391E12033CE1E5Cab60f5
	//0xfCD028Ae2A9c75e009810cC28B21030a39a17EC3

	// 多媒体域名
	'media_domain' => 'www.' . SITE_TOP_DOMAIN,
	// 智能合约地址
	'contract_address' =>   '0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593',//静态
	'token_address' =>      '0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593',
	'egg_address' =>        '0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593',  //彩蛋
	// 智能合约地址
	// 'contract_address' => '0x773e04bB1AB9A2244431170D9bb9Caf2EE0F4d98',//静态
	///'token_address' => '0x773e04bB1AB9A2244431170D9bb9Caf2EE0F4d98',
	//'egg_address' => '0x773e04bB1AB9A2244431170D9bb9Caf2EE0F4d98'  //彩蛋
];
