<?php

namespace Tests\Feature\Admin\Import;

use App\Enums\UserRole;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsTestSpreadsheets;
use Tests\TestCase;

class GuardianImportTest extends TestCase
{
    use BuildsTestSpreadsheets, RefreshDatabase;

    private array $headers = ['guardian_first_name', 'guardian_last_name', 'guardian_email', 'child_first_name', 'child_last_name', 'child_class'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_guardian_with_multiple_children_creates_one_account_and_two_children(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Maria', 'Papadopoulou', 'B1'],
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Nikos', 'Papadopoulos', 'G2'],
            ['Anna', 'Georgiou', 'ageorgiou@example.gr', 'Eleni', 'Georgiou', 'A2'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);
        $this->assertSame(2, $preview->viewData('summary')['guardians_new']);

        $token = $preview->viewData('token');
        $result = $this->actingAs($admin)->post(route('admin.imports.commit', 'guardians'), ['token' => $token]);
        $result->assertOk();
        $result->assertSee('Η εισαγωγή ολοκληρώθηκε.');
        $result->assertSee('3 γραμμές επεξεργάστηκαν');
        $result->assertSee('3 παιδιά συσχετίστηκαν');

        $this->assertSame(2, User::where('role', UserRole::Guardian)->count());

        $guardian = User::where('email', 'gpap@example.gr')->firstOrFail();
        $this->assertSame(2, $guardian->children()->count());
        $this->assertTrue($guardian->children()->where('first_name', 'Maria')->exists());
        $this->assertTrue($guardian->children()->where('first_name', 'Nikos')->exists());

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(3, $batch->successful_rows);
    }

    public function test_repeated_guardian_email_does_not_create_duplicate_guardian_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Maria', 'Papadopoulou', 'B1'],
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Nikos', 'Papadopoulos', 'G2'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'guardians'), ['token' => $token]);

        $this->assertSame(1, User::where('email', 'gpap@example.gr')->count());
    }

    public function test_existing_guardian_account_is_skipped_and_no_children_are_associated(): void
    {
        $admin = User::factory()->admin()->create();
        $existingGuardian = User::factory()->guardian()->create(['email' => 'gpap@example.gr']);

        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Maria', 'Papadopoulou', 'B1'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);
        $this->assertSame(1, $preview->viewData('summary')['skip']);
        $this->assertSame(1, $preview->viewData('summary')['guardians_existing']);

        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'guardians'), ['token' => $token]);

        $this->assertSame(0, $existingGuardian->children()->count());

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(0, $batch->successful_rows);
        $this->assertSame(1, $batch->skipped_rows);
    }

    public function test_invalid_class_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Maria', 'Papadopoulou', 'Z9'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame('Μη έγκυρη τάξη', $rows->first()['errors']['child_class']);
    }

    public function test_invalid_guardian_email_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'not-an-email', 'Maria', 'Papadopoulou', 'B1'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame('Μη έγκυρο email', $rows->first()['errors']['guardian_email']);
    }

    public function test_missing_child_name_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', '', 'Papadopoulou', 'B1'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);

        $rows = $response->viewData('rows');
        $this->assertSame('Υποχρεωτικό', $rows->first()['errors']['child_first_name']);
    }

    public function test_malformed_guardian_file_missing_columns_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload(['guardian_first_name', 'guardian_email'], [
            ['Giorgos', 'gpap@example.gr'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_partial_errors_still_commit_the_valid_guardian_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            ['Giorgos', 'Papadopoulos', 'gpap@example.gr', 'Maria', 'Papadopoulou', 'B1'],
            ['Anna', 'Georgiou', 'not-an-email', 'Eleni', 'Georgiou', 'A2'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);
        $token = $preview->viewData('token');
        $result = $this->actingAs($admin)->post(route('admin.imports.commit', 'guardians'), ['token' => $token]);
        $result->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'gpap@example.gr']);
        $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);

        $batch = ImportBatch::firstOrFail();
        $this->assertSame(2, $batch->total_rows);
        $this->assertSame(1, $batch->successful_rows);
        $this->assertSame(1, $batch->failed_rows);
    }

    public function test_guardian_name_normalization_trims_but_preserves_greek_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->makeXlsxUpload($this->headers, [
            [' Γιώργος ', ' Παπαδόπουλος ', ' GPAP@EXAMPLE.GR ', 'Μαρία', 'Παπαδοπούλου', 'b1'],
        ]);

        $preview = $this->actingAs($admin)->post(route('admin.imports.preview', 'guardians'), ['file' => $file]);
        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('admin.imports.commit', 'guardians'), ['token' => $token]);

        $guardian = User::where('email', 'gpap@example.gr')->firstOrFail();
        $this->assertSame('Γιώργος', $guardian->first_name);
        $this->assertSame('Παπαδόπουλος', $guardian->last_name);
        $this->assertSame('B1', $guardian->children()->first()->class);
    }
}
