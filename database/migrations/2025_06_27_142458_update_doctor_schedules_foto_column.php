<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            // Pastikan kolom foto ada dan nullable
            if (!Schema::hasColumn('doctor_schedules', 'foto')) {
                $table->string('foto')->nullable()->after('is_active');
            } else {
                // Jika sudah ada, ubah jadi nullable
                $table->string('foto')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            // Hapus kolom foto jika rollback
            if (Schema::hasColumn('doctor_schedules', 'foto')) {
                $table->dropColumn('foto');
            }
        });
    }
};