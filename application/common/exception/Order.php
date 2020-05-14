<?php
/**
 * 订单异常
 */
namespace app\common\exception;

class Order extends Base
{
	const E_CONTRACT_ADDRESS_ERROR = '12001';
	const E_NOT_EXISTS             = '12002';
	const E_CASH_ERROR             = '12003';
    const E_MIN_INVEST             = '12004';
    const E_CANNOT_CASH            = '12005';
    const E_ADDRESS_EMPTY          = '12006';
    const E_ADDRESS_HASH_ERROR     = '12007';
    const E_INVALID_CODE           = '12008';
    const E_ORDER_STATUS_ERROR     = '12009';
    const E_ORDER_UNMET_QUOTA      = '12010';
    const E_TOKEN_ORDER_LIMIT      = '12011';
	const E_AMOUNT_TYPE_ERR        = '12012';
    public static $messageList = [
		self::E_CONTRACT_ADDRESS_ERROR => 'contract address error',
		self::E_NOT_EXISTS             => 'order not exists',
		self::E_CASH_ERROR             => 'order withdrawal failed',
        self::E_MIN_INVEST             => 'the quota must be greater than 1 ETH',
        self::E_CANNOT_CASH            => 'order cannot be withdrawn',
        self::E_ADDRESS_EMPTY          => 'order address empty',
        self::E_ADDRESS_HASH_ERROR     => 'address or hash error',
        self::E_INVALID_CODE           => 'invalid invitation code',
        self::E_ORDER_STATUS_ERROR     => 'order status error',
        self::E_ORDER_UNMET_QUOTA      => 'order unmet quota',
        self::E_TOKEN_ORDER_LIMIT      => 'token order limit',
	    self::E_AMOUNT_TYPE_ERR        => 'amount must be int',
    ];
}
