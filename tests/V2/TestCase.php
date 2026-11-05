<?php

namespace Tests\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase as BaseTestCase;
use Tests\V2\Support\Fixtures;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $name = DB::connection()->getDatabaseName();
        if (substr($name, -5) !== '_test') {
            $this->fail("V2 tests run only against a database ending in _test; connected to '$name'.");
        }

        Fixtures::reset();
    }
}
