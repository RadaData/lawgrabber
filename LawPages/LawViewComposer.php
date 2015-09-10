<?php

namespace LawPages;

use Illuminate\View\View;
use LawGrabber\Laws\Exceptions\LawHasNoTextAtRevision;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawPages\Renderer\RenderManager;

class LawViewComposer
{

    /**
     * @param View $view
     */
    public function compose(View $view)
    {
        if ($view->offsetExists('revision')) {
            $revision = $view->offsetGet('revision');
        }
        else {
            if ($view->offsetExists('law')) {
                $law = $view->offsetGet('law');
                $revision = $law->active_revision()->first();
            }
            else {
                abort(404, 'No law or revision passed.');
            }
        }

        $is_raw = $view->offsetExists('raw') && $view->offsetGet('raw');

        $view->with('meta', $this->getLawMetaData($revision));
        $view->with('text', $this->getLawText($revision, $is_raw));
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

        // TODO: law type, law issuer.

        return $data;
    }

    /**
     * @param Revision $revision
     *
     * @return string
     */
    private function formatRevisionTitle(Revision $revision) {
        $output = $revision->date;
        if ($revision->comment) {
            $output .= ' (' . $revision->comment . ')';
        }
        return $output;
    }

    /**
     * @param Revision $revision
     *
     * @return string
     * @throws LawHasNoTextAtRevision
     */
    private function getLawText(Revision $revision, $is_raw = false)
    {
        $text = $this->getRevisionText($revision);

        if ($is_raw) {
            return $text;
        }

        $render_manager = new RenderManager($text);
        return $render_manager->render();
    }

    /**
     * @param Revision $revision
     *
     * @return string
     * @throws LawHasNoTextAtRevision
     */
    private function getRevisionText(Revision $revision) {
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