<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An appointment can now be cancelled by the guardian who booked it, by
     * the teacher it was booked with, or by an admin on the teacher's
     * behalf — so who actually cancelled it has to be recorded, both to word
     * the notification to the other side correctly and to show it in the
     * appointment lists. NULL for rows cancelled before this column existed
     * (all of which were guardian cancellations) and if that user is later
     * deleted.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
        });
    }
};
