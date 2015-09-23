<?php

namespace LawGrabber\Downloader;

use Symfony\Component\DomCrawler\Crawler;

class CardDownloader extends BaseDownloader
{

    /**
     * @param string $law_id Id of the law.
     * @param array  $options
     *
     * @return array[
     *   'html' => string,
     *   'meta' => array,
     *   'has_text' => bool,
     *   'revisions' => array[
     *     ['law_id' => string, 'date' => string, 'comment' => string, 'no_text' => null|bool,
     *     ...
     *   ],
     *   'active_revision' => string,
     *   'changes_laws' => null|array[
     *     ['id' => string, 'date' => string],
     *     ...
     *   ]
     * ]
     *
     * @throws Exceptions\DocumentHasErrors
     */
    public function downloadCard($law_id, $options = [])
    {
        $url = '/laws/card/' . $law_id;
        $options += [
            'law_id' => $law_id,
        ];
        return $this->download($url, $options);
    }

    /**
     * Extract data from the downloaded content.
     *
     * @param $html
     * @param $status
     * @param $options
     *
     * @return array
     * @throws Exceptions\DocumentHasErrors
     */
    protected function process($html, $status, $options)
    {
        $law_id = $options['law_id'];

        $data = [];
        $crawler = crawler($html)->filter('.txt');
        $data['card'] = $crawler->html();
        $data['meta'] = [];
        $last_field = null;
        $crawler->filterXPath('//h2[text()="Картка документа"]/following-sibling::dl[1]')->children()->each(function (Crawler $node) use (&$data, &$last_field, $law_id) {
            if ($node->getNode(0)->tagName == 'dt') {
                $last_field = rtrim($node->text(), ':');
                $data['meta'][$last_field] = [];
            } elseif ($node->getNode(0)->tagName == 'dd') {
                if ($last_field == 'Дати') {
                    $data['date'] = $this->parseDate($node->filterXPath('//font')->text(), "Law date is not valid in card of '{$law_id}'");
                }
                $data['meta'][$last_field][] = $node->text();
            }
        });
        if (!isset($data['date'])) {
            throw new Exceptions\DocumentHasErrors("Law date is missing in '{$law_id}'");
        }
        $data['title'] = $crawler->filterXPath('//h1')->html();
        $data['title'] = str_replace(' <img src="http://zakonst.rada.gov.ua/images/fav1.gif" title="Популярний">', '', $data['title']);

        $data['has_text'] = (strpos($html, 'Текст відсутній') === false && strpos($html, 'Текст документа') !== false);

        $data['revisions'] = [];
        $last_revision = null;
        $data['active_revision'] = null;
        $crawler->filterXPath('//h2[contains(text(), "Історія документа")]/following-sibling::dl[1]')->children()->each(function (Crawler $node) use (&$data, &$last_revision, $law_id) {
            if ($node->getNode(0)->tagName == 'dt') {
                $raw_date = $node->filterXPath('//span[@style="color: #004499" or @style="color: #006600"]')->text();
                $date = $this->parseDate($raw_date, "Revision date '{$raw_date}' is not valid in card of '{$law_id}'");
                $last_revision = count($data['revisions']);

                $data['revisions'][] = [
                    'law_id'  => $law_id,
                    'date'    => $date,
                    'comment' => [],
                ];
                if (!$node->filter('a')->count()) {
                    $data['revisions'][$last_revision]['no_text'] = true;
                }

                if (str_contains($node->text(), 'поточна редакція')) {
                    $data['active_revision'] = $data['revisions'][$last_revision]['date'];
                }
            } elseif ($node->getNode(0)->tagName == 'dd') {
                $comment = $node->html();
                if (strpos($comment, '<a name="Current"></a>') !== false) {
                    $data['active_revision'] = $data['revisions'][$last_revision]['date'];
                }

                $comment = str_replace('<a name="Current"></a>', '', $comment);
                $comment = preg_replace('|<u>(.*?)</u>|u', '$1', $comment);

                $data['revisions'][$last_revision]['comment'][] = $comment;
            }
        });
        foreach ($data['revisions'] as $date => &$revision) {
            $revision['comment'] = implode("\n", $revision['comment']);
        }

        if (!$data['active_revision'] && $data['has_text']) {
            $sub_options = $options;
            $sub_url = '/laws/show/' . $law_id;
            $sub_options['url'] = $sub_url;
            $this->download($sub_url, $options, function($html, $status, $options) use ($data) {
                $d = app()->make('lawgrabber.revision_downloader');
                try {
                    $data['active_revision'] = $d->getRevisionDate($html, '', '');
                }
                catch (\Exception $e) {
                    throw new Exceptions\DocumentHasErrors("Card has text, but no revisions in '{$law_id}'");
                }
            });
        }

        if (isset($options['check_related']) && $options['check_related']) {
            $changes_link =
                $crawler->filterXPath('//h2[contains(text(), "Пов\'язані документи")]/following-sibling::dl[1]/*/a/font[text()="Змінює документ..."]/..');
            if ($changes_link->count()) {
                $list = $this->downloadList($changes_link->attr('href'));
                $data['changes_laws'] = $list['laws'];
                for ($i = 2; $i <= $list['page_count']; $i++) {
                    $list = $this->downloadList($changes_link->attr('href') . '/page' . $i);
                    $data['changes_laws'] += $list['laws'];
                }
            }
        }

        return $data;
    }

    private function downloadList()
    {
        return call_user_func_array([app()->make('lawgrabber.list_downloader'), 'downloadList'], func_get_args());
    }
}