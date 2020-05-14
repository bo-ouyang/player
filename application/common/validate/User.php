<?php
/**
 * 用户验证类
 */
namespace app\common\validate;
use think\Validate;

class User extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'invite_code' => 'require|length:6',
        'address'     => 'require',
    ];

    /**
     * 字段说明
     * @var array
     */
    protected $field = [
        'invite_code' => '邀请码',
        'address'     => '转币地址',
    ];

    /**
     * 错误提示信息
     * @var array
     */
    protected $message = [
        'invite_code.require' => '{%invite_code_require}',
        'invite_code.length'  => '{%invite_code_length}',
        'address.require'     => '{%address_require}',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [

    ];
}
