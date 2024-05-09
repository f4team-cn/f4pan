<?php

return [
    // 默认使用的数据库连接配置
    'default'     => 'data',
    // 数据库连接配置信息
    'connections' => [
        'data' => [
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'password'   => '',
            'select'     => 0,
            'timeout'    => 3,
            'options'    => [],
            'persistent' => false,
        ],
        'poolDemo' => [
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'password'   => '',
            'select'     => 0,
            'timeout'    => 3,
            'prefix'     => null,
            'options'    => [],
            'persistent' => false,
            // 连接池设置
            'pool'       => [
                'min_active' => 0,
                'max_active' => 10,
                'max_wait_time' => 5,
                'max_idle_time' => 30,
                'idle_check_interval' => 60,
            ],
            // 快速释放
            'fast_freed' => false,
            // 自动丢弃
            'auto_discard' => false,
        ],
    ],
];
