<?php

namespace TLCMap\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Builds the database the V2 tests run against, from the migrations.
 *
 * The migrations assume things that exist only in production: the tlcmap schema, the PostGIS and
 * pg_trgm extensions, and the datasource and recordtype rows. This command creates them (the
 * extensions in the public schema), never editing a migration. Adjustments made for migrations,
 * each listed here when found:
 *
 * - The create_text_table migration inserts the geocoder datasource row with the next sequence
 *   value, id 1 on a new database. It is moved to id 4 so ids 1-3 are free for the production rows.
 */
class BuildTestDatabase extends Command
{
    protected $signature = 'tlcmap:test-db {--database=tlcmap_test : Database to (re)build; must end in _test}';

    protected $description = 'Drop and rebuild the test database from the migrations';

    public function handle()
    {
        $name = $this->option('database');
        if (substr($name, -5) !== '_test') {
            $this->error("Refusing to build '$name': the database name must end in _test.");
            return 1;
        }

        $this->createDatabaseIfAbsent($name);

        config(['database.connections.pgsql.database' => $name]);
        DB::purge('pgsql');

        // The schemas are dropped on the connection actually open, so check that it is the test one.
        $connected = DB::connection('pgsql')->getDatabaseName();
        if (substr($connected, -5) !== '_test') {
            $this->error("Refusing to drop schemas in '$connected': the database name must end in _test.");
            return 1;
        }

        DB::unprepared('DROP SCHEMA IF EXISTS tlcmap CASCADE');
        DB::unprepared('DROP SCHEMA IF EXISTS public CASCADE');
        DB::unprepared('CREATE SCHEMA public');
        DB::unprepared('CREATE SCHEMA tlcmap');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS postgis SCHEMA public');
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS pg_trgm SCHEMA public');

        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        $this->seedDatasources();
        $this->seedRecordTypes();

        $this->info("Test database '$name' is ready.");
        return 0;
    }

    private function createDatabaseIfAbsent(string $name): void
    {
        config(['database.connections.maintenance' => array_merge(
            config('database.connections.pgsql'),
            ['database' => 'postgres']
        )]);

        $maintenance = DB::connection('maintenance');
        $exists = $maintenance->selectOne('select 1 as found from pg_database where datname = ?', [$name]);
        if (!$exists) {
            // Identifiers cannot be bound; the name is validated to end in _test and quoted.
            $maintenance->unprepared('CREATE DATABASE "' . str_replace('"', '""', $name) . '"');
        }
        DB::purge('maintenance');
    }

    private function seedDatasources(): void
    {
        $rows = [
            [1, 'GHAP', 'Gazetteer of Historical Australian Places', 'searchpublicdatasets', 'https://ghap.tlcmap.org/'],
            [2, 'ANPS', 'Australian National Placenames Survey Gazetteer', 'searchausgaz', 'https://www.anps.org.au/'],
            [3, 'NCG', 'Composite Gazetteer of Australia', 'searchncg', 'https://placenames.fsdf.org.au/'],
        ];

        // The create_text_table migration inserts the geocoder row with the next sequence value.
        // Move it to id 4 so ids 1-3 are free for the rows below.
        DB::table('tlcmap.datasource')->where('search_param_name', 'searchgeocoder')->update(['id' => 4]);

        foreach ($rows as [$id, $source, $description, $param, $link]) {
            DB::table('tlcmap.datasource')->updateOrInsert(
                ['search_param_name' => $param],
                [
                    'id' => $id,
                    'name' => $source,
                    'description' => $description,
                    'link' => $link,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::statement("select setval(pg_get_serial_sequence('tlcmap.datasource', 'id'), (select max(id) from tlcmap.datasource))");
    }

    private function seedRecordTypes(): void
    {
        $types = ['Other', 'Placename', 'Media', 'Text', 'Event', 'Person', 'Journey', 'Site', 'Organisation'];

        foreach ($types as $i => $type) {
            $id = $i + 1;
            if (!DB::table('tlcmap.recordtype')->where('id', $id)->exists()) {
                DB::table('tlcmap.recordtype')->insert(['id' => $id, 'type' => $type]);
            }
        }

        DB::statement("select setval(pg_get_serial_sequence('tlcmap.recordtype', 'id'), (select max(id) from tlcmap.recordtype))");
    }
}
