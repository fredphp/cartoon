<?php

return [
    'autoload' => false,
    'hooks' => [
        'baidupush' => [
            'baidupush',
        ],
        'app_init' => [
            'banip',
            'log',
        ],
        'epay_config_init' => [
            'epay',
        ],
        'addon_action_begin' => [
            'epay',
        ],
        'action_begin' => [
            'epay',
            'third',
        ],
        'response_send' => [
            'loginbgindex',
        ],
        'index_login_init' => [
            'loginbgindex',
        ],
        'user_delete_successed' => [
            'third',
        ],
        'user_logout_successed' => [
            'third',
        ],
        'module_init' => [
            'third',
        ],
        'config_init' => [
            'third',
            'ueditor',
        ],
        'view_filter' => [
            'third',
        ],
    ],
    'route' => [
        '/third$' => 'third/index/index',
        '/third/connect/[:platform]' => 'third/index/connect',
        '/third/callback/[:platform]' => 'third/index/callback',
        '/third/bind/[:platform]' => 'third/index/bind',
        '/third/unbind/[:platform]' => 'third/index/unbind',
    ],
    'priority' => [],
    'domain' => '',
];
