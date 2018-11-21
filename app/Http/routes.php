<?php
//登录注册

$app->router->group(['namespace' => 'App\Http\Controllers'], function ($router) {
    //客服消息
    $router->group(['prefix' => 'msg'], function ($router) {
        $router->get('session[/{id}]', 'MsgController@get');
        $router->post('session/{session_id}/{user_id}', 'MsgController@post');
        $router->delete('session/{session_id}', 'MsgController@delete');
        //msg-user
        $router->group(['prefix' => 'user'], function ($router) {
            $router->get('/', 'MsgUserController@index');
            $router->get('/{id}', 'MsgUserController@get');
            $router->post('/', 'MsgUserController@post');
            $router->put('/{id}', 'MsgUserController@put');
            $router->delete('/{id}', 'MsgUserController@delete');
        });
    });
    $router->addRoute(['GET', 'POST'], 'ueditor', 'UploadController@ueditor');
    $router->post('user/findbackpasswd', 'UsersTokenController@findBackPasswd');
    $router->get('redirect', 'QuanticController@redirect');
    $router->get('quantic/test', 'QuanticController@test');
    //授权平台
    $router->addRoute(['GET', 'POST'], 'user/login', 'UsersTokenController@login');
    $router->addRoute(['GET', 'POST'], 'user/register', 'UsersTokenController@register');
    $router->addRoute(['GET', 'POST'], 'user/send/{type}', 'UsersTokenController@sendMsg');
    $router->addRoute(['GET', 'POST'], 'sendTemplateMessage', 'TemplateMessageController@main');
    $router->addRoute(['GET', 'POST'], 'qccodeLogin', 'UsersTokenController@qccodeLogin');
    //任务模块
    $router->group(['prefix' => 'mission', 'middleware' => 'jwt.auth'], function ($router) {
        $router->addRoute(['GET'], '/list', 'MissionController@getMission');
    });
    //SAAS统计
    $router->group(['prefix' => 'stats'], function ($router) {
        $router->addRoute(['GET', 'POST'], '/signStats', 'StatsController@signStats');
        $router->addRoute(['GET', 'POST'], '/dauStats', 'StatsController@dauStats');
        $router->addRoute(['GET', 'POST'], '/totalStats', 'StatsController@totalStats');
        $router->addRoute(['GET', 'POST'], '/inviteStats', 'StatsController@inviteStats');
        $router->addRoute(['GET', 'POST'], '/growStats', 'StatsController@growStats');
        $router->addRoute(['GET', 'POST'], '/pageviewStats', 'StatsController@pageviewStats');
        $router->addRoute(['GET'], '/todayTotal', 'StatsController@todayTotal');
    });
    $router->group(['prefix' => 'weather'], function ($router) {
        $router->addRoute(['GET', 'POST'], '/{longitude}/{latitude}', 'WeatherController@main');
    });
    $router->group(['middleware' => 'authToken'], function ($router) {
//    $router->group([], function ($router) {
        $router->post('user/changepasswd', 'UsersTokenController@changePasswd');
        $router->addRoute(['GET', 'POST'], 'user/info', 'UsersTokenController@info');
        $router->addRoute(['GET', 'POST'], 'user/logout', 'UsersTokenController@logout');
        $router->addRoute(['GET', 'POST'], 'user/applist', 'UsersTokenController@applist');
        $router->addRoute(['GET', 'POST'], 'user/switch/{appid}', 'UsersTokenController@switch_curapp');
        $router->addRoute(['GET', 'POST'], 'user/updateinfo', 'UsersTokenController@updateInfo');
        $router->addRoute(['GET', 'POST'], 'user/appremove/{appid}', 'UsersTokenController@appRemove');
        $router->addRoute(['PUT', 'POST'], 'myupload', 'UploadController@index');
        $router->addRoute(['GET', 'POST'], 'wechatuser/subscribelog', 'WechatUserSubscribeLogController@index');
        $router->get('wechatuser/statistics', 'WechatUserSubscribeLogController@statistics');
        //achievement
        $router->group(['prefix' => 'achievement'], function ($router) {
            $router->get('/getimages', 'AchievementController@getimages');
            $router->get('/listing', 'AchievementController@listing');
            $router->get('/get/{id}', 'AchievementController@get');
            $router->get('/', 'AchievementController@index');
            $router->post('/', 'AchievementController@form');
            $router->post('/fonts', 'AchievementController@fonts');
            $router->put('/{id}', 'AchievementController@form');
            $router->delete('/{id}', 'AchievementController@delete');
            $router->post('sync-config/{id}/{appids}[/{date}]', 'AchievementController@syncCardConfig');
        });
        //WeChatReply
        $router->group(['prefix' => 'wechatreply'], function ($router) {
            $router->addRoute(['GET', 'POST'], 'set', 'WeChatReplyController@set');
            $router->addRoute(['GET', 'POST'], 'get', 'WeChatReplyController@get');
            $router->addRoute(['GET', 'POST'], 'restore', 'WeChatReplyController@restore');
        });
        //WeChatReply
        $router->group(['prefix' => 'wechateveningreply'], function ($router) {
            $router->addRoute(['GET', 'POST'], 'set', 'WeChatEveningReplyController@set');
            $router->addRoute(['GET', 'POST'], 'get', 'WeChatEveningReplyController@get');
            $router->addRoute(['GET', 'POST'], 'restore', 'WeChatEveningReplyController@restore');
        });
        //reply
        $router->group(['prefix' => 'reply'], function ($router) {
            $router->get('/', 'ReplyController@index');
            $router->get('/{id}', 'ReplyController@get');
            $router->post('/', 'ReplyController@form');
            $router->put('/{id}', 'ReplyController@form');
            $router->delete('/{id}', 'ReplyController@delete');
        });
        //menu
        $router->group(['prefix' => 'wechat/menu'], function ($router) {
            $router->get('/', 'WeChatMenuController@index');
            $router->post('/', 'WeChatMenuController@post');
            $router->delete('/{id}', 'WeChatMenuController@delete');
            $router->get('material/{type}/{offset}/{count}', 'WeChatMenuController@material');
            $router->post('material_upload_image', 'WeChatMenuController@material_upload_image');
            $router->post('material_upload_news', 'WeChatMenuController@material_upload_news');
            $router->get('material_upload_get/{mediaId}', 'WeChatMenuController@material_upload_get');
            $router->post('material_upload_text', 'WeChatMenuController@material_upload_text');
            $router->post('material_upload_video', 'WeChatMenuController@material_upload_video');
            $router->post('material_upload_voice', 'WeChatMenuController@material_upload_voice');
            $router->get('get_miniprogram_info', 'WeChatMenuController@get_miniprogram_info');
        });
        // upload
        $router->addRoute(['PUT', 'POST'], 'i-upload', 'UploadController@index');
        //PlatformApply
        $router->group(['prefix' => 'platform'], function ($router) {
            $router->get('/', 'PlatformApplyController@find');
            $router->post('/', 'PlatformApplyController@post');
            $router->delete('/', 'PlatformApplyController@del');
            $router->put('/{id}/{status}', 'PlatformApplyController@put');
        });
        //UserCenter
        $router->get('UserCenter/userList', 'UserCenterController@userList');
        $router->get('UserCenter/getPortRaitAndRegion', 'UserCenterController@getPortRaitAndRegion');
        $router->get('UserCenter/{user_id}', 'UserCenterController@delete');
        //Material
        $router->get('MaterialManager/stat', 'MaterialManagerController@stat');
        $router->get('MaterialManager/{type}/{offset}/{count}', 'MaterialManagerController@index');
        $router->get('MaterialManager/{material_id}', 'MaterialManagerController@get');
        $router->post('MaterialManager/{type}', 'MaterialManagerController@post');
        $router->delete('MaterialManager/{material_id}', 'MaterialManagerController@delete');
        $router->get('MaterialManager/trade/{mediaId}', 'MaterialManagerController@trade');
        //Staff
        $router->post('staff/sendText/{text}/{openid}', 'StaffController@sendText');
        $router->post('staff/sendTextToPart/{text}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendTextToPart');
        $router->post('staff/sendImage/{mediaId}/{openid}', 'StaffController@sendImage');
        $router->post('staff/sendImageUrl/{url}/{openid}', 'StaffController@sendImageUrl');
        $router->post('staff/sendImageToPart/{mediaId}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendImageToPart');
        $router->post('staff/sendImageUrlToPart/{url}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendImageUrlToPart');
        $router->post('staff/sendVideo/{mediaId}/{openid}', 'StaffController@sendVideo');
        $router->post('staff/sendVideoUrl/{url}/{openid}/{title}/{description}', 'StaffController@sendVideoUrl');
        $router->post('staff/sendVideoToPart/{mediaId}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendVideoToPart');
        $router->post('staff/sendVideoUrlToPart/{url}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendVideoUrlToPart');
        $router->post('staff/sendVoice/{mediaId}/{openid}', 'StaffController@sendVoice');
        $router->post('staff/sendVoiceUrl/{url}/{openid}', 'StaffController@sendVoiceUrl');
        $router->post('staff/sendVoiceToPart/{mediaId}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendVoiceToPart');
        $router->post('staff/sendVoiceUrlToPart/{url}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendVoiceUrlToPart');
        $router->post('staff/sendNews/{mediaId}/{openid}', 'StaffController@sendNews');
        $router->post('staff/sendNewsToPart/{mediaId}/{sex}/{province}/{city}/{tagId}', 'StaffController@sendNewsToPart');
        $router->get('staff/fetchSendLog/{tokenUsersId}/{materialType}', 'StaffController@fetchSendLog');
        //BroadCast
        $router->post('BroadCast/send/{type}/{mediaId}/{groupId}/{preview}', 'BroadCastController@send');
        $router->get('BroadCast/getTags', 'BroadCastController@getTags');
        $router->get('bindQrcode', 'UsersTokenController@bindQrcode');
        $router->get('/statsTitle', 'DataAnalyseController@statsTitle');
    });
    // wechat
    $router->group(['prefix' => 'wechat'], function ($router) {
        $router->addRoute(['GET', 'POST'], 'serve/mini_program', 'WeChatController@miniprogram');
        $router->addRoute(['GET', 'POST'], 'platform', 'WeChatController@platform');
        $router->addRoute(['GET', 'POST'], 'platformauth/{id}', 'WeChatController@platformAuth');
        $router->addRoute(['GET', 'POST'], 'platformauth', 'WeChatController@platformAuth');
        $router->addRoute(['GET', 'POST'], 'notify', 'PayController@notify');
        $router->addRoute(['GET', 'POST'], 'platformpush/{id}', 'WeChatController@platformPush');
        $router->get('image', 'WeChatController@image');
        $router->get('oauth', 'WeChatController@oauth');
        $router->get('oauthAssociated', 'WeChatController@oauthAssociated');
        $router->post('jssdk', 'WeChatController@jssdk');
        $router->get('refresh', 'WeChatController@refresh');
        $router->post('switchGzh', 'WeChatController@switchGzh');
        $router->addRoute(['GET', 'POST'], 'admin', 'WeChatController@admin');
        $router->get('login', 'WeChatController@login');
        $router->post('walk', 'WeChatController@walk');
    });
    //dev
    $router->get('dev/{user_id}', function ($user_id) {
        return \Auth::fromUser(App\Models\User::find($user_id));
    });
    $router->get('update', 'TestController@update');
    $router->addRoute(['GET', 'POST'], 'test', 'TestController@test');
    $router->addRoute(['GET', 'POST'], 'quantic', 'QuanticController@main');
    // need authentication
    $router->group(['middleware' => 'jwt.auth'], function ($router) {
        // upload
        $router->addRoute(['PUT', 'POST'], 'upload', 'UploadController@index');
        // pay
        $router->get('pay/{order_id}/{type}', 'PayController@pay');
        //express
        $router->get('express/{no}', function ($no) {
            return \App\Services\Express::get($no);
        });
        // sign
        $router->group(['prefix' => 'day'], function ($router) {
            $router->get('calculateDiamond', 'DayController@calculateDiamond');
            $router->addRoute(['GET', 'POST', 'put'], 'sign', 'DayController@sign');
            $router->addRoute(['GET', 'POST'], 'sign/add', 'DayController@signAdd');
            $router->addRoute(['GET', 'PUT', 'POST'], 'walk', 'DayController@walk');
            $router->addRoute(['GET', 'POST'], 'moment', 'DayController@moment');
            $router->post('commentLike/{appid}/{score}', 'DayController@commentLike');
            $router->addRoute(['GET', 'POST'], 'like', 'DayController@like');
            $router->addRoute(['GET', 'POST'], 'webLike', 'DayController@webLike');
            $router->addRoute(['GET', 'POST'], 'give/{user_id}', 'DayController@give');
            $router->get('gives', 'DayController@gives');
            $router->post('voice', 'DayController@voice');
            $router->get('qrcode/{user_id}', 'DayController@qrcode');
            $router->get('invite/{user_id}', 'DayController@invite');
            $router->get('weather/{longitude}/{latitude}', 'DayController@weather');
            $router->post('resign', 'DayController@resign');
            $router->get('get-help-list', 'DayController@getHelpList');
        });
        // rank
        $router->group(['prefix' => 'rank'], function ($router) {
            $router->get('sign-time[/{num}]', 'RankController@signTime');
            $router->get('sign-day[/{num}]', 'RankController@signDay');
            $router->get('sign-day-{year}[/{num}]', 'RankController@signDayYear');
            $router->get('walk[/{num}]', 'RankController@walk');
            $router->get('coin[/{num}]', 'RankController@coin');
            $router->get('invite[/{num}]', 'RankController@invite');
            $router->get('like/{to_user_id}/{type}', 'RankController@like');
            $router->get('blueDiamond[/{num}]', 'RankController@blueDiamond');
        });
        // buy
        $router->group(['prefix' => 'buy'], function ($router) {
            $router->get('list', 'BuyController@list');
            $router->get('remind[/{type}]', 'BuyController@remind');
            $router->get('detail/{id}', 'BuyController@detail');
            $router->post('order/{buy_id}/{address_id}', 'BuyController@order');
        });
        // coupon
        $router->group(['prefix' => 'coupon'], function ($router) {
            $router->get('/', 'CouponController@list');
            $router->get('/{id}', 'CouponController@get');
        });
        // user
        $router->group(['prefix' => 'user'], function ($router) {
            $router->get('/', 'UserController@get');
            $router->get('/getUserById/{id}', 'UserController@getUserById');
            $router->put('/', 'UserController@put');
            $router->put('stat', 'UserController@stat');
            $router->get('unread', 'UserController@unread');
            $router->get('sign', 'UserController@sign');
            $router->get('tplmsg/{id}', 'UserController@tplmsg');
            $router->get('activity/{type}[/{id}]', 'UserController@activity');
            $router->put('phone', 'UserController@phone');
            $router->post('feedback', 'UserController@feedback');
            $router->addRoute(['GET', 'POST', 'PUT', 'DELETE'], 'task[/{id}]', 'UserController@task');
            $router->addRoute(['POST', 'DELETE'], 'task/{id}/comment[/{comment_id}]', 'UserController@taskComment');
            $router->addRoute(['GET', 'POST'], 'task/{id}/user', 'UserController@taskUser');
            $router->addRoute(['GET', 'POST'], 'config[/{type}]', 'UserController@config');
            $router->addRoute(['GET', 'POST'], 'mission', 'UserController@mission');
            $router->addRoute(['GET', 'POST'], 'h5mission', 'UserController@h5mission');
            $router->get('getCardInfo[/{userid}]', 'UserController@getCardInfo');
            $router->group(['prefix' => 'address'], function ($router) {
                $router->get('/', 'UserAddressController@list');
                $router->get('/{id}', 'UserAddressController@get');
                $router->post('/', 'UserAddressController@post');
                $router->put('/{id}', 'UserAddressController@put');
                $router->delete('/{id}', 'UserAddressController@delete');
            });
            $router->group(['prefix' => 'order'], function ($router) {
                $router->get('/', 'UserOrderController@list');
                $router->get('/{id}', 'UserOrderController@get');
                $router->put('/{id}', 'UserOrderController@put');
                $router->post('/{id}', 'UserOrderController@post');
                $router->delete('/{id}', 'UserOrderController@delete');
            });
            $router->post('blueGiamond', 'UserController@blueGiamond');
        });
        //user-buy-order
        $router->group(['prefix' => 'user-buy-order'], function ($router) {
            $router->get('/', 'UserBuyOrderController@index');
            $router->get('/{id}', 'UserBuyOrderController@get');
        });
        //RedpacketWelfare
        $router->get('RedpacketWelfare/join', 'RedpacketWelfareController@join');
        $router->get('RedpacketWelfare/redPacketUserInfo', 'RedpacketWelfareController@redPacketUserInfo');
        $router->get('RedpacketWelfare/fetchQrcode', 'RedpacketWelfareController@fetchQrcode');
        $router->post('RedpacketWelfare/validateLink/{code}', 'RedpacketWelfareController@validateLink');
    });
});
$app->router->group(['namespace' => 'App\Http\Controllers\Admin', 'prefix' => 'admin', 'middleware' => 'jwt.auth'], function ($router) {
//$app->router->group(['namespace' => 'App\Http\Controllers\Admin', 'prefix' => 'admin', ], function ($router) {
    $router->group(['prefix' => 'achievement'], function ($router) {
        $router->get('/getimages', 'AchievementController@getimages');
        $router->get('/', 'AchievementController@index');
        $router->get('/{id}', 'AchievementController@get');
        $router->post('/', 'AchievementController@form');
        $router->post('/fonts', 'AchievementController@fonts');
        $router->put('/{id}', 'AchievementController@form');
        $router->delete('/{id}', 'AchievementController@delete');
        $router->post('sync-config/{id}/{appids}[/{date}]', 'AchievementController@syncCardConfig');
    });
    $router->group(['prefix' => 'tips'], function ($router) {
        $router->get('/{id}', 'AchievementTipsController@detail');
        $router->get('/', 'AchievementTipsController@list');
        $router->post('/', 'AchievementTipsController@add');
        $router->put('/{id}', 'AchievementTipsController@change');
        $router->delete('/{id}', 'AchievementTipsController@delete');
    });
    //后台设置审核提醒人
    $router->group(['prefix' => 'user-remind'], function ($router) {
        $router->get('/', 'UserRemindController@index');
        $router->delete('/', 'UserRemindController@remove');
    });
    //user-feedback
    $router->group(['prefix' => 'user-feedback'], function ($router) {
        $router->get('/', 'UserFeedbackController@index');
        $router->get('/{id}', 'UserFeedbackController@get');
        $router->put('/{id}', 'UserFeedbackController@put');
        $router->delete('/{id}', 'UserFeedbackController@delete');
    });
    //advert
    $router->group(['prefix' => 'advert'], function ($router) {
        $router->get('/', 'AdvertController@index');
        $router->get('/{id}', 'AdvertController@get');
        $router->post('/', 'AdvertController@form');
        $router->put('/{id}', 'AdvertController@form');
        $router->delete('/{id}', 'AdvertController@delete');
    });
    //activity
    $router->group(['prefix' => 'activity'], function ($router) {
        $router->get('/', 'ActivityController@index');
        $router->get('/{id}', 'ActivityController@get');
        $router->post('/', 'ActivityController@form');
        $router->put('/{id}', 'ActivityController@form');
        $router->delete('/{id}', 'ActivityController@delete');
    });
    //tag
    $router->group(['prefix' => 'tag'], function ($router) {
        $router->get('/', 'TagController@index');
        $router->get('/{id}', 'TagController@get');
        $router->post('/', 'TagController@form');
        $router->put('/{id}', 'TagController@form');
        $router->delete('/{id}', 'TagController@delete');
    });
    //kefu
    $router->group(['prefix' => 'kefu'], function ($router) {
        $router->get('/', 'KefuController@index');
        $router->get('/{id}', 'KefuController@get');
        $router->post('/', 'KefuController@form');
        $router->put('/{id}', 'KefuController@form');
        $router->delete('/{id}', 'KefuController@delete');
        $router->get('/{id}/msg', 'KefuController@msgGet');
        $router->put('/{id}/msg', 'KefuController@msgPut');
        $router->post('/{id}/msg', 'KefuController@msgPost');
    });
    //buy
    $router->group(['prefix' => 'buy'], function ($router) {
        $router->get('/', 'BuyController@index');
        $router->get('/{id}', 'BuyController@get');
        $router->post('/', 'BuyController@form');
        $router->put('/{id}', 'BuyController@form');
        $router->delete('/{id}', 'BuyController@delete');
    });
    //catalog
    $router->group(['prefix' => 'catalog'], function ($router) {
        $router->get('/', 'CatalogController@index');
        $router->get('/{id}', 'CatalogController@get');
        $router->post('/', 'CatalogController@form');
        $router->put('/{id}', 'CatalogController@form');
        $router->delete('/{id}', 'CatalogController@delete');
    });
    //spec
    $router->group(['prefix' => 'spec'], function ($router) {
        $router->get('/', 'SpecController@index');
        $router->get('/{id}', 'SpecController@get');
        $router->post('/', 'SpecController@form');
        $router->put('/{id}', 'SpecController@form');
        $router->delete('/{id}', 'SpecController@delete');
        $router->delete('/{value_id}/value', 'SpecController@deleteValue');
    });
    //express
    $router->group(['prefix' => 'express'], function ($router) {
        $router->get('/', 'ExpressController@index');
        $router->get('/{id}', 'ExpressController@get');
        $router->post('/', 'ExpressController@form');
        $router->put('/{id}', 'ExpressController@form');
        $router->delete('/{id}', 'ExpressController@delete');
    });
    //coupon
    $router->group(['prefix' => 'coupon'], function ($router) {
        $router->get('/', 'CouponController@index');
        $router->get('/{id}', 'CouponController@get');
        $router->post('/', 'CouponController@form');
        $router->put('/{id}', 'CouponController@form');
        $router->post('/{id}', 'CouponController@make');
        $router->post('/{id}/send', 'CouponController@send');
        $router->delete('/{id}', 'CouponController@delete');
        $router->delete('/{item_id}/item', 'ProductController@deleteItem');
    });
    //appcode
    $router->group(['prefix' => 'appcode'], function ($router) {
        $router->get('/', 'AppCodeController@index');
        $router->get('/{id}', 'AppCodeController@get');
        $router->post('/', 'AppCodeController@form');
        $router->put('/{id}', 'AppCodeController@form');
        $router->delete('/{id}', 'AppCodeController@delete');
    });
    //PlatformUser
    $router->group(['prefix' => 'PlatformUser'], function ($router) {
        $router->get('/', 'PlatformUserController@index');
    });
    //user
    $router->group(['prefix' => 'user'], function ($router) {
        $router->get('/', 'UserController@index');
        $router->get('/{id}', 'UserController@get');
        $router->put('/{id}', 'UserController@put');
        $router->post('/{id}/coin', 'UserController@coin');
        $router->post('/{id}/sign', 'UserController@sign');
        $router->delete('/{id}', 'UserController@delete');
    });
    //group
    $router->group(['prefix' => 'group'], function ($router) {
        $router->get('/', 'GroupController@index');
        $router->get('/{id}', 'GroupController@get');
        $router->post('/', 'GroupController@form');
        $router->put('/{id}', 'GroupController@form');
        $router->delete('/{id}', 'GroupController@delete');
    });
    //user-buy-order
    $router->group(['prefix' => 'user-buy-order'], function ($router) {
        $router->get('/', 'UserBuyOrderController@index');
        $router->get('/{id}', 'UserBuyOrderController@get');
        $router->delete('/{id}', 'UserBuyOrderController@delete');
        $router->post('send/{id}', 'UserBuyOrderController@send');
    });
    //user-order
    $router->group(['prefix' => 'user-order'], function ($router) {
        $router->get('/', 'UserOrderController@index');
        $router->get('/{id}', 'UserOrderController@get');
        $router->delete('/{id}', 'UserOrderController@delete');
        $router->put('cancel/{id}', 'UserOrderController@cancel');
        $router->post('send/{id}', 'UserOrderController@send');
        $router->post('after/{id}', 'UserOrderController@after');
        $router->post('price/{id}', 'UserOrderController@price');
        $router->post('refund/{trade_no}', 'UserOrderController@refund');
        $router->put('query/{no}/{type}', 'UserOrderController@query');
    });
    //wechat
    $router->group(['prefix' => 'wechat'], function ($router) {
        //account
        $router->group(['prefix' => 'account'], function ($router) {
            $router->get('/', 'WechatController@index');
            $router->get('/{id}', 'WechatController@get');
            $router->put('/{id}', 'WechatController@put');
            $router->delete('/{id}', 'WechatController@delete');
            $router->addRoute(['PUT', 'POST'], 'option/{appid}', 'WechatController@option');
        });
        //userReport:用户报表
        $router->group(['prefix' => 'userReport'], function ($router) {
            $router->get('/', 'WechatController@getUserReport');
        });
        //cardTimeScatter:打卡时间分布，分为【单个公众号】的单日打卡时间分布和【所有公众号】的单日打卡时间分布
        $router->group(['prefix' => 'cardTimeScatter'], function ($router) {
            $router->get('/', 'WechatController@getCardTimeScatter');
        });
        //wechatScatter:公众号分步（各公众号单日用户分布）
        $router->group(['prefix' => 'wechatScatter'], function ($router) {
            $router->get('/', 'WechatController@getWechatScatter');
        });
        //menu
        $router->group(['prefix' => 'menu'], function ($router) {
            $router->get('/', 'WeChatMenuController@index');
            $router->post('/', 'WeChatMenuController@post');
            $router->delete('/{id}', 'WeChatMenuController@delete');
            $router->get('material/{type}/{offset}/{count}', 'WeChatMenuController@material');
        });
        //reply
        $router->group(['prefix' => 'reply'], function ($router) {
            $router->get('/', 'WeChatReplyController@index');
            $router->get('/{id}', 'WeChatReplyController@get');
            $router->post('/', 'WeChatReplyController@form');
            $router->put('/{id}', 'WeChatReplyController@form');
            $router->delete('/{id}', 'WeChatReplyController@delete');
        });
        //EveningReply
        $router->group(['prefix' => 'eveningReply'], function ($router) {
            $router->get('/', 'WeChatEveningReplyController@index');
            $router->get('/{id}', 'WeChatEveningReplyController@get');
            $router->post('/', 'WeChatEveningReplyController@form');
            $router->put('/{id}', 'WeChatEveningReplyController@form');
            $router->delete('/{id}', 'WeChatEveningReplyController@delete');
        });
        //staff
        $router->group(['prefix' => 'staff'], function ($router) {
            $router->get('/', 'WeChatStaffController@index');
            $router->get('/{id}', 'WeChatStaffController@get');
            $router->post('/', 'WeChatStaffController@form');
            $router->put('/{id}', 'WeChatStaffController@form');
            $router->delete('/{id}', 'WeChatStaffController@delete');
            $router->addRoute(['GET', 'POST'], '/{id}/send', 'WeChatStaffController@send');
        });
        //qrcode
        $router->group(['prefix' => 'qrcode'], function ($router) {
            $router->get('/', 'WeChatQrcodeController@index');
            $router->get('/{id}', 'WeChatQrcodeController@get');
            $router->post('/', 'WeChatQrcodeController@post');
            $router->put('/{id}', 'WeChatQrcodeController@put');
            $router->delete('/{id}', 'WeChatQrcodeController@delete');
        });
        //achieve
        $router->group(['prefix' => 'achieve'], function ($router) {
            $router->get('/', 'WeChatAchieveController@index');
            $router->get('/{id}', 'WeChatAchieveController@get');
            $router->post('/', 'WeChatAchieveController@form');
            $router->post('/fonts', 'WeChatAchieveController@fonts');
            $router->put('/{id}', 'WeChatAchieveController@form');
            $router->delete('/{id}', 'WeChatAchieveController@delete');
        });
    });
    //count
    $router->group(['prefix' => 'count'], function ($router) {
        $router->get('main/{type}', 'CountController@main');
        $router->get('user', 'CountController@user');
        $router->get('order', 'CountController@order');
        $router->get('stat', 'CountController@stat');
        $router->get('product', 'CountController@product');
        $router->get('coupon', 'CountController@coupon');
        $router->get('order2pay', 'CountController@order2pay');
        $router->get('cart2order', 'CountController@cart2order');
        $router->get('portrait', 'CountController@portrait');
        $router->get('source', 'CountController@source');
        $router->get('wechat', 'CountController@wechat');
    });
    //setting
    $router->addRoute(['GET', 'POST'], 'setting', 'SettingController@setting');
    $router->addRoute(['GET', 'POST'], 'setting/minicard', 'SettingController@minicard');
    //PlatformApplyController
    $router->group(['prefix' => 'platform'], function ($router) {
        $router->get('/', 'PlatformApplyController@index');
        $router->get('/{id}', 'PlatformApplyController@get');
        $router->post('/', 'PlatformApplyController@post');
        $router->delete('/{id}', 'PlatformApplyController@del');
        $router->put('/{id}/{status}', 'PlatformApplyController@put');
    });
    //RedpacketWelfare
    $router->get('RedpacketWelfare', 'RedpacketWelfareController@index');
    $router->post('RedpacketWelfare/updateQrCodeImg', 'RedpacketWelfareController@updateQrCodeImg');
    $router->post('RedpacketWelfare/sendRedPacket/{userId}', 'RedpacketWelfareController@sendRedPacket');
    $router->get('RedpacketWelfare/generalValidateLink/{openid}', 'RedpacketWelfareController@generalValidateLink');
    //主后台统计
    $router->group(['prefix' => 'mainStats'], function ($router) {
        $router->get('/todayTotal', 'MainAdminStatsController@todayTotal');
        $router->get('/historyTotal', 'MainAdminStatsController@historyTotal');
        $router->get('/signToday', 'MainAdminStatsController@signToday');
        $router->get('/signHistory', 'MainAdminStatsController@signHistory');
        $router->get('/activeUserStatistics', 'MainAdminStatsController@activeUserStatistics');
        $router->get('/activeToday', 'MainAdminStatsController@activeToday');
    });
    $router->get('UsersToken', 'UsersTokenController@index');
});
//第三方开放接口
Route::group(['namespace' => 'App\Http\Controllers', 'prefix' => 'thirdParty'], function () {
    Route::get('/advertData', 'StatsController@advertData');
    Route::post('/read', 'ThirdPartyController@read');
    Route::get('/getGZHInfos', 'ThirdPartyController@getGZHInfos');//获取公众号信息
    Route::get('/getArticle', 'ThirdPartyController@getArticle');//获取微信文章
    Route::get('/getActiveNum', 'ThirdPartyController@getActiveNum');//获取当前活跃数
});
//微信公众号文章
Route::group(['namespace' => 'App\Http\Controllers', 'prefix' => 'article'], function () {
    Route::post('/', 'ArticleController@index');
});
//saas平台,公众号产品接口
Route::group(['namespace' => 'App\Http\Controllers', 'prefix' => 'product'], function () //Route::group(['namespace' => 'App\Http\Controllers','prefix'=>'admin'], function()
{
    Route::group(['middleware' => 'authToken'], function () {
        Route::post('/saas', 'ProductSaasController@index');
    });
    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::post('/examine', 'ProductExamineController@index');
    });
});
//saas平台,公众号产品接口(部分功能沿用原来的代码,或改进原来的代码)
$app->router->group(['namespace' => 'App\Http\Controllers\Admin', 'prefix' => 'admin', 'middleware' => 'jwt.auth'], function ($router) {
//$app->router->group(['namespace' => 'App\Http\Controllers\Admin', 'prefix' => 'admin'], function ($router) {
    $router->group(['prefix' => 'product'], function ($router) {
        $router->get('/', 'ProductController@index');//获取商品列表
        $router->get('/getListByWxid/{wx_id}', 'ProductController@index');//获取公众号的商品列表
        $router->get('/{id}', 'ProductController@get');//获取单个商品信息
        $router->post('/', 'ProductController@form');//添加商品
        $router->put('/{id}', 'ProductController@form');//编辑商品
        $router->delete('/{id}', 'ProductController@delete');//删除商品
        $router->delete('/{spec_id}/spec', 'ProductController@deleteSpec');//删除规格
        $router->get('/{id}/comment', 'ProductController@comment');
        $router->post('/{id}/{spec_id}/comment', 'ProductController@postComment');
        $router->put('/{comment_id}/comment', 'ProductController@putComment');
        $router->delete('/{comment_id}/comment', 'ProductController@deleteComment');
    });
});
$app->router->group(['namespace' => 'App\Http\Controllers\Admin', 'middleware' => 'authToken'], function ($router) {
//$app->router->group(['namespace' => 'App\Http\Controllers\Admin'], function ($router) {
    $router->group(['prefix' => 'product'], function ($router) {
        $router->get('/', 'ProductController@index');//获取商品列表
        $router->get('/getListByWxid/{wx_id}', 'ProductController@index');//获取公众号的商品列表
        $router->get('/{id}', 'ProductController@get');//获取单个商品信息
        $router->post('/', 'ProductController@form');//添加商品
        $router->put('/{id}', 'ProductController@form');//编辑商品
        $router->delete('/{id}', 'ProductController@delete');//删除商品
        $router->delete('/{spec_id}/spec', 'ProductController@deleteSpec');//删除规格
        $router->get('/{id}/comment', 'ProductController@comment');
        $router->post('/{id}/{spec_id}/comment', 'ProductController@postComment');
        $router->put('/{comment_id}/comment', 'ProductController@putComment');
        $router->delete('/{comment_id}/comment', 'ProductController@deleteComment');
    });
    //product-part
    $router->group(['prefix' => 'product-part'], function ($router) {
        $router->get('/', 'ProductPartController@index');
        $router->get('/{id}', 'ProductPartController@get');
        $router->post('/', 'ProductPartController@form');
        $router->put('/{id}', 'ProductPartController@form');
        $router->delete('/{id}', 'ProductPartController@delete');
    });
    //product-spec
    $router->group(['prefix' => 'product-spec'], function ($router) {
        $router->get('/', 'ProductSpecController@index');
        $router->get('/{id}', 'ProductSpecController@get');
        $router->post('/', 'ProductSpecController@form');
        $router->put('/{id}', 'ProductSpecController@form');
        $router->delete('/{id}', 'ProductSpecController@delete');
        $router->delete('/{value_id}/value', 'ProductSpecController@deleteValue');
    });
    //product-catalog
    $router->group(['prefix' => 'product-catalog'], function ($router) {
        $router->get('/', 'CatalogController@index');
        $router->get('/{id}', 'CatalogController@get');
        $router->post('/', 'CatalogController@form');
        $router->put('/{id}', 'CatalogController@form');
        $router->delete('/{id}', 'CatalogController@delete');
    });
    //product-express
    $router->group(['prefix' => 'product-express'], function ($router) {
        $router->get('/', 'ProductExpressController@index');
        $router->get('/{id}', 'ProductExpressController@get');
        $router->post('/', 'ProductExpressController@form');
        $router->put('/{id}', 'ProductExpressController@form');
        $router->delete('/{id}', 'ProductExpressController@delete');
    });
});
$app->router->group(['namespace' => 'App\Http\Controllers\Admin', 'middleware' => 'authToken'], function ($router) {
    //user
//    $router->group(['prefix' => 'user'], function ($router) {
//        $router->get('/', 'UserController@index');
//        $router->get('/{id}', 'UserController@get');
//        $router->put('/{id}', 'UserController@put');
//        $router->delete('/{id}', 'UserController@delete');
//        $router->addRoute(['GET', 'POST'], 'tplmsg/{openid}', 'UserController@tplmsg');
//    });
    //user-group
    $router->group(['prefix' => 'user-group'], function ($router) {
        $router->get('/', 'UserGroupController@index');
        $router->get('/{id}', 'UserGroupController@get');
        $router->post('/', 'UserGroupController@form');
        $router->put('/{id}', 'UserGroupController@form');
        $router->delete('/{id}', 'UserGroupController@delete');
    });
//    //user-feedback
//    $router->group(['prefix' => 'user-feedback'], function ($router) {
//        $router->get('/', 'UserFeedbackController@index');
//        $router->get('/{id}', 'UserFeedbackController@get');
//        $router->put('/{id}', 'UserFeedbackController@put');
//        $router->delete('/{id}', 'UserFeedbackController@delete');
//    });
    //user-order
    $router->group(['prefix' => 'user-order'], function ($router) {
        $router->get('/', 'UserOrderController@index');
        $router->get('/{id}', 'UserOrderController@get');
        $router->delete('/{id}', 'UserOrderController@delete');
        $router->put('cancel/{id}', 'UserOrderController@cancel');
        $router->post('send/{id}', 'UserOrderController@send');
        $router->post('after/{id}', 'UserOrderController@after');
        $router->post('price/{id}', 'UserOrderController@price');
        $router->post('refund/{trade_no}', 'UserOrderController@refund');
        $router->put('query/{no}/{type}', 'UserOrderController@query');
        $router->post('/import', 'UserOrderController@import');
    });
//    //user-buy-order
//    $router->group(['prefix' => 'user-buy-order'], function ($router) {
//        $router->get('/', 'UserBuyOrderController@index');
//        $router->get('/{id}', 'UserBuyOrderController@get');
//        $router->delete('/{id}', 'UserBuyOrderController@delete');
//    });
});
$app->router->group(['namespace' => 'App\Http\Controllers\Activity', 'prefix' => 'activity', 'middleware' => 'jwt.auth'], function ($router) {
    $router->get('wheel', 'WheelController@wheel');
});

//活动,saas和admin的接口
Route::group(['namespace' => 'App\Http\Controllers\Activity', 'prefix' => 'activity'], function ()
{
    Route::group(['middleware' => 'authToken'], function () {
        Route::post('/admin', 'ActivityAdminController@index');
    });
    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::post('/saas', 'ActivitySaasController@index');
    });
});

//用户,h5 saas和admin的接口
Route::group(['namespace' => 'App\Http\Controllers\User', 'prefix' => 'user'], function ()
{
    Route::group(['middleware' => 'authToken'], function () {

    });
    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::post('/h5', 'UserH5Controller@index');
    });
});