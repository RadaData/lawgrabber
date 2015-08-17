<?php

namespace LawGrabber\Downloader;

use JonnyW\PhantomJs\Client as PJClient;
use LawGrabber\Proxy\ProxyManager;
use LawGrabber\Downloader\Exceptions;

class BaseDownloader
{

    const SUCCESS = 10;
    const FAILURE = 3;

    private $stop_words = [
        '404'   => [
            '502 Bad Gateway',
            'Ліміт перегляду списків на сьогодні',
            'Дуже багато відкритих сторінок за хвилину',
            'Доступ до списку заборонен',
            'Документи потрібно відкривати по одному',
            'Сторiнку не знайдено',
            'Доступ тимчасово обмежено',
            'Документ не знайдено!',
            'Цього списку вже немає в кеші.',
        ],
        '403'   => [
            'Error 403',
            'Доступ заборонено',
            'Ваш IP автоматично заблоковано',
            'Ви потрапили до забороненого ресурсу',
        ],
        'error' => [
            '??.??.????',
        ],
    ];

    /**
     * @var Identity
     */
    private $identity;

    /**
     * @var ProxyManager
     */
    private $proxyManager;

    /**
     * @param Identity     $identity
     * @param ProxyManager $proxyManager
     */
    public function __construct(Identity $identity, ProxyManager $proxyManager)
    {
        $this->identity = $identity;
        $this->proxyManager = $proxyManager;
    }

    /**
     * Download a page.
     *
     * @param string   $url URL of the page.
     * @param array    $options
     *                      - bool $re_download Whether or not to re-download the page if it's already in cache.
     *                      - bool $save Whether or not to cache a local copy of the page.
     *                      - string $save_as Alternative file name for the page.
     *                      download successful.
     *
     * @param callable $process_callback
     *
     * @return string
     * @throws Exceptions\DocumentCantBeDownloaded
     * @throws Exceptions\DocumentHasErrors
     * @throws Exceptions\DocumentIsMissing
     * @throws Exceptions\ProxyBanned
     * @throws Exceptions\UnknownProblem
     */
    public function download($url, $options = [], callable $process_callback = null)
    {
        $default_options = [
            'url'         => $url,
            're_download' => false,
            'save'        => true,
            'save_as'     => null,
        ];
        $options = array_merge($default_options, $options);

        $save_as = $options['save_as'] ? $options['save_as'] : null;

        $output = $this->shortURL($url) . ': ';

        if ($this->isDownloaded($save_as ?: $url) && !$options['re_download']) {
            $file_path = $this->URL2path($save_as ?: $url);
            $html = file_get_contents($file_path);
            $status = 200;

            try {
                if (!$this->validate($html, $status, $options)) {
                    throw new \Exception('Can not validate saved file.');
                }

                $output .= ('* ');
                _log($output);

                $result = $this->doProcess($html, $status, $options, $process_callback);

                return $result;

            } catch (\Exception $e) {
                unlink($file_path);
            }
        }

        try {
            $output = ($this->proxyManager->getProxyAddress() . '/' . $this->proxyManager->getProxyIp() . ' → ' . $output . ' @');

            $result = [];
            $attempts = 0;
            do {
                // log failed stages when loop restarts
                if ($result) {
                    $output .= '-' . $status;
                }

                $attempts++;
                $result = $this->doDownload($url);
                $html = $result['html'];
                $status = $result['status'];

                if (!$this->validate($html, $status, $options)) {
                    continue;
                }

                try {
                    $result = $this->doProcess($html, $status, $options, $process_callback);
                }
                catch (\Exception $e) {
                    continue;
                }

                $output .= '-' . $status . '-OK';

                if ($options['save']) {
                    $this->saveFile($save_as ?: $url, $html);
                }

                return $result;

            } while ($attempts < 5);

            throw new Exceptions\DocumentCantBeDownloaded('Too many failed attempts (' . $attempts . ').');
        }
        finally {
            $this->proxyManager->releaseProxy();
            _log($output);
        }
    }

    /**
     * Perform the actual download.
     *
     * @param string $url
     * @param int    $delay
     *
     * @return array
     */
    private function doDownload($url, $delay = 5)
    {
        $client = PJClient::getInstance();
        if ($this->proxyManager->useProxy()) {
            $client->addOption('--proxy=' . $this->proxyManager->getProxyAddress());
        }
        $client->addOption('--load-images=false');
        $request = $client->getMessageFactory()->createRequest($this->fullURL($url));
        $request->setDelay($delay);
        $request->setTimeout(60000);
        $request->addHeader('User-Agent', $this->identity->getUserAgent());
        $response = $client->getMessageFactory()->createResponse();

        $start = time();
        $client->send($request, $response);
        $status = $response->getStatus();
        $html = $response->getContent();

        sleep(max(10, min(0, time() - $start)));

        return [
            'status' => $status,
            'html'   => preg_replace('|charset="?windows-1251"?|', 'charset="utf-8"', $html),
        ];
    }

    /**
     * Prepare download results before returning.
     *
     * @param               $html
     * @param               $status
     * @param array         $options
     * @param callable|null $process_callback
     *
     * @return array
     */
    public function doProcess($html, $status, $options = [], callable $process_callback = null)
    {
        $result = [
            'html'      => $html,
            'timestamp' => time(),
        ];

        if ($process_callback) {
            $processed_result = $process_callback($html, $status, $options);
        } else {
            $processed_result = $this->process($html, $status, $options);
        }

        return $processed_result ? array_merge($result, $processed_result) : $result;
    }

    /**
     * Validate download result.
     *
     * @param $html
     * @param $status
     * @param $options
     *
     * @return bool
     * @throws Exceptions\DocumentCantBeDownloaded
     * @throws Exceptions\DocumentHasErrors
     * @throws Exceptions\DocumentIsMissing
     * @throws Exceptions\ProxyBanned
     * @throws Exceptions\UnknownProblem
     */
    protected function validate(&$html, &$status, $options)
    {
        // access denied
        if ($status == 403 || $this->detectFakeContent($html, '403')) {
            $this->proxyManager->banProxy();
            throw new Exceptions\ProxyBanned($this->proxyManager->getProxyIp());
        }

        // document is missing or server might be down
        if (in_array($status, [204, 400, 404, 500, 502]) || $this->detectFakeContent($html, '404')) {
            $hasMoreIdentities = $this->identity->switchIdentity();
            if ($hasMoreIdentities) {
                $status = $status != 200 ? $status : 204;

                return false;
            } else {
                throw new Exceptions\DocumentIsMissing();
            }
        }

        // status is ok, but document load was not finished
        if (in_array($status, [206]) || (strpos($html, '</body>') === false)) {
            $status = 206;

            return false;
        }

        // status is ok, but document content has errors
        if ($errors = $this->detectFakeContent($html, 'error')) {
            throw new Exceptions\DocumentHasErrors($errors);
        }

        // status is ok, but document JS protected
        if ($newUrl = $this->detectJSProtection($html)) {
            $result = $this->doDownload($newUrl, 10);
            $html = $result['html'];
            $status = $result['status'];

            if ($this->detectJSProtection($html)) {
                throw new Exceptions\DocumentCantBeDownloaded('Strong JS protection.');
            }

            // do a second validation run on fresh content.
            return $this->validate($html, $status, $options);
        }

        if (!in_array($status, [200, 300, 301, 302, 303, 304, 307, 408])) {
            throw new Exceptions\UnknownProblem("Download status is {$status}.", $this->shortURL($options['url']), isset($html) ? $html : '{NO DATA}');
        }

        return true;
    }

    /**
     * Process download result and return additional data.
     *
     * @param $html
     * @param $status
     * @param $options
     *
     * @return array
     */
    protected function process($html, $status, $options)
    {
        return [];
    }

    /**
     * Save the HTML content to specified path under downloads dir.
     *
     * @param string $path
     * @param string $html
     */
    private function saveFile($path, $html)
    {
        $path = $this->URL2path($path);
        $dir = preg_replace('|/[^/]*$|', '/', $path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        // replace encoding to utf8 to achieve nice browsing experience.
        file_put_contents($path, $html);
    }

    /**
     * Return a directory, where all the downloads will be saved.
     *
     * @return string
     */
    protected function getDownloadsDir()
    {
        return base_path() . '/' . trim(env('DOWNLOADS_DIR', '../downloads'), '/');
    }

    /**
     * Whether or not the url can be found in download dir.
     *
     * @param string $url
     *
     * @return bool
     */
    public function isDownloaded($url)
    {
        if (!$url) {
            return false;
        }

        $path = $this->URL2path($url);

        return file_exists($path);
    }

    /**
     * Return full URL (with domain name) of the page by given path or short url.
     *
     * @param string $url Path or short URL.
     * @param bool   $urlencode
     *
     * @return mixed|string
     */
    public function fullURL($url, $urlencode = true)
    {
        $url = $this->shortURL($url);

        $protocol = '';
        if (preg_match('@^(https?|file|ftp)://@', $url, $matches)) {
            $protocol = $matches[0];
            $url = preg_replace('@^(https?|file|ftp)://@', '', $url);
        }

        if ($urlencode) {
            list($url, $query) = explode('?', $url . '?');
            $url_parts = explode('/', $url);
            $new_url = [];
            foreach ($url_parts as $part) {
                $new_url[] = urlencode($part);
            }
            $url = $protocol . implode('/', $new_url);
            if ($query) {
                $query = urlencode($query);
                $query = preg_replace('|%3d|i', '=', $query);
                $query = preg_replace('|%26|i', '&', $query);
                $url .= '?' . $query;
            }
        }

        if (!preg_match('@^(https?|file|ftp)://@', $url)) {
            $url = $this->identity->getMirror() . $url;
        }

        return $url;
    }

    /**
     * Return short URL (without domain name) of the page by given path or short url.
     *
     * @param string $url Path or long URL.
     *
     * @return string
     */
    public function shortURL($url)
    {
        $url = preg_replace('|' . $this->getWebsiteRegexp() . '|', '', $url);

        if (strpos($url, '%') !== false) {
            $url = urldecode($url);
        }

        return $url;
    }

    /**
     * Get the regular expression to cut the domain from the RADA addresses.
     *
     * @return string
     */
    public function getWebsiteRegexp()
    {
        return '^(https?://)*zakon([0-9]*)\.rada\.gov\.ua';
    }

    /**
     * Return file path under downloads dir for a given short or long URL.
     *
     * @param $url
     *
     * @return mixed|string
     */
    public function URL2path($url)
    {
        $url = $this->fullURL($url, false);

        $path = preg_replace('@(https?|file|ftp)://@', '', $url);
        $path = preg_replace('@zakon[0-9]+\.rada@', 'zakon.rada', $path);

        if (substr($path, -1) == '/') {
            $path .= 'index.html';
        } else {
            $path .= '.html';
        }
        $path = $this->getDownloadsDir() . '/' . $path;

        return $path;
    }

    /**
     * Sometimes even when download seem to be successful, the actual page contains crap. This function tries to detect
     * such cases to signal page for re-download.
     *
     * @param string $html HTML content of the page.
     * @param string $type Type of error to detect (all, 403, 404).
     *
     * @return bool
     */
    public function detectFakeContent($html, $type = 'all')
    {
        if ($html == '' && ($type != '403')) {
            return '{document is empty}';
        }
        if (strpos($html, '</body>') === false && ($type != '403' && $type != '404')) {
            return '{document is not fully downloaded}';
        }
        //if (($type == 'error' || $type == 'all') &&
        //    (strpos($html, 'class=txt') === false &&
        //        strpos($html, 'class="txt') === false &&
        //        strpos($html, 'hdr2') === false &&
        //        strpos($html, 'valid txt') === false
        //    )) {
        //    return true;
        //}
        if ($type == 'all') {
            $words = array_merge($this->stop_words['404'], $this->stop_words['403']);
        } else {
            $words = $this->stop_words[$type];
        }

        return $this->contains($html, $words);
    }

    private function contains($str, array $arr)
    {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false) {
                return $a;
            }
        }

        return false;
    }

    /**
     * See if download triggered a JS robot protection.
     *
     * @param string $html HTML content of the page.
     *
     * @return bool
     */
    public function detectJSProtection($html)
    {
        if (preg_match('|<a href="?(.*)\?test=(.*)"? target="?_top"?><b>посилання</b></a>|', $html, $matches)) {
            return $matches[1] . '?test=' . $matches[2];
        }

        return false;
    }

    protected function parseDate($radaDate, $error_text = null)
    {
        $raw_date = preg_replace('|([0-9]{2}\.[0-9]{2}\.[0-9]{4}).*|', '$1', $radaDate);
        if (!preg_match('|[0-9]{2}\.[0-9]{2}\.[0-9]{4}|', $raw_date)) {
            $error_text = $error_text ?: "Date {$radaDate} is not valid date.";
            throw new Exceptions\WrongDateException($error_text);
        }
        $date = date_format(date_create_from_format('d.m.Y', $raw_date), 'Y-m-d');

        return $date;
    }
}
