<?php

namespace LawGrabber\History;

use Illuminate\Support\ServiceProvider;

class HistoryServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'history');
        $this->app['view']->composer('history::revision', 'LawGrabber\History\HistoryViewComposer');
    }
}