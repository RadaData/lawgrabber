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

        $cards_discovered = Law::count();
        $most_recent = Law::orderBy('date', 'desc')->take(1)->value('date');
        $most_recent_diff = floor((time() - (strtotime($most_recent)))/3600/24);
        $most_recent_age = $most_recent_diff ? $most_recent_diff . ' days ago' : 'up to date';

        $cards_downloaded = Law::where('status', Law::DOWNLOADED_CARD)->count();
        $cards_downloaded_p = floor(($cards_downloaded / ($cards_discovered ?: ($cards_downloaded ?: 1))) * 100);
        $cards_needs_update = Law::where('status', '<', Law::DOWNLOADED_CARD)->count();
        $cards_with_text = Law::where('has_text', '<', Law::HAS_TEXT)->count();
        $cards_without_text = Law::where('has_text', Law::NO_TEXT)->count();
        $cards_errors = Law::where('status', Law::DOWNLOAD_ERROR)->count();

        $revisions_count = Revision::count();
        $revisions_with_text = Revision::where('status', '<', Revision::NO_TEXT)->count();
        $revisions_without_text = Revision::where('status', Revision::NO_TEXT)->count();
        $revisions_needs_update = Revision::where('status', Revision::NEEDS_UPDATE)->count();
        $revisions_downloaded = Revision::where('status', Revision::UP_TO_DATE)->count();
        $revisions_downloaded_p = floor(($revisions_downloaded / ($revisions_with_text ?: ($revisions_downloaded ?: 1))) * 100);
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
        exec('pgrep -l -f "^php (.*?)artisan start"', $output);
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

        $cards_discovered = number_format($cards_discovered);
        $l = strlen($cards_discovered);
        $cards_downloaded = str_pad(number_format($cards_downloaded), $l, ' ', STR_PAD_LEFT);
        $cards_needs_update = str_pad(number_format($cards_needs_update), $l, ' ', STR_PAD_LEFT);
        $cards_with_text = str_pad(number_format($cards_with_text), $l, ' ', STR_PAD_LEFT);
        $cards_without_text = str_pad(number_format($cards_without_text), $l, ' ', STR_PAD_LEFT);
        $cards_errors = str_pad(number_format($cards_errors), $l, ' ', STR_PAD_LEFT);

        $revisions_count = number_format($revisions_count);
        $l = strlen($revisions_count);
        $revisions_with_text = str_pad(number_format($revisions_with_text), $l, ' ', STR_PAD_LEFT);
        $revisions_without_text = str_pad(number_format($revisions_without_text), $l, ' ', STR_PAD_LEFT);
        $revisions_downloaded = str_pad(number_format($revisions_downloaded), $l, ' ', STR_PAD_LEFT);
        $revisions_needs_update = str_pad(number_format($revisions_needs_update), $l, ' ', STR_PAD_LEFT);
        $revisions_errors = str_pad(number_format($revisions_errors), $l, ' ', STR_PAD_LEFT);

        $jobs_count = number_format($jobs_count);
        $jobs_discovery = number_format($jobs_discovery);
        $jobs_download_cards = number_format($jobs_download_cards);
        $jobs_download_revisions = number_format($jobs_download_revisions);
        $jobs_last_10_minutes = number_format($jobs_last_10_minutes);
        $jobs_last_hour = number_format($jobs_last_hour);
        $jobs_last_day = number_format($jobs_last_day);

        $status = <<<STATUS
=== Errors in log: {$errors}

=== Discovered laws: {$cards_discovered}
    Most recent law: {$most_recent} ({$most_recent_age})


=== Downloaded:
-------- Cards:
         - all: {$cards_discovered}
  - downloaded: {$cards_downloaded} ({$cards_downloaded_p}%)
 - need-update: {$cards_needs_update}
      - errors: {$cards_errors}

    - with-text {$cards_with_text}
     - no-text: {$cards_without_text}


---- Revisions:
         - all: {$revisions_count}
  - downloaded: {$revisions_downloaded} ({$revisions_downloaded_p}%)
 - need-update: {$revisions_needs_update}
      - errors: {$revisions_errors}

   - with-text: {$revisions_with_text}
     - no-text: {$revisions_without_text}


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