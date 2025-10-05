<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        $databasePath = dirname(__DIR__) . '/database/database.sqlite';

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        parent::setUp();

        $this->withServerVariables([
            'HTTP_HOST' => config('panels.merchant_domain'),
            'SERVER_NAME' => config('panels.merchant_domain'),
        ]);
    }
}
