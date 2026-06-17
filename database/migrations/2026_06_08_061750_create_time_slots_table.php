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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')
                  ->constrained('fields')
                  ->cascadeOnDelete();
            // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu
            $table->tinyInteger('day_of_week')->unsigned();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            // Pastikan tidak ada slot duplikat di lapangan & hari yang sama
            $table->unique(['field_id', 'day_of_week', 'start_time'], 'uq_time_slot');
            $table->index(['field_id', 'day_of_week'], 'idx_ts_field_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
