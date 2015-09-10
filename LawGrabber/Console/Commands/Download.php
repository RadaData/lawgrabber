<?php

namespace LawGrabber\Console\Commands;

use Illuminate\Console\Command;
use DB;
use LawGrabber\Jobs\JobsManager;
use LawGrabber\Jobs\Exceptions\JobChangePriorityException;
use LawGrabber\Laws\Issuer;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawGrabber\Downloader\Exceptions;
use LawGrabber\Laws\State;
use LawGrabber\Laws\Type;

class Download extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download
                            {law? : The law ID.}
                            {revision? : The revision date.}
                            {--l|law : Reset the download jobs pool and fill it with download jobs for NOT DOWNLOADED laws.}
                            {--r|reset : Reset the download jobs pool and fill it with download jobs for NOT DOWNLOADED laws.}
                            {--d|re-download : Re-download any page from the live website.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download laws scheduled for download.';

    /**
     * @var JobsManager
     */
    private $jobsManager;

    private $reset = false;

    private $re_download = false;

    /**
     * @param JobsManager $jobsManager
     */
    public function __construct(JobsManager $jobsManager)
    {
        parent::__construct();

        $this->jobsManager = $jobsManager;
    }

    /**
     * Execute console command.
     */
    public function handle()
    {
        $law_id = $this->argument('law');
        $revision_date = $this->argument('revision');

        $this->reset = $this->option('reset');
        $this->re_download = $this->option('re-download');

        if ($law_id) {
            if ($revision_date && $revision_date != '@') {
                $this->downloadRevision($law_id, $revision_date, $this->re_download);
            } else {
                $this->downloadCard($law_id, $this->re_download);
                if ($revision_date == '@') {
                    $this->downloadRevision($law_id, null, $this->re_download);
                }
            }
        } else {
            if ($this->reset) {
                $this->downloadNewLaws();
            }
            $this->jobsManager->launch(50, 'download');
        }

        return true;
    }

    /**
     * Reset download jobs.
     */
    function downloadNewLaws()
    {
        $this->jobsManager->deleteAll('download');

        $laws = DB::table('laws')->where('date', '<', max_date())->whereIn('status', [
            Law::NOT_DOWNLOADED,
            Law::DOWNLOADED_BUT_NEEDS_UPDATE,
        ])->select('id', 'status')->get();
        foreach ($laws as $law) {
            $this->jobsManager->add('command.lawgrabber.download', 'downloadCard', [
                'id'          => $law->id,
                're_download' => $law->status == Law::DOWNLOADED_BUT_NEEDS_UPDATE,
            ], 'download', 1);
        }

        // Cards with ??.??.???? should be rescanned only once in a while.
        $laws = DB::table('laws')->where('date', '<', max_date())->where('status', Law::DOWNLOADED_BUT_HAS_UNKNOWN_REVISION)->where('card_updated', '<', time() + 3600 * 24)->select('id')->get();
        foreach ($laws as $law) {
            $this->jobsManager->add('command.lawgrabber.download', 'downloadCard', [
                'id'          => $law->id,
                're_download' => true,
            ], 'download', 1);
        }

        $revisions = DB::table('law_revisions')->where('status', Revision::NEEDS_UPDATE)->select('law_id', 'date')->get();
        foreach ($revisions as $revision) {
            $this->jobsManager->add('command.lawgrabber.download', 'downloadRevision', [
                'law_id' => $revision->law_id,
                'date'   => $revision->date,
            ], 'download');
        }
    }

    /**
     * Download a specific law's card page.
     *
     * @param string $id          Law ID.
     * @param bool   $re_download Whether or not to re-download card page.
     *
     * @return Law
     * @throws JobChangePriorityException
     * @throws Exceptions\ProxyBanned
     */
    function downloadCard($id, $re_download = false)
    {
        /**
         * @var $law Law
         */
        $law = Law::find($id);

        try {
            $card = downloadCard($id, [
                're_download'   => $re_download || $this->re_download,
                'check_related' => $law->status == Law::NOT_DOWNLOADED && !max_date(),
            ]);
        } catch (Exceptions\ProxyBanned $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = str_replace('ShvetsGroup\Service\Exceptions\\', '', get_class($e)) .
                ($e->getMessage() ? ': ' . $e->getMessage() : '');
            throw new JobChangePriorityException($message, -15);
        }

        DB::transaction(function () use ($law, $card) {
            $law->card = $card['card'];
            $law->title = $card['title'];
            $law->date = $card['date'];
            $law->issuers()->sync($card['meta'][Issuer::FIELD_NAME]);
            $law->types()->sync($card['meta'][Type::FIELD_NAME]);
            $law->state = isset($card['meta'][State::FIELD_NAME]) ? reset($card['meta'][State::FIELD_NAME]) : State::STATE_UNKNOWN;

            $law->has_text = $card['has_text'] ? $law->has_text = Law::HAS_TEXT : $law->has_text = Law::NO_TEXT;

            $has_unknown_revision = false;
            foreach ($card['revisions'] as &$revision) {
                if ($revision['date'] == '??.??.????') {
                    $has_unknown_revision = true;
                    continue;
                }
                $data = [
                    'law_id'  => $revision['law_id'],
                    'date'    => $revision['date'],
                    'comment' => $revision['comment'],
                ];
                if ($law->notHasText() || (isset($revision['no_text']) && $revision['no_text'] && $revision['date'] != $card['active_revision'])) {
                    $data['status'] = Revision::NO_TEXT;
                    $data['text'] = '';
                }
                $r = Revision::findROrNew($data['law_id'], $data['date']);
                $r->save();
                $r->update($data);
            }
            // We should update revision which has just come into power.
            if ($law->active_revision && $law->active_revision != $card['active_revision']) {
                Revision::find($data['law_id'], $card['active_revision'])->update(['status' => Revision::NEEDS_UPDATE]);
            }
            $law->active_revision = $card['active_revision'];

            foreach ($law->revisions()->where('status', Revision::NEEDS_UPDATE)->get() as $revision) {
                $this->jobsManager->add('command.lawgrabber.download', 'downloadRevision', [
                    'law_id' => $revision->law_id,
                    'date'   => $revision->date,
                ], 'download', $revision->date == $law->active_revision ? 0 : -1);
            }

            if (isset($card['changes_laws']) && $card['changes_laws']) {
                Law::where('id', array_column($card['changes_laws'], 'id'))->update(['status' => Law::DOWNLOADED_BUT_HAS_UNKNOWN_REVISION]);
                foreach ($card['changes_laws'] as $l) {
                    $this->jobsManager->add('command.lawgrabber.download', 'downloadCard', [
                        'id'          => $l['id'],
                        're_download' => true,
                    ], 'download', 2);
                }
            }

            $law->card_updated = $card['timestamp'];

            $law->status = $has_unknown_revision ? Law::DOWNLOADED_BUT_HAS_UNKNOWN_REVISION : Law::UP_TO_DATE;

            $law->save();
        });

        return $law;
    }

    /**
     * Job failure callback.
     *
     * @param            $id
     * @param bool|false $re_download
     */
    public function downloadCardFail($id, $re_download = false)
    {
        Law::find($id)->update(['status' => Law::DOWNLOAD_ERROR]);
    }

    /**
     * Download a specific law's revision pages.
     *
     * @param string $law_id
     * @param string $date
     * @param bool   $re_download Whether or not to re-download card page.
     *
     * @return Revision
     * @throws JobChangePriorityException
     * @throws Exceptions\ProxyBanned
     */
    public function downloadRevision($law_id, $date = null, $re_download = false)
    {
        $law = Law::find($law_id);

        if (!$date) {
            $date = $law->active_revision;
        }

        $revision = $law->getRevision($date);

        if ($revision->text && !$re_download) {
            $revision->update([
                'status' => Revision::UP_TO_DATE,
            ]);

            return $revision;
        }

        if ($law->notHasText()) {
            $revision->update([
                'status' => Revision::NO_TEXT,
            ]);

            return $revision;
        }

        if ($revision->status != Revision::NEEDS_UPDATE && !$re_download) {
            return $revision;
        }

        try {
            try {
                $data = downloadRevision($revision->law_id, $revision->date, ['re_download' => $re_download]);
            } catch (Exceptions\RevisionDateNotFound $e) {
                $this->downloadCard($law->id, true);
                $data = downloadRevision($revision->law_id, $revision->date, ['re_download' => $re_download]);
            }
        } catch (Exceptions\RevisionDateNotFound $e) {
            // If no revision date was found second time, this means that the revision actually does not have a text change.
            $revision->update([
                'status' => Revision::NO_TEXT,
            ]);

            return $revision;
        } catch (Exceptions\ProxyBanned $e) {
            throw $e;
        } catch (Exceptions\OfflinePriority $e) {
            $message = str_replace('ShvetsGroup\Service\Exceptions\\', '', get_class($e)) .
                ($e->getMessage() ? ': ' . $e->getMessage() : '');
            throw new JobChangePriorityException($message, -1);
        } catch (\Exception $e) {
            $message = str_replace('ShvetsGroup\Service\Exceptions\\', '', get_class($e)) .
                ($e->getMessage() ? ': ' . $e->getMessage() : '');
            throw new JobChangePriorityException($message, -15);
        }

        $revision->update([
            'text'         => $data['text'],
            'text_updated' => $data['timestamp'],
            'status'       => Revision::UP_TO_DATE,
        ]);

        return $revision;
    }

    /**
     * Job failure callback.
     *
     * @param            $law_id
     * @param            $date
     * @param bool|false $re_download
     */
    public function downloadRevisionFail($law_id, $date, $re_download = false)
    {
        $law = Law::find($law_id);
        $revision = $law->getRevision($date);
        $revision->update(['status' => Revision::DOWNLOAD_ERROR]);
    }
}