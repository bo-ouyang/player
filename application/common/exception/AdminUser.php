<?php
/**
 * 后台管理员异常
 */
namespace app\common\exception;

class AdminUser extends Base
{
    const E_NOT_EXISTS       = '14001';
    const E_NOT_ENABLE       = '14002';
    const E_PASSWORD         = '14003';
    const E_NAME_EXISTS      = '14004';
    const E_ADD_FAIL         = '14005';
    const E_MAX_LOGIN        = '14006';
    const E_JWT_ERROR        = '14007';
    const E_LOGIN_INVALID    = '14008';
    const E_GROUP_NOT_EXISTS = '14009';
    const E_ADD_ADMIN_ERROR  = '14010';
    public static $messageList = [
        self::E_NOT_EXISTS       => '管理员不存在',
        self::E_NOT_ENABLE       => '管理员已禁用',
        self::E_PASSWORD         => '密码错误',
        self::E_NAME_EXISTS      => '管理员用户名已存在',
        self::E_ADD_FAIL         => '管理员添加失败',
        self::E_MAX_LOGIN        => '已超过当天最次登录次数',
        self::E_JWT_ERROR        => 'Token异常',
        self::E_LOGIN_INVALID    => '登录已失效，请重新登录',
        self::E_GROUP_NOT_EXISTS => '管理员组不存在',
        self::E_ADD_ADMIN_ERROR  => '管理员或经理人添加失败',
    ];
}