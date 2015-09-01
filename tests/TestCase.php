<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    use DatabaseMigrations;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        exec('cp ' . base_path() . '/tests/db/stub.sqlite ' . base_path() . '/tests/db/test.sqlite');
    }

    protected function db()
    {
        return $this->app->make('db');
    }
}
