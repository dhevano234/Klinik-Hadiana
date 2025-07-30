<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // Tambah kolom doctor_id setelah service_id
            $table->foreignId('doctor_id')
                  ->nullable()
                  ->after('service_id')
                  ->constrained('doctor_schedules')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('doctor_id');
        });
    }
};