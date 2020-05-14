<?php
/**
 * 用户异常
 */
namespace app\common\exception;

class User extends Base
{
    const E_NOT_EXISTS  = '11001';
    const E_NO_PURVIEW  = '11002';
    const E_NOT_ENABLE  = '11003';
    const E_EXISTS      = '11004';
    const E_INVITE_CODE = '11005';
    public static $messageList = [
        self::E_NO_PURVIEW  => '没有权限,联系管理员',
        self::E_NOT_EXISTS  => 'user does not exist',
        self::E_NOT_ENABLE  => 'user is frozen',
        self::E_EXISTS      => 'user already exists',
        self::E_INVITE_CODE => 'invitation code not exists',
    ];
}
