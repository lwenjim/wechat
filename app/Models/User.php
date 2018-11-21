<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use App\Services\ModelCache\ModelCache;
class User extends Base implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, CascadesDeletes, SoftCascadeTrait,ModelCache;
    protected $cascadeDeletes = ['addresses', 'msgs', 'coins', 'missions', 'invites', 'invites', 'likes', 'walks', 'signs', 'sign_years', 'coupon_items', 'buy_orders'];
    protected $softCascade = ['carts', 'orders'];
    protected $table = 'user';
    protected $casts = [
        'config' => 'array',
    ];

    // jwt需要实现的方法
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // jwt需要实现的方法
    public function getJWTCustomClaims()
    {
        return ['id' => $this->id, 'headimgurl' => $this->headimgurl, 'nickname' => $this->nickname, 'config' => $this->config];
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function coins()
    {
        return $this->hasMany(UserCoin::class);
    }

    public function missions()
    {
        return $this->hasMany(UserMission::class);
    }

    public function invites()
    {
        return $this->hasMany(UserInvite::class);
    }

    public function likes()
    {
        return $this->hasMany(UserLike::class);
    }

    public function to_likes()
    {
        return $this->hasMany(UserLike::class, 'to_user_id');
    }

    public function msgs()
    {
        return $this->hasMany(UserMsg::class);
    }

    public function walks()
    {
        return $this->hasMany(UserWalk::class);
    }

    public function signs()
    {
        return $this->hasMany(UserSign::class);
    }

    public function sign_years()
    {
        return $this->hasMany(UserSignYear::class);
    }

    public function carts()
    {
        return $this->hasMany(UserCart::class);
    }

    public function orders()
    {
        return $this->hasMany(UserOrder::class);
    }

    public function phones()
    {
        return $this->hasMany(UserPhone::class);
    }
    public function stats()
    {
        return $this->hasMany(UserStat::class);
    }

    public function buy_orders()
    {
        return $this->hasMany(UserBuyOrder::class);
    }

    public function coupon_items()
    {
        return $this->hasMany(CouponItem::class);
    }

    public function wechats()
    {
        return $this->belongsToMany(Wechat::class, 'wechat_user', 'user_id', 'wechat_id')->withPivot('openid', 'subscribe', 'subscribe_time', 'is_default');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id')->withPivot('owner', 'remind', 'sort', 'star');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'item', 'tag_item');
    }

    public function kefus()
    {
        return $this->belongsToMany(Kefu::class)->withPivot('realname');
    }

    public function openids()
    {
        return $this->hasMany(UserOpenids::class);
    }

    public function remind(){
        return $this->hasone('App\Models\UserRemind', 'user_id', 'id');
    }

    public function userSignComment()
    {
        return $this->hasMany(UserSignComment::class);
    }

    //公众号用户获得绑定的小程序用户
    public function switchToMiniUser()
    {
        if (!empty($this->mini_user_id)) {
            return  $this->find($this->mini_user_id);
        }
        return $this;
    }

    //小程序用户获得与之绑定的最近一次活跃的公众号用户
    public function getLastActiveGzhUser($isLast = true)
    {
        if(!empty($this->last_openid)){
            return $this->where('openid', $this->last_openid)->first();
        }
        $obj = $this->where(['mini_user_id' => $this->id]);
        if ($isLast) {
            return $obj->orderBy('updated_at', 'desc')->limit(1)->first();
        } else {
            return $obj->get();
        }
    }

    public function getDays($thatDate = null)
    {
        $last_sign = $this->signs()->select('day', 'date')->orderBy('date', 'desc')->first();
        if ($thatDate == null) {
            $thatDate = date('Y-m-d', strtotime('-1 day'));
        }
        if (!$last_sign || $last_sign->date < $thatDate) {
            $day = 1;
        } else {
            $day = $last_sign->day + 1;
        }
        return $day;
    }

    public function getOrderCount()
    {
        return $this->signs()
            ->where('date', date('Y-m-d'))
            ->count();
    }

    public function blueDiamond()
    {
        return $this->hasMany(UserBlueDiamond::class);
    }

    public function wechatUser(){
        return $this->hasOne(WechatUser::class);
    }

    public function feedback()
    {
        return $this->hasMany(UserFeedback::class);
    }

    public function morningUp()
    {
        return $this->hasOne(userMorningUp::class);
    }

    public function redPacketUser()
    {
        return $this->hasOne(UserRedPacketInfo::class);
    }

    public function redPacketAdminUser()
    {
        return $this->hasOne(UserRedPacketAdminInfo::class);
    }

    public function fisherMission()
    {
        return $this->hasOne(FisherMission::class);
    }
}