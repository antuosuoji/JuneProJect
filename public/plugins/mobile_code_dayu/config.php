<?php
// +----------------------------------------------------------------------
// | Alidayu [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 Tangchao All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tangchao <79300975@qq.com>
// +----------------------------------------------------------------------
return array (
	'AppKey' => array (
		'title' => 'App Key',
		'type' => 'text',
		'value' => '',
		'tip' => '阿里大于短信接口'
	),
    'AppSecret' => array (
        'title' => 'App Secret',
        'type' => 'text',
        'value' => '',
        'tip' => '申请地址：http://www.alidayu.com 本插件仅限老版阿里大于用户使用'
    ),
    'autograph' => array (
        'title' => '签名',
        'type' => 'text',
        'value' => '',
        'tip' => '这里用来填写在阿里大于申请签名。例：ThinkCmf'
    ),
    'Template' => array (
        'title' => '模板ID',
        'type' => 'text',
        'value' => '',
        'tip' => '默认格式为：SMS_6370459。如需添加，请自行申请。格式：(${code})注册验证码10分钟内有效，请尽快完成验证。注册验证码传递参数${code}'
    ),
    'expire_minute' => array (
        'title' => '有效期',
        'type' => 'text',
        'value' => '30',
        'tip' => '短信验证码过期时间，单位分钟'
    ),
);
					