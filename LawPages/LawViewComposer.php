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
            'Стан' => $this->getRevisionState($revision),
            'Прийнято' => $law->date,
            'Сайт ВР' => 'http://zakon.rada.gov.ua/go/' . $law->id //. ($law->active_revision != $revision->date ? '/ed' . str_replace('-', '', $revision->date) : ''),
        ];

        if ($files = $this->getLawFiles($law)) {
            $data['Файли'] = $files;
        }

        return $data;
    }

    private function setRevisionState(Revision $revision, $state)
    {
        Revision::where('id', $revision->id)->update(['state' => $state]);
        $revision->state = $state;
        return $state;
    }
    
    public function getRevisionState(Revision $revision) {
        if ($revision->state) {
            return $revision->state;
        }

        if ($revision->getLaw()->state == 'Не визначено') {
            return $this->setRevisionState($revision, 'Не визначено');
        }

        $activities = [
            'Прийняття' => 'Набирає чинності',
            'Введення в дію' => 'Чинний',
            'Набрання чинності' => 'Чинний',
            'Припинення дії' => 'Втратив чинність',
            'Зупинення дії' => 'Дію зупинено',
            'Відновлення дії' => 'Дію відновлено',
            'Не набрав чинності' => 'Не набрав чинності'
        ];
        $result = null;
        foreach (explode("\n", $revision->comment) as $line) {
            foreach ($activities as $activity => $new_state) {
                if (preg_match('%' . $activity . '(?:$|,)%u', $line)) {
                    $result = $new_state;
                    break;
                }
            }
        }
        if ($result) {
            return $this->setRevisionState($revision, $result);
        }

        $previous_revision = $revision->getPreviousRevision();
        if ($previous_revision) {
            return $this->setRevisionState($revision, $this->getRevisionState($previous_revision));
        }
        else {
            return $this->setRevisionState($revision, $revision->getLaw()->state);
        }
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
     * @param bool $is_raw
     *
     * @return string
     * @throws LawHasNoTextAtRevision
     */
    private function getLawText(Revision $revision, $is_raw = false)
    {
        $text = $this->getRevisionText($revision);

        $render_manager = new RenderManager($text, $revision, $is_raw);
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
    public function getRevisionText(Revision $revision) {
        $law = $revision->getLaw();
        if ($law->notHasText()) {
            return '';
        }
        if ($revision->text) {
            return $revision->text;
        }

        $previous_revision = Revision::where('text', '<>', '')
            ->where('law_id', $revision->law_id)
            ->where('date', '<', $revision->date)
            ->orderBy('date', 'desc')
            ->first();

        if (!$previous_revision) {
            return '';
        }

        return $previous_revision->text;

    }


}