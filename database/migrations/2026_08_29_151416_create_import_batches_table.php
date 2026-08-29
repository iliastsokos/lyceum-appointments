<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('filename');
            $table->enum('import_type', ['teachers', 'guardians']);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows');
            $table->unsignedInteger('failed_rows');
            // Beyond the spec's literal column list: without this, "skipped
            // because the account already exists" (a deliberate no-op, not
            // a failure) would be indistinguishable from a real validation
            // failure in later reporting — spec §23 explicitly shows these
            // as separate counts in the result summary.
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
