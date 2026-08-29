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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('appointment_slots')->restrictOnDelete();

            // Mirrors slot_id while the appointment is new/confirmed, and is
            // set to NULL on cancellation. A plain unique constraint on
            // slot_id can't work here because a slot must be re-bookable
            // after a cancellation; MySQL/MariaDB treat NULLs as distinct in
            // a unique index, so this column gives us a real DB-level
            // backstop against double booking (spec §10) while still
            // allowing legitimate rebooking. Maintained by BookingService.
            $table->unsignedBigInteger('active_slot_id')->nullable()->unique();

            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('guardian_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('child_id')->constrained('children')->restrictOnDelete();
            $table->enum('status', ['new', 'confirmed', 'cancelled', 'completed'])->default('new');
            $table->timestamp('booked_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['guardian_id', 'status']);
            $table->index(['teacher_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
