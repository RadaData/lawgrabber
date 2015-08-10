<?php

namespace LawGrabber\Laws;

use Illuminate\Support\ServiceProvider;

class LawsServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('lawgrabber.laws.meta', function ($app) {
            return new LawsMeta();
        });
    }

}