<?php
/**
 * 平台抽象基类
 * @version  1.0
 */
namespace platform\driver;

abstract class Driver
{
    /**
     * Api接口地址
     * @var string
     */
    private $api = '';

    /**
     * 行情
     * @access public
     * @return mixed
     */
    abstract public function ticker(...$data);

    /**
     * K线
     * @access public
     * @return mixed
     */
    abstract public function kline(...$data);
}
