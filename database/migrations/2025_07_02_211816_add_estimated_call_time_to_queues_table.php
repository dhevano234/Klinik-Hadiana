<?php
// File: database/migrations/2025_07_02_add_estimated_call_time_to_queues_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // Estimasi waktu panggilan (calculated field)
            $table->timestamp('estimated_call_time')->nullable()->after('chief_complaint');
            
            // Extra delay kalau sudah lewat estimasi tapi belum dipanggil
            $table->integer('extra_delay_minutes')->default(0)->after('estimated_call_time');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn(['estimated_call_time', 'extra_delay_minutes']);
        });
    }
};