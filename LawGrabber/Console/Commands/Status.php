<?php

namespace LawGrabber\Console\Commands;

use Illuminate\Console\Command;
use DB;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawGrabber\Jobs\Job;

class Status extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows the grabber progress.';

    /**
     * Execute the console command.
     */
    public function handle($web = false)
    {
        DB::beginTransaction();

        $errors = 0;
        if (file_exists(log_path() . '/log.txt')) {
            $errors = substr_count(file_get_contents(log_path() . '/log.txt'), '|%|');
        }

        $discovered_count = Law::count();
        $most_recent = Law::orderBy('date', 'desc')->take(1)->value('date');
        $most_recent_diff = floor((time() - (strtotime($most_recent)))/3600/24);
        $most_recent_age = $most_recent_diff ? $most_recent_diff . ' days ago' : 'up to date';

        $cards_downloaded = Law::where('status', Law::DOWNLOADED_CARD)->count();
        $cards_downloaded_p = round(($cards_downloaded / ($discovered_count ?: ($cards_downloaded ?: 1))) * 100);
        $revisions_count = Revision::where('status', '<', Revision::NO_TEXT)->count();
        $revisions_downloaded = Revision::where('status', Revision::UP_TO_DATE)->count();
        $revisions_downloaded_p = round(($revisions_downloaded / ($revisions_count ?: ($revisions_downloaded ?: 1))) * 100);

        $cards_errors = Law::where('status', Law::DOWNLOAD_ERROR)->count();
        $revisions_errors = Revision::where('status', Revision::DOWNLOAD_ERROR)->count();

        $jobs_count = Job::where('finished', 0)->count();
        $jobs_last_10_minutes = Job::where('finished', '>', time() - 600)->count();
        $jobs_last_hour = Job::where('finished', '>', time() - 3600)->count();
        $jobs_last_day = Job::where('finished', '>', time() - 3600 * 24)->count();

        if ($jobs_count) {
            if ($jobs_last_10_minutes) {
                $jobs_completion_time = round($jobs_count / ($jobs_last_10_minutes * 6));
                if ($jobs_completion_time == 0) {
                    $jobs_completion_time = '(estimated finish time: less than hour)';
                }
                else {
                    $jobs_completion_time = '(estimated finish time: ' . $jobs_completion_time . ' hours)';
                }
            }
            else {
                $jobs_completion_time = '(no progress)';
            }
        }
        else {
            $jobs_completion_time = '';
        }

        $output = [];
        exec('pgrep -l -f "^php artisan start"', $output);
        if (count($output)) {
            $currently_running = 'RUNNING';
        }
        else {
            $currently_running = 'IDLE';
        }

        $jobs_discovery = Job::where('finished', 0)->where('group', 'discover')->count();
        $jobs_download_cards = Job::where('finished', 0)->where('method', 'downloadCard')->count();
        $jobs_download_revisions = Job::where('finished', 0)->where('method', 'downloadRevision')->count();

        DB::commit();

        $errors = number_format($errors);
        $discovered_count = number_format($discovered_count);
        $cards_downloaded = number_format($cards_downloaded);
        $revisions_downloaded = number_format($revisions_downloaded);
        $revisions_count = number_format($revisions_count);
        $cards_errors = number_format($cards_errors);
        $revisions_errors = number_format($revisions_errors);
        $jobs_count = number_format($jobs_count);
        $jobs_discovery = number_format($jobs_discovery);
        $jobs_download_cards = number_format($jobs_download_cards);
        $jobs_download_revisions = number_format($jobs_download_revisions);
        $jobs_last_10_minutes = number_format($jobs_last_10_minutes);
        $jobs_last_hour = number_format($jobs_last_hour);
        $jobs_last_day = number_format($jobs_last_day);

        $status = <<<STATUS
=== Errors in log: {$errors}

=== Discovered laws: {$discovered_count}
    Most recent law: {$most_recent} ({$most_recent_age})

=== Downloaded:
         Cards: {$cards_downloaded} / {$discovered_count} ({$cards_downloaded_p}%)
     Revisions: {$revisions_downloaded} / {$revisions_count} ({$revisions_downloaded_p}%)

===     Errors:
         Cards: {$cards_errors}
     Revisions: {$revisions_errors}

=== Jobs: {$currently_running}
    Todo: {$jobs_count} {$jobs_completion_time}

    Discovery jobs: {$jobs_discovery}
    Download cards: {$jobs_download_cards}
    Download revisions: {$jobs_download_revisions}

    Done last 10 minutes: {$jobs_last_10_minutes}
    Done last hour: {$jobs_last_hour}
    Done last day: {$jobs_last_day}

STATUS;

        if ($web) {
            return $status;
        }
        else {
            $this->info($status);
        }
    }
}