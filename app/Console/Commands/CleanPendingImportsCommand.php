<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanPendingImportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imports:clean-pending {--hours=24 : Delete pending upload files older than this many hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete abandoned Excel import uploads (preview started but never confirmed). Safe to run on a schedule.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours((int) $this->option('hours'))->getTimestamp();
        $deleted = 0;

        foreach ($disk->files('imports/pending') as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} abandoned import upload(s).");

        return self::SUCCESS;
    }
}
