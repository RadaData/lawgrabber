<?php

namespace LawPages;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use LawPages\Renderer\RenderManager;

class LawPagesServiceProvider extends RouteServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot(Router $router)
    {
        parent::boot($router);

        $this->loadViewsFrom(__DIR__ . '/views', 'lawpages');
        $this->app['view']->composer('lawpages::law', 'LawPages\LawViewComposer');
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