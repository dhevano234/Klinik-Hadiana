<?php
// File: database/migrations/2025_06_28_030000_ensure_doctor_id_in_queues_table.php
// Migration untuk memastikan kolom doctor_id ada dan benar

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // Cek apakah kolom doctor_id sudah ada
            if (!Schema::hasColumn('queues', 'doctor_id')) {
                $table->foreignId('doctor_id')
                      ->nullable()
                      ->after('service_id')
                      ->constrained('doctor_schedules')
                      ->nullOnDelete();
            } else {
                // Jika sudah ada, pastikan constraint-nya benar
                // Drop existing constraint jika ada
                try {
                    $table->dropForeign(['doctor_id']);
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
                
                // Tambah constraint baru yang benar
                $table->foreign('doctor_id')
                      ->references('id')
                      ->on('doctor_schedules')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'doctor_id')) {
                $table->dropForeign(['doctor_id']);
                $table->dropColumn('doctor_id');
            }
        });
    }
};