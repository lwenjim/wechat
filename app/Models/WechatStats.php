<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class WechatStats extends Model
{
    protected $table = 'wechat_stats';

    protected $guarded = [];
    
    /***
     * 获取单个公众号的相关统计数据（用户数，活跃数，打卡数）
     * $wechatId  :公众号id（若传的是公众号id，就需要连表查询；如果直接传公众号的appid，就不需要连表查询）
     * $startDate ：开始日期，默认为当天日期（格式示例：2018-09-11）
     * $endDate   ：结束日期，默认为当天日期（格式示例：2018-09-11）
     * （注：单个公众号指定日期内的每天统计数据，一个公众号一天对应一行记录，故不用sum）
     */
    public function getStatDataOfSingleWechat($wechatId=null,$startDate=null,$endDate=null){
    	$res = [];
    	$todayDate = date('Y-m-d',time());
    	if(empty($wechatId)){
    		return $res;
    	}
    	if(empty($startDate)){
    		$startDate = $todayDate;
    	}
    	if(empty($endDate)){
    		$endDate = $todayDate;
    	}
    	//查询数据
    	$res = DB::table("wechat_stats as ws")
    	->leftJoin('wechat as w', 'ws.appid', '=', 'w.appid')
    	->select(DB::raw('w.id as wechatId,ws.appid,ws.date,ws.count as user_count,ws.active as active_user_count,ws.sign as punch_clock_count'))
    	->where('w.status', '=', 1)//wechat表的3个条件限制
    	->whereIn('w.type', [1, 2])
    	->where('w.owner', '<>', "个人")
    	->where('ws.date', '>=', $startDate)
    	->where('ws.date', '<=', $endDate)
    	->where('w.id', '=', $wechatId)
    	->whereNotNull('ws.appid')
    	->groupBy('ws.date')
    	->get()
    	//->toSql();//得到当前构造器的SQL语句（需要注释掉get()）
    	->map(function ($value) {
    		return (array)$value;
    	})->toArray();
    	$res = array_column($res,null,'date');
    	return $res;
    }
    /***
     * 获取指定日期（某天）的所有公众号的统计数据（用户数，活跃数，打卡数）【单日各公众号的用户分布】
     * $dayDate   ：指定日期，默认为当天日期（格式示例：2018-09-11）
     * （注：一天一个公众号的数据《==》一条记录，故不必sum）
     */
    public function getStatDataOfAllWechat($dayDate=null){
    	$res = [];
    	$todayDate = date('Y-m-d',time());
    	if(empty($dayDate)){
    		$dayDate = $todayDate;
    	}
    	//查询数据
    	$res = DB::table('wechat_stats as ws')
    	->leftJoin('wechat as w', 'ws.appid', '=', 'w.appid')
    	->select(DB::raw('ws.appid,w.name as wechat_name,ws.count as user_count,ws.active as active_user_count,ws.sign as punch_clock_count'))
    	->where('w.status', '=', 1)//wechat表的3个条件限制
    	->whereIn('w.type', [1, 2])
    	->where('w.owner', '<>', "个人")
    	->where('ws.date', '=', $dayDate)
    	->get()
    	//->toSql();//得到当前构造器的SQL语句（需要注释掉get()）
    	->map(function ($value) {
    		return (array)$value;
    	})->toArray();
    	$res = array_column($res,null,'appid');
    	return $res;
    }

}
