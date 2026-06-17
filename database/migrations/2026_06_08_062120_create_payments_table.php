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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                  ->unique()                      // One-to-One dengan bookings
                  ->constrained('bookings')
                  ->cascadeOnDelete();
            $table->string('payment_method', 50)->nullable();   // BCA, BNI, GoPay, dll
            $table->string('payment_proof', 255)->nullable();   // Path file bukti transfer
            $table->decimal('amount', 10, 2);
            $table->enum('payment_status', [
                'unpaid',
                'pending_verification',
                'verified',
                'refunded',
            ])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
