<?php
/**
 * 奖励制度
 */
return [
	// 最低投资
	'min_invest' => 1,
	// 活期每日利息
	'current_interest' => 0.005,
	// 定期利息
	'regular_interest' => [
		'one_month'   => 0.007,// 定期一个月
		'three_month' => 0.009,// 定期三个月
		'six_month'   => 0.011,// 定期六个月
		'year'        => 0.013,// 定期12个月
	],
	// 基金收益利息
	'fund_interest' => [
		// 直推人数1[即:第一代只有1个人]
		'1' => [
			'1' => 0.5,
		],
		// 直推人数2[即:第一代只有2个人]
		'2' => [
			'1' => 0.5,
			'2' => 0.25,
		],
		// 直推人数3[即:第一代只有3个人]
		'3' => [
			'1' => 0.5,
			'2' => 0.25,
			'3' => 0.125,
		],
		// 直推人数4[即:第一代只有4个人]
		'4' => [
			'1' => 0.5,
			'2' => 0.25,
			'3' => 0.125,
			'4' => 0.0625,
		],
		// 直推人数5[即:第一代只有5个人]
		'5' => [
			'1' => 0.5,
			'2' => 0.25,
			'3' => 0.125,
			'4' => 0.0625,
			'5' => 0.03125,
		],
		// 直推人数6[即:第一代只有6个人]
		'6' => [
			'1' => 0.5,
			'2' => 0.25,
			'3' => 0.125,
			'4' => 0.0625,
			'5' => 0.03125,
			'6' => 0.015625,
		],
		// 直推人数7[即:第一代大于等于7个人]
		'7' => [
			'1' => 0.5,
			'2' => 0.25,
			'3' => 0.125,
			'4' => 0.0625,
			'5' => 0.03125,
			'6' => 0.015625,
			'7' => 0.01,
		],
	],

    //基金收益利息
    'fund_interest_new'=>[
        //第几代
        '1' =>[
            'people_num' => 1, //需要直推的人数
            'profit' => 0.5, //收益
        ],
        '2' =>[
            'people_num' => 2,
            'profit' => 0.25,
        ],
        '3' =>[
            'people_num' => 3,
            'profit' => 0.125,
        ],
        '4' =>[
            'people_num' => 4,
            'profit' => 0.0625,
        ],
        '5' =>[
            'people_num' => 5,
            'profit' => 0.03125,
        ],
        '6' =>[
            'people_num' => 6,
            'profit' => 0.015625,
        ],
        '7' =>[
            'people_num' => 7,
            'profit' => 0.01,
        ]
    ],

	// 团队收益
	'team_income' => [
	    '0' => [
	        '1' => 0.1, //一代
            '2' => 0.05 //二代
        ], //平级
        '1' => 0.01, //顾问
        '2' => 0.03,
        '3' => 0.05,
        '4' => 0.07,
        '5' => 0.09,
	],

    'upgrade_amount' => [
        'amount' => 500,
        'number' => 2
    ],

    'grade' => [
        'adviser'   => 1,
        'manager'   => 2,
        'executive' => 3,
        'planner'   => 4,
        'director'  => 5
    ]
];