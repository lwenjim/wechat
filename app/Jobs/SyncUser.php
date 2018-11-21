<?php

namespace App\Jobs;

class SyncUser extends Job
{
    public $tries = 3;
    public $timeout = 600;
    protected $appid;
    protected $next_openid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($appid, $next_openid = '')
    {
        $this->appid = $appid;
        $this->next_openid = $next_openid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        syncRegisterUser($this->appid, $this->next_openid);
    }
}
