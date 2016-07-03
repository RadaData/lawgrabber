<?php

namespace LawGrabber\Console\Commands;

use Illuminate\Console\Command;
use LawGrabber\Jobs\JobsManager;
use LawGrabber\Proxy\ProxyManager;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radadata:cleanup
                            {--a|all : Kill all proxies and jobs.}
                            {--j|jobs : Flush all jobs.}
                            {--p|proxies : Kill all proxies.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup various temp data (jobs or proxies.)';

    /**
     * @var JobsManager
     */
    private $jobsManager;

    /**
     * @var ProxyManager
     */
    private $proxy;

    /**
     * @param JobsManager $jobsManager
     * @param ProxyManager $proxyManager
     */
    public function __construct(JobsManager $jobsManager, ProxyManager $proxyManager)
    {
        parent::__construct();

        $this->jobsManager = $jobsManager;
        $this->proxy = $proxyManager;
    }

    /**
     * Execute console command.
     */
    public function handle()
    {
        if ($this->option('jobs') || $this->option('all')) {
            $this->jobsManager->deleteAll();
        }
        if ($this->option('proxies') || $this->option('all')) {
            $this->proxy->reset();
        }

        return true;
    }
}