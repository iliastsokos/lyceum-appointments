<?php

namespace Tests\Feature\Admin\Import;

use App\Enums\UserRole;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\BuildsTestSpreadsheets;
use Tests\TestCase;

class TeacherImportTest extends TestCase
{
    use BuildsTestSpreadsheets, RefreshDatabase;

    private array $headers = ['first_name', 'last_name', 'email', 'role', 'subject'];

    public function test_admin_can_preview_a_valid_teacher_import(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
            ['Nikos', 'Ioannidis', 'nikos@example.gr', 'teacher', 'Physics'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertOk();
        $this->assertSame(2, $response->viewData('summary')['valid']);
        $this->assertSame(0, $response->viewData('summary')['error']);
        $this->assertDatabaseCount('users', 1); // only the admin so far — nothing committed yet
    }

    public function test_admin_can_commit_a_valid_teacher_import(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');

        $response = $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $response->assertOk();
        $response->assertSee('Import completed.');
        $response->assertSee('1 rows processed');
        $response->assertSee('1 accounts created');
        $response->assertSee('Download Temporary Passwords');

        $teacher = User::where('email', 'maria@example.gr')->firstOrFail();
        $this->assertSame(UserRole::Teacher, $teacher->role);
        $this->assertSame('Mathematics', $teacher->subject);
        $this->assertTrue($teacher->must_change_password);

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(1, $batch->total_rows);
        $this->assertSame(1, $batch->successful_rows);
        $this->assertSame(0, $batch->failed_rows);
        $this->assertSame(0, $batch->skipped_rows);
    }

    public function test_duplicate_email_within_file_is_flagged_as_an_error(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'same@example.gr', 'teacher', 'Mathematics'],
            ['Nikos', 'Ioannidis', 'same@example.gr', 'teacher', 'Physics'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame(1, $response->viewData('summary')['valid']);
        $this->assertSame(1, $response->viewData('summary')['error']);
        $this->assertSame('Duplicate', $rows->firstWhere('status', 'error')['errors']['email']);
    }

    public function test_existing_teacher_account_is_skipped_not_overwritten(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->teacher()->create(['email' => 'maria@example.gr', 'subject' => 'History']);
        $originalPassword = $existing->password;

        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $this->assertSame(1, $preview->viewData('summary')['skip']);

        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $existing->refresh();
        $this->assertSame('History', $existing->subject);
        $this->assertSame($originalPassword, $existing->password);

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(0, $batch->successful_rows);
        $this->assertSame(1, $batch->skipped_rows);
    }

    public function test_invalid_email_is_reported_as_an_error(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'not-an-email', 'teacher', 'Mathematics'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame('Invalid email', $rows->first()['errors']['email']);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'admin', 'Mathematics'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame('Invalid role', $rows->first()['errors']['role']);
    }

    public function test_missing_required_field_is_reported(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame('Required', $rows->first()['errors']['first_name']);
    }

    public function test_malformed_file_missing_columns_is_rejected_before_row_processing(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload(['first_name', 'last_name'], [
            ['Maria', 'Papadopoulou'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_non_xlsx_file_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('teachers.txt', 10, 'text/plain');

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_partial_errors_produce_a_correct_summary_and_only_valid_rows_are_created(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
            ['', 'Ioannidis', 'nikos@example.gr', 'teacher', 'Physics'],
            ['Anna', 'Georgiou', 'not-an-email', 'teacher', 'Biology'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');

        $result = $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);
        $result->assertOk();

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(1, $batch->successful_rows);
        $this->assertSame(2, $batch->failed_rows);
        $this->assertSame(2, $batch->errors()->count());
    }

    public function test_admin_can_download_error_report_for_a_batch(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $batch = ImportBatch::firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.imports.history.errors', $batch));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_download_temporary_passwords_once(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $batch = ImportBatch::firstOrFail();

        $first = $this->actingAs($admin)->get(route('admin.imports.history.credentials', $batch));
        $first->assertOk();

        $second = $this->actingAs($admin)->get(route('admin.imports.history.credentials', $batch));
        $second->assertNotFound();
    }

    public function test_non_admin_cannot_access_import_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $this->actingAs($teacher)->post(route('admin.imports.preview', 'teachers'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_passwords_are_never_stored_in_plain_text_or_in_row_data(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $teacher = User::where('email', 'maria@example.gr')->firstOrFail();
        $this->assertStringStartsWith('$', $teacher->password);
        $this->assertGreaterThan(50, strlen($teacher->password));

        $this->assertDatabaseMissing('import_errors', ['field' => 'password']);
    }
}
