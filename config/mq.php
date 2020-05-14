<?php
/**
 * RabbitMQ配置
 */
return [
    'user_upgrade' => [
        'vhost'    => 'VHOST_SDRPAY',
        'queue'    => 'QUEUE_USER_UPGRADE',
        'routekey' => 'ROUTEKEY_USER_UPGRADE',
        'exchange' => 'EXCHANGE_USER'
    ],
    'eth_order' => [
        'vhost'    => 'VHOST_SDRPAY',
        'queue'    => 'QUEUE_ETH_ORDER',
        'routekey' => 'ROUTEKEY_ETH_ORDER',
        'exchange' => 'EXCHANGE_ORDER'
    ],
    'exchange_order' => [
        'vhost'    => 'VHOST_SDRPAY',
        'queue'    => 'QUEUE_EXCHANGE_ORDER',
        'routekey' => 'ROUTEKEY_EXCHANGE_ORDER',
        'exchange' => 'EXCHANGE_ORDER'
    ],
];