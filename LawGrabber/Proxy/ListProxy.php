<?php

namespace LawGrabber\Proxy;

class ListProxy implements IProxyProvider
{
    private $proxy_list = [];
    private $banned_list_path;

    public function __construct($proxy_list = null)
    {
        if (is_array($proxy_list)) {
            $this->proxy_list = $proxy_list;
        }
        elseif ($listFromEnvFile = $this->getProxyListFromEnvFile()) {
            $this->proxy_list = $listFromEnvFile;
        }
        $this->banned_list_path = storage_path() . '/banned_proxies.txt';
    }

    public function getProxyListFromEnvFile()
    {
        $file = base_path('.env_proxies');
        if (file_exists($file)) {
            return file($file);
        }
    }

    public function get($count = null, $reset = false)
    {
        $proxies = [];
        if (file_exists($this->banned_list_path)) {
            $banned = file($this->banned_list_path, FILE_IGNORE_NEW_LINES);
        }
        else {
            $banned = [];
        }

        foreach ($this->proxy_list as $proxy) {
            $ip = trim(preg_replace('|:.*|', '', $proxy));
            if (array_search($ip, $banned) === false) {
                $proxies[$proxy] = $ip;
            }
        }
        return $proxies;
    }

    public function ban($ip)
    {
        unset($this->proxy_list[$ip]);
        if (file_exists($this->banned_list_path)) {
            $banned = file($this->banned_list_path, FILE_IGNORE_NEW_LINES);
        }
        else {
            $banned = [];
        }
        $banned[] = $ip;
        file_put_contents($this->banned_list_path, implode("\n",$banned));
    }

    public function reset()
    {
        if (file_exists($this->banned_list_path)) {
            unlink($this->banned_list_path);
        }
    }
}