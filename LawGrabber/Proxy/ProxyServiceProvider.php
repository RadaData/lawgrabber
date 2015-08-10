<?php

namespace LawGrabber\Proxy;

use Illuminate\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('lawgrabber.proxy.manager', function ($app) {
            return new ProxyManager(new ListProxy());
        });
    }

}