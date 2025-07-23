<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->enum('day_of_week', [
                'monday', 'tuesday', 'wednesday', 'thursday', 
                'friday', 'saturday', 'sunday'
            ])->after('service_id')->nullable();
        });
        
        // Convert existing data
        $schedules = DB::table('doctor_schedules')->get();
        
        foreach ($schedules as $schedule) {
            $days = json_decode($schedule->days, true);
            
            if (is_array($days) && count($days) > 0) {
                // Update dengan hari pertama
                DB::table('doctor_schedules')
                    ->where('id', $schedule->id)
                    ->update(['day_of_week' => $days[0]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
        });
    }
};