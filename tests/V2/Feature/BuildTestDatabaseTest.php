<?php

namespace Tests\V2\Feature;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * The guards of tlcmap:test-db. Not a V2 TestCase: the command purges the connection, which a
 * test transaction would not survive, and nothing here writes to any database.
 */
class BuildTestDatabaseTest extends TestCase
{
    public function test_refuses_a_name_not_ending_in_test()
    {
        $this->assertSame(1, Artisan::call('tlcmap:test-db', ['--database' => 'temp_tlcmap']));
        $this->assertStringContainsString("Refusing to build 'temp_tlcmap'", Artisan::output());
    }

    public function test_refuses_to_drop_schemas_when_the_open_connection_is_not_the_test_database()
    {
        // Whatever the configuration says, the connection the command opens reports another
        // database; nothing may be run on it.
        $connection = Mockery::mock(Connection::class)->shouldIgnoreMissing();
        $connection->shouldReceive('getDatabaseName')->andReturn('temp_tlcmap');
        $connection->shouldNotReceive('unprepared', 'statement', 'select', 'insert', 'update');
        // Keyed by driver as well as name, so the maintenance connection is made as usual.
        DB::extend('pgsql', function ($config, $name) use ($connection) {
            return $name === 'pgsql' ? $connection : app('db.factory')->make($config, $name);
        });

        $this->assertSame(1, Artisan::call('tlcmap:test-db', ['--database' => 'tlcmap_test']));
        $this->assertStringContainsString("Refusing to drop schemas in 'temp_tlcmap'", Artisan::output());
    }
}
