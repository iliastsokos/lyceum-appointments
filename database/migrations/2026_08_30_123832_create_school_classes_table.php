<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Replaces the formerly hardcoded App\Enums\SchoolClass values —
        // seeded here (not in DatabaseSeeder) so every environment,
        // including test databases created via RefreshDatabase, has this
        // reference data available immediately after migrating. Admins can
        // rename/add/remove from here on via the admin UI.
        $now = now();
        DB::table('school_classes')->insert(
            collect(['A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'G1', 'G2', 'G3'])
                ->map(fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
                ->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
