<?php

$projectFlag = 'design_api';
return [
    ///
    'user_info' => ['key' => $projectFlag.'=>admin:user_info:%s'], /// 用户信息
    ///
    'x_token' => ['key' => $projectFlag.'=>admin:system:token:%s'], /// 登录授权令牌信息
    ///
    'rbac' => ['key' => $projectFlag.'=>admin:rbac:%s'], /// 角色权限信息
    'captcha' => ['key' => $projectFlag.'=>admin:captcha:%s', 'ttl' => 1800],


    'mem_verify_code_tel' => ['key' => $projectFlag.'=>mem:verify_code_tel:%s', 'ttl' => 300], /// 手机号验证码
    'mem_verify_code_bind_tel' => ['key' => $projectFlag.'=>mem:mem_verify_code_bind_tel:%s', 'ttl' => 300], /// 绑定手机号验证码
    'mem_verify_code_login_tel' => ['key' => $projectFlag.'=>mem:mem_verify_code_login_tel:%s', 'ttl' => 300], /// 登录手机号验证码
    'mem_password_code_tel' => ['key' => $projectFlag.'=>mem:password_code_tel:%s', 'ttl' => 300], /// 找回密码验证码
    'mem_info' => ['key' => $projectFlag.'=>mem:info:%s', 'ttl' => 86400], /// 用户信息
    'web_verify_code_mail' => ['key' => $projectFlag.'=>mem:verify_code_mail:%s', 'ttl' => 300], /// 邮箱验证码

    'm_token' => ['key' => $projectFlag.'=>mem:system:token:%s:%s'], /// 登录授权令牌信息
    'mem_appSecret_status' => ['key' => $projectFlag.'=>mem:mem_appSecret_status:%s'], /// 用户状态以及密钥信息


    'package_renew_inform' => ['key' => $projectFlag.'=>mem:home:renew_inform:%s:%s', 'ttl' => 86400], /// 套餐续费提醒
];
