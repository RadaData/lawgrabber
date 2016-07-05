<?php

namespace LawGrabber\Downloader;

use LawGrabber\Laws\Law;

class RevisionDownloader extends BaseDownloader
{

    /**
     * @param string $law_id
     * @param string $date
     * @param array  $options
     *
     * @return string
     * @throws Exceptions\RevisionDateNotFound
     * @throws Exceptions\WrongDateException
     */
    public function downloadRevision($law_id, $date, $options = [])
    {
        $opendata = false;

        $law = Law::find($law_id);
        $law_url = ($opendata ? '/go/' : '/laws/show/') . $law_id;
        $edition_part = '/ed' . date_format(date_create_from_format('Y-m-d', $date), 'Ymd');

        if ($law->active_revision == $date) {
            $url = $law_url;
            $options['save_as'] = '/laws/show/' . $law_id . $edition_part . '/page';
        } else {
            $url = $law_url . $edition_part;
            $options['save_as'] = '/laws/show/' . $law_id . $edition_part . '/page';
        }

        $options += [
            'law_id' => $law_id,
            'date' => $date,
            'law_url' => $law_url,
            'edition_part' => $edition_part,
            'opendata' => $opendata,
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
     * @throws Exceptions\DocumentCantBeDownloaded
     * @throws Exceptions\RevisionDateNotFound
     * @throws Exceptions\WrongDateException
     */
    protected function process($html, $status, $options)
    {
        $opendata = strpos($html, '<div id="article">');
        $law_id = $options['law_id'];
        $date = $options['date'];
        $url = $options['url'];
        $law_url = $options['law_url'];
        $edition_part = $options['edition_part'];

        $data = [];
        $selector = $opendata ? '#article' : '.txt';
        $crawler = crawler($html)->filter($selector);
        $data['text'] = $crawler->html();

        $revision_date = $this->getRevisionDate($html, $date, $url);
        if ($revision_date != $options['date']) {
            throw new Exceptions\RevisionDateNotFound("Revision date does not match the planned date (planned: {$date}, but found {$revision_date}).");
        }

        if (!$opendata) {
            $pager = crawler($html)->filterXPath('(//span[@class="nums"])[1]/br/preceding-sibling::a[1]');
            $page_count = $pager->count() ? $pager->text() : 1;

            for ($i = 2; $i <= $page_count; $i++) {
                $page_url = $url . '/page' . $i;
                $options['save_as'] = $law_url . $edition_part . '/page' . $i;
                $this->download($page_url, $options, function ($html) use (&$data, $date, $url, $opendata) {
                    $data['text'] .= crawler($html)->filter('.txt')->html();

                    $revision_date = $this->getRevisionDate($html, $date, $url);
                    if ($revision_date != $date) {
                        throw new Exceptions\RevisionDateNotFound("Revision date does not match the planned date (planned: {$date}, but found {$revision_date}).");
                    }
                });
            }
        }

        return $data;
    }

    /**
     * @param $html
     * @param $default_date
     * @param $url
     *
     * @return bool|string
     * @throws Exceptions\WrongDateException
     */
    public function getRevisionDate($html, $default_date, $url)
    {
        if (strpos($html, 'txt txt-old') !== false) {
            $revision_date = $default_date;
        } else {
            try {
                // OpenData downloaded document.
                if (strpos($html, '<div id="article">')) {
                    $title_text = crawler($html)->filterXPath('//h3[1]')->text();
                }
                // Regular paged download.
                else {
                    $title_text = crawler($html)->filterXPath('//div[@id="pan_title"]')->text();
                }
                if (preg_match('| від ([0-9\?]{2}\.[0-9\?]{2}\.[0-9\?]{4})|u', $title_text, $matches)) {
                    $raw_date = $matches[1];
                    if ($raw_date == '??.??.????') {
                        $revision_date = $raw_date;
                    }
                    else {
                        $revision_date = $this->parseDate($raw_date);
                    }
                }
                else {
                    throw new Exceptions\WrongDateException("Revision date has not been found in text of $url");
                }
            } catch (\Exception $e) {
                throw new Exceptions\WrongDateException("Revision date has not been found in text of $url");
            }
        }

        return $revision_date;
    }
}