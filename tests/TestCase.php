<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function setUp()
    {
        parent::setUp();
        exec('cp ' . base_path() . '/tests/db/stub.sqlite ' . base_path() . '/tests/db/test.sqlite');
        $this->runDatabaseMigrations();
    }

    public function runDatabaseMigrations()
    {
        $this->artisan('migrate');

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }

    protected function db()
    {
        return $this->app->make('db');
    }
}
