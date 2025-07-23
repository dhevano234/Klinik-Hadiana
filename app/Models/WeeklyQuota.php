<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WeeklyQuota extends Model
{
    protected $fillable = [
        'doctor_schedule_id',
        'day_of_week',
        'total_quota',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_quota' => 'integer',
    ];

    protected $appends = [
        'day_name_indonesia',
        'used_quota_today',
        'available_quota_today',
        'usage_percentage_today',
        'status_label_today',
    ];

    // ✅ Relationships
    public function doctorSchedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class);
    }

    public function queues()
    {
        return $this->hasMany(Queue::class, 'doctor_id', 'doctor_schedule_id')
            ->whereDate('tanggal_antrian', today());
    }

    // ✅ Accessors (untuk hari ini saja - backward compatibility)
    public function getDayNameIndonesiaAttribute(): string
    {
        $days = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        return $days[$this->day_of_week] ?? $this->day_of_week;
    }

    public function getUsedQuotaTodayAttribute(): int
    {
        return $this->getUsedQuotaForDate(today());
    }

    public function getAvailableQuotaTodayAttribute(): int
    {
        return $this->getAvailableQuotaForDate(today());
    }

    public function getUsagePercentageTodayAttribute(): float
    {
        return $this->getUsagePercentageForDate(today());
    }

    public function getStatusLabelTodayAttribute(): string
    {
        return $this->getStatusLabelForDate(today());
    }

    public function getFormattedQuotaTodayAttribute(): string
    {
        return $this->getFormattedQuotaForDate(today());
    }

    // ✅ NEW: Methods untuk tanggal spesifik (FIXED untuk hari besok dst)
    public function getUsedQuotaForDate($date): int
    {
        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($dateCarbon->format('l'));
        
        // ✅ FIXED: Hanya cek hari yang sama, tidak peduli hari ini atau besok
        if ($dayOfWeek !== $this->day_of_week) {
            return 0;
        }

        return Queue::where('doctor_id', $this->doctor_schedule_id)
            ->whereDate('tanggal_antrian', $date)
            ->whereIn('status', ['waiting', 'serving', 'finished'])
            ->count();
    }

    public function getAvailableQuotaForDate($date): int
    {
        return max(0, $this->total_quota - $this->getUsedQuotaForDate($date));
    }

    public function getUsagePercentageForDate($date): float
    {
        if ($this->total_quota == 0) return 0;
        return round(($this->getUsedQuotaForDate($date) / $this->total_quota) * 100, 1);
    }

    public function getStatusLabelForDate($date): string
    {
        $percentage = $this->getUsagePercentageForDate($date);
        
        if ($percentage >= 100) return 'Penuh';
        if ($percentage >= 80) return 'Hampir Penuh';
        if ($percentage >= 50) return 'Sedang';
        if ($percentage > 0) return 'Tersedia';
        return 'Kosong';
    }

    public function getFormattedQuotaForDate($date): string
    {
        return "{$this->getUsedQuotaForDate($date)}/{$this->total_quota}";
    }

    // ✅ Methods
    public function isQuotaFullToday(): bool
    {
        return $this->isQuotaFullForDate(today());
    }

    public function isQuotaFullForDate($date): bool
    {
        return $this->getUsedQuotaForDate($date) >= $this->total_quota;
    }

    public function canAddQueueToday(): bool
    {
        return $this->canAddQueueForDate(today());
    }

    public function canAddQueueForDate($date): bool
    {
        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($dateCarbon->format('l'));
        
        return $this->is_active 
            && $dayOfWeek === $this->day_of_week 
            && !$this->isQuotaFullForDate($date);
    }

    // ✅ NEW: Methods untuk nearly full
    public function isQuotaNearlyFullForDate($date): bool
    {
        return $this->getUsagePercentageForDate($date) >= 80 
            && !$this->isQuotaFullForDate($date);
    }

    public function getStatusColor(): string
    {
        return $this->getStatusColorForDate(today());
    }

    public function getStatusColorForDate($date): string
    {
        $percentage = $this->getUsagePercentageForDate($date);
        
        if ($percentage >= 100) return 'danger';
        if ($percentage >= 80) return 'warning';
        if ($percentage >= 50) return 'info';
        if ($percentage > 0) return 'success';
        return 'gray';
    }

    // ✅ Static Methods
    public static function getOrCreateQuota($doctorScheduleId, $dayOfWeek, $defaultQuota = 20): self
    {
        return self::firstOrCreate(
            [
                'doctor_schedule_id' => $doctorScheduleId,
                'day_of_week' => $dayOfWeek,
            ],
            [
                'total_quota' => $defaultQuota,
                'is_active' => true,
            ]
        );
    }

    public static function getQuotaForDoctorAndDay($doctorScheduleId, $dayOfWeek): ?self
    {
        return self::where('doctor_schedule_id', $doctorScheduleId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }

    public static function getTodayQuotas(): array
    {
        $today = today();
        $todayDayOfWeek = strtolower($today->format('l'));
        
        $quotas = self::with('doctorSchedule.service')
            ->where('day_of_week', $todayDayOfWeek)
            ->where('is_active', true)
            ->get();

        return $quotas->map(function ($quota) {
            return [
                'id' => $quota->id,
                'doctor_name' => $quota->doctorSchedule->doctor_name,
                'service_name' => $quota->doctorSchedule->service->name ?? 'Unknown',
                'total_quota' => $quota->total_quota,
                'used_quota' => $quota->used_quota_today,
                'available_quota' => $quota->available_quota_today,
                'usage_percentage' => $quota->usage_percentage_today,
                'status_label' => $quota->status_label_today,
                'formatted_quota' => $quota->formatted_quota_today,
                'is_full' => $quota->isQuotaFullToday(),
                'can_add_queue' => $quota->canAddQueueToday(),
                'time_range' => $quota->doctorSchedule->time_range ?? 'Unknown',
                'status_color' => $quota->getStatusColor(),
            ];
        })->toArray();
    }

    // ✅ NEW: Get quotas untuk tanggal spesifik
    public static function getQuotasForDate($date): array
    {
        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($dateCarbon->format('l'));
        
        $quotas = self::with('doctorSchedule.service')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        return $quotas->map(function ($quota) use ($date) {
            return [
                'id' => $quota->id,
                'doctor_name' => $quota->doctorSchedule->doctor_name,
                'service_name' => $quota->doctorSchedule->service->name ?? 'Unknown',
                'total_quota' => $quota->total_quota,
                'used_quota' => $quota->getUsedQuotaForDate($date),
                'available_quota' => $quota->getAvailableQuotaForDate($date),
                'usage_percentage' => $quota->getUsagePercentageForDate($date),
                'status_label' => $quota->getStatusLabelForDate($date),
                'formatted_quota' => $quota->getFormattedQuotaForDate($date),
                'is_full' => $quota->isQuotaFullForDate($date),
                'can_add_queue' => $quota->canAddQueueForDate($date),
                'time_range' => $quota->doctorSchedule->time_range ?? 'Unknown',
                'status_color' => $quota->getStatusColorForDate($date),
            ];
        })->toArray();
    }

    public static function bulkCreateQuotasForDoctor($doctorScheduleId, $quotaAmount = 20): array
    {
        $doctor = DoctorSchedule::find($doctorScheduleId);
        if (!$doctor) {
            return ['created' => 0, 'existing' => 0, 'quotas' => []];
        }

        $created = 0;
        $existing = 0;
        $quotas = [];

        foreach ($doctor->days as $day) {
            $quota = self::where('doctor_schedule_id', $doctorScheduleId)
                ->where('day_of_week', $day)
                ->first();

            if (!$quota) {
                $quota = self::create([
                    'doctor_schedule_id' => $doctorScheduleId,
                    'day_of_week' => $day,
                    'total_quota' => $quotaAmount,
                    'is_active' => true,
                ]);
                $created++;
            } else {
                $existing++;
            }

            $quotas[] = $quota;
        }

        return [
            'created' => $created,
            'existing' => $existing,
            'quotas' => $quotas,
        ];
    }

    public static function bulkCreateQuotasForAllDoctors($quotaAmount = 20): array
    {
        $doctors = DoctorSchedule::where('is_active', true)->get();
        $totalCreated = 0;
        $totalExisting = 0;
        $results = [];

        foreach ($doctors as $doctor) {
            $result = self::bulkCreateQuotasForDoctor($doctor->id, $quotaAmount);
            $totalCreated += $result['created'];
            $totalExisting += $result['existing'];
            
            $results[] = [
                'doctor_name' => $doctor->doctor_name,
                'service_name' => $doctor->service->name ?? 'Unknown',
                'created' => $result['created'],
                'existing' => $result['existing'],
            ];
        }

        return [
            'total_created' => $totalCreated,
            'total_existing' => $totalExisting,
            'results' => $results,
        ];
    }

    // ✅ Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_schedule_id', $doctorId);
    }

    public function scopeToday($query)
    {
        $todayDayOfWeek = strtolower(today()->format('l'));
        return $query->where('day_of_week', $todayDayOfWeek);
    }

    public function scopeForDate($query, $date)
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));
        return $query->where('day_of_week', $dayOfWeek);
    }
}