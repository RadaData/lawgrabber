<?php

namespace LawGrabber\Console\Commands;

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

        $filename = $revision->law_id . '.md';
        $text = $this->renderRevision($revision);
        file_put_contents($this->getHistoryDir($filename), $text);
        return true;
    }

    public function renderRevision(Revision $revision)
    {
        $meta = $this->getLawMeta($revision);
        $text = $this->getLawText($revision);
        return $meta . $text;
    }

    private function getLawMeta(Revision $revision) {
        $data = $this->getLawMetaData($revision);

        $meta = "---\n";
        foreach ($data as $key => $value) {
            $meta .= $key . ': ' . $value . "\n";
        }
        $meta .= "---\n\n";
        return $meta;
    }

    /**
     * @param Revision $revision
     *
     * @return array
     */
    private function getLawMetaData(Revision $revision)
    {
        $law = $revision->getLaw();
        $data = [
            'Назва'         => $law->title,
            'Стан'          => $law->state,
            'Прийнято'      => $law->date,
            //'Набрав чинність' => $law->getEnforceDate(),
            'Останні зміни' => $this->formatRevisionTitle($law->getActiveRevision()),
        ];

        return $data;
    }

    private function formatRevisionTitle(Revision $revision) {
        $output = $revision->date;
        if ($revision->comment) {
            $output .= ' (' . $revision->comment . ')';
        }
        return $output;
    }

    private function getLawText(Revision $revision) {
        $law = $revision->getLaw();
        if ($law->notHasText()) {
            return '';
        }

        if ($revision->text) {
            return $revision->text;
        }

        $last_revision_with_text = Revision::where('text', '<>', '')->where('law_id', $revision->law_id)->where('date', '<', $revision->date)->orderBy('date', 'asc')->first();
        if (!$last_revision_with_text) {
            throw new LawHasNoTextAtRevision($law, $revision);
        }

        return $last_revision_with_text->text;
    }
}