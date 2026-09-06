<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The "confirmed" status was never actually assigned by any code path
     * (appointments only ever move new -> cancelled or new -> completed),
     * so it's dropped here rather than kept as dead, confusing schema.
     *
     * Uses the portable Schema Blueprint (not a raw MySQL `MODIFY ... ENUM`
     * statement) so this also runs on SQLite, which this branch's test
     * suite uses.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', ['new', 'cancelled', 'completed'])->default('new')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', ['new', 'confirmed', 'cancelled', 'completed'])->default('new')->change();
        });
    }
};
