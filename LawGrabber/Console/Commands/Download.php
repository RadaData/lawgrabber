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
        $this->reset = $this->option('reset');
        $this->re_download = $this->option('re-download');

        if ($this->reset) {
            $this->downloadNewLaws();
        }
        $this->jobsManager->launch(50, 'download');

        return true;
    }

    /**
     * Reset download jobs.
     */
    function downloadNewLaws()
    {
        $this->jobsManager->deleteAll('download');

        Law::where('status', '<', Law::DOWNLOADED_CARD)->where('date', '<', max_date())->where('date', '<', max_date())->chunk(200, function ($laws) {
            foreach ($laws as $law) {
                $this->jobsManager->add('command.lawgrabber.download', 'downloadCard', [
                    'id'          => $law->id,
                    're_download' => $law->status == Law::DOWNLOADED_BUT_NEEDS_UPDATE
                ], 'download', 1);
            }
        });
        Revision::where('status', Revision::NEEDS_UPDATE)->chunk(200, function ($revisions) {
            foreach ($revisions as $revision) {
                $this->jobsManager->add('command.lawgrabber.download', 'downloadRevision', [
                    'law_id' => $revision->law_id,
                    'date'   => $revision->date,
                ], 'download');
            }
        });
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
                'check_related' => $law->status == Law::NOT_DOWNLOADED && !max_date()
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

            foreach ($card['revisions'] as $date => &$revision) {
                $data = [
                    'law_id'  => $revision['law_id'],
                    'date'    => $revision['date'],
                    'comment' => $revision['comment']
                ];
                // We should be careful with statuses, since we don't want to re-download already downloaded revisions.
                if (isset($revision['no_text']) && $revision['no_text']) {
                    $data['status'] = Revision::NO_TEXT;
                }
                if (isset($revision['needs_update']) && $revision['needs_update']) {
                    $data['status'] = Revision::NEEDS_UPDATE;
                }
                $r = Revision::findROrNew($data['law_id'], $data['date']);
                $r->save();
                $r->update($data);
            }
            $law->active_revision = $card['active_revision'];

            foreach ($law->revisions()->where('status', Revision::NEEDS_UPDATE)->get() as $revision) {
                $this->jobsManager->add('command.lawgrabber.download', 'downloadRevision', [
                    'law_id' => $revision->law_id,
                    'date'   => $revision->date,
                ], 'download', $revision->date == $law->active_revision ? 0 : -1);
            }

            if (isset($card['changes_laws']) && $card['changes_laws']) {
                Law::where('id', array_column($card['changes_laws'], 'id'))->update(['status' => Law::DOWNLOADED_BUT_NEEDS_UPDATE]);
                foreach ($card['changes_laws'] as $l) {
                    $this->jobsManager->add('command.lawgrabber.download', 'downloadCard', [
                        'id'          => $l['id'],
                        're_download' => true
                    ], 'download', 2);
                }
            }

            $law->card_updated = $card['timestamp'];

            $law->status = Law::DOWNLOADED_CARD;

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
    public function downloadCardFail($id, $re_download = false) {
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
    public function downloadRevision($law_id, $date, $re_download = false)
    {
        $law = Law::find($law_id);
        $revision = $law->getRevision($date);

        if ($law->notHasText()) {
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
        }
        catch (Exceptions\ProxyBanned $e) {
            throw $e;
        }
        catch (\Exception $e) {
            $message = str_replace('ShvetsGroup\Service\Exceptions\\', '', get_class($e)) .
                ($e->getMessage() ? ': ' . $e->getMessage() : '');
            throw new JobChangePriorityException($message, -15);
        }

        DB::transaction(function () use ($law, $revision, $data) {
            $revision->update([
                'text'         => $data['text'],
                'text_updated' => $data['timestamp'],
                'status'       => Revision::UP_TO_DATE
            ]);
        });

        return $revision;
    }

    /**
     * Job failure callback.
     *
     * @param            $law_id
     * @param            $date
     * @param bool|false $re_download
     */
    public function downloadRevisionFail($law_id, $date, $re_download = false) {
        $law = Law::find($law_id);
        $revision = $law->getRevision($date);
        $revision->update(['status' => Revision::DOWNLOAD_ERROR]);
    }
}