<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--keep=7 : Number of most recent backups to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Back up the SQLite database file (no-ops on other drivers). Safe to run on a schedule.';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->info('Database connection is not SQLite; nothing to back up.');

            return self::SUCCESS;
        }

        $source = config('database.connections.sqlite.database');

        if (! $source || ! is_file($source)) {
            $this->error("SQLite database file not found: {$source}");

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $destination = $backupDir.'/database-'.now()->format('Y-m-d_His').'.sqlite';

        // VACUUM INTO produces a clean, consistent snapshot in one step —
        // unlike a plain file copy, it can't land mid-write or miss data
        // still sitting in the WAL file (this app runs SQLite in WAL mode).
        DB::connection('sqlite')->statement('VACUUM INTO ?', [$destination]);

        $this->info("Backed up database to {$destination}");

        $keep = max(1, (int) $this->option('keep'));
        $backups = collect(File::glob($backupDir.'/database-*.sqlite'))
            ->sortByDesc(fn (string $path) => File::lastModified($path))
            ->values();

        $backups->slice($keep)->each(fn (string $path) => File::delete($path));

        return self::SUCCESS;
    }
}
