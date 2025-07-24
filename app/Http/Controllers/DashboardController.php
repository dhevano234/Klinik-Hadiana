<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Queue;
use App\Models\User;
use App\Models\Service;
use App\Models\DoctorSchedule;
use App\Models\WeeklyQuota;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        
        // ✅ UPDATED: Antrian aktif user berdasarkan tanggal_antrian hari ini (INCLUDE PENDING)
        $antrianAktif = Queue::where('user_id', $userId)
            ->whereIn('status', ['waiting', 'serving', 'pending']) // ✅ TAMBAH PENDING
            ->whereDate('tanggal_antrian', $today)
            ->with(['service', 'counter'])
            ->first();

        // ✅ EXISTING: Statistik real untuk cards
        $stats = [
            'antrian_hari_ini' => Queue::whereDate('tanggal_antrian', $today)->count(),
        ];

        // ✅ UPDATED: Quota info menggunakan WeeklyQuota
        $quotaInfo = $this->getTodayQuotaInfo();

        // ✅ UPDATED: Status antrian real untuk chart (INCLUDE PENDING)
        $statusAntrian = [
            'menunggu' => Queue::where('status', 'waiting')->whereDate('tanggal_antrian', $today)->count(),
            'pending' => Queue::where('status', 'pending')->whereDate('tanggal_antrian', $today)->count(), // ✅ TAMBAH PENDING
            'dipanggil' => Queue::where('status', 'serving')->whereDate('tanggal_antrian', $today)->count(),
            'selesai' => Queue::where('status', 'finished')->whereDate('tanggal_antrian', $today)->count(),
            'dibatalkan' => Queue::where('status', 'canceled')->whereDate('tanggal_antrian', $today)->count(),
        ];

        // ✅ UPDATED: Estimasi waktu tunggu untuk antrian aktif user (HANDLE PENDING)
        $estimasiInfo = null;
        if ($antrianAktif) {
            if ($antrianAktif->status === 'waiting') {
                $estimasiInfo = $this->calculateWaitingTime($antrianAktif);
            } elseif ($antrianAktif->status === 'pending') {
                // ✅ NEW: Handle pending status
                $estimasiInfo = $this->calculatePendingTime($antrianAktif);
            }
        }

        return view('frontend.dashboard', compact(
            'antrianAktif',
            'stats', 
            'quotaInfo',
            'statusAntrian', 
            'estimasiInfo'
        ));
    }

    /**
     * ✅ NEW: Calculate info untuk antrian pending
     */
    private function calculatePendingTime($antrian)
    {
        $savedMinutes = $antrian->extra_delay_minutes ?? 15;
        
        return [
            'posisi' => 'Pending',
            'estimasi_menit' => $savedMinutes,
            'waktu_estimasi' => '--:--',
            'status' => 'pending',
            'antrian_didepan' => 'Tidak berlaku',
            'extra_delay' => 0,
            'total_estimated_minutes' => $savedMinutes,
            'message' => 'Antrian Anda sedang dijeda. Menunggu admin untuk melanjutkan.'
        ];
    }

    /**
     * ✅ UPDATED: Get quota information untuk hari ini menggunakan WeeklyQuota
     */
    private function getTodayQuotaInfo()
    {
        $today = today();
        $todayDayOfWeek = strtolower($today->format('l')); // monday, tuesday, etc.
        
        // Get quotas untuk hari ini
        $quotas = WeeklyQuota::with('doctorSchedule.service')
            ->where('day_of_week', $todayDayOfWeek)
            ->where('is_active', true)
            ->get();
        
        if ($quotas->isEmpty()) {
            return [
                'total_quota' => 0,
                'used_quota' => 0,
                'available_quota' => 0,
                'percentage_used' => 0,
                'status' => 'no_quota',
                'doctors_available' => 0,
                'doctors_full' => 0
            ];
        }
        
        $totalQuota = $quotas->sum('total_quota');
        $usedQuota = $quotas->sum(function($quota) use ($today) {
            return $quota->getUsedQuotaForDate($today);
        });
        $availableQuota = $totalQuota - $usedQuota;
        $percentageUsed = $totalQuota > 0 ? round(($usedQuota / $totalQuota) * 100, 1) : 0;
        
        $doctorsAvailable = $quotas->filter(function($quota) use ($today) {
            return $quota->getAvailableQuotaForDate($today) > 0;
        })->count();
        
        $doctorsFull = $quotas->filter(function($quota) use ($today) {
            return $quota->isQuotaFullForDate($today);
        })->count();
        
        return [
            'total_quota' => $totalQuota,
            'used_quota' => $usedQuota,
            'available_quota' => $availableQuota,
            'percentage_used' => $percentageUsed,
            'status' => $percentageUsed >= 90 ? 'critical' : ($percentageUsed >= 70 ? 'warning' : 'normal'),
            'doctors_available' => $doctorsAvailable,
            'doctors_full' => $doctorsFull
        ];
    }

    /**
     * ✅ EXISTING: Calculate waiting time untuk antrian waiting
     */
    private function calculateWaitingTime($antrian)
    {
        // Hitung posisi antrian berdasarkan session atau service
        if ($antrian->doctor_id) {
            // Berdasarkan session dokter
            $antrianDidepan = Queue::where('doctor_id', $antrian->doctor_id)
                ->where('status', 'waiting')
                ->where('id', '<', $antrian->id)
                ->whereDate('tanggal_antrian', $antrian->tanggal_antrian ?? today())
                ->count();
        } else {
            // Berdasarkan service + tanggal (sistem lama)
            $antrianDidepan = Queue::where('service_id', $antrian->service_id)
                ->where('status', 'waiting')
                ->where('id', '<', $antrian->id)
                ->whereDate('tanggal_antrian', $antrian->tanggal_antrian ?? today())
                ->whereNull('doctor_id')
                ->count();
        }

        $extraDelay = $antrian->extra_delay_minutes ?: 0;
        $totalEstimatedMinutes = (($antrianDidepan + 1) * 15) + $extraDelay;

        // Hitung estimasi waktu panggil
        if ($antrian->estimated_call_time) {
            $waktuEstimasi = $antrian->estimated_call_time;
        } else {
            // Fallback calculation
            if ($antrian->doctor_id && $antrian->doctorSchedule) {
                $sessionStartTime = $antrian->doctorSchedule->start_time;
                $sessionDate = $antrian->tanggal_antrian ?? today();
                
                if ($sessionDate->isToday()) {
                    $sessionStartDateTime = $sessionDate->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'));
                    $startTime = now()->max($sessionStartDateTime);
                } else {
                    $startTime = $sessionDate->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'));
                }
                
                $waktuEstimasi = $startTime->addMinutes($totalEstimatedMinutes);
            } else {
                $waktuEstimasi = $antrian->created_at->addMinutes($totalEstimatedMinutes);
            }
        }

        // Tentukan status delay
        $sekarang = now();
        if ($waktuEstimasi < $sekarang) {
            $estimasiMenit = 5;
            $status = 'delayed';
        } else {
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
     * ✅ UPDATED: API endpoint untuk update estimasi dengan tanggal antrian (INCLUDE PENDING)
     */
    public function realtimeEstimation(Request $request)
    {
        $userId = Auth::id();
        
        // ✅ UPDATED: Cek antrian aktif berdasarkan tanggal_antrian hari ini (INCLUDE PENDING)
        $antrianAktif = Queue::where('user_id', $userId)
            ->whereIn('status', ['waiting', 'serving', 'pending']) // ✅ TAMBAH PENDING
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

        // ✅ NEW: Handle pending status
        if ($antrianAktif->status === 'pending') {
            $pendingInfo = $this->calculatePendingTime($antrianAktif);
            
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'data' => $pendingInfo,
                'queue_number' => $antrianAktif->number,
                'service_name' => $antrianAktif->service->name ?? 'Unknown',
                'queue_date' => $antrianAktif->tanggal_antrian ? $antrianAktif->tanggal_antrian->format('d F Y') : 'Hari ini',
                'message' => 'Antrian sedang dijeda'
            ]);
        }

        // Handle waiting status
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