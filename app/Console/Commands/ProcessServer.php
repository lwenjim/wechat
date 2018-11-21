<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Swoole\Process;
use Swoole\Timer;

class ProcessServer extends Command
{
    protected $signature = 'process:start {--d}';
    protected $description = 'Swoole Process Server';
    protected $config = [];
    protected $master = null;
    protected $masterFile = '.process';
    protected $slaves = [];
    protected $workers = [];

    public function handle()
    {
        if (file_exists($this->masterFile)) {
            $master = $this->getMaster();
            if ($master && Process::kill($master, 0)) {
                exit('已有进程运行中,请先结束或重启' . PHP_EOL);
            }
        }
        if ($this->option('d')) Process::daemon();
        $this->master = getmypid();
        $this->setMaster();
        $this->setProcessName('processServerMaster:' . $this->master);
        $this->config = config('process');
        foreach ($this->config['app'] as $key => $value) {
            if (!isset($value['command']) || !isset($value['number'])) {
                continue;
            }
            $slave['name'] = $value['name'];
            $slave['command'] = $value['command'];
            $slave['arguments'] = $value['arguments'];
            //开启多个子进程
            for ($i = 0; $i < $value['number']; $i++) {
                $this->runSlave($i, $slave);
            }
            $this->slaves[$value['name']] = $value['number'];
        }
        $this->handleSignal();
    }

    /**
     * 启动子进程，跑业务代码
     *
     * @param [type] $slave
     * @param mixed $number
     */
    public function runSlave($number, $slave)
    {
        $worker = new Process(function ($worker) use ($number, $slave) {
            $this->checkMaster($worker);
            $worker->exec($slave['command'], $slave['arguments']);
        });
        $pid = $worker->start();
        $this->workers[$pid] = $worker;
        echo 'processServerSlave:' . $pid . ':' . $slave['name'] . count($this->workers) . 'master:' . $this->master . "\n";
    }

    /**
     * 注册信号
     */
    public function handleSignal()
    {
        Process::signal(SIGTERM, function ($signo) {
            $this->killAll();
        });
        Process::signal(SIGKILL, function ($signo) {
            $this->killAll();
        });
        Process::signal(SIGUSR1, function ($signo) {
            $this->waitWorkers();
        });
        Process::signal(SIGCHLD, function ($signo) {
            while ($ret = Process::wait(false)) {
                $worker = $this->workers[$ret['pid']];
                $newpid = 0;
                while (!$newpid) {
                    $newpid = $worker->start();
                }
                $this->workers[$newpid] = $worker;
                unset($this->workers[$ret['pid']]);
            }
        });
    }

    /**
     * 平滑等待子进程退出之后，再退出主进程
     */
    private function killAll()
    {
        if ($this->workers) {
            foreach ($this->workers as $pid => $worker) {
                //强制杀workers子进程
                if (Process::kill($pid) == true) {
                    unset($this->workers[$pid]);
                }
            }
        }
        $this->killMaster();
    }

    /**
     * 强制杀死子进程并退出主进程
     */
    private function waitWorkers()
    {

    }

    /**
     * 设置进程名.
     *
     * @param mixed $name
     */
    private function setProcessName($name)
    {
        //mac os不支持进程重命名
        if (function_exists('swoole_set_process_name') && PHP_OS != 'Darwin') {
            swoole_set_process_name($name);
        }
    }

    private function setMaster()
    {
        file_put_contents($this->masterFile, $this->master);
    }

    private function getMaster()
    {
        return file_get_contents($this->masterFile);
    }

    /**
     * 主进程如果不存在了，子进程退出
     */
    private function checkMaster(&$worker)
    {
        if (!Process::kill($this->master, 0)) {
            $worker->exit();
        }
    }

    /**
     * 退出主进程
     */
    private function killMaster()
    {
        @unlink($this->masterFile);
        sleep(1);
        exit();
    }
}