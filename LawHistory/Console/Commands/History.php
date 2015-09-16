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
        $this->gitReset();

        foreach ($this->selectRevisions() as $revision) {
            try {
                if ($this->save($revision)) {
                    $this->gitCommit($revision);
                }
            }
            catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->gitPush();
    }

    private function selectRevisions() {
//        $date = $this->option('date') ?: env('MAX_CRAWL_DATE', date('Y-m-d'));
//        $date = '1886-09-09';
        return Revision::where('law_id', '254к/96-вр')->get(); // where('date', '<', $date)->get();
    }

    private function gitReset() {
        $dir = $this->getHistoryDir();
        if (is_dir($dir)) {
            exec('rm -rf ' . $dir);
        }
        mkdir($dir, 0777, true);
        exec('cd ' . $dir . '; git init');
    }
    
    private function gitCommit(Revision $revision) {
        $dir = $this->getHistoryDir();
        $commit_time = strtotime($revision->date);
        $commit_message = $this->formatRevisionTitle($revision);
        $command = "cd $dir; git add . ; GIT_AUTHOR_DATE=$commit_time GIT_COMMITTER_DATE=$commit_time git commit -m \"$commit_message\"";
        exec($command);

        $this->info('History saved: ' . $revision->law_id . '/ed' . $revision->date);
    }
    
    private function formatRevisionTitle(Revision $revision)
    {
        $output = $revision->law_id . '/ed' . str_replace('-', '', $revision->date);

        if ($revision->comment) {
            $comment = $revision->comment;
            $comment = preg_replace('%<a href="(.*?)" target="_blank">(.*?)</a>%', '[$2]($1)', $comment);

            $output .= ': ' . $comment;
        }
        return $output;
    }

    private function gitPush() {
        $dir = $this->getHistoryDir();
        $command = "cd $dir; git remote add origin git@github.com:RadaData/zakon.git; git push -u -f origin master";
        exec($command);
    }

    public function getHistoryDir($filename = null)
    {
        $dir = base_path() . '/' . trim(env('HISTORY_DIR', '../history'), '/');
        return $dir . ($filename ? '/' . $filename : '');
    }

    public function save(Revision $revision)
    {
        if ($revision->status != Revision::UP_TO_DATE && $revision->status != Revision::NO_TEXT) {
            return false;
        }

        $filename = $this->getHistoryDir($revision->law_id . '.md');
        $text = view('lawpages::law')->with([
            'revision' => $revision
        ]);

        $dir = preg_replace('|/[^/]*$|', '/', $filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($filename, $text);
        return true;
    }
}