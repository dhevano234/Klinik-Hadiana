<?php
// File: database/migrations/2025_07_08_140000_create_daily_quotas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_schedule_id')->constrained('doctor_schedules')->cascadeOnDelete();
            $table->date('quota_date'); // Tanggal kuota
            $table->integer('total_quota')->default(20); // Total kuota harian
            $table->integer('used_quota')->default(0); // Kuota yang sudah terpakai
            $table->integer('available_quota')->virtualAs('total_quota - used_quota'); // Kuota tersisa (virtual column)
            $table->boolean('is_active')->default(true); // Status aktif
            $table->text('notes')->nullable(); // Catatan admin
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['doctor_schedule_id', 'quota_date']);
            $table->index(['quota_date', 'is_active']);
            $table->unique(['doctor_schedule_id', 'quota_date'], 'unique_doctor_date_quota');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quotas');
    }
};