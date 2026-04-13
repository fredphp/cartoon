<?php

return [
    [
        'name'    => 'classname',
        'title'   => '渲染文本框元素',
        'type'    => 'string',
        'content' => [],
        'value'   => '.editor',
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '用于对指定的元素渲染，一般情况下无需修改',
        'ok'      => '',
        'extend'  => '',
    ],
    [
        'name'    => 'baiduMapAk',
        'title'   => '百度地图ak',
        'type'    => 'string',
        'content' => [],
        'value'   => '',
        'rule'    => '',
        'msg'     => '',
        'tip'     => '需要设置百度地图api密钥（ak），否则地图无法使用',
        'ok'      => '',
        'extend'  => '',
    ],
    [
        'name' => 'catchRemoteImageEnable',
        'title' => '是否开启抓取远程图片',
        'type' => 'radio',
        'content' => [
            true => '开启',
            false => '不开启',
        ],
        'value' => false,
        'rule' => 'required',
        'msg' => '请选择',
        'tip' => '是否开启抓取远程图片',
        'ok' => '',
        'extend' => '',
    ],
];
