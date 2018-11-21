<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserSign;

class SignAdd extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $user;
    protected $user_ip;
    protected $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $user_ip, $date)
    {
        $this->user = $user;
        $this->user_ip = $user_ip;
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //补签输入插入一条day=0，add=1，created_at为补签那天的记录
        $this->user->signs()->create(['user_ip' => $this->user_ip, 'day' => 0, 'date' => $this->date, 'add' => 1, 'created_at' => $this->date . ' ' . date('H:i:s')]);
        //计算当天到上次断签时间的天数
        $user_sign = $this->user->signs()->select('id', 'date')->orderBy('created_at', 'desc')->get();
        $user_sign_count = count($user_sign);
        for ($i = 0; $i < $user_sign_count; $i++) {
            if ($user_sign[$i]->date != date('Y-m-d', strtotime('-' . $i . ' day'))) {
                break;
            }
        }
        //更新day
        $m = 0;
        for ($j = $i; $j > 0; $j--) {
            $this->user->signs()->where('id', $user_sign[$m]->id)->update(['day' => $j]);
            $m++;
        }
        //连续天数
        $this->user->update(['day' => $i]);
        $this->user->sign_years()->where('year', date('Y'))->increment('number');
        //清除缓存
        UserSign::cacheFlush();
    }
}
