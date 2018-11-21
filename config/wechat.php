<?php

return [
    /*
     * Debug 模式，bool 值：true/false
     *
     * 当值为 false 时，所有的日志都不会记录
     */
    'debug' => false,

    /*
     * 使用 Laravel 的缓存系统
     */
    'use_laravel_cache' => true,

    /*
     * 账号基本信息，请从微信公众平台/开放平台获取
     */
    'app_id' => 'wxa7852bf49dcb27d7',         // AppID
    'secret' => 'f187160a98b50a85bc66faa2063d8f68',     // AppSecret
    'token' => 'mornight',         // Token
    'aes_key' =>  'CA8tK3R6t9ZJ2yu8j7MhLsFfA62pbCGhfNxlTLNfwIZ',                    // EncodingAESKey
    'mini_program' => [
        'app_id' => 'wx702b3f6a786271ab',
        'secret' => 'ccf12f4e589c7222086bd42562e111c3',
        // token 和 aes_key 开启消息推送后可见
        'token' => 'mornight',
        'aes_key' => '7MJEAwUWPFiSO8xmZRLL0MbqPUJKPTufQl75Gc9Q8Qb',
    ],
    /*
     * 日志配置
     *
     * level: 日志级别，可选为：
     *                 debug/info/notice/warning/error/critical/alert/emergency
     * file：日志文件位置(绝对路径!!!)，要求可写权限
     */
    'log' => [
        'level' => env('WECHAT_LOG_LEVEL', 'debug'),
        'file' => env('WECHAT_LOG_FILE', storage_path('logs/wechat-' . date('Ymd') . '.log')),
    ],
    /*
     * OAuth 配置
     *
     * only_wechat_browser: 只在微信浏览器跳转
     * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
     * callback：OAuth授权完成后的回调页地址(如果使用中间件，则随便填写。。。)
     */
    // 'oauth' => [
    //     'only_wechat_browser' => false,
    //     'scopes'   => array_map('trim', explode(',', env('WECHAT_OAUTH_SCOPES', 'snsapi_userinfo'))),
    //     'callback' => env('WECHAT_OAUTH_CALLBACK', '/examples/oauth_callback.php'),
    // ],

    /*
     * 微信支付
     */
    'payment' => [
        //'app_id' => 'wx702b3f6a786271ab',
        'merchant_id' => '1496347572',
        'key' => 'mediabook8888888phonetalk8888888',
        'cert_path' => storage_path('cert/apiclient_cert.pem'), // XXX: 绝对路径！！！！
        'key_path' => storage_path('cert/apiclient_key.pem'),      // XXX: 绝对路径！！！！
        // 'device_info'     => env('WECHAT_PAYMENT_DEVICE_INFO', ''),
        // 'sub_app_id'      => env('WECHAT_PAYMENT_SUB_APP_ID', ''),
        // 'sub_merchant_id' => env('WECHAT_PAYMENT_SUB_MERCHANT_ID', ''),
        // ...
    ],

    'open_platform' => [
        'app_id' => 'wxf97db6b57ffcd191',
        'secret' => 'bd424cd9a1297d1abd71494dc1d8a921',
        'token' => 'mornight',
        'aes_key' => 'mornightmornightmornightmornightmornight888'
    ],

    /*
     * 开发模式下的免授权模拟授权用户资料
     *
     * 当 enable_mock 为 true 则会启用模拟微信授权，用于开发时使用，开发完成请删除或者改为 false 即可
     */
    // 'enable_mock' => env('WECHAT_ENABLE_MOCK', true),
    // 'mock_user' => [
    //     "openid" =>"odh7zsgI75iT8FRh0fGlSojc9PWM",
    //     // 以下字段为 scope 为 snsapi_userinfo 时需要
    //     "nickname" => "overtrue",
    //     "sex" =>"1",
    //     "province" =>"北京",
    //     "city" =>"北京",
    //     "country" =>"中国",
    //     "headimgurl" => "http://wx.qlogo.cn/mmopen/C2rEUskXQiblFYMUl9O0G05Q6pKibg7V1WpHX6CIQaic824apriabJw4r6EWxziaSt5BATrlbx1GVzwW2qjUCqtYpDvIJLjKgP1ug/0",
    // ],
];
