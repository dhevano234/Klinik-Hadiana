<?php
// File: database/migrations/2025_06_27_120000_update_doctor_schedules_days_structure.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ STEP 1: Backup existing data
        $existingSchedules = DB::table('doctor_schedules')->get();
        
        // ✅ STEP 2: Add new 'days' column as JSON
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->json('days')->nullable()->after('service_id');
        });

        // ✅ STEP 3: Convert existing single day_of_week to array format
        foreach ($existingSchedules as $schedule) {
            if ($schedule->day_of_week) {
                DB::table('doctor_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'days' => json_encode([$schedule->day_of_week])
                    ]);
            }
        }

        // ✅ STEP 4: Group and merge duplicate schedules
        $this->mergeDuplicateSchedules();

        // ✅ STEP 5: Drop old day_of_week column
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
        });
    }

    public function down(): void
    {
        // ✅ ROLLBACK: Restore old structure
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                  ->after('service_id');
        });

        // Convert back from JSON to individual records
        $schedules = DB::table('doctor_schedules')->get();
        
        foreach ($schedules as $schedule) {
            $days = json_decode($schedule->days, true);
            
            if (is_array($days) && count($days) > 0) {
                // Keep first day in original record
                DB::table('doctor_schedules')
                    ->where('id', $schedule->id)
                    ->update(['day_of_week' => $days[0]]);

                // Create new records for additional days
                for ($i = 1; $i < count($days); $i++) {
                    DB::table('doctor_schedules')->insert([
                        'doctor_name' => $schedule->doctor_name,
                        'service_id' => $schedule->service_id,
                        'day_of_week' => $days[$i],
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'is_active' => $schedule->is_active,
                        'foto' => $schedule->foto,
                        'created_at' => $schedule->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Drop new days column
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropColumn('days');
        });
    }

    /**
     * ✅ HELPER: Merge duplicate schedules
     */
    private function mergeDuplicateSchedules(): void
    {
        // Group schedules by doctor, service, and time
        $groups = DB::table('doctor_schedules')
            ->select('doctor_name', 'service_id', 'start_time', 'end_time', 'is_active', 'foto')
            ->selectRaw('GROUP_CONCAT(id) as ids')
            ->selectRaw('JSON_ARRAYAGG(JSON_EXTRACT(days, "$[0]")) as all_days')
            ->whereNotNull('days')
            ->groupBy('doctor_name', 'service_id', 'start_time', 'end_time', 'is_active', 'foto')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $ids = explode(',', $group->ids);
            $allDays = json_decode($group->all_days, true);
            
            // Remove duplicates and nulls
            $uniqueDays = array_unique(array_filter($allDays));
            
            if (count($uniqueDays) > 1) {
                // Keep first record, update with merged days
                $keepId = $ids[0];
                $deleteIds = array_slice($ids, 1);
                
                DB::table('doctor_schedules')
                    ->where('id', $keepId)
                    ->update([
                        'days' => json_encode($uniqueDays),
                        'updated_at' => now()
                    ]);

                // Delete duplicate records
                DB::table('doctor_schedules')
                    ->whereIn('id', $deleteIds)
                    ->delete();

                echo "✅ Merged schedule for {$group->doctor_name} - " . count($uniqueDays) . " days\n";
            }
        }
    }
};