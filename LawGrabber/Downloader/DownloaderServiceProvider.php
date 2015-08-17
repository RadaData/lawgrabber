<?php

namespace LawGrabber\Downloader;

use Illuminate\Support\ServiceProvider;
use LawGrabber\Downloader\Downloader;

class DownloaderServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('lawgrabber.downloader', function ($app) {
            return new BaseDownloader(new Identity(), $app->make('lawgrabber.proxy.manager'));
        });
        $this->app->singleton('lawgrabber.list_downloader', function ($app) {
            return new ListDownloader(new Identity(), $app->make('lawgrabber.proxy.manager'));
        });
        $this->app->singleton('lawgrabber.card_downloader', function ($app) {
            return new CardDownloader(new Identity(), $app->make('lawgrabber.proxy.manager'));
        });
        $this->app->singleton('lawgrabber.revision_downloader', function ($app) {
            return new RevisionDownloader(new Identity(), $app->make('lawgrabber.proxy.manager'));
        });
    }

}