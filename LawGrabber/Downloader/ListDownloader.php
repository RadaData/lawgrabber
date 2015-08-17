<?php

namespace LawGrabber\Downloader;

use Symfony\Component\DomCrawler\Crawler;

class ListDownloader extends BaseDownloader
{

    /**
     * @param string $url List url to download
     * @param array  $options
     *
     * @return array[
     *   'html' => string,
     *   'page_count' => integer,
     *   'laws' => array[
     *     ['id' => string, 'date' => string],
     *     ...
     *   ]
     * ]
     */
    public function downloadList($url, $options = [])
    {
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
     */
    protected function process($html, $status, $options)
    {
        $data = [];
        $data['laws'] = [];
        $page = crawler($html);
        $last_pager_link = $page->filterXPath('//*[@id="page"]/div[2]/table/tbody/tr[1]/td[3]/div/div[2]/span/a[last()]');
        $data['page_count'] = $last_pager_link->count() ? preg_replace('/(.*?)([0-9]+)$/', '$2', $last_pager_link->attr('href')) : 1;

        $page->filterXPath('//*[@id="page"]/div[2]/table/tbody/tr[1]/td[3]/div/dl/dd/ol/li')->each(
            function (Crawler $node) use (&$data) {
                $url = $node->filterXPath('//a')->attr('href');
                $id = preg_replace('|/laws/show/|', '', shortURL($url));

                $raw_date = $node->filterXPath('//font[@color="#004499"]')->text();
                $date = $this->parseDate($raw_date, "Date has not been found in #{$id} at text: " . $node->text());

                $data['laws'][$id] = [
                    'id'   => $id,
                    'date' => $date,
                ];
            }
        );

        return $data;
    }
}