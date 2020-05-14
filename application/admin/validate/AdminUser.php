<?php
namespace app\admin\validate;
use think\Validate;

class AdminUser extends Validate
{
    protected $rule = [
        'username'      => 'require|min:2|max:20',
        'password'      => 'require|min:6|max:20',
        'repassword'    => 'confirm:password',
        'salt'          => 'require|length:6',
        'login_failure' => 'between:0,5',
        'status'        => 'require|in:1,2',
        'group_id'      => 'require',
        'admin_user_id' => 'require',
        'mobile' => [
            'require',
            'max' => 11,
            'regex' => '/^((13[0-9])|(14[5,7])|(15[0-3,5-9])|16[6]|(17[0,3,5-8])|(18[0-9])|19[89])\d{8}$/',
        ],
    ];

    protected $field = [
        'username'      => '用户名',
        'password'      => '密码',
        'salt'          => '密码盐值',
        'login_failure' => '登录失败次数',
        'status'        => '状态',
        'login_time'    => '最后登录时间',
        'last_login_ip' => '最后登录IP',
        'create_time'   => '创建时间',
        'update_time'   => '修改时间',
        'invite_code'   => '邀请码',
        'admin_user_id' => '管理员id'
    ];

    protected $message = [
        'username.require'      => '用户名不能为空',
        'username.max'          => '用户名长度 2-20 位之间',
        'username.min'          => '用户名长度 2-20 位之间',
        'password.require'      => '密码不能为空',
        'password.min'          => '密码长度 6-20 位之间',
        'password.max'          => '密码长度 6-20 位之间',
        'salt.require'          => '盐值不能为空',
        'salt.length'           => '盐值长度不等于6个字符',
        'login_failure.between' => '登录失败次数超过5次',
        'status.in'             => '状态异常',
        'status.require'        => '状态不能为空',
        'admin_user_id.require' => '管理员id不能为空',        
        'invite_code.require'   => '邀请码不能为空',
    ];

    protected $scene = [
        'register' => ['username', 'password','invite_code'],
        'login'    => ['username', 'password'],
        'update'   => ['username','admin_user_id'],
        'delete'   => ['admin_user_id'],
        'addAdmin' => ['username', 'mobile', 'password', 'status'],
        'editAdmin' => ['admin_user_id','username', 'mobile', 'password', 'status'],
    ];
}
