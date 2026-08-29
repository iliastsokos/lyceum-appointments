<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The "confirmed" status was never actually assigned by any code path
     * (appointments only ever move new -> cancelled or new -> completed),
     * so it's dropped here rather than kept as dead, confusing schema.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY status ENUM('new', 'cancelled', 'completed') NOT NULL DEFAULT 'new'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY status ENUM('new', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'new'");
    }
};
