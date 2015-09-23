<?php

namespace LawHistory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query;
use Illuminate\Database\Eloquent;
use LawGrabber\Laws\Revision;
use LawHistory\Formatter;
use LawHistory\Git;
use DB;

class History extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history
                            {--c|create : Whether or not to create new fresh repository.}
                            {--r|raw : Whether or not to put raw texts into repository.}
                            {--d|date : Generate history up to this date.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate document history.';

    /**
     * @var Git
     */
    private $git;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * Execute console command.
     */
    public function handle()
    {
        $is_raw = (bool)$this->option('raw');
        $create = (bool)$this->option('create');
        $this->formatter = new Formatter($this, $is_raw);
        $this->git = new Git($this, $is_raw ? 'zakon' : 'zakon-markdown');

        if ($create) {
            $this->info('Resetting repository caches.');
            DB::table('law_revisions')->update(['r_' . $this->git->repository_name => 0]);
            
            $this->info('Initializing repository.');
            $this->git->gitReset();
        }

        $this->forAllDatesWithLaws(function ($dates) {
            $i = 0;
            foreach ($dates as $date) {
                if ($this->isFutureDate($date->date)) {
                    $this->git->gitCheckIfOutdatedAndPush('master');
                }
                
                $this->handleOneDayOfLaws($date->date);
                
                if ($i > 100) {
                    $i = 0;
                    $this->git->gitCheckIfOutdatedAndPush('master');
                }
                
                $i++;
            }
        });
        $this->git->gitCheckIfOutdatedAndPush('master');
    }
    
    public function isFutureDate($date) {
        return $date > date('Y-m-d');
    }

    public function forAllDatesWithLaws($func)
    {
        $this->filterQuery(DB::table('law_revisions')
            ->select('date')->where('r_' . $this->git->repository_name, 0)
            ->groupBy('date'))->chunk(300, $func);
    }

    /**
     * @param Eloquent\Builder|Query\Builder $query
     * @return Eloquent\Builder|Query\Builder
     */
    private function filterQuery($query)
    {
//        $date = '1991-11-05';
//        $query->whereIn('law_id', ['254к/96-вр', '586-18'])
//            ->where('date', '>=', $date);
        
        return $query;
    }

    public function handleOneDayOfLaws($date)
    {
        $commits = $this->groupRevisionsForCommits($date);

        foreach ($commits as $commit) {
            $branch = $this->getBranchName($commit, $date);
            $this->git->gitCheckout($branch);

            $this->doChanges($commit);

            $message = $this->formatter->createCommitMessage($branch, $commit);
            $this->git->gitCommit($date, $message);

            if ($branch != 'master') {
                $this->git->gitPush($branch);
                $pr_title = $this->formatter->createPRTitle($branch, $commit);
                $pr_body = $this->formatter->createPRBody($branch, $commit);
                $this->git->gitSendPullRequest($branch, $pr_title, $pr_body);
            }
            
            DB::table('law_revisions')->where('date', $date)->whereIn('law_id', array_keys($commit))
                ->update(['r_' . $this->git->repository_name => 1]);
        }
    }

    public function groupRevisionsForCommits($date)
    {
        /**
         * @var $all Revision[]
         */
        $all = $this->filterQuery(Revision::where(['date' => $date]))->get();

        $revisions = [];
        foreach ($all as $revision) {
            $revisions[$revision->law_id] = $revision;
        }

        foreach ($revisions as $revision) {
            $revision->related = array_merge($this->getRevisionReferences($revision), $revision->related ?: []);
            foreach ($revision->related as $ref) {
                if (!isset($revisions[$ref])) {
                    continue;
                }
                $r = $revisions[$ref]->related ?: [];
                $r[$revision->law_id] = $revision->law_id;
                $revisions[$ref]->related = $r;
            }
        }

        $commits = [];
        reset($revisions);
        while (list($key, $revision) = each($revisions)) {
            $commit = [$key => $revision];
            foreach ($revision->related as $ref) {
                if (!isset($revisions[$ref])) {
                    continue;
                }
                $commit[$ref] = $revisions[$ref];
                unset($revisions[$ref]);
            }

            $main_revision = $this->getRevisionWithNoReferences($commit);
            unset($commit[$main_revision->law_id]);
            $commit = array_merge([$main_revision->law_id => $main_revision], $commit);

            $commits[] = $commit;
        }
        return $commits;
    }

    private function getRevisionReferences(Revision $revision)
    {
        if ($revision->references) {
            return $revision->references;
        }
        preg_match_all('%<a href=".*?" target="_blank">(.*?)</a>%u', $revision->comment, $matches);
        $result = isset($matches[1]) ? $matches[1] : [];
        $revision->references = array_combine($result, $result);
        return $revision->references;
    }

    private function getRevisionWithNoReferences(array $commit)
    {
        foreach ($commit as $revision) {
            if (!$this->getRevisionReferences($revision)) {
                return $revision;
            }
        }
        return reset($commit);
    }

    public function doChanges(array $commit)
    {
        foreach ($commit as $revision) {
            $this->save($revision);
        }
    }

    public function save(Revision $revision)
    {
        if ($revision->status != Revision::UP_TO_DATE && $revision->status != Revision::NO_TEXT) {
            return false;
        }

        $text = $this->formatter->getRevisionText($revision);
        $path = $this->getRevisionFilePath($revision);
        
        file_put_contents($path, $text);
        
        return true;
    }

    public function getRevisionFilePath(Revision $revision)
    {
        $path = $this->formatter->getLawURL($revision->law_id, $this->git->getHistoryDir());
        $this->createDirs($path);
        return $path;
    }

    public function createDirs($path)
    {
        $dir = preg_replace('|/[^/]*$|', '/', $path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    public function getBranchName($commit, $date)
    {
        if ($this->isFutureDate($date)) {
            return reset($commit)->law_id . '@' . $date;
        }
        return 'master';
    }
}