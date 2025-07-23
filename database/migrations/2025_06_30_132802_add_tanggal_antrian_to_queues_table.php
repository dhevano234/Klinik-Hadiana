<?php

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
        Schema::table('queues', function (Blueprint $table) {
            // ✅ PERBAIKAN: Tambah nullable() untuk menghindari error strict mode
            $table->date('tanggal_antrian')
                  ->nullable()  // ✅ TAMBAH INI
                  ->after('doctor_id')
                  ->comment('Tanggal antrian yang dipilih user di date picker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('tanggal_antrian');
        });
    }
};

// ===================================================================
// ✅ ALTERNATIF: Jika masih error, gunakan migration dengan default
// ===================================================================

/*
Schema::table('queues', function (Blueprint $table) {
    $table->date('tanggal_antrian')
          ->nullable()
          ->default(null)  // ✅ EXPLICIT NULL DEFAULT
          ->after('doctor_id')
          ->comment('Tanggal antrian yang dipilih user di date picker');
});
*/

// ===================================================================
// ✅ STEP PERBAIKAN ERROR:
// ===================================================================

// 1. Rollback migration yang error:
// php artisan migrate:rollback

// 2. Edit file migration dengan kode di atas

// 3. Jalankan lagi:
// php artisan migrate

// ===================================================================
// ✅ POPULATE DATA LAMA (Setelah migration berhasil):
// ===================================================================

// Jalankan query ini untuk isi tanggal_antrian data lama:
/*
UPDATE queues 
SET tanggal_antrian = DATE(created_at) 
WHERE tanggal_antrian IS NULL;
*/

// Atau buat seeder:
/*
// File: database/seeders/PopulateTanggalAntrianSeeder.php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopulateTanggalAntrianSeeder extends Seeder
{
    public function run()
    {
        // Populate tanggal_antrian untuk data lama
        DB::table('queues')
            ->whereNull('tanggal_antrian')
            ->update([
                'tanggal_antrian' => DB::raw('DATE(created_at)')
            ]);
    }
}

// Jalankan: php artisan db:seed --class=PopulateTanggalAntrianSeeder
*/