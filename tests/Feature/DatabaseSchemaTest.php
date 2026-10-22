<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    public function test_schema_can_be_migrated_rolled_back_and_migrated_again(): void
    {
        // Never use the configured application database for this test.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $tables = ['role_utilisateur', 'statut_inscription', 'statut_tournoi', 'categorie', 'utilisateur', 'tournoi', 'inscription'];

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--force' => true]));
        foreach ($tables as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
    }
}
