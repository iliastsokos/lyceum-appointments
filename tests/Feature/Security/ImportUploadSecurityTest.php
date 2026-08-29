<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class ImportUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_text_file_renamed_to_xlsx_is_rejected_by_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('fake.xlsx', "not a spreadsheet\njust text");

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_a_corrupted_zip_with_xlsx_extension_fails_gracefully_not_with_a_500(): void
    {
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
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('big.xlsx', 6000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_uploaded_import_file_is_not_stored_under_the_public_webroot(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->createWithContent('teachers.xlsx', $this->realXlsxBytes());

        $this->actingAs($admin)->post(route('admin.imports.preview', 'teachers'), ['file' => $file]);

        $this->assertFileDoesNotExist(public_path('imports'));
        $this->assertDirectoryExists(storage_path('app/private/imports/pending'));
    }

    private function realXlsxBytes(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(['first_name', 'last_name', 'email', 'role', 'subject'], null, 'A1');
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($spreadsheet))->save($tempPath);
        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content;
    }
}
