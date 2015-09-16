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
        } else {
            if ($view->offsetExists('law')) {
                $law = $view->offsetGet('law');
                $revision = $law->active_revision()->first();
            } else {
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
            'Назва' => $law->title,
            'Тип' => implode(', ', $law->getTypes()),
            'Видавник' => implode(', ', $law->getIssuers()),
            'Стан' => $law->state,
            'Прийнято' => $law->date,
        ];

        if ($files = $this->getLawFiles($law)) {
            $data['Файли'] = $files;
        }

        return $data;
    }

    private function getLawFiles($law)
    {
        $rada_url = 'http://zakon1.rada.gov.ua';
        $files = [];
        if (preg_match_all('|Сигнальний документ — <b><a href="(.*?)"|', $law->card, $matches)) {
            foreach ($matches[1] as $match) {
                $files[] = $rada_url . $match;
            }
        }
        return $files;
    }

    private function renderLawFiles($law)
    {
        $files = $this->getLawFiles($law);
        
        if (!$files) {
            return '';
        }

        $result = "\n\n## Пов’язані файли\n\n";
        foreach ($files as $file) {
            $result .= '- [' . basename($file) . '](' . $file . ')' . "\n";
        }
        
        return $result;
    }

    /**
     * @param Revision $revision
     *
     * @return string
     */
    private function formatRevisionTitle(Revision $revision)
    {
        $output = $revision->date;
        if ($revision->comment) {
            $comment = $revision->comment;
            $comment = preg_replace('%<a href="(.*?)" target="_blank">(.*?)</a>%', '[$2]($1)', $comment);
            
            $output .= ' (' . $comment . ')';
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

        $render_manager = new RenderManager($text, $revision);
        $text = $render_manager->render();

        $text .= $this->renderLawFiles($revision->getLaw());

        return $text;
    }

    /**
     * @param Revision $revision
     *
     * @return string
     * @throws LawHasNoTextAtRevision
     */
    private function getRevisionText(Revision $revision)
    {
        $law = $revision->getLaw();
        if ($law->notHasText()) {
            return '';
        }

        if ($revision->text) {
            return $revision->text;
        }

        $last_revision_with_text = Revision::where('text', '<>', '')
            ->where('law_id', $revision->law_id)
            ->where('date', '<', $revision->date)
            ->orderBy('date', 'asc')
            ->first();
        
        if (!$last_revision_with_text) {
            return '{ТЕКСТ ВІДСУТНІЙ}';
        }

        return $last_revision_with_text->text;
    }

}