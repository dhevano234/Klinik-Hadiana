<?php
// File: database/migrations/2025_07_13_add_doctor_id_to_doctor_schedules_table.php

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
        Schema::table('doctor_schedules', function (Blueprint $table) {
            // Tambah kolom doctor_id sebagai foreign key ke users table
            $table->foreignId('doctor_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Tambah index untuk performa
            $table->index(['doctor_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            // Drop foreign key constraint dan kolom
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });
    }
};