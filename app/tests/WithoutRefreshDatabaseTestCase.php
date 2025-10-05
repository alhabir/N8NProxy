<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class WithoutRefreshDatabaseTestCase extends BaseTestCase
{
    use CreatesApplication;
}
