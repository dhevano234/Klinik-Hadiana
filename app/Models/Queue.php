<?php
// File: app/Models/Queue.php - UPDATED dengan Session Support

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Queue extends Model
{
    protected $fillable = [
        'counter_id',
        'service_id',
        'user_id',
        'doctor_id',
        'number',
        'status',
        'tanggal_antrian',
        'chief_complaint',
        'estimated_call_time', // ✅ ESTIMASI WAKTU PANGGILAN
        'extra_delay_minutes', // ✅ EXTRA DELAY 5 MENIT
        'whatsapp_reminder_sent_at',
        'called_at',
        'served_at',
        'canceled_at',
        'finished_at',
    ];

    protected $casts = [
        'tanggal_antrian' => 'date',
        'estimated_call_time' => 'datetime', // ✅ CAST DATETIME
        'called_at' => 'datetime',
        'served_at' => 'datetime', 
        'canceled_at' => 'datetime',
        'finished_at' => 'datetime',
        'whatsapp_reminder_sent_at',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ RELATIONSHIPS
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctorSchedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'doctor_id');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    // ✅ EXISTING ACCESSORS
    public function getDoctorNameAttribute(): ?string
    {
        if ($this->doctor_id && $this->doctorSchedule) {
            return $this->doctorSchedule->doctor_name;
        }
        
        if ($this->medicalRecord && $this->medicalRecord->doctor) {
            return $this->medicalRecord->doctor->name;
        }
        
        return null;
    }

    public function hasChiefComplaint(): bool
    {
        return !empty($this->chief_complaint);
    }

    public function getFormattedChiefComplaintAttribute(): ?string
    {
        return empty($this->chief_complaint) ? null : $this->chief_complaint;
    }

    public function getShortComplaintAttribute(): ?string
    {
        if (empty($this->chief_complaint)) {
            return null;
        }
        
        return strlen($this->chief_complaint) > 50 
            ? substr($this->chief_complaint, 0, 50) . '...'
            : $this->chief_complaint;
    }

    public function getPoliAttribute(): ?string
    {
        return $this->service->name ?? null;
    }

    public function getNameAttribute(): ?string
    {
        return $this->user->name ?? null;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user->phone ?? null;
    }

    public function getGenderAttribute(): ?string
    {
        return $this->user->gender ?? null;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'waiting' => 'warning',
            'serving' => 'info', 
            'finished' => 'success',
            'canceled' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'waiting' => 'Menunggu',
            'serving' => 'Sedang Dilayani',
            'finished' => 'Selesai',
            'canceled' => 'Dibatalkan',
            default => ucfirst($this->status)
        };
    }

    public function getFormattedTanggalAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('d F Y');
    }

    public function getWaktuAmbilAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('H:i');
    }

    public function getWaktuDipanggilAttribute(): ?string
    {
        return $this->called_at ? $this->called_at->setTimezone('Asia/Jakarta')->format('H:i') : null;
    }

    public function getWaktuSelesaiAttribute(): ?string
    {
        return $this->finished_at ? $this->finished_at->setTimezone('Asia/Jakarta')->format('H:i') : null;
    }

    public function getWaktuDilayaniAttribute(): ?string
    {
        return $this->served_at ? $this->served_at->setTimezone('Asia/Jakarta')->format('H:i') : null;
    }

    public function getWaktuDibatalkanAttribute(): ?string
    {
        return $this->canceled_at ? $this->canceled_at->setTimezone('Asia/Jakarta')->format('H:i') : null;
    }

    public function getFullDateTimeAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('l, d F Y H:i');
    }

    public function getTimelineInfoAttribute(): array
    {
        $timeline = [];
        
        $timeline['dibuat'] = [
            'waktu' => $this->waktu_ambil,
            'status' => 'Antrian dibuat',
            'icon' => 'fas fa-plus-circle',
            'color' => 'primary'
        ];
        
        if ($this->called_at) {
            $timeline['dipanggil'] = [
                'waktu' => $this->waktu_dipanggil,
                'status' => 'Antrian dipanggil',
                'icon' => 'fas fa-bell',
                'color' => 'warning'
            ];
        }
        
        if ($this->served_at) {
            $timeline['dilayani'] = [
                'waktu' => $this->waktu_dilayani,
                'status' => 'Mulai dilayani',
                'icon' => 'fas fa-user-md',
                'color' => 'info'
            ];
        }
        
        if ($this->finished_at) {
            $timeline['selesai'] = [
                'waktu' => $this->waktu_selesai,
                'status' => 'Selesai dilayani',
                'icon' => 'fas fa-check-circle',
                'color' => 'success'
            ];
        }
        
        if ($this->canceled_at) {
            $timeline['dibatalkan'] = [
                'waktu' => $this->waktu_dibatalkan,
                'status' => 'Antrian dibatalkan',
                'icon' => 'fas fa-times-circle',
                'color' => 'danger'
            ];
        }
        
        return $timeline;
    }

    // ✅ NEW: SESSION SUPPORT METHODS

    /**
     * Generate session identifier berdasarkan dokter, tanggal, dan waktu mulai
     */
    public static function generateSessionIdentifier($doctorId, $tanggalAntrian): ?string
    {
        $doctor = \App\Models\DoctorSchedule::find($doctorId);
        if (!$doctor) {
            return null;
        }
        
        $date = is_string($tanggalAntrian) ? $tanggalAntrian : $tanggalAntrian->format('Y-m-d');
        $startTime = $doctor->start_time->format('Hi'); // Format: 0800, 1400, etc.
        
        return strtoupper(str_replace([' ', '.', 'dr.', 'dr '], ['_', '', '', ''], $doctor->doctor_name)) . 
               '-' . $date . 
               '-' . $startTime;
    }

    /**
     * Get session identifier untuk queue ini
     */
    public function getSessionIdentifierAttribute(): ?string
    {
        if (!$this->doctor_id || !$this->tanggal_antrian) {
            return null;
        }
        
        return self::generateSessionIdentifier($this->doctor_id, $this->tanggal_antrian);
    }

    /**
     * Get posisi dalam session dokter tertentu
     */
    public function getSessionQueuePositionAttribute(): int
    {
        if (!$this->doctor_id) {
            // Fallback ke sistem lama berdasarkan service + tanggal
            return self::where('service_id', $this->service_id)
                ->where('status', 'waiting')
                ->where('id', '<', $this->id)
                ->whereDate('tanggal_antrian', $this->tanggal_antrian ?? today())
                ->whereNull('doctor_id')
                ->count() + 1;
        }
        
        // Hitung posisi dalam session dokter
        return self::where('doctor_id', $this->doctor_id)
            ->where('status', 'waiting')
            ->where('id', '<', $this->id)
            ->whereDate('tanggal_antrian', $this->tanggal_antrian ?? today())
            ->count() + 1;
    }

    /**
     * Get informasi session dokter
     */
    public function getDoctorSessionInfoAttribute(): ?array
    {
        if (!$this->doctor_id || !$this->doctorSchedule) {
            return null;
        }
        
        return [
            'doctor_name' => $this->doctorSchedule->doctor_name,
            'start_time' => $this->doctorSchedule->start_time->format('H:i'),
            'end_time' => $this->doctorSchedule->end_time->format('H:i'),
            'session_identifier' => $this->session_identifier,
            'is_active' => $this->isDoctorSessionActive()
        ];
    }

    /**
     * Check apakah session dokter masih aktif
     */
    public function isDoctorSessionActive(): bool
    {
        if (!$this->doctor_id || !$this->doctorSchedule || !$this->tanggal_antrian) {
            return false;
        }
        
        $sessionDate = $this->tanggal_antrian;
        $doctor = $this->doctorSchedule;
        
        // Jika bukan hari ini, bisa booking untuk masa depan
        if (!$sessionDate->isToday()) {
            return $sessionDate->isFuture();
        }
        
        // Jika hari ini, cek apakah session masih berlangsung
        $currentTime = now()->format('H:i');
        $sessionEndTime = $doctor->end_time->format('H:i');
        
        return $currentTime < $sessionEndTime;
    }

    // ✅ UPDATED: ESTIMASI WAKTU TUNGGU dengan SESSION SUPPORT
    
    /**
     * ✅ UPDATED: Get estimasi waktu tunggu berdasarkan session atau tanggal_antrian
     */
    public function getEstimasiTungguAttribute(): ?int
    {
        if ($this->status !== 'waiting') {
            return null;
        }
        
        // Jika ada estimated_call_time, gunakan itu
        if ($this->estimated_call_time) {
            $now = now();
            $estimatedTime = $this->estimated_call_time;
            
            if ($estimatedTime > $now) {
                return (int) round($now->diffInMinutes($estimatedTime));
            } else {
                return (int) ($this->extra_delay_minutes ?: 5);
            }
        }
        
        // ✅ NEW: Fallback berdasarkan session dokter atau tanggal
        if ($this->doctor_id) {
            // Berdasarkan session dokter
            $sessionPosition = $this->session_queue_position;
            return $sessionPosition * 15; // 15 menit per antrian
        } else {
            // Berdasarkan service + tanggal (sistem lama)
            $antrianDidepan = self::where('service_id', $this->service_id)
                ->where('status', 'waiting')
                ->where('id', '<', $this->id)
                ->whereDate('tanggal_antrian', $this->tanggal_antrian ?? today())
                ->whereNull('doctor_id')
                ->count();
            
            return ($antrianDidepan + 1) * 15;
        }
    }

    /**
     * ✅ Get estimasi waktu panggilan yang sudah diformat
     */
    public function getFormattedEstimasiAttribute(): ?string
    {
        if ($this->status !== 'waiting') {
            return null;
        }

        $estimasiMenit = $this->estimasi_tunggu;
        
        if ($estimasiMenit < 1) {
            return "Segera dipanggil";
        } elseif ($estimasiMenit < 60) {
            return "~{$estimasiMenit} menit lagi";
        } else {
            $hours = floor($estimasiMenit / 60);
            $minutes = $estimasiMenit % 60;
            return "~{$hours}j {$minutes}m lagi";
        }
    }

    /**
     * ✅ Get status delay (on_time, delayed)
     */
    public function getDelayStatusAttribute(): string
    {
        if ($this->status !== 'waiting') {
            return 'unknown';
        }

        if (!$this->estimated_call_time) {
            return 'on_time';
        }

        $now = now();
        $estimatedTime = $this->estimated_call_time;
        
        return $estimatedTime > $now ? 'on_time' : 'delayed';
    }

    /**
     * ✅ UPDATED: Get posisi dalam antrian berdasarkan session atau tanggal_antrian
     */
    public function getQueuePositionAttribute(): int
    {
        if ($this->doctor_id) {
            // Gunakan session queue position jika ada dokter
            return $this->session_queue_position;
        } else {
            // Fallback ke sistem lama
            return self::where('service_id', $this->service_id)
                ->where('status', 'waiting')
                ->where('id', '<', $this->id)
                ->whereDate('tanggal_antrian', $this->tanggal_antrian ?? today())
                ->whereNull('doctor_id')
                ->count() + 1;
        }
    }

    /**
     * ✅ Get estimasi waktu panggilan dalam format jam
     */
    public function getEstimatedCallTimeFormattedAttribute(): ?string
    {
        if (!$this->estimated_call_time) {
            // FALLBACK: Hitung estimasi manual
            $estimasiMenit = $this->estimasi_tunggu;
            
            if ($this->doctor_id && $this->doctorSchedule) {
                // Berdasarkan session dokter
                $sessionStartTime = $this->doctorSchedule->start_time;
                $estimatedTime = $this->tanggal_antrian->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'))->addMinutes($estimasiMenit);
            } else {
                // Fallback sistem lama
                $estimatedTime = $this->created_at->addMinutes($estimasiMenit);
            }
            
            return $estimatedTime->setTimezone('Asia/Jakarta')->format('H:i');
        }

        return $this->estimated_call_time->setTimezone('Asia/Jakarta')->format('H:i');
    }

    /**
     * ✅ Check apakah antrian sudah terlambat dari estimasi
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'waiting') {
            return false;
        }

        if (!$this->estimated_call_time) {
            // FALLBACK: Hitung berdasarkan session atau created_at + estimasi
            $estimasiMenit = $this->estimasi_tunggu;
            
            if ($this->doctor_id && $this->doctorSchedule) {
                $sessionStartTime = $this->doctorSchedule->start_time;
                $estimatedTime = $this->tanggal_antrian->copy()->setTimeFromTimeString($sessionStartTime->format('H:i'))->addMinutes($estimasiMenit);
            } else {
                $estimatedTime = $this->created_at->addMinutes($estimasiMenit);
            }
            
            return $estimatedTime < now();
        }

        return $this->estimated_call_time < now();
    }

    // ✅ HELPER METHODS
    public function canEdit(): bool
    {
        return in_array($this->status, ['waiting']);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['waiting']);
    }

    public function canPrint(): bool
    {
        return in_array($this->status, ['waiting', 'serving', 'finished']);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['finished', 'canceled']);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['waiting', 'serving']);
    }

    // ✅ SCOPE METHODS - FIXED untuk tanggal_antrian dan session
    public function scopeToday($query)
    {
        return $query->whereDate('tanggal_antrian', today());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForService($query, $serviceName)
    {
        return $query->whereHas('service', function($q) use ($serviceName) {
            $q->where('name', $serviceName);
        });
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'serving']);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['finished', 'canceled']);
    }

    public function scopeWithComplaint($query)
    {
        return $query->whereNotNull('chief_complaint')
                    ->where('chief_complaint', '!=', '');
    }

    public function scopeWithoutComplaint($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('chief_complaint')
              ->orWhere('chief_complaint', '');
        });
    }

    // ✅ SCOPES untuk estimasi waktu
    public function scopeOverdue($query)
    {
        return $query->where('status', 'waiting')
                    ->where('estimated_call_time', '<', now());
    }

    public function scopeOnTime($query)
    {
        return $query->where('status', 'waiting')
                    ->where('estimated_call_time', '>=', now());
    }

    // ✅ NEW SCOPES untuk tanggal_antrian dan session
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('tanggal_antrian', $date);
    }

    public function scopeWaitingOnDate($query, $date)
    {
        return $query->where('status', 'waiting')
                    ->whereDate('tanggal_antrian', $date);
    }

    public function scopeTodayQueues($query)
    {
        return $query->whereDate('tanggal_antrian', today());
    }

    /**
     * ✅ NEW: Scope untuk filter berdasarkan session dokter
     */
    public function scopeInDoctorSession($query, $doctorId, $tanggalAntrian)
    {
        return $query->where('doctor_id', $doctorId)
                     ->whereDate('tanggal_antrian', $tanggalAntrian);
    }

    /**
     * ✅ NEW: Scope untuk antrian dalam session yang sama
     */
    public function scopeSameSession($query, $doctorId, $tanggalAntrian)
    {
        return $query->where('doctor_id', $doctorId)
                     ->whereDate('tanggal_antrian', $tanggalAntrian)
                     ->where('status', 'waiting');
    }

    /**
     * ✅ NEW: Scope untuk non-session queues (backward compatibility)
     */
    public function scopeNonSession($query)
    {
        return $query->whereNull('doctor_id');
    }

    /**
     * ✅ NEW: Scope untuk session-based queues
     */
    public function scopeSessionBased($query)
    {
        return $query->whereNotNull('doctor_id');
    }
}