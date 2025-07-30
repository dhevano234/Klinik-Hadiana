<?php


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
            $table->date('quota_date'); // 
            $table->integer('total_quota')->default(20); 
            $table->integer('used_quota')->default(0); 
            $table->integer('available_quota')->virtualAs('total_quota - used_quota'); 
            $table->boolean('is_active')->default(true); 
            $table->text('notes')->nullable(); 
            $table->timestamps();
            
            
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