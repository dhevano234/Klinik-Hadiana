<?php
// File: database/migrations/2025_07_14_create_weekly_quotas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_schedule_id')->constrained('doctor_schedules')->cascadeOnDelete();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->integer('total_quota')->default(20); // Kuota per hari
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['doctor_schedule_id', 'day_of_week']);
            $table->index(['day_of_week', 'is_active']);
            $table->unique(['doctor_schedule_id', 'day_of_week'], 'unique_doctor_day_quota');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_quotas');
    }
};