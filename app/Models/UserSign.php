<?php

namespace App\Models;

use App\Services\ModelCache\ModelCache;
use Illuminate\Support\Facades\DB;

class UserSign extends Model
{
    use ModelCache;
    protected $cacheTag = 'user_sign';
    protected $table = 'user_sign';
    protected $guarded = ['id', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /***
     * 获取单个公众号的打卡数
     * $wechatId  :公众号id（若传的是公众号id，就需要连表查询；如果直接传公众号的appid，就不需要连表查询）
     * $startDate ：开始日期，默认为当天日期（格式示例：2018-09-11）
     * $endDate   ：结束日期，默认为当天日期（格式示例：2018-09-11）
     */
    public function getPunchClockNumOfSingleWechat($wechatId = null, $startDate = null, $endDate = null)
    {
        $res = [];
        $todayDate = date('Y-m-d', time());
        if (empty($wechatId)) {
            return $res;
        }
        if (empty($startDate)) {
            $startDate = $todayDate;
        }
        if (empty($endDate)) {
            $endDate = $todayDate;
        }
        //查询数据
        $res = DB::table('user_sign as us')
            ->leftJoin('wechat as w', 'us.appid', '=', 'w.appid')
            ->select(DB::raw('w.id as wechatId,us.appid,us.date,count(us.user_id) as punch_clock_count'))
            //->whereBetween('us.date', [$startDate,$endDate])//无效待测
            //->whereBetween('us.date', ["'".$startDate."'","'".$endDate."'"])
            //->where("us.date between '".$startDate."' and '".$endDate."'")
            ->where('us.date', '>=', $startDate)
            ->where('us.date', '<=', $endDate)
            ->where('w.id', '=', $wechatId)
            ->whereNotNull('us.appid')
            ->groupBy('us.appid')
            ->groupBy('us.date')
            ->get()
            //->toSql();//得到当前构造器的SQL语句（需要注释掉get()）
            ->map(function ($value) {
                return (array)$value;
            })->toArray();
        $res = array_column($res, null, 'date');
        return $res;
    }

    /***
     * 获取单个公众号的指定日期（某天）各个小时的打卡数【单日打卡时间分布】
     * $type      :查询方式（是否要指定具体的公众号id 1是；2否）
     * $wechatId  :公众号id（若传的是公众号id，就需要连表查询；如果直接传公众号的appid，就不需要连表查询）
     * $dayDate   ：指定日期，默认为当天日期（格式示例：2018-09-11）
     */
    public function getCardTimeScatterOfSingleWechat($type = 1, $wechatId = null, $dayDate = null)
    {
        $res = [];
        $todayDate = date('Y-m-d', time());
        if (empty($dayDate)) {
            $dayDate = $todayDate;
        }
        switch (intval($type)) {
            case 1:
                if (empty($wechatId)) {
                    return $res;
                }
                //查询数据
                $res = DB::table('user_sign as us')
                    ->leftJoin('wechat as w', 'us.appid', '=', 'w.appid')
                    ->select(DB::raw('substring(us.created_at,12,2) as hour_time,count(us.user_id) as punch_clock_count,us.appid'))
                    ->where('w.status', '=', 1)//wechat表的3个条件限制
                    ->whereIn('w.type', [1, 2])
                    ->where('w.owner', '<>', "个人")
                    ->where('us.date', '=', $dayDate)
                    ->where('w.id', '=', $wechatId)
                    ->whereNotNull('us.appid')
                    ->groupBy(DB::raw('substring(us.created_at,12,2)'))//使用MySQL函数就要使用DB::raw()
                    ->groupBy('us.appid')
                    ->get()
                    //->toSql();//得到当前构造器的SQL语句（需要注释掉get()）
                    ->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                break;
            case 2:
                //查询数据
                $res = DB::table('user_sign as us')
                    ->select(DB::raw('substring(us.created_at,12,2) as hour_time,count(us.user_id) as punch_clock_count'))//GROUP_CONCAT(us.user_id),GROUP_CONCAT(us.appid)
                    ->where('us.date', '=', $dayDate)
                    //->whereNotNull('us.appid')//有些是空的，这里统计把此条件先去掉
                    ->groupBy(DB::raw('substring(us.created_at,12,2)'))//使用MySQL函数就要使用DB::raw()
                    ->get()
                    //->toSql();//得到当前构造器的SQL语句（需要注释掉get()）
                    ->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                break;
            default:
                break;
        }
        $res = array_column($res, null, 'hour_time');
        return $res;
    }

    public function wechat()
    {
        return $this->belongsTo(Wechat::class, 'id', 'wechat_id');
    }
}
