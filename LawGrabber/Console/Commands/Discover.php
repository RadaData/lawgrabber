<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 8/10/15
 * Time: 15:07
 */

namespace LawGrabber\Console\Commands;

use Illuminate\Console\Command;
use DB;

use LawGrabber\Jobs\JobsManager;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\LawsMeta;

class Discover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rd:discover
                            {--r|reset : Run law discovery from the beginning of time.}
                            {--m|reset-meta : Reset the law meta cache (issuers, types, etc.)}
                            {--d|re-download : Re-download any page from the live website.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover law issuers and their law listings.';

    /**
     * @var JobsManager
     */
    private $jobsManager;

    /**
     * @var LawsMeta
     */
    private $lawsMeta;

    private $reset = false;
    private $reset_meta = false;
    private $re_download = false;

    /**
     * @param JobsManager $jobsManager
     * @param LawsMeta $meta
     */
    public function __construct(JobsManager $jobsManager, LawsMeta $meta)
    {
        parent::__construct();

        $this->jobsManager = $jobsManager;
        $this->lawsMeta = $meta;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->reset = $this->option('reset');
        $this->reset_meta = $this->option('reset-meta');
        $this->re_download = $this->option('re-download');

        if ($this->lawsMeta->isEmpty() || $this->reset_meta) {
            $this->lawsMeta->parse($this->re_download);
            if ($this->reset_meta) {
                die();
            }
        }

        $this->discoverNewLaws($this->reset);

        $this->jobsManager->launch(50, 'discover', 'command.lawgrabber.discover', 'discoverDailyLawList');
        $this->jobsManager->launch(50, 'discover', 'command.lawgrabber.discover', 'discoverDailyLawListPage');

        return true;
    }

    /**
     * Add discovery jobs for all dates since most recent law.
     *
     * @param bool $reset
     */
    public function discoverNewLaws($reset = false)
    {
        $most_recent = Law::orderBy('date', 'desc')->take(1)->value('date');
        if ($most_recent && !$reset) {
            $this->addLawListJobs($most_recent . ' -1 day', true);
        }
        else {
            $this->addLawListJobs();
        }
    }

    /**
     * Schedule crawls of each law list pages.
     *
     * @param null $starting_date If not passed or null, the 1991-01-01 will be taken as default.
     * @param bool $re_download Whether or not force re-download of the listings. Might be useful when updating recent days.
     */
    protected function addLawListJobs($starting_date = null, $re_download = false)
    {
        $this->jobsManager->deleteAll('discover');

        $date = strtotime($starting_date ?: '1991-01-01 00:00:00');
        $date = max($date, strtotime('1991-01-01 00:00:00'));

        if ($date <= strtotime('1991-01-01 00:00:00')) {
            $this->jobsManager->add('command.lawgrabber.discover', 'discoverDailyLawList', [
                'law_list_url' => '/laws/main/ay1990/page',
                'date' => date('Y-m-d', $date),
            ], 'discover');
        }

        while ($date <= strtotime('midnight') && (!max_date() || (max_date() && $date < strtotime(max_date())))) {
            $this->jobsManager->add('command.lawgrabber.discover', 'discoverDailyLawList', [
                'law_list_url' => '/laws/main/a' . date('Ymd', $date) . '/sp5/page',
                'date' => date('Y-m-d', $date),
                're_download' => $re_download
            ], 'discover', 5);
            $date = strtotime(date('c', $date) . '+1 day');
        }
    }

    /**
     * Crawl the daily law list page. Take the number of law list pages from it and schedule crawls for each of them.
     *
     * @param string $law_list_url
     * @param string $date
     * @param bool $re_download
     */
    public function discoverDailyLawList($law_list_url, $date, $re_download = false)
    {
        $data = downloadList($law_list_url, [
            're_download' => $re_download || $this->re_download,
            'save' => $date != date('Y-m-d')
        ]);
        for ($i = 1; $i <= $data['page_count']; $i++) {
            $this->jobsManager->add('command.lawgrabber.discover', 'discoverDailyLawListPage', [
                'law_list_url' => $law_list_url . ($i > 1 ? $i : ''),
                'date' => $date,
                'page_num' => $i,
                're_download' => $re_download
            ], 'discover', 10);
        }
    }

    /**
     * Crawl the law list page. Take all law urls from it and add them to database.
     *
     * @param string $law_list_url Law list URL.
     * @param string $date
     * @param int $page_num
     * @param bool $re_download
     */
    public function discoverDailyLawListPage($law_list_url, $page_num, $date, $re_download = false)
    {
        $data = downloadList($law_list_url, [
            're_download' => $page_num > 1 ? ($re_download || $this->re_download) : false,
            'save' => $date != date('Y-m-d')
        ]);
        foreach ($data['laws'] as $id => $law) {
            Law::firstOrCreate(['id' => $id])->update(['date' => $law['date']]);
            $this->jobsManager->add('command.lawgrabber.download', 'downloadCard', ['id' => $id], 'download', 1);
        }
    }

}