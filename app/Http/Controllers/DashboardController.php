<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Queue;
use App\Models\User;
use App\Models\Service;
use App\Models\DoctorSchedule;
use App\Models\WeeklyQuota; // ✅ CHANGED: Import WeeklyQuota instead of DailyQuota
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        
        // ✅ EXISTING: Antrian aktif user berdasarkan tanggal_antrian hari ini
        $antrianAktif = Queue::where('user_id', $userId)
            ->whereIn('status', ['waiting', 'serving'])
            ->whereDate('tanggal_antrian', $today)
            ->with(['service', 'counter'])
            ->first();

        // ✅ EXISTING: Statistik real untuk cards
        $stats = [
            'antrian_hari_ini' => Queue::whereDate('tanggal_antrian', $today)->count(),
        ];

        // ✅ UPDATED: Quota info menggunakan WeeklyQuota
        $quotaInfo = $this->getTodayQuotaInfo();

        // ✅ EXISTING: Status antrian real untuk chart
        $statusAntrian = [
            'menunggu' => Queue::where('status', 'waiting')->whereDate('tanggal_antrian', $today)->count(),
            'dipanggil' => Queue::where('status', 'serving')->whereDate('tanggal_antrian', $today)->count(),
            'selesai' => Queue::where('status', 'finished')->whereDate('tanggal_antrian', $today)->count(),
            'dibatalkan' => Queue::where('status', 'canceled')->whereDate('tanggal_antrian', $today)->count(),
        ];

        // ✅ EXISTING: Estimasi waktu tunggu untuk antrian aktif user
        $estimasiInfo = null;
        if ($antrianAktif && $antrianAktif->status === 'waiting') {
            $estimasiInfo = $this->calculateWaitingTime($antrianAktif);
        }

        return view('frontend.dashboard', compact(
            'antrianAktif',  // ✅ MINOR FIX: Tambahkan variable yang hilang
            'stats', 
            'quotaInfo',
            'statusAntrian', 
            'estimasiInfo'
        ));
    }

    /**
     * ✅ UPDATED: Get quota information untuk hari ini menggunakan WeeklyQuota
     */
    private function getTodayQuotaInfo()
    {
        $today = today();
        $todayDayOfWeek = strtolower($today->format('l')); // monday, tuesday, etc.
        
        // Get all active quotas untuk hari ini berdasarkan day_of_week
        $quotas = WeeklyQuota::with(['doctorSchedule.service'])
            ->where('day_of_week', $todayDayOfWeek) // ✅ CHANGED: Use day_of_week instead of quota_date
            ->where('is_active', true)
            ->orderBy('total_quota', 'desc') // Urutkan berdasarkan quota terbesar
            ->get();

        if ($quotas->isEmpty()) {
            return [
                'has_quotas' => false,
                'total_doctors' => 0,
                'quotas' => collect([])
            ];
        }

        return [
            'has_quotas' => true,
            'total_doctors' => $quotas->count(),
            'quotas' => $quotas->map(function ($quota) {
                return [
                    'doctor_name' => $quota->doctorSchedule->doctor_name ?? 'Unknown',
                    'service_name' => $quota->doctorSchedule->service->name ?? 'Unknown',
                    'total_quota' => $quota->total_quota,
                    'used_quota' => $quota->used_quota_today,        // ✅ CHANGED: Use accessor
                    'available_quota' => $quota->available_quota_today,
                    'usage_percentage' => $quota->usage_percentage_today,
                    'status_color' => $quota->getStatusColor(),
                    'status_label' => $quota->status_label_today,
                    'formatted_quota' => $quota->formatted_quota_today,
                    'time_range' => $quota->doctorSchedule->time_range ?? 'Unknown',
                ];
            })
        ];
    }

    /**
     * ✅ EXISTING: Hitung estimasi waktu tunggu berdasarkan tanggal antrian
     */
    private function calculateWaitingTime($queue)
    {
        // Hitung berapa antrian di depan berdasarkan tanggal_antrian
        $antrianDidepan = Queue::where('service_id', $queue->service_id)
            ->where('status', 'waiting')
            ->where('id', '<', $queue->id)
            ->whereDate('tanggal_antrian', $queue->tanggal_antrian ?? today())
            ->count();

        // Setiap antrian butuh 15 menit + extra delay
        $baseMinutes = ($antrianDidepan + 1) * 15;
        $extraDelay = $queue->extra_delay_minutes ?: 0;
        $totalEstimatedMinutes = $baseMinutes + $extraDelay;

        // Waktu estimasi berdasarkan estimated_call_time jika ada
        if ($queue->estimated_call_time) {
            $waktuEstimasi = $queue->estimated_call_time;
        } else {
            // Fallback: hitung dari created_at + estimasi
            $waktuEstimasi = $queue->created_at->addMinutes($totalEstimatedMinutes);
        }

        $sekarang = now();

        // Cek apakah sudah lewat estimasi
        if ($waktuEstimasi < $sekarang) {
            // Sudah lewat, gunakan extra delay atau default 5 menit
            $estimasiMenit = $extraDelay ?: 5;
            $status = 'delayed';
        } else {
            // Masih dalam estimasi
            $diffMinutes = $sekarang->diffInMinutes($waktuEstimasi);
            $estimasiMenit = $diffMinutes;
            $status = 'on_time';
        }

        return [
            'posisi' => $antrianDidepan + 1,
            'estimasi_menit' => (int) round($estimasiMenit),
            'waktu_estimasi' => $waktuEstimasi->format('H:i'),
            'status' => $status,
            'antrian_didepan' => $antrianDidepan,
            'extra_delay' => $extraDelay,
            'total_estimated_minutes' => $totalEstimatedMinutes
        ];
    }

    /**
     * ✅ EXISTING: API endpoint untuk update estimasi dengan tanggal antrian
     */
    public function realtimeEstimation(Request $request)
    {
        $userId = Auth::id();
        
        // Cek antrian aktif berdasarkan tanggal_antrian hari ini
        $antrianAktif = Queue::where('user_id', $userId)
            ->whereIn('status', ['waiting', 'serving'])
            ->whereDate('tanggal_antrian', today())
            ->with(['service'])
            ->first();

        if (!$antrianAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada antrian aktif'
            ]);
        }

        if ($antrianAktif->status === 'serving') {
            return response()->json([
                'success' => true,
                'status' => 'serving',
                'message' => 'Sedang dilayani'
            ]);
        }

        $estimasiInfo = $this->calculateWaitingTime($antrianAktif);

        return response()->json([
            'success' => true,
            'status' => 'waiting',
            'data' => $estimasiInfo,
            'queue_number' => $antrianAktif->number,
            'service_name' => $antrianAktif->service->name ?? 'Unknown',
            'queue_date' => $antrianAktif->tanggal_antrian ? $antrianAktif->tanggal_antrian->format('d F Y') : 'Hari ini'
        ]);
    }
}