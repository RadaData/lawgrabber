<?php

namespace LawPages;

use Illuminate\View\View;
use LawGrabber\Laws\Exceptions\LawHasNoTextAtRevision;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use League\HTMLToMarkdown\HtmlConverter;

class LawRenderer
{

    /**
     * @param View $view
     */
    public function compose(View $view)
    {
        $law = $view->offsetGet('law');
        if ($view->offsetExists('revision')) {
            $revision = $view->offsetGet('revision');
        }
        else {
            $revision = $law->active_revision()->first();
        }

        $view->with('text', $this->getText($law, $revision));
    }

    /**
     * @param Law      $law
     * @param Revision $revision
     *
     * @return string
     * @throws LawHasNoTextAtRevision
     */
    public function getText(Law $law, Revision $revision)
    {
        $meta = $this->getLawMeta($revision);
        $text = $this->toMD($this->getLawText($revision));

        return $meta . $text;
    }

    /**
     * @param Revision $revision
     *
     * @return string
     */
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

    private function toMD($text)
    {
        $converter = new HtmlConverter();
        return $converter->convert($text);
    }

}