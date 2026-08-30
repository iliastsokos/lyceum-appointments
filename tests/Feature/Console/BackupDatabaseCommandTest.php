<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    private function useTemporarySqliteConnection(string $workDir): string
    {
        $this->app->useStoragePath($workDir);

        $sourceDb = $workDir.'/source.sqlite';
        (new PDO('sqlite:'.$sourceDb))->exec('CREATE TABLE t (id INTEGER)');

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $sourceDb]);
        DB::purge('sqlite');

        return $sourceDb;
    }

    public function test_backup_creates_a_snapshot_of_the_sqlite_database(): void
    {
        $workDir = sys_get_temp_dir().'/backup_test_'.uniqid();
        mkdir($workDir);
        $this->useTemporarySqliteConnection($workDir);

        $this->artisan('db:backup')->assertSuccessful();

        $backups = glob($workDir.'/app/backups/database-*.sqlite');
        $this->assertCount(1, $backups);
    }

    public function test_backup_keeps_only_the_configured_number_of_recent_backups(): void
    {
        $workDir = sys_get_temp_dir().'/backup_test_'.uniqid();
        mkdir($workDir);
        $backupDir = $workDir.'/app/backups';
        mkdir($backupDir, 0777, true);

        foreach (range(1, 3) as $i) {
            $path = $backupDir."/database-2020-01-0{$i}_000000.sqlite";
            file_put_contents($path, 'x');
            touch($path, time() - (10 - $i));
        }

        $this->useTemporarySqliteConnection($workDir);

        $this->artisan('db:backup', ['--keep' => 2])->assertSuccessful();

        $remaining = glob($backupDir.'/database-*.sqlite');
        $this->assertCount(2, $remaining);
    }

    public function test_backup_noops_when_connection_is_not_sqlite(): void
    {
        config(['database.default' => 'mysql']);

        $this->artisan('db:backup')->assertSuccessful();
    }
}
