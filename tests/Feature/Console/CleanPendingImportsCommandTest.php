<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanPendingImportsCommandTest extends TestCase
{
    public function test_it_deletes_only_stale_pending_uploads(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('imports/pending/old.xlsx', 'old-content');
        Storage::disk('local')->put('imports/pending/fresh.xlsx', 'fresh-content');

        // Backdate the "old" file's mtime by touching the underlying file directly.
        $oldPath = Storage::disk('local')->path('imports/pending/old.xlsx');
        touch($oldPath, now()->subHours(48)->getTimestamp());

        $this->artisan('imports:clean-pending', ['--hours' => 24])->assertSuccessful();

        Storage::disk('local')->assertMissing('imports/pending/old.xlsx');
        Storage::disk('local')->assertExists('imports/pending/fresh.xlsx');
    }
}
