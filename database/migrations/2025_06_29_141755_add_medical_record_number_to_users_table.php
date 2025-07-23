<?php
// File: database/migrations/2025_06_29_add_medical_record_number_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambah kolom medical_record_number ke tabel users
            $table->string('medical_record_number', 20)->nullable()->unique()->after('nomor_ktp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('medical_record_number');
        });
    }
};