<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_never_touch_the_development_database(): void
    {
        $this->assertSame('elenor_test', DB::getDatabaseName());
    }

    /**
     * WAMP's MySQL defaults to MyISAM, which has no transactions or row locks.
     * Orders need both, so every table must be InnoDB.
     */
    public function test_every_table_uses_innodb(): void
    {
        $tables = DB::select(
            'SELECT table_name AS name, engine AS engine FROM information_schema.tables WHERE table_schema = ?',
            [DB::getDatabaseName()],
        );

        $this->assertNotEmpty($tables);

        foreach ($tables as $table) {
            $this->assertSame('InnoDB', $table->engine, "Table {$table->name} is not InnoDB.");
        }
    }

    public function test_the_connection_runs_in_strict_mode_and_argentine_time(): void
    {
        $session = DB::selectOne('SELECT @@session.sql_mode AS sql_mode, @@session.time_zone AS time_zone');

        $this->assertStringContainsString('STRICT_TRANS_TABLES', $session->sql_mode);
        $this->assertSame('-03:00', $session->time_zone);
    }
}
