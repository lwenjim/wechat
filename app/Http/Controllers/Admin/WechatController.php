<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wechat;
use App\Models\TokenUsers;
use App\Models\UserSign;    //用户打卡表model
use App\Models\WechatStats; //微信公众号统计表model
use App\Transformers\WechatTransformer;

use Illuminate\Support\Facades\DB;

class WechatController extends AdminController
{
    function index()
    {
        $where['title'] = $this->request->get('title');
        $where['status'] = $this->request->get('status');
        $where['type'] = $this->request->get('type');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        $limit = $this->request->get('limit', '10');
        list($order_field, $order_type) = explode(',', $where['order']);
        $wechats = Wechat::where(function ($query) use ($where) {
            if ($where['title']) {
                $query->where('name', 'like', '%' . $where['title'] . '%')->orWhere('wechat', 'like', '%' . $where['title'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            if ($where['type'] != '') {
                $query->where('type', $where['type']);
            }
        })->withCount('fans')->orderBy($order_field, $order_type)->paginate($limit);
        //****************************  服务-》运营-》公众号列表加几个数量字段begin  *****************************
        //注：原有的结果为object，就不再转为array，使用array_walk处理
        //1.单个公众号的用户数（即时用户总数）--》【wechat_user.wechat_id=公众号id】
//        $wechatUserCount = DB::table('wechat_user')
//        ->select(DB::raw('wechat_id,count(*) as user_count'))
//        ->groupBy('wechat_id')
//        ->get()
//        ->map(function ($value) {
//        	return (array)$value;
//        })->toArray();
//        $wechatUserCount = array_column($wechatUserCount, 'user_count','wechat_id');
        $wechatUserCount = app('redis')->zRange(config('config.RedisKey.0'), 0, -1, true);
        //2.单个公众号的活跃数（即时活跃总数）【放置循环体中一一获取】
        //3.单个公众号的打卡数（当日0点至即时打卡总数）--》【user_sign date appid】
        $punchClockCount = DB::table('user_sign')
            ->select(DB::raw('appid,count(*) as punch_clock_count'))
            ->where('date', date("Y-m-d"))
            ->groupBy('appid')
            ->get()
            ->map(function ($value) {
                return (array)$value;
            })->toArray();
        $punchClockCount = array_column($punchClockCount, 'punch_clock_count', 'appid');
        //4.单个公众号的留言数（当日0点至即时留言总数）【放置循环体中一一获取】
        //****************************  服务-》运营-》公众号列表加几个数量字段end  *****************************
        foreach ($wechats as $key => &$value) {
            $wechats[$key]->admin = TokenUsers::where('appid', 'like', '%' . $value->appid . '%')->get();
            /*//1.单个公众号的用户数
            if (isset($wechatUserCount[$value->id])) {
                $userTotalNum = $wechatUserCount[$value->id];
            } else {
                $userTotalNum = 0;//默认为0
            }
            $value->user_total_num = $userTotalNum;*/
            //2.单个公众号的活跃数（即时活跃总数）
            $tmp_data = getGzhStatic($value->appid);
            if (isset($tmp_data['todayWechatStats']['count'])) {
                $value->user_total_num = $tmp_data['todayWechatStats']['count'];
            } else {
                $value->user_total_num = 0;
            }
            $value->fans_count = $value->user_total_num;
            if (isset($tmp_data['todayWechatStats']['active'])) {
                $value->active_total_num = $tmp_data['todayWechatStats']['active'];
            } else {
                $value->active_total_num = 0;
            }
            if (isset($tmp_data['todayWechatStats']['sign'])) {
                $value->punch_clock_total_num = $tmp_data['todayWechatStats']['sign'];
            } else {
                $value->punch_clock_total_num = 0;
            }
            $statistics = [];
            $statistics['totalCount'] = $value->user_total_num;
            $statistics['activeCount'] = $value->active_total_num;
            $statistics['signCount'] = $value->punch_clock_total_num;
            $value->setAttribute('statistics', $statistics);
//            $value->active_total_num = count(getActiveUser($value->appid));
            /*//3.单个公众号的打卡数
            if (isset($punchClockCount[$value->appid])) {
                $punchClockTotalNum = $punchClockCount[$value->appid];
            } else {
                $punchClockTotalNum = 0;//默认为0
            }
            $value->punch_clock_total_num = $punchClockTotalNum;*/
            //4.单个公众号的留言数（当日0点至即时留言总数）
            $value->msg_total_num = !empty($value->appid) ? \App\Models\UserSignComment::where(['date' => date('Y-m-d'), 'wechat_id' => \App\Models\Wechat::where(['appid' => $value->appid])->value('id')])->count() : 0;
        }
        return $wechats;
    }

    function put($id = 0)
    {
        $data = $this->request->input();
        Wechat::updateOrCreate(['id' => $id], array_only($data, ['is_default', 'status', 'sort']));
        return $this->created();
    }

    function get($id)
    {
        $wechat = Wechat::find($id);
        if (!$wechat) {
            return $this->errorNotFound();
        }
        return $this->item($wechat, new WechatTransformer());
    }

    function delete($id)
    {
        $wechat = Wechat::find($id);
        if (!$wechat) {
            return $this->errorNotFound();
        }
        $wechat->delete();
        return $this->noContent();
    }

    function option($appid)
    {
        $name = $this->request->input('name');
        $authorizer = app('wechat')->open_platform->authorizer;
        if ($this->request->isMethod('put')) {
            return $authorizer->getApi()->getAuthorizerOption($appid, $name);
        } else {
            $value = $this->request->input('value');
            $authorizer->getApi()->setAuthorizerOption($appid, $name, $value);
            return $this->created();
        }
    }

    function checkDateRangeValid($startDate = null, $endDate = null)
    {
        if (empty($startDate) || empty($endDate)) {//任一个为空，即返回false
            return false;
        }
        if (strtotime($endDate) - strtotime($startDate) < 0) {
            return false;
        }
        return true;
    }

    //检查日期参数是否合法，且为指定格式
    function checkDateValid($date = null, $formatType = 'Y-m-d')
    {
        if (empty($date)) {//前提：空判断
            return false;
        }
        if (date($formatType, strtotime($date)) != $date) {
            return false;
        }
        return true;//合法返回true
    }
    //检查参数是否为空
    function checkValidAndEmpty($para = null, $checkType = 1)
    {
        switch ($checkType) {
            case 1://1.设置且不为空，包括不为0/false
                if (!isset($para) || empty($para)) {
                    return false;//没通过验证
                }
                break;
            case 2://只要设置即可
                if (!isset($para)) {
                    return false;//没通过验证
                }
                break;
            default:
                break;
        }
        return true;//通过验证即返回true
    }
    /***
     * 用户报表：总用户数、活跃数、打卡数日变化
     * 公众号；日期范围；用户数；活跃数；打卡数；
     */
    function getUserReport()
    {
        //****************************** 获取参数 ******************************
        $wechatId = $this->request->input('wechatId'); //公众号id:此获取参数方式不传即为null
        $startDate = $this->request->input('startDate');//开始日期
        $endDate = $this->request->input('endDate');  //结束日期
        //****************************** 参数验证 ******************************
        //1.空检查
        if (!$this->checkValidAndEmpty($wechatId)) {//公众号id是否存在于wechat表中，暂不做判断
            echoJson(210, '公众号id缺失！');
        }
        if (!$this->checkValidAndEmpty($startDate)) {
            echoJson(220, '开始日期缺失！');
        }
        if (!$this->checkValidAndEmpty($endDate)) {
            echoJson(230, '结束日期缺失！');
        }
        //2.检查日期格式是否符合要求：使用默认的日期格式'Y-m-d'，例如"2018-09-11"
        if (!$this->checkDateValid($startDate)) {
            echoJson(240, '开始日期无效！');
        }
        if (!$this->checkDateValid($endDate)) {
            echoJson(250, '结束日期无效！');
        }
        //3.检查结束日期是否>=开始日期（以天为单位，可以相等，取同一天数据）
        if (!$this->checkDateRangeValid($startDate, $endDate)) {
            echoJson(260, '开始日期大于结束日期！');
        }
        //****************************** 查询数据 ******************************
        //单个公众号的用户数和活跃数，打卡数，直接从user_stat表中读取（统计表，每15分钟更新一次，可能有延时）
        $wechatStats = new WechatStats();
        $wechatStatData = $wechatStats->getStatDataOfSingleWechat($wechatId, $startDate, $endDate);
        //--------------------------  下边方式的打卡数是从即时表中读取（最新的） begin --------------------------
        /* //1.用户数
        $userNum = [];
        //2.活跃数
        $activeUserNum = [];
        //3.打卡数
        $userSign = new UserSign();
        $punchClockNum = $userSign->getPunchClockNumOfSingleWechat($wechatId,$startDate,$endDate);
        //*********************  将分别查询的数据进行格式化汇总  *********************
        //因为此接口目前只有打卡数能从MySQL中获取每天的数据，故先以此数据作为基准array
        array_walk($punchClockNum,function(&$value, $key) use ($userNum,$activeUserNum){
            //用户数
            if(isset($userNum[$value['date']]['user_num'])){
                $userCount = $userNum[$value['date']]['user_num'];
            }else{
                $userCount = 0;//默认为0
            }
            $value['user_count'] = $userCount;
            //活跃数
            if(isset($activeUserNum[$value['date']]['active_user_num'])){
                $activeUserCount = $activeUserNum[$value['date']]['active_user_num'];
            }else{
                $activeUserCount = 0;//默认为0
            }
            $value['active_user_count'] = $activeUserCount;
        }); */
        //--------------------------  下边方式的打卡数是从即时表中读取（最新的）end  --------------------------
        echoJson(200, 'success', $wechatStatData);
    }

    /***
     * 打卡时间分布：单日打卡时间分布
     * 公众号；日期点；打卡时间（按小时，每日24条数据）；打卡人数；
     */
    function getCardTimeScatter()
    {
        $res = [];//最后返回的结果
        //****************************** 获取参数 ******************************
        $type = $this->request->input('type');    //查询方式:1需要指定具体的公众号id；2不需要指定具体的公众号id（查一天的所有公众号的打卡小时分布数）
        $wechatId = $this->request->input('wechatId');//公众号id:此获取参数方式不传即为null
        $dayDate = $this->request->input('dayDate'); //选定的日期值（年-月-日）
        //****************************** 参数验证 ******************************
        //1.空检查
        if (!$this->checkValidAndEmpty($type)) {//如果没有传查询类型，就给定默认值1
            $type = 1;
        } else {//如果有传参，检查是否为指定值:目前只定义了2种类型
            $validTypeArr = [1, 2];
            if (!in_array(intval($type), $validTypeArr)) {//如果不在有效范围内，就返回参数错误
                echoJson(210, 'type参数错误！');
            }//在就没问题
        }
        switch (intval($type)) {
            case 1:
                if (!$this->checkValidAndEmpty($wechatId)) {//公众号id是否存在于wechat表中，暂不做判断
                    echoJson(220, '公众号id缺失！');
                }
                $res['wechatId'] = $wechatId;//公众号id
                break;
            case 2://不需要指定具体的公众号id
                break;
            default:
                break;
        }
        if (!$this->checkValidAndEmpty($dayDate)) {
            $dayDate = date('Y-m-d', time());//若为空，就取当天日期
            //echoJson(230,'日期缺失！');
        }
        //2.检查日期格式是否符合要求：使用默认的日期格式'Y-m-d'，例如"2018-09-11"
        if (!$this->checkDateValid($dayDate)) {
            echoJson(240, '日期无效！');
        }
        //****************************** 查询数据 ******************************
        //各个小时的打卡数
        $userSign = new UserSign();
        $punchClockNum = $userSign->getCardTimeScatterOfSingleWechat($type, $wechatId, $dayDate);
        //*********************  将查询的数据进行格式化汇总  *********************
        $res['date'] = $dayDate;//时间
        $res['cardTimeScatter'] = $punchClockNum;
        echoJson(200, 'success', $res);
    }

    /***
     * 公众号分步：各公众号单日用户分布
     * 日期/天；公众号；用户数（sum）；活跃数（sum）；打卡数（sum）；
     */
    function getWechatScatter()
    {
        //****************************** 获取参数 ******************************
        $dayDate = $this->request->input('dayDate'); //选定的日期值（年-月-日）
        //****************************** 参数验证 ******************************
        //1.空检查
        if (!$this->checkValidAndEmpty($dayDate)) {
            $dayDate = date('Y-m-d', time());//若为空，就取当天日期
        }
        //2.检查日期格式是否符合要求：使用默认的日期格式'Y-m-d'，例如"2018-09-11"
        if (!$this->checkDateValid($dayDate)) {
            echoJson(210, '日期无效！');
        }
        //****************************** 查询数据 ******************************
        //同一天的各个公众号的数值（用户数，活跃数，打卡数）
        $wechatStats = new WechatStats();
        $wechatScatterData = $wechatStats->getStatDataOfAllWechat($dayDate);
        //*********************  将查询的数据进行格式化汇总  *********************
        $res = [
            'date' => $dayDate,//时间
            'wechatScatter' => $wechatScatterData,
        ];
        echoJson(200, 'success', $res);
    }
}
