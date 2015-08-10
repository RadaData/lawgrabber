<?php

namespace LawGrabber\Jobs;

use Illuminate\Support\ServiceProvider;

class JobsServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('lawgrabber.jobs.manager', function ($app) {
            return new JobsManager($app->make('lawgrabber.proxy.manager'));
        });
    }

}