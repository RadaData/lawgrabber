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
        $law = Law::find($law_id);
        $law_url = '/laws/show/' . $law_id;
        $edition_part = '/ed' . date_format(date_create_from_format('Y-m-d', $date), 'Ymd');

        if ($law->active_revision == $date) {
            $url = $law_url;
            $options['save_as'] = $law_url . $edition_part . '/page';
        } else {
            $url = $law_url . $edition_part;
            $options['save_as'] = $law_url . $edition_part . '/page';
        }

        $options += [
            'law_id' => $law_id,
            'date' => $date,
            'law_url' => $law_url,
            'edition_part' => $edition_part,
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
        $law_id = $options['law_id'];
        $date = $options['date'];
        $url = $options['url'];
        $law_url = $options['law_url'];
        $edition_part = $options['edition_part'];

        $data = [];
        $crawler = crawler($html)->filter('.txt');
        $data['text'] = $crawler->html();

        $revision_date = $this->getRevisionDate($html, $date, $url);
        if ($revision_date != $options['date']) {
            throw new Exceptions\RevisionDateNotFound("Revision date does not match the planned date (planned: {$date}, but found {$revision_date}).");
        }

        $pager = crawler($html)->filterXPath('(//span[@class="nums"])[1]/br/preceding-sibling::a[1]');
        $page_count = $pager->count() ? $pager->text() : 1;

        for ($i = 2; $i <= $page_count; $i++) {
            $page_url = $url . '/page' . $i;
            $options['save_as'] = $law_url . $edition_part . '/page' . $i;
            $this->download($page_url, $options, function ($html) use (&$data, $date, $url) {
                $data['text'] .= crawler($html)->filter('.txt')->html();

                $revision_date = $this->getRevisionDate($html, $date, $url);
                if ($revision_date != $date) {
                    throw new Exceptions\RevisionDateNotFound("Revision date does not match the planned date (planned: {$date}, but found {$revision_date}).");
                }
            });
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
                $raw_date = crawler($html)->filterXPath('//div[@id="pan_title"]/*/font[@color="#004499" or @color="#666666" or @color="navy" or @color="#CC0000"]/b')->text();
                $revision_date = $this->parseDate($raw_date);
            } catch (\Exception $e) {
                throw new Exceptions\WrongDateException("Revision date has not been found in text of $url");
            }
        }

        return $revision_date;
    }
}