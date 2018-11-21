<?php

namespace App\Jobs;

class TplMsg extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $id;
    protected $url;
    protected $data;
    protected $openid;
    protected $origin;
    protected $appid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $url, $data, $openid, $origin = 1, $appid = null)
    {
        $this->id = $id;
        $this->url = $url;
        $this->data = $data;
        $this->openid = $openid;
        $this->origin = $origin;
        $this->appid = $appid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        sendTplMsg($this->id, $this->url, $this->data, $this->openid, $this->origin, $this->appid);
    }
}
