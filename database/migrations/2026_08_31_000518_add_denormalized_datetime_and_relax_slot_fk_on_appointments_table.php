<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * appointments.slot_id was ON DELETE RESTRICT, which is right for an
     * active booking but also permanently blocks deleting the slot (and, by
     * cascade, the whole availability) once an appointment on it has been
     * cancelled — nothing is booked there any more, yet the historical row
     * still points at it forever. The appointment's own date/time is
     * denormalized here so its history keeps displaying correctly once the
     * slot it pointed to is gone, and the foreign key is relaxed to SET
     * NULL so the slot (and its availability) can actually be deleted.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });

        DB::statement('
            UPDATE appointments
            SET date = (SELECT date FROM appointment_slots WHERE appointment_slots.id = appointments.slot_id),
                start_time = (SELECT start_time FROM appointment_slots WHERE appointment_slots.id = appointments.slot_id),
                end_time = (SELECT end_time FROM appointment_slots WHERE appointment_slots.id = appointments.slot_id)
            WHERE slot_id IS NOT NULL
        ');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['slot_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('slot_id')->nullable()->change();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('slot_id')->references('id')->on('appointment_slots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['slot_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('slot_id')->nullable(false)->change();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('slot_id')->references('id')->on('appointment_slots')->restrictOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['date', 'start_time', 'end_time']);
        });
    }
};
