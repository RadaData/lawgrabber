<?php

namespace LawHistory\Console\Commands;

use Illuminate\Console\Command;
use LawGrabber\Laws\Exceptions\LawHasNoTextAtRevision;
use LawGrabber\Laws\Revision;
use Log;

class History extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history
                            {--d|date : Generate history up to this date.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate document history.';

    /**
     * Execute console command.
     */
    public function handle()
    {
        $date = $this->option('date') ?: env('MAX_CRAWL_DATE', date('Y-m-d'));

        $date = '1886-09-09';

        if (!is_dir($this->getHistoryDir())) {
            mkdir($this->getHistoryDir(), 0777, true);
        }

        $revisions = Revision::where('date', '<', $date)->get();
        foreach ($revisions as $revision) {
            try {
                if ($this->save($revision)) {
                    $this->info('History saved: #' . $revision->law_id . '/' . $revision->date);
                }
                else {
                    $this->line('History skipped: #' . $revision->law_id . '/' . $revision->date);
                }
            }
            catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    public function getHistoryDir($filename = null)
    {
        $dir = base_path() . '/' . trim(env('HISTORY_DIR', '../history'), '/');
        return $dir . ($filename ? '/' . $filename : '');
    }

    public function save(Revision $revision)
    {
        if ($revision->status != Revision::UP_TO_DATE) {
            return false;
        }

        $filename = $this->getHistoryDir($revision->law_id . '.md');
        $text = view('lawpages::law')->with([
            'revision' => $revision
        ]);

        file_put_contents($filename, $text);
        return true;
    }
}