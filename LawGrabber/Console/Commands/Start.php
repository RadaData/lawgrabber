<?php

namespace LawGrabber\Console\Commands;

use Illuminate\Console\Command;
use DB;
use LawGrabber\Laws\Law;
use LawGrabber\Proxy\ProxyManager;
use LawGrabber\Jobs\Job;
use LawGrabber\Jobs\JobsManager;

class Start extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radadata:start
                            {--s|single : Run single instance of the script (old instances will be terminated).}
                            {--p|proxies= : Whether or not to use proxy servers and how much proxies to create.}
                            {--k|kill_old_proxies : Kill old proxies and create new.}
                            {--j|job= : Execute specific job.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start or continue grabber jobs.';

    /**
     * @var int
     */
    private $workers = 10;

    /**
     * @var JobsManager
     */
    private $jobsManager;

    /**
     * @var ProxyManager
     */
    private $proxyManager;

    /**
     * @var Discover
     */
    private $discoverer;

    /**
     * @var Download
     */
    private $downloader;

    /**
     * @param JobsManager  $jobsManager
     * @param ProxyManager $proxyManager
     */
    public function __construct(JobsManager $jobsManager, ProxyManager $proxyManager)
    {
        parent::__construct();

        $this->jobsManager = $jobsManager;
        $this->proxyManager = $proxyManager;
        $this->discoverer = app()->make('command.lawgrabber.discover');
        $this->downloader = app()->make('command.lawgrabber.download');
    }

    /**
     * Execute console command.
     */
    public function handle()
    {
        if ($this->option('single')) {
            $output = [];
            exec('pgrep -l -f "^php (.*?)artisan start"', $output);
            foreach ($output as $line) {
                $pid = preg_replace('|([0-9]+)(\s.*)|u', '$1', $line);
                if ($pid != getmypid()) {
                    exec("kill -9 $pid");
                }
            }
        }

        if ($this->option('proxies')) {
            $this->workers = $this->option('proxies');
            $this->proxyManager->useProxy(true);
        }

        if ($this->proxyManager->useProxy()) {
            $this->proxyManager->connect($this->workers, $this->option('kill_old_proxies'));
        }

        if ($job_id = $this->option('job')) {
            $job = Job::find($job_id);
            if ($job) {
                $job->execute();
            }
            else {
                _log("Job {$job_id} is not found.");
            }
            return;
        }

        if (!$this->jobsManager->count()) {
            _log('No jobs found. Initializing a new discovery and download jobs.');
            $this->discoverer->discoverNewLaws();
            $this->downloader->downloadNewLaws();
        }
        $this->jobsManager->launch($this->workers);
    }
}