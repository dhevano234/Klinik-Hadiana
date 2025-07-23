<?php
// File: database/migrations/2025_06_01_070931_update_queues_table_for_admin.php
// FIXED: Ganti patient_id dengan user_id

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // ✅ GANTI: patient_id → user_id dan reference ke users
            if (!Schema::hasColumn('queues', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('service_id')->constrained('users')->cascadeOnDelete();
            }
            
            // Pastikan kolom status ada dengan nilai default
            if (!Schema::hasColumn('queues', 'status')) {
                $table->string('status')->default('waiting')->after('number');
            }
            
            // Pastikan kolom timestamp ada
            if (!Schema::hasColumn('queues', 'called_at')) {
                $table->timestamp('called_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('queues', 'served_at')) {
                $table->timestamp('served_at')->nullable()->after('called_at');
            }
            if (!Schema::hasColumn('queues', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('served_at');
            }
            if (!Schema::hasColumn('queues', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('finished_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // ✅ GANTI: Hapus user_id bukan patient_id
            $table->dropColumn(['user_id', 'status', 'called_at', 'served_at', 'finished_at', 'canceled_at']);
        });
    }
};