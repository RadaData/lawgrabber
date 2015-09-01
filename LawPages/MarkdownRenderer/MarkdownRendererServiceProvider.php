<?php

namespace LawHistory\History;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class HistoryServiceProvider extends RouteServiceProvider
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
        $this->app['view']->composer('history::revision', 'LawHistory\History\HistoryViewComposer');
    }

    /**
     * Define the routes for the application.
     *
     * @param  Router $router
     *
     * @return void
     */
    public function map(Router $router)
    {
        $router->group(['namespace' => __NAMESPACE__ . '\Controllers'], function () {
            require __DIR__ . '/routes.php';
        });
    }
}