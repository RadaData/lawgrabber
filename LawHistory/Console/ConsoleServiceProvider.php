<?php

namespace LawHistory\Console;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('command.lawhistory.history', function ($app) {
            return new Commands\History();
        });

        $this->commands([
            'command.lawhistory.history',
        ]);
    }

}