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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
             $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->cascadeOnDelete();
            $table->foreignId('time_slot_id')
                  ->constrained('time_slots')
                  ->restrictOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            // Snapshot harga saat booking — penting agar perubahan harga di depan tidak mengubah data historis
            $table->decimal('price_per_hour', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_id', 'start_time', 'end_time'], 'idx_bdet_booking_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
