<?php

namespace LawGrabber\Console;

use Illuminate\Support\ServiceProvider;
use Route;
use Cache;

class ConsoleServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('command.lawgrabber.check', function ($app) {
            return new Commands\Check();
        });

        $this->app->singleton('command.lawgrabber.cleanup', function ($app) {
            return new Commands\Cleanup($app['lawgrabber.jobs.manager'], $app['lawgrabber.proxy.manager']);
        });

        $this->app->singleton('command.lawgrabber.discover', function ($app) {
            return new Commands\Discover($app['lawgrabber.jobs.manager'], $app['lawgrabber.laws.meta']);
        });

        $this->app->singleton('command.lawgrabber.download', function ($app) {
            return new Commands\Download($app['lawgrabber.jobs.manager']);
        });

        $this->app->singleton('command.lawgrabber.start', function ($app) {
            return new Commands\Start($app['lawgrabber.jobs.manager'], $app['lawgrabber.proxy.manager']);
        });

        $this->app->singleton('command.lawgrabber.status', function ($app) {
            return new Commands\Status();
        });

        $this->commands([
            'command.lawgrabber.check',
            'command.lawgrabber.cleanup',
            'command.lawgrabber.discover',
            'command.lawgrabber.download',
            'command.lawgrabber.start',
            'command.lawgrabber.status',
        ]);

    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Route::get('/status', function () {
            $output = Cache::get('status');

            if (!$output) {
                $output = app()->make('command.lawgrabber.status')->handle(true);
                Cache::put('status', $output, 1);
            }

            return "<pre>$output</pre>";
        });
    }

}