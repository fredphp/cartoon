<?php

return [
    [
        'name'    => 'daily',
        'title'   => '快速收录',
        'type'    => 'array',
        'content' => [
        ],
        'value'   => [
            'site'  => '',
            'token' => '',
        ],
        'rule'    => '',
        'msg'     => '',
        'tip'     => '请前往百度站长平台获取',
        'ok'      => '',
        'extend'  => ''
    ],
    [
        'name'    => 'normal',
        'title'   => '普通收录',
        'type'    => 'array',
        'content' => [
        ],
        'value'   => [
            'site'  => '',
            'token' => '',
        ],
        'rule'    => '',
        'msg'     => '',
        'tip'     => '请前往百度站长平台获取',
        'ok'      => '',
        'extend'  => ''
    ],
    [
        'name'    => 'status',
        'title'   => '推送状态',
        'type'    => 'checkbox',
        'content' =>
            [
                'normal' => '普通收录',
                'daily'  => '快速收录',
            ],
        'value'   => 'normal,daily',
        'rule'    => '',
        'msg'     => '',
        'tip'     => '',
        'ok'      => '',
        'extend'  => '',
    ],
    [
        'name'    => '__tips__',
        'title'   => '温馨提示',
        'type'    => 'string',
        'content' =>
            [],
        'value'   => '1.普通收录请前往<a href="https://ziyuan.baidu.com/linksubmit/index" target="_blank">百度站长平台(普通收录)</a>获取Site和Token<br>
                      2.快速收录请前往<a href="https://ziyuan.baidu.com/dailysubmit/index" target="_blank">百度站长平台(快速收录)</a>获取Site和Token',
        'rule'    => '',
        'msg'     => '',
        'tip'     => '',
        'ok'      => '',
        'extend'  => '',
    ]
];
