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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();         // FK-20240601-0001
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->foreignId('field_id')
                  ->constrained('fields')
                  ->restrictOnDelete();
            $table->foreignId('promotion_id')
                  ->nullable()
                  ->constrained('promotions')
                  ->nullOnDelete();
            $table->date('booking_date');
            $table->decimal('total_hours', 4, 1);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', [
                'pending',
                'confirmed',
                'rejected',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index('booking_date', 'idx_bookings_date');
            $table->index('status',       'idx_bookings_status');
            $table->index('user_id',      'idx_bookings_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
