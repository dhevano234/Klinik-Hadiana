<?php
// File: app/Http/Controllers/DoctorController.php
// SIMPLE VERSION: Keep original simple logic

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function jadwaldokter()
    {
        // ✅ SIMPLE: Keep original logic, just group doctors
        $doctors = DoctorSchedule::with('service')
            ->where('is_active', true)
            ->get()
            ->groupBy('doctor_name') // Group berdasarkan nama dokter
            ->map(function ($schedules) {
                $firstSchedule = $schedules->first();
                return [
                    'id' => $firstSchedule->id,
                    'doctor_name' => $firstSchedule->doctor_name,
                    'foto' => $firstSchedule->foto,
                    'service' => $firstSchedule->service,
                    'schedules' => $schedules,
                    'all_days' => $schedules->flatMap(function ($schedule) {
                        return $schedule->days ?? [];
                    })->unique()->sort()->values(),
                    'time_range' => $firstSchedule->time_range,
                ];
            })
            ->sortBy('doctor_name');

        return view('jadwaldokter', compact('doctors'));
    }

    public function index()
    {
        $doctors = DoctorSchedule::with('service')
            ->where('is_active', true)
            ->get();
            
        return view('doctors.index', compact('doctors'));
    }

    public function show($id)
    {
        $schedule = DoctorSchedule::with('service')->findOrFail($id);
        return view('doctors.show', compact('schedule'));
    }
}