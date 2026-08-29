<?php

namespace Tests\Feature\Security;

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsTestSpreadsheets;
use Tests\TestCase;
use ZipArchive;

class ImportUploadSecurityTest extends TestCase
{
    use BuildsTestSpreadsheets, RefreshDatabase;

    public function test_a_plain_text_file_renamed_to_xlsx_is_rejected_by_validation(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('fake.xlsx', "not a spreadsheet\njust text");

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_a_corrupted_zip_with_xlsx_extension_fails_gracefully_not_with_a_500(): void
    {
        Storage::fake('local');

        // A real zip (so it may pass MIME sniffing, since .xlsx *is* a zip
        // container) but with none of the internal parts PhpSpreadsheet
        // needs — this must be caught, never surfaced as a raw 500.
        $tempPath = tempnam(sys_get_temp_dir(), 'corrupt_xlsx_');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::OVERWRITE);
        $zip->addFromString('not-a-workbook.txt', 'garbage');
        $zip->close();
        $content = file_get_contents($tempPath);
        unlink($tempPath);

        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('corrupt.xlsx', $content);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('big.xlsx', 6000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_uploaded_import_file_is_not_stored_under_the_public_webroot(): void
    {
        // A permanent, deterministic check of the security property itself:
        // the "local" disk's configured root must never resolve under
        // public/, regardless of what any individual request does.
        $localRoot = realpath(config('filesystems.disks.local.root'));
        $this->assertNotEmpty($localRoot);
        $this->assertFalse(str_starts_with($localRoot, realpath(public_path())));

        // Fake only after capturing the real configured root above — faking
        // the disk swaps that config value out for the rest of this test.
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload(
            ['first_name', 'last_name', 'email', 'role', 'subject'],
            [['Maria', 'Papadopoulou', 'maria@example.gr', 'teacher', 'Mathematics']]
        );

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $response->assertSessionHasNoErrors();

        Storage::disk('local')->assertExists(Storage::disk('local')->files('imports/pending')[0] ?? 'imports/pending/__missing__');
    }

    public function test_credentials_export_escapes_a_formula_injection_email(): void
    {
        // PHP's FILTER_VALIDATE_EMAIL (and therefore our import validation)
        // accepts a local-part starting with "=", "+", or "-" as a
        // syntactically valid email — e.g. "=cmd@example.gr" passes. If that
        // email were exported into a CSV a spreadsheet opens, a leading "="
        // would be interpreted as a formula unless escaped.
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload(
            ['first_name', 'last_name', 'email', 'role', 'subject'],
            [['Maria', 'Papadopoulou', '=1+1@example.gr', 'teacher', 'Mathematics']]
        );

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $batch = ImportBatch::firstOrFail();
        $csv = $this->actingAs($admin)->get(route('admin.imports.history.credentials', $batch))->streamedContent();

        $this->assertStringNotContainsString(',=1+1@example.gr', $csv);
        $this->assertStringContainsString("'=1+1@example.gr", $csv);
    }

    public function test_error_report_never_lets_a_formula_leading_field_start_a_cell(): void
    {
        // Belt-and-suspenders: even though row_data is JSON-wrapped (so a
        // malicious value nested inside it can never be the leading
        // character of the CSV cell), every exported field still passes
        // through the same escaping helper.
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload(
            ['first_name', 'last_name', 'email', 'role', 'subject'],
            [['=cmd|\'/c calc\'!A1', 'Papadopoulou', 'not-an-email', 'teacher', 'Mathematics']]
        );

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'teachers'), ['token' => $token]);

        $batch = ImportBatch::firstOrFail();
        $csv = $this->actingAs($admin)->get(route('admin.imports.history.errors', $batch))->streamedContent();

        // The JSON-encoded row_data cell must start with "{", never "=".
        $this->assertMatchesRegularExpression('/,"\{.*\}"\s*$/m', $csv);
    }
}
