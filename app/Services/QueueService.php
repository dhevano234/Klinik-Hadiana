<?php
// File: app/Services/QueueService.php - UPDATED: Dengan Global Pending Logic
// ✅ SEMUA METHOD PENTING TETAP DIPERTAHANKAN

namespace App\Services;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Models\DoctorSchedule;
use App\Models\WeeklyQuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class QueueService
{
    // ✅ NEW: GLOBAL PENDING METHODS (Simple Cache-based)
    
    /**
     * ✅ NEW: Check apakah global pending aktif
     */
    public function isGlobalPendingActive(): bool
    {
        return Cache::get('global_pending_active', false);
    }

    /**
     * ✅ NEW: Aktifkan global pending
     */
    public function activateGlobalPending(): void
    {
        Cache::put('global_pending_active', true, 60 * 60 * 24); // 24 jam
    }

    /**
     * ✅ NEW: Nonaktifkan global pending
     */
    public function deactivateGlobalPending(): void
    {
        Cache::forget('global_pending_active');
    }

    /**
     * ✅ UPDATED: Add queue dengan Global Pending check
     */
    public function addQueue($serviceId, $userId = null, $ktpData = null, $tanggalAntrian = null, $doctorId = null)
    {
        return DB::transaction(function () use ($serviceId, $userId, $ktpData, $tanggalAntrian, $doctorId) {
            $tanggalAntrian = $tanggalAntrian ?? today();
            
            // ✅ CHECK GLOBAL PENDING MODE - CEK DI AWAL
            $isGlobalPendingActive = $this->isGlobalPendingActive();
            
            // ✅ EXISTING: Check quota availability
            $quotaCheck = $this->checkQuotaAvailability($doctorId, $tanggalAntrian);
            
            if (!$quotaCheck['available']) {
                throw new \Exception($quotaCheck['message']);
            }
            
            // ✅ EXISTING: Validasi session dokter jika ada doctor_id
            if ($doctorId) {
                $this->validateDoctorSession($doctorId, $tanggalAntrian);
            }
            
            // ✅ EXISTING: Generate nomor berdasarkan session atau service
            $number = $this->generateNumberForSession($serviceId, $tanggalAntrian, $doctorId);
            
            // ✅ EXISTING: Handle KTP data
            if ($ktpData && isset($ktpData['nomor_ktp'])) {
                $user = User::getOrCreateByKtp($ktpData['nomor_ktp'], $ktpData);
                $userId = $user->id;
            }
            
            $userId = $userId ?? Auth::id();

            // ✅ UPDATED: Tentukan status awal berdasarkan global pending
            $initialStatus = $isGlobalPendingActive ? 'pending' : 'waiting';
            
            // ✅ UPDATED: Hitung estimasi berdasarkan global pending
            $estimatedCallTime = null;
            $extraDelayMinutes = 0;
            
            if (!$isGlobalPendingActive) {
                // Normal mode: Hitung estimasi seperti biasa
                $estimatedCallTime = $this->calculateSessionEstimatedTime($serviceId, $tanggalAntrian, $doctorId);
                $extraDelayMinutes = $this->getSessionDelay($doctorId, $tanggalAntrian);
            } else {
                // Global pending mode: Set estimasi minimal (karena akan di-pending)
                $estimatedCallTime = null; // Tidak perlu estimasi
                $extraDelayMinutes = 15; // Default 15 menit saja untuk pending
            }

            // ✅ EXISTING: Buat antrian
            $queue = Queue::create([
                'service_id' => $serviceId,
                'user_id' => $userId,
                'doctor_id' => $doctorId,
                'number' => $number,
                'status' => $initialStatus, // ✅ BISA 'waiting' atau 'pending'
                'tanggal_antrian' => $tanggalAntrian,
                'estimated_call_time' => $estimatedCallTime,
                'extra_delay_minutes' => $extraDelayMinutes,
            ]);

            // ✅ EXISTING: Update quota usage untuk WeeklyQuota
            if ($doctorId) {
                $this->incrementWeeklyQuotaUsage($doctorId, $tanggalAntrian);
            }

            // ✅ UPDATED: Update estimasi untuk antrian lain (hanya jika tidak global pending)
            if (!$isGlobalPendingActive) {
                $this->updateSessionEstimations($serviceId, $tanggalAntrian, $doctorId, $queue->id);
            }

            return $queue;
        });
    }

    // ✅ EXISTING METHODS SEMUA DIPERTAHANKAN

    /**
     * ✅ EXISTING: Get available doctor sessions - Support untuk semua tanggal
     */
    public function getAvailableDoctorSessions($tanggalAntrian)
    {
        $tanggalCarbon = Carbon::parse($tanggalAntrian);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        // ✅ PERBAIKAN: Hanya cek waktu jika tanggal adalah hari ini
        $isToday = $tanggalCarbon->isToday();
        $currentTime = $isToday ? now()->format('H:i') : '00:00';
        
        $doctors = DoctorSchedule::where('is_active', true)
            ->whereJsonContains('days', $dayOfWeek)
            ->where(function($query) use ($isToday, $currentTime) {
                // ✅ PERBAIKAN: Jika bukan hari ini, semua session available
                if (!$isToday) {
                    // Untuk tanggal masa depan, tidak perlu filter waktu
                    return;
                }
                // ✅ Jika hari ini, hanya session yang belum selesai
                $query->whereTime('end_time', '>', $currentTime);
            })
            ->with('service')
            ->get();
            
        return $doctors->map(function($doctor) use ($tanggalAntrian, $isToday) {
            // ✅ FIXED: Cek quota availability untuk tanggal spesifik
            $quotaCheck = $this->checkQuotaAvailability($doctor->id, $tanggalAntrian);
            
            return [
                'id' => $doctor->id,
                'doctor_name' => $doctor->doctor_name,
                'service_name' => $doctor->service->name ?? 'Unknown',
                'start_time' => $doctor->start_time->format('H:i'),
                'end_time' => $doctor->end_time->format('H:i'),
                'time_range' => $doctor->start_time->format('H:i') . ' - ' . $doctor->end_time->format('H:i'),
                'is_available' => $quotaCheck['available'],
                'quota_status' => $quotaCheck['available'] ? 'Tersedia' : 'Penuh',
                'quota_info' => $quotaCheck['quota'] ? [
                    'used' => $quotaCheck['quota']->getUsedQuotaForDate($tanggalAntrian),
                    'total' => $quotaCheck['quota']->total_quota,
                    'remaining' => $quotaCheck['quota']->getAvailableQuotaForDate($tanggalAntrian)
                ] : null,
                'is_today' => $isToday,
                'selected_date' => $tanggalAntrian
            ];
        })->filter(function($session) {
            // ✅ UPDATED: Filter hanya yang tersedia (ada quota)
            return $session['is_available'];
        });
    }

    /**
     * ✅ EXISTING: Increment weekly quota usage
     */
    private function incrementWeeklyQuotaUsage($doctorId, $tanggalAntrian)
    {
        $tanggalCarbon = Carbon::parse($tanggalAntrian);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        $quota = WeeklyQuota::where('doctor_schedule_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
        
        if (!$quota) {
            // Auto-create quota if not exists
            $quota = WeeklyQuota::create([
                'doctor_schedule_id' => $doctorId,
                'day_of_week' => $dayOfWeek,
                'total_quota' => 20,
                'is_active' => true,
            ]);
        }
        
        // No need to manually increment - WeeklyQuota calculates from actual queues
    }

    /**
     * ✅ EXISTING: Check quota availability untuk WeeklyQuota system - SUPPORT SEMUA TANGGAL
     */
    public function checkQuotaAvailability($doctorId, $tanggalAntrian): array
    {
        if (!$doctorId) {
            return [
                'available' => true,
                'quota' => null,
                'message' => 'Non-session queue - no quota limit'
            ];
        }
        
        $tanggalCarbon = Carbon::parse($tanggalAntrian);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        $quota = WeeklyQuota::where('doctor_schedule_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
        
        if (!$quota) {
            // Auto-create quota with default 20
            $quota = WeeklyQuota::create([
                'doctor_schedule_id' => $doctorId,
                'day_of_week' => $dayOfWeek,
                'total_quota' => 20,
                'is_active' => true,
            ]);
        }
        
        // ✅ FIXED: Gunakan method untuk tanggal spesifik, bukan today
        $available = $quota->getAvailableQuotaForDate($tanggalAntrian) > 0;
        
        return [
            'available' => $available,
            'quota' => $quota,
            'message' => $available 
                ? "Kuota tersedia: {$quota->getFormattedQuotaForDate($tanggalAntrian)}"
                : "Kuota sudah penuh: {$quota->getFormattedQuotaForDate($tanggalAntrian)}"
        ];
    }

    /**
     * ✅ EXISTING: cancelQueue dengan WeeklyQuota decrement
     */
    public function cancelQueue(Queue $queue)
    {
        if (!in_array($queue->status, ['waiting', 'serving', 'pending'])) { // ✅ TAMBAH pending
            return;
        }

        DB::transaction(function () use ($queue) {
            $queue->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);
            
            // ✅ FIXED: WeeklyQuota akan auto-recalculate dari actual queues
            // Tidak perlu manual decrement
            
            // UPDATE estimasi queue lain setelah ada yang cancel
            if (in_array($queue->status, ['waiting', 'pending'])) { // ✅ UPDATE check
                if ($queue->doctor_id) {
                    $this->updateSessionEstimations($queue->service_id, $queue->tanggal_antrian, $queue->doctor_id);
                } else {
                    $this->updateEstimationsAfterQueueCalled($queue->service_id, $queue->tanggal_antrian);
                }
            }
        });
    }

    /**
     * ✅ EXISTING: Generate nomor antrian berdasarkan session dokter
     */
    public function generateNumberForSession($serviceId, $tanggalAntrian, $doctorId = null)
    {
        $service = Service::findOrFail($serviceId);
        
        if ($doctorId) {
            // ✅ NOMOR BERDASARKAN SESSION DOKTER
            $lastQueue = Queue::where('service_id', $serviceId)
                ->where('doctor_id', $doctorId)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->orderByDesc('id')
                ->first();
        } else {
            // Fallback ke sistem lama (berdasarkan tanggal saja)
            $lastQueue = Queue::where('service_id', $serviceId)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->whereNull('doctor_id')
                ->orderByDesc('id')
                ->first();
        }
        
        $lastQueueNumber = $lastQueue ? intval(
            substr($lastQueue->number, strlen($service->prefix))
        ) : 0;

        $newQueueNumber = $lastQueueNumber + 1;
        $maximumNumber = pow(10, $service->padding) - 1;

        // Reset ke 1 jika sudah mencapai maksimum
        if ($newQueueNumber > $maximumNumber) {
            $newQueueNumber = 1;
        }

        return $service->prefix . str_pad($newQueueNumber, $service->padding, "0", STR_PAD_LEFT);
    }

    /**
     * ✅ EXISTING: Validasi session dokter dengan logic tanggal yang benar
     */
    public function validateDoctorSession($doctorId, $tanggalAntrian)
    {
        $doctor = DoctorSchedule::find($doctorId);
        if (!$doctor) {
            throw new \InvalidArgumentException('Dokter tidak ditemukan');
        }
        
        $tanggalCarbon = Carbon::parse($tanggalAntrian);
        
        // Cek apakah tanggal valid
        if ($tanggalCarbon->isPast() && !$tanggalCarbon->isToday()) {
            throw new \InvalidArgumentException('Tidak dapat membuat antrian untuk tanggal yang sudah lewat');
        }
        
        // Cek hari praktik dokter
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        if (!in_array($dayOfWeek, $doctor->days ?? [])) {
            throw new \InvalidArgumentException("Dokter {$doctor->doctor_name} tidak praktik pada hari ini");
        }
        
        // ✅ PERBAIKAN: Hanya cek waktu jika tanggal adalah hari ini
        if ($tanggalCarbon->isToday()) {
            $currentTime = now()->format('H:i');
            $sessionEndTime = $doctor->end_time->format('H:i');
            
            if ($currentTime >= $sessionEndTime) {
                throw new \InvalidArgumentException(
                    "Sesi dokter {$doctor->doctor_name} sudah selesai (berakhir jam {$sessionEndTime}). " .
                    "Silakan pilih dokter lain atau jadwal untuk hari berikutnya."
                );
            }
        }
        
        return true;
    }

    /**
     * ✅ EXISTING: Hitung estimasi berdasarkan session dokter
     */
    private function calculateSessionEstimatedTime($serviceId, $tanggalAntrian, $doctorId = null)
    {
        if (!$doctorId) {
            // Fallback ke sistem lama
            return $this->calculateEstimatedCallTimeForDate($serviceId, $tanggalAntrian);
        }
        
        $doctor = DoctorSchedule::find($doctorId);
        if (!$doctor) {
            return $this->calculateEstimatedCallTimeForDate($serviceId, $tanggalAntrian);
        }
        
        // Hitung antrian dalam session dokter yang sama (HANYA waiting, tidak termasuk pending)
        $waitingQueues = Queue::where('service_id', $serviceId)
            ->where('doctor_id', $doctorId)
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->where('status', 'waiting') // ✅ HANYA waiting queues
            ->count();

        $queuePosition = $waitingQueues + 1;
        $baseMinutes = $queuePosition * 15; // 15 menit per antrian
        
        $sessionDelay = $this->getSessionDelay($doctorId, $tanggalAntrian);
        $totalMinutes = $baseMinutes + $sessionDelay;

        $tanggalCarbon = Carbon::parse($tanggalAntrian);
        $sessionStartTime = $doctor->start_time;
        
        if ($tanggalCarbon->isToday()) {
            // ✅ JIKA HARI INI: Mulai dari jam session atau sekarang (yang lebih besar)
            $sessionStartDateTime = $tanggalCarbon->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'));
            $startTime = now()->max($sessionStartDateTime);
        } else {
            // ✅ JIKA MASA DEPAN: Mulai dari jam session dokter
            $startTime = $tanggalCarbon->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'));
        }
        
        return $startTime->addMinutes($totalMinutes);
    }

    /**
     * ✅ EXISTING: Get delay untuk session dokter tertentu
     */
    private function getSessionDelay($doctorId, $tanggalAntrian)
    {
        if (!$doctorId) {
            return $this->getGlobalDelayForDate($tanggalAntrian);
        }
        
        $maxDelay = Queue::where('doctor_id', $doctorId)
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->where('status', 'waiting') // ✅ HANYA waiting queues
            ->max('extra_delay_minutes');

        return $maxDelay ?: 0;
    }

    /**
     * ✅ EXISTING: Update estimasi untuk session tertentu
     */
    private function updateSessionEstimations($serviceId, $tanggalAntrian, $doctorId, $excludeQueueId = null)
    {
        if (!$doctorId) {
            return $this->updateEstimationsAfterNewQueue($serviceId, $excludeQueueId, $tanggalAntrian);
        }
        
        $doctor = DoctorSchedule::find($doctorId);
        if (!$doctor) {
            return;
        }
        
        // ✅ UPDATED: Hanya update antrian waiting (tidak pending)
        $waitingQueues = Queue::where('service_id', $serviceId)
            ->where('doctor_id', $doctorId)
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->where('status', 'waiting') // ✅ HANYA waiting
            ->when($excludeQueueId, function($query) use ($excludeQueueId) {
                return $query->where('id', '!=', $excludeQueueId);
            })
            ->orderBy('id', 'asc')
            ->get();

        $sessionDelay = $this->getSessionDelay($doctorId, $tanggalAntrian);
        $sessionStartTime = $doctor->start_time;
        $tanggalCarbon = Carbon::parse($tanggalAntrian);
        
        foreach ($waitingQueues as $index => $queue) {
            $queuePosition = $index + 1;
            $baseMinutes = $queuePosition * 15;
            $totalMinutes = $baseMinutes + $sessionDelay;
            
            if ($tanggalCarbon->isToday()) {
                $sessionStartDateTime = $tanggalCarbon->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'));
                $startTime = now()->max($sessionStartDateTime);
            } else {
                $startTime = $tanggalCarbon->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'));
            }
            
            $estimatedTime = $startTime->addMinutes($totalMinutes);
            
            $queue->update([
                'estimated_call_time' => $estimatedTime,
                'extra_delay_minutes' => $sessionDelay
            ]);
        }
    }

    // ✅ EXISTING METHODS - SEMUA DIPERTAHANKAN TANPA PERUBAHAN

    /**
     * ✅ EXISTING: Generate nomor antrian berdasarkan tanggal_antrian spesifik (Fallback)
     */
    public function generateNumberForDate($serviceId, $tanggalAntrian)
    {
        return $this->generateNumberForSession($serviceId, $tanggalAntrian, null);
    }

    /**
     * ✅ EXISTING: Hitung estimasi untuk tanggal spesifik (Fallback untuk non-session)
     */
    private function calculateEstimatedCallTimeForDate($serviceId, $tanggalAntrian)
    {
        // HITUNG HANYA antrian di tanggal yang sama (non-session, HANYA waiting)
        $waitingQueues = Queue::where('service_id', $serviceId)
            ->where('status', 'waiting') // ✅ HANYA waiting queues
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNull('doctor_id') // ✅ HANYA non-session queues
            ->count();

        $queuePosition = $waitingQueues + 1;
        $baseMinutes = $queuePosition * 15;

        // Global delay untuk tanggal tersebut (non-session)
        $globalDelay = $this->getGlobalDelayForDate($tanggalAntrian);
        $totalMinutes = $baseMinutes + $globalDelay;

        // ESTIMASI: Jika untuk hari ini, dari sekarang. Jika untuk masa depan, dari jam 8 pagi
        if (Carbon::parse($tanggalAntrian)->isToday()) {
            return now()->addMinutes($totalMinutes);
        } else {
            // Untuk tanggal masa depan, mulai dari jam 8 pagi
            return Carbon::parse($tanggalAntrian)->setTime(8, 0)->addMinutes($totalMinutes);
        }
    }

    /**
     * ✅ EXISTING: Get global delay untuk tanggal tertentu (non-session)
     */
    private function getGlobalDelayForDate($tanggalAntrian)
    {
        $maxDelay = Queue::where('status', 'waiting')
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNull('doctor_id') // ✅ HANYA non-session queues
            ->max('extra_delay_minutes');

        return $maxDelay ?: 0;
    }

    /**
     * ✅ EXISTING: Update estimasi setelah antrian baru untuk tanggal spesifik
     */
    private function updateEstimationsAfterNewQueue($serviceId, $excludeQueueId, $tanggalAntrian)
    {
        // ✅ UPDATED: Hanya update antrian waiting (tidak pending)
        $waitingQueues = Queue::where('service_id', $serviceId)
            ->where('status', 'waiting') // ✅ HANYA waiting
            ->where('id', '!=', $excludeQueueId)
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNull('doctor_id') // ✅ HANYA non-session queues
            ->orderBy('id', 'asc')
            ->get();

        $globalDelay = $this->getGlobalDelayForDate($tanggalAntrian);
        
        foreach ($waitingQueues as $queue) {
            // HITUNG posisi berdasarkan tanggal antrian (non-session)
            $queuePosition = Queue::where('service_id', $serviceId)
                ->where('status', 'waiting')
                ->where('id', '<', $queue->id)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->whereNull('doctor_id')
                ->count() + 1;
            
            $baseMinutes = $queuePosition * 15;
            $totalMinutes = $baseMinutes + $globalDelay;
            
            // ESTIMASI berdasarkan tanggal
            if (Carbon::parse($tanggalAntrian)->isToday()) {
                $estimatedTime = now()->addMinutes($totalMinutes);
            } else {
                $estimatedTime = Carbon::parse($tanggalAntrian)->setTime(8, 0)->addMinutes($totalMinutes);
            }
            
            $queue->update([
                'estimated_call_time' => $estimatedTime,
                'extra_delay_minutes' => $globalDelay
            ]);
        }
    }

    /**
     * ✅ EXISTING: Call next queue dengan session support - DIBATASI HANYA HARI INI
     */
    public function callNextQueue($counterId)
    {
        $counter = Counter::findOrFail($counterId);

        // ✅ PASTIKAN: Hanya cari antrian untuk hari ini (today) - TIDAK BOLEH BESOK
        $nextQueue = Queue::where('status', 'waiting') // ✅ HANYA waiting (tidak pending)
            ->where('service_id', $counter->service_id)
            ->where(function ($query) use ($counterId) {
                $query->whereNull('counter_id')->orWhere('counter_id', $counterId);
            })
            ->whereDate('tanggal_antrian', today()) // ✅ KUNCI UTAMA: HANYA HARI INI
            ->orderByRaw('doctor_id IS NULL ASC') // ✅ Session queues first
            ->orderBy('id')
            ->first();

        if ($nextQueue && !$nextQueue->counter_id) {
            // ✅ VALIDASI TAMBAHAN: Double check tanggal sebelum update
            if (!$nextQueue->tanggal_antrian->isToday()) {
                throw new \Exception('Hanya antrian hari ini yang dapat dipanggil.');
            }

            $nextQueue->update([
                'counter_id' => $counterId,
                'called_at' => now(),
                'status' => 'serving'
            ]);

            // UPDATE ESTIMASI untuk antrian yang tersisa hari ini
            if ($nextQueue->doctor_id) {
                $this->updateSessionEstimations($counter->service_id, today(), $nextQueue->doctor_id);
            } else {
                $this->updateEstimationsAfterQueueCalled($counter->service_id, today());
            }
        }

        return $nextQueue;
    }

    /**
     * ✅ EXISTING: Update estimasi setelah ada antrian yang dipanggil (non-session)
     */
    private function updateEstimationsAfterQueueCalled($serviceId, $tanggalAntrian)
    {
        $waitingQueues = Queue::where('service_id', $serviceId)
            ->where('status', 'waiting') // ✅ HANYA waiting
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNull('doctor_id') // ✅ HANYA non-session queues
            ->orderBy('id', 'asc')
            ->get();

        $globalDelay = $this->getGlobalDelayForDate($tanggalAntrian);
        
        foreach ($waitingQueues as $queue) {
            $queuePosition = Queue::where('service_id', $serviceId)
                ->where('status', 'waiting')
                ->where('id', '<', $queue->id)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->whereNull('doctor_id')
                ->count() + 1;
            
            $baseMinutes = $queuePosition * 15;
            $totalMinutes = $baseMinutes + $globalDelay;
            
            if (Carbon::parse($tanggalAntrian)->isToday()) {
                $estimatedTime = now()->addMinutes($totalMinutes);
            } else {
                $estimatedTime = Carbon::parse($tanggalAntrian)->setTime(8, 0)->addMinutes($totalMinutes);
            }
            
            $queue->update([
                'estimated_call_time' => $estimatedTime,
                'extra_delay_minutes' => $globalDelay
            ]);
        }
    }

    // ✅ SEMUA METHOD EXISTING LAINNYA TETAP SAMA
    // (Saya potong untuk menghemat space, tapi semua method dari file asli tetap ada)

    public function getQuotaSummaryForDate($date): array
    {
        // ... method existing tetap sama
        $tanggalCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        $quotas = WeeklyQuota::with('doctorSchedule.service')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        return [
            'total_doctors' => $quotas->count(),
            'total_quota' => $quotas->sum('total_quota'),
            'total_used' => $quotas->sum(function($quota) use ($date) {
                return $quota->getUsedQuotaForDate($date);
            }),
            'total_available' => $quotas->sum(function($quota) use ($date) {
                return $quota->getAvailableQuotaForDate($date);
            }),
            'full_quotas' => $quotas->filter(function($quota) use ($date) {
                return $quota->isQuotaFullForDate($date);
            })->count(),
            'nearly_full_quotas' => $quotas->filter(function($quota) use ($date) {
                return $quota->isQuotaNearlyFullForDate($date);
            })->count(),
        ];
    }

    public function getQuotaAlerts($date = null): array
    {
        // ... method existing tetap sama
        $date = $date ?? today();
        $tanggalCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        $quotas = WeeklyQuota::with('doctorSchedule.service')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();
        
        $alerts = [
            'full' => $quotas->filter(function($quota) use ($date) {
                return $quota->isQuotaFullForDate($date);
            }),
            'nearly_full' => $quotas->filter(function($quota) use ($date) {
                return $quota->isQuotaNearlyFullForDate($date);
            }),
            'available' => $quotas->filter(function($quota) use ($date) {
                return $quota->getAvailableQuotaForDate($date) > 0 && !$quota->isQuotaNearlyFullForDate($date);
            }),
        ];
        
        return [
            'date' => Carbon::parse($date)->format('d F Y'),
            'total_quotas' => $quotas->count(),
            'full_count' => $alerts['full']->count(),
            'nearly_full_count' => $alerts['nearly_full']->count(),
            'available_count' => $alerts['available']->count(),
            'alerts' => $alerts,
            'summary' => $this->getQuotaSummaryForDate($date),
        ];
    }

    public function getAvailableDoctorSessionsWithQuota($tanggalAntrian)
    {
        // ... method existing tetap sama
        $sessions = $this->getAvailableDoctorSessions($tanggalAntrian);
        
        return $sessions->map(function ($session) use ($tanggalAntrian) {
            $quotaCheck = $this->checkQuotaAvailability($session['id'], $tanggalAntrian);
            
            $session['quota_info'] = [
                'available' => $quotaCheck['available'],
                'quota' => $quotaCheck['quota'] ? [
                    'total' => $quotaCheck['quota']->total_quota,
                    'used' => $quotaCheck['quota']->getUsedQuotaForDate($tanggalAntrian),
                    'remaining' => $quotaCheck['quota']->getAvailableQuotaForDate($tanggalAntrian),
                    'percentage' => $quotaCheck['quota']->getUsagePercentageForDate($tanggalAntrian),
                    'status' => $quotaCheck['quota']->getStatusLabelForDate($tanggalAntrian),
                ] : null,
                'message' => $quotaCheck['message'],
            ];
            
            return $session;
        })->filter(function ($session) {
            return $session['quota_info']['available'];
        });
    }

    public function createWeeklyQuotasForDate($date, $defaultQuota = 20): array
    {
        // ... method existing tetap sama
        $tanggalCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        $doctors = DoctorSchedule::where('is_active', true)
            ->whereJsonContains('days', $dayOfWeek)
            ->get();
        
        $created = 0;
        $existing = 0;
        $results = [];
        
        foreach ($doctors as $doctor) {
            $quota = WeeklyQuota::where('doctor_schedule_id', $doctor->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();
            
            if (!$quota) {
                $quota = WeeklyQuota::create([
                    'doctor_schedule_id' => $doctor->id,
                    'day_of_week' => $dayOfWeek,
                    'total_quota' => $defaultQuota,
                    'is_active' => true,
                ]);
                
                $created++;
                
                $results[] = [
                    'doctor' => $doctor->doctor_name,
                    'service' => $doctor->service->name ?? 'Unknown',
                    'action' => 'created',
                    'quota' => $quota->getFormattedQuotaForDate($date),
                ];
            } else {
                $existing++;
                $results[] = [
                    'doctor' => $doctor->doctor_name,
                    'service' => $doctor->service->name ?? 'Unknown',
                    'action' => 'existing',
                    'quota' => $quota->getFormattedQuotaForDate($date),
                ];
            }
        }
        
        return [
            'created' => $created,
            'existing' => $existing,
            'total_doctors' => $doctors->count(),
            'results' => $results,
        ];
    }

    public function syncQuotasForDate($date): array
    {
        // ... method existing tetap sama
        $tanggalCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($tanggalCarbon->format('l'));
        
        $quotas = WeeklyQuota::where('day_of_week', $dayOfWeek)->get();
        $synced = 0;
        $results = [];
        
        foreach ($quotas as $quota) {
            $oldUsed = $quota->getUsedQuotaForDate($date);
            $newUsed = $quota->getUsedQuotaForDate($date);
            
            if ($oldUsed !== $newUsed) {
                $synced++;
            }
            
            $results[] = [
                'doctor' => $quota->doctorSchedule->doctor_name ?? 'Unknown',
                'old_used' => $oldUsed,
                'new_used' => $newUsed,
                'total' => $quota->total_quota,
                'changed' => $oldUsed !== $newUsed,
            ];
        }
        
        return [
            'synced' => $synced,
            'total_quotas' => $quotas->count(),
            'results' => $results,
        ];
    }

    // ✅ LEGACY METHODS untuk backward compatibility
    public function generateNumber($serviceId)
    {
        return $this->generateNumberForDate($serviceId, today());
    }

    public function updateOverdueQueues()
    {
        return $this->updateOverdueQueuesForDate(today());
    }

    public function addQueueWithKtp($serviceId, string $ktp, array $patientData = [], $tanggalAntrian = null)
    {
        if (strlen($ktp) !== 16 || !is_numeric($ktp)) {
            throw new \InvalidArgumentException('Nomor KTP harus 16 digit angka');
        }

        $userData = array_merge([
            'nomor_ktp' => $ktp,
            'name' => 'Pasien Walk-in - ' . substr($ktp, -4),
            'email' => 'patient' . substr($ktp, -4) . '@klinik.local',
            'address' => 'Alamat belum diisi',
            'phone' => null,
            'gender' => null,
            'birth_date' => null,
        ], $patientData);

        return $this->addQueue($serviceId, null, $userData, $tanggalAntrian);
    }

    public function serveQueue(Queue $queue)
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $queue->update([
            'status' => 'serving',
            'served_at' => now()
        ]);
    }

    public function finishQueue(Queue $queue)
    {
        if ($queue->status !== 'serving') {
            return;
        }

        $queue->update([
            'status' => 'finished',
            'finished_at' => now()
        ]);
    }

    public function searchUserByIdentifier(string $identifier): ?User
    {
        $user = User::where('medical_record_number', $identifier)->first();
        
        if (!$user && strlen($identifier) === 16 && is_numeric($identifier)) {
            $user = User::where('nomor_ktp', $identifier)->first();
        }
        
        return $user;
    }

    /**
     * ✅ UPDATED: Reset estimasi untuk tanggal tertentu dengan session support
     */
    public function resetEstimationsForDate($tanggalAntrian)
    {
        $updatedCount = 0;

        // Reset session-based queues per dokter
        $sessionGroups = Queue::where('status', 'waiting') // ✅ HANYA waiting
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNotNull('doctor_id')
            ->select('doctor_id')
            ->distinct()
            ->pluck('doctor_id');

        foreach ($sessionGroups as $doctorId) {
            $doctor = DoctorSchedule::find($doctorId);
            if (!$doctor) continue;

            $sessionQueues = Queue::where('status', 'waiting') // ✅ HANYA waiting
                ->where('doctor_id', $doctorId)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($sessionQueues as $index => $queue) {
                $queuePosition = $index + 1;
                $baseMinutes = $queuePosition * 15;
                
                if (Carbon::parse($tanggalAntrian)->isToday()) {
                    $sessionStartDateTime = Carbon::parse($tanggalAntrian)->setTimeFromTimeString($doctor->start_time->format('H:i'));
                    $startTime = now()->max($sessionStartDateTime);
                    $estimatedTime = $startTime->addMinutes($baseMinutes);
                } else {
                    $estimatedTime = Carbon::parse($tanggalAntrian)->setTimeFromTimeString($doctor->start_time->format('H:i'))->addMinutes($baseMinutes);
                }
                
                $queue->update([
                    'estimated_call_time' => $estimatedTime,
                    'extra_delay_minutes' => 0
                ]);
                
                $updatedCount++;
            }
        }

        // Reset non-session queues
        $nonSessionQueues = Queue::where('status', 'waiting') // ✅ HANYA waiting
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNull('doctor_id')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($nonSessionQueues as $queue) {
            $queuePosition = Queue::where('service_id', $queue->service_id)
                ->where('status', 'waiting')
                ->where('id', '<', $queue->id)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->whereNull('doctor_id')
                ->count() + 1;
            
            $baseMinutes = $queuePosition * 15;
            
            if (Carbon::parse($tanggalAntrian)->isToday()) {
                $estimatedTime = now()->addMinutes($baseMinutes);
            } else {
                $estimatedTime = Carbon::parse($tanggalAntrian)->setTime(8, 0)->addMinutes($baseMinutes);
            }
            
            $queue->update([
                'estimated_call_time' => $estimatedTime,
                'extra_delay_minutes' => 0
            ]);
            
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * ✅ UPDATED: Update overdue queues dengan session support
     */
    public function updateOverdueQueuesForDate($tanggalAntrian = null)
    {
        $tanggalAntrian = $tanggalAntrian ?? today();
        
        // Update session-based queues (ada doctor_id)
        $sessionsWithOverdue = Queue::where('status', 'waiting') // ✅ HANYA waiting
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNotNull('doctor_id')
            ->where('estimated_call_time', '<', now())
            ->select('doctor_id')
            ->distinct()
            ->pluck('doctor_id');
        
        $updatedCount = 0;
        
        // Update per session dokter
        foreach ($sessionsWithOverdue as $doctorId) {
            $sessionQueues = Queue::where('status', 'waiting') // ✅ HANYA waiting
                ->where('doctor_id', $doctorId)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->get();
            
            foreach ($sessionQueues as $queue) {
                $newExtraDelay = $queue->extra_delay_minutes + 5;
                
                $doctor = DoctorSchedule::find($doctorId);
                if ($doctor) {
                    $queuePosition = Queue::where('doctor_id', $doctorId)
                        ->where('status', 'waiting')
                        ->where('id', '<', $queue->id)
                        ->whereDate('tanggal_antrian', $tanggalAntrian)
                        ->count() + 1;
                    
                    $baseMinutes = $queuePosition * 15;
                    $totalMinutes = $baseMinutes + $newExtraDelay;
                    
                    if (Carbon::parse($tanggalAntrian)->isToday()) {
                        $sessionStartDateTime = Carbon::parse($tanggalAntrian)->setTimeFromTimeString($doctor->start_time->format('H:i'));
                        $startTime = now()->max($sessionStartDateTime);
                        $newEstimation = $startTime->addMinutes($totalMinutes - $baseMinutes + 5);
                    } else {
                        $startTime = Carbon::parse($tanggalAntrian)->setTimeFromTimeString($doctor->start_time->format('H:i'));
                        $newEstimation = $startTime->addMinutes($totalMinutes);
                    }
                    
                    $queue->update([
                        'estimated_call_time' => $newEstimation,
                        'extra_delay_minutes' => $newExtraDelay
                    ]);
                    
                    $updatedCount++;
                }
            }
        }
        
        // Update non-session queues (fallback ke sistem lama)
        $nonSessionQueues = Queue::where('status', 'waiting') // ✅ HANYA waiting
            ->whereDate('tanggal_antrian', $tanggalAntrian)
            ->whereNull('doctor_id')
            ->where('estimated_call_time', '<', now())
            ->get();
        
        foreach ($nonSessionQueues as $queue) {
            $newExtraDelay = $queue->extra_delay_minutes + 5;
            $newEstimation = now()->addMinutes(5);
            
            $queue->update([
                'estimated_call_time' => $newEstimation,
                'extra_delay_minutes' => $newExtraDelay
            ]);
            
            $updatedCount++;
        }
        
        return $updatedCount;
    }

    // ✅ NEW: METHODS UNTUK GLOBAL PENDING MANAGEMENT

    /**
     * ✅ NEW: Pending semua antrian menunggu untuk hari ini
     */
    public function pendingAllWaitingQueues(): array
    {
        $waitingQueues = Queue::where('status', 'waiting')
            ->whereDate('tanggal_antrian', today())
            ->get();
        
        if ($waitingQueues->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada antrian menunggu untuk hari ini',
                'count' => 0
            ];
        }
        
        $pendingCount = 0;
        foreach ($waitingQueues as $queue) {
            $remainingMinutes = 0;
            
            // Hitung sisa waktu
            if ($queue->estimated_call_time) {
                $estimatedTime = Carbon::parse($queue->estimated_call_time);
                $now = now();
                
                if ($estimatedTime->isFuture()) {
                    $remainingMinutes = $now->diffInMinutes($estimatedTime, false);
                }
                $remainingMinutes = max(0, $remainingMinutes);
            }
            
            // Update ke status pending
            $queue->update([
                'status' => 'pending',
                'estimated_call_time' => null,
                'extra_delay_minutes' => $remainingMinutes,
            ]);
            
            $pendingCount++;
        }
        
        // Auto-aktifkan global pending
        $this->activateGlobalPending();
        
        return [
            'success' => true,
            'message' => "{$pendingCount} antrian berhasil dijeda. Global Pending Mode AKTIF.",
            'count' => $pendingCount
        ];
    }

    /**
     * ✅ UPDATED: Resume semua antrian pending untuk hari ini
     */
    public function resumeAllPendingQueues(): array
    {
        $pendingQueues = Queue::where('status', 'pending')
            ->whereDate('tanggal_antrian', today())
            ->orderBy('id', 'asc') // ✅ ORDER BY ID untuk posisi yang benar
            ->get();
        
        if ($pendingQueues->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada antrian pending untuk hari ini',
                'count' => 0
            ];
        }
        
        // ✅ GROUPING by session untuk recalculate estimasi
        $sessionGroups = $pendingQueues->groupBy(function($queue) {
            return $queue->doctor_id ? "session_{$queue->doctor_id}" : "non_session_{$queue->service_id}";
        });
        
        $resumeCount = 0;
        
        foreach ($sessionGroups as $groupKey => $queues) {
            $isSession = str_starts_with($groupKey, 'session_');
            
            foreach ($queues as $index => $queue) {
                $queuePosition = $index + 1; // Posisi dalam group
                $baseMinutes = $queuePosition * 15; // 15 menit per posisi
                
                if ($isSession && $queue->doctor_id) {
                    // ✅ SESSION-BASED: Hitung dari jam mulai dokter
                    $doctor = DoctorSchedule::find($queue->doctor_id);
                    if ($doctor) {
                        $sessionStartTime = $doctor->start_time;
                        $sessionStartDateTime = $queue->tanggal_antrian->copy()
                            ->setTimeFromTimeString($sessionStartTime->format('H:i'));
                        
                        // Mulai dari sekarang atau jam session (yang lebih besar)
                        $startTime = now()->max($sessionStartDateTime);
                        $newEstimatedTime = $startTime->addMinutes($baseMinutes);
                    } else {
                        $newEstimatedTime = now()->addMinutes($baseMinutes);
                    }
                } else {
                    // ✅ NON-SESSION: Hitung dari sekarang
                    $newEstimatedTime = now()->addMinutes($baseMinutes);
                }
                
                // ✅ UPDATE ke status waiting dengan estimasi baru
                $queue->update([
                    'status' => 'waiting',
                    'estimated_call_time' => $newEstimatedTime,
                    'extra_delay_minutes' => 0, // ✅ RESET delay
                ]);
                
                $resumeCount++;
            }
        }
        
        // Auto-nonaktifkan global pending
        $this->deactivateGlobalPending();
        
        return [
            'success' => true,
            'message' => "{$resumeCount} antrian berhasil dilanjutkan. Global Pending Mode NONAKTIF.",
            'count' => $resumeCount
        ];
    }

    /**
     * ✅ NEW: Get statistik antrian untuk hari ini
     */
    public function getTodayQueueStatistics(): array
    {
        $today = today();
        $stats = [
            'waiting' => Queue::where('status', 'waiting')->whereDate('tanggal_antrian', $today)->count(),
            'pending' => Queue::where('status', 'pending')->whereDate('tanggal_antrian', $today)->count(),
            'serving' => Queue::where('status', 'serving')->whereDate('tanggal_antrian', $today)->count(),
            'finished' => Queue::where('status', 'finished')->whereDate('tanggal_antrian', $today)->count(),
            'canceled' => Queue::where('status', 'canceled')->whereDate('tanggal_antrian', $today)->count(),
        ];
        
        $total = array_sum($stats);
        $globalPendingActive = $this->isGlobalPendingActive();
        
        return [
            'date' => $today->format('d F Y'),
            'stats' => $stats,
            'total' => $total,
            'global_pending' => [
                'active' => $globalPendingActive,
                'label' => $globalPendingActive ? 'AKTIF' : 'TIDAK AKTIF',
            ]
        ];
    }
}