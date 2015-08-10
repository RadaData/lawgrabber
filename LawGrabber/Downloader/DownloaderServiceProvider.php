<?php

namespace LawGrabber\Downloader;

use Illuminate\Support\ServiceProvider;
use LawGrabber\Downloader\Downloader;

class DownloaderServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('lawgrabber.downloader', function ($app) {
            return new Downloader(new Identity(), $app->make('lawgrabber.proxy.manager'));
        });
    }

}