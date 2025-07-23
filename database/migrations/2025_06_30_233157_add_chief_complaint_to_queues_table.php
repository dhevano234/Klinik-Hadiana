<?php
// File: database/migrations/2025_06_30_150000_add_chief_complaint_to_queues_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // Tambah kolom chief_complaint (keluhan) - optional
            $table->text('chief_complaint')
                  ->nullable()
                  ->after('doctor_id')
                  ->comment('Keluhan utama pasien saat ambil antrian - optional');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('chief_complaint');
        });
    }
};