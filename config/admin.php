<?php
/**
 * 后台相关配置
 */

return [
    // sort唯一
    'nav' => [
        'Website' => [
            'sort' => 1,
            'alias' => '网站管理'
        ],
        'System' => [
            'sort' => 3,
            'alias' => '系统管理'
        ],
    ],

    // 栏目
    'nav_show_list' => [
        // 网站管理
        '@Get:lv_website_option_detail',
        '@Get:lv_website_slide_list',
        '@Get:lv_website_article_list',

        // 系统管理
        '@Get:lv_permissions',
        '@Get:lv_roles',
        '@Get:lv_users',
        '@Get:lv_logs'
    ],

    // 是否多登录
    'multiple_login' => false,

    // 选项配置
    'option_list' => [
        'sex' => ['name' => '性别', 'value' => ''],
        'whether' => ['name' => '是否', 'value' => '']
    ],

    // 阿里云oss配置
    'aliyun_oss' => [
        'AccessKeyId' => '',
        'AccessKeySecret' => '',
        'city' => '',
        'bucket' => 'img-dsg',
        'OSS_ROOT' => env('OSS_TEST',''),
        'url_pre' => 'https://img-dsg.oss-cn-shanghai.aliyuncs.com/',
        'url_pre_internal' =>'https://img-dsg.oss-cn-shanghai-internal.aliyuncs.com/',
        'headImg' =>'member/headImg/', // 用户头像路径
        'space' =>'member/space/%s/', // 用户空间
        'setting' =>'admin/setting/', // 配置图片
    ]
];
