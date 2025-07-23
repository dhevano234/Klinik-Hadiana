<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\WeeklyQuota;

class DoctorSchedule extends Model
{
    protected $fillable = [
        'doctor_id',        // ✅ ADDED: Foreign key ke users table
        'doctor_name',      // ✅ KEEP: Untuk backward compatibility
        'service_id',
        'day_of_week',
        'days', 
        'start_time',
        'end_time',
        'is_active',
        'foto'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'days' => 'array',
    ];

    /**
     * ✅ NEW: Relationship ke User (dokter)
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Relationship ke Service (Poli)
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Service::class);
    }

    /**
     * ✅ UPDATED: Relationship ke User (untuk backward compatibility)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * ✅ PERBAIKAN: Relationship ke Queue (antrian yang memilih dokter ini)
     */
    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class, 'doctor_id');
    }

    /**
     * ✅ NEW: Relationship ke WeeklyQuota
     */
    public function WeeklyQuotas(): HasMany
    {
        return $this->hasMany(WeeklyQuota::class);
    }

    /**
     * ✅ ACCESSOR: Get photo URL with fallback
     */
    public function getFotoUrlAttribute(): string
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return Storage::url($this->foto);
        }
        
        return asset('assets/img/default-doctor.png');
    }

    /**
     * ✅ ACCESSOR: Check if doctor has photo
     */
    public function getHasFotoAttribute(): bool
    {
        return !empty($this->foto) && Storage::disk('public')->exists($this->foto);
    }

    /**
     * ✅ NEW: Get doctor name (with fallback)
     */
    public function getDoctorNameAttribute($value): string
    {
        // Jika ada doctor_id, ambil nama dari relationship
        if ($this->doctor_id && $this->doctor) {
            return $this->doctor->name;
        }
        
        // Fallback ke doctor_name field (untuk backward compatibility)
        return $value ?? 'Nama Dokter Tidak Diketahui';
    }

    /**
     * ✅ ACCESSOR: Get formatted days name in Indonesian
     */
    public function getFormattedDaysAttribute(): string
    {
        if (!$this->days || !is_array($this->days)) {
            return '-';
        }

        $dayNames = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        $formattedDays = array_map(function($day) use ($dayNames) {
            return $dayNames[$day] ?? ucfirst($day);
        }, $this->days);

        return $this->formatConsecutiveDays($formattedDays);
    }

    /**
     * ✅ HELPER: Format consecutive days
     */
    private function formatConsecutiveDays(array $days): string
    {
        $dayOrder = [
            'Senin' => 1,
            'Selasa' => 2,
            'Rabu' => 3,
            'Kamis' => 4,
            'Jumat' => 5,
            'Sabtu' => 6,
            'Minggu' => 7,
        ];

        usort($days, function($a, $b) use ($dayOrder) {
            return ($dayOrder[$a] ?? 99) - ($dayOrder[$b] ?? 99);
        });

        if (count($days) >= 5 && array_diff(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'], $days) == []) {
            $weekend = array_intersect(['Sabtu', 'Minggu'], $days);
            if (count($weekend) > 0) {
                return 'Senin-Jumat, ' . implode(', ', $weekend);
            } else {
                return 'Senin-Jumat';
            }
        }

        if (count($days) == 2 && array_diff(['Sabtu', 'Minggu'], $days) == []) {
            return 'Weekend';
        }

        return implode(', ', $days);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get duration in hours
     */
    public function getDurationAttribute(): float
    {
        $start = Carbon::createFromFormat('H:i', $this->start_time->format('H:i'));
        $end = Carbon::createFromFormat('H:i', $this->end_time->format('H:i'));
        
        return $start->diffInHours($end, true);
    }

    /**
     * Check if schedule is active today
     */
    public function isActiveToday(): bool
    {
        $today = strtolower(now()->format('l'));
        return $this->is_active && in_array($today, $this->days ?? []);
    }

    /**
     * ✅ NEW: Check if schedule has conflict with existing schedules (UPDATED untuk doctor_id)
     */
    public static function hasConflict($doctorId, $serviceId, $days, $startTime, $endTime, $excludeId = null): bool
    {
        $query = self::where('doctor_id', $doctorId)
                     ->where('service_id', $serviceId)
                     ->where('is_active', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSchedules = $query->get();

        foreach ($existingSchedules as $schedule) {
            // Check if any day overlaps
            $existingDays = $schedule->days ?? [];
            $newDays = is_array($days) ? $days : [$days];
            
            $dayOverlap = array_intersect($existingDays, $newDays);
            
            if (!empty($dayOverlap)) {
                // Check time overlap
                $existingStart = Carbon::parse($schedule->start_time)->format('H:i');
                $existingEnd = Carbon::parse($schedule->end_time)->format('H:i');
                $newStart = Carbon::parse($startTime)->format('H:i');
                $newEnd = Carbon::parse($endTime)->format('H:i');

                // Time overlap logic
                if (($newStart < $existingEnd) && ($newEnd > $existingStart)) {
                    return true; // Conflict found
                }
            }
        }

        return false; // No conflict
    }

    /**
     * ✅ HELPER: Check if time ranges overlap (TETAP ADA)
     */
    private static function timeOverlaps(string $start1, string $end1, string $start2, string $end2): bool
    {
        return ($start1 < $end2) && ($end1 > $start2);
    }

    /**
     * Scope untuk jadwal aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ SCOPE: Filter by specific day
     */
    public function scopeForDay($query, string $day)
    {
        return $query->whereJsonContains('days', strtolower($day));
    }

    /**
     * Scope untuk service tertentu
     */
    public function scopeForService($query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * ✅ SCOPE: Filter by doctor ID (NEW)
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * ✅ SCOPE: Filter by doctor name (KEEP untuk backward compatibility)
     */
    public function scopeForDoctor($query, string $doctorName)
    {
        return $query->where('doctor_name', $doctorName);
    }

    /**
     * ✅ SCOPE: Filter yang punya foto
     */
    public function scopeWithPhoto($query)
    {
        return $query->whereNotNull('foto');
    }

    /**
     * Get all unique doctor names
     */
    public static function getUniqueDoctorNames(): array
    {
        return self::distinct('doctor_name')
            ->pluck('doctor_name')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * ✅ GET: Schedule for specific doctor and day
     */
    public static function getScheduleForDoctorAndDay(string $doctorName, string $day): ?self
    {
        return self::where('doctor_name', $doctorName)
            ->whereJsonContains('days', strtolower($day))
            ->where('is_active', true)
            ->first();
    }

    /**
     * ✅ NEW: Get quota untuk tanggal tertentu
     */
    public function getQuotaForDate($date): ?WeeklyQuota
    {
        return $this->WeeklyQuotas()
            ->where('quota_date', $date)
            ->where('is_active', true)
            ->first();
    }

    /**
     * ✅ NEW: Check apakah ada quota untuk tanggal tertentu
     */
    public function hasQuotaForDate($date): bool
    {
        return $this->getQuotaForDate($date) !== null;
    }

    /**
     * ✅ NEW: Get atau create quota untuk tanggal tertentu
     */
    public function getOrCreateQuotaForDate($date, $defaultQuota = 20): WeeklyQuota
    {
        return WeeklyQuota::getOrCreateQuota($this->id, $date, $defaultQuota);
    }

    /**
     * ✅ NEW: Check apakah quota masih tersedia untuk tanggal tertentu
     */
    public function isQuotaAvailableForDate($date): bool
    {
        return WeeklyQuota::isQuotaAvailable($this->id, $date);
    }

    /**
     * ✅ NEW: Get quota summary untuk periode tertentu
     */
    public function getQuotaSummary($startDate, $endDate): array
    {
        $quotas = $this->WeeklyQuotas()
            ->whereBetween('quota_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->get();
        
        return [
            'total_days' => $quotas->count(),
            'total_quota' => $quotas->sum('total_quota'),
            'total_used' => $quotas->sum('used_quota'),
            'total_available' => $quotas->sum('available_quota'),
            'average_usage' => $quotas->count() > 0 ? $quotas->avg('usage_percentage') : 0,
            'full_days' => $quotas->filter->isQuotaFull()->count(),
            'available_days' => $quotas->filter(fn($q) => $q->available_quota > 0)->count(),
        ];
    }

    /**
     * ✅ NEW: Scope untuk yang punya quota aktif
     */
    public function scopeWithActiveQuota($query, $date = null)
    {
        $date = $date ?? today();
        
        return $query->whereHas('WeeklyQuotas', function ($q) use ($date) {
            $q->where('quota_date', $date)
              ->where('is_active', true);
        });
    }

    /**
     * ✅ NEW: Scope untuk yang quota masih tersedia
     */
    public function scopeWithAvailableQuota($query, $date = null)
    {
        $date = $date ?? today();
        
        return $query->whereHas('WeeklyQuotas', function ($q) use ($date) {
            $q->where('quota_date', $date)
              ->where('is_active', true)
              ->whereRaw('used_quota < total_quota');
        });
    }

    /**
     * ✅ NEW: Get formatted quota info
     */
    public function getQuotaInfoForDate($date): array
    {
        $quota = $this->getQuotaForDate($date);
        
        if (!$quota) {
            return [
                'has_quota' => false,
                'message' => 'Belum ada kuota untuk tanggal ini',
                'can_book' => true, // Akan dibuat otomatis
            ];
        }
        
        return [
            'has_quota' => true,
            'quota' => $quota,
            'formatted' => $quota->formatted_quota,
            'percentage' => $quota->usage_percentage,
            'status' => $quota->status_label,
            'available' => $quota->available_quota,
            'can_book' => !$quota->isQuotaFull(),
            'message' => $quota->isQuotaFull() 
                ? "Kuota penuh ({$quota->formatted_quota})"
                : "Tersedia {$quota->available_quota} dari {$quota->total_quota} kuota",
        ];
    }

    /**
     * ✅ BOOT: Handle foto deletion saat record dihapus
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($schedule) {
            // Hapus foto dari storage saat record dihapus
            if ($schedule->foto && Storage::disk('public')->exists($schedule->foto)) {
                Storage::disk('public')->delete($schedule->foto);
            }
        });

        static::updating(function ($schedule) {
            // Hapus foto lama jika diganti dengan foto baru
            if ($schedule->isDirty('foto')) {
                $originalFoto = $schedule->getOriginal('foto');
                if ($originalFoto && Storage::disk('public')->exists($originalFoto)) {
                    Storage::disk('public')->delete($originalFoto);
                }
            }
        });
    }
}