<?php
/**
 * 平台底层数据获取唯一入口
 *
 * @author   ame-option
 * @version  1.0
 */
namespace platform;

class Platform
{
    use \app\common\traits\Instance;

    /**
     * 构造子类方法
     */
    public static function createClass(string $name): string
    {
        return '\\platform\\driver\\' . strtolower($name) . '\\' . ucwords($name);
    }
}