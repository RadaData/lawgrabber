<?php

class TestCase extends Laravel\Lumen\Testing\TestCase
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

    public function beginDatabaseTransaction()
    {
        $this->app->make('db')->beginTransaction();

        $this->beforeApplicationDestroyed(function () {
            $this->app->make('db')->rollBack();
        });
    }

    public function db()
    {
        return $this->app->make('db');
    }

    protected function assertArraysEqual($a, $b, $strict = false, $message = '')
    {
        if (count($a) !== count($b)) {
            $this->fail($message);
        }
        sort($a);
        sort($b);
        $this->assertTrue(($strict && $a === $b) || $a == $b, $message);
    }
}
