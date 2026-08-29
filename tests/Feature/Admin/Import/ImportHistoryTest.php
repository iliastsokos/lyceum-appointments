<?php

namespace Tests\Feature\Admin\Import;

use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_imports_landing_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Import Teachers')
            ->assertSee('Import Guardians');
    }

    public function test_admin_can_view_import_history_list(): void
    {
        $admin = User::factory()->admin()->create();
        $batch = ImportBatch::factory()->create([
            'admin_id' => $admin->id,
            'filename' => 'teachers-january.xlsx',
            'total_rows' => 10,
            'successful_rows' => 8,
            'failed_rows' => 1,
            'skipped_rows' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.imports.history'));

        $response->assertOk();
        $response->assertSee('teachers-january.xlsx');
        $response->assertSee($admin->full_name);
    }

    public function test_admin_can_view_import_batch_detail_with_its_errors(): void
    {
        $admin = User::factory()->admin()->create();
        $batch = ImportBatch::factory()->create(['admin_id' => $admin->id]);
        $error = ImportError::factory()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 5,
            'field' => 'email',
            'error_message' => 'Invalid email',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.imports.history.show', $batch));

        $response->assertOk();
        $response->assertSee('Invalid email');
        $response->assertSee('5');
    }

    public function test_non_admin_cannot_view_import_history(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.imports.history'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.imports.index'))->assertForbidden();
    }
}
