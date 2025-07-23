<?php
// File: app/Models/Counter.php - FINAL: Complete Model with Today Only Restriction

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'service_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ===== RELATIONSHIPS =====
    
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function activeQueue(): HasOne
    {
        return $this->hasOne(Queue::class)
            ->where('status', 'serving')
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->latest();
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function todayQueues(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->whereDate('tanggal_antrian', today()); // ✅ HANYA HARI INI
    }

    // ===== ACCESSORS - DIBATASI HANYA HARI INI =====

    /**
     * ✅ FINAL ACCESSOR: Cek apakah ada antrian berikutnya untuk hari ini saja
     */
    public function getHasNextQueueAttribute()
    {
        return Queue::where('status', 'waiting')
            ->where('service_id', $this->service_id)
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->where(function ($query) {
                $query->whereNull('counter_id')
                      ->orWhere('counter_id', $this->id);
            })
            ->exists();
    }

    /**
     * ✅ FINAL ACCESSOR: Hitung jumlah antrian menunggu untuk hari ini
     */
    public function getWaitingQueueCountAttribute()
    {
        return Queue::where('status', 'waiting')
            ->where('service_id', $this->service_id)
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->where(function ($query) {
                $query->whereNull('counter_id')
                      ->orWhere('counter_id', $this->id);
            })
            ->count();
    }

    /**
     * ✅ FINAL ACCESSOR: Get next queue untuk display (hanya hari ini)
     */
    public function getNextQueueAttribute()
    {
        return Queue::where('status', 'waiting')
            ->where('service_id', $this->service_id)
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->where(function ($query) {
                $query->whereNull('counter_id')
                      ->orWhere('counter_id', $this->id);
            })
            ->orderByRaw('doctor_id IS NULL ASC')
            ->orderBy('id')
            ->first();
    }

    /**
     * ✅ NEW ACCESSOR: Get served queues count untuk hari ini
     */
    public function getServedQueueCountAttribute()
    {
        return Queue::where('counter_id', $this->id)
            ->where('status', 'finished')
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->count();
    }

    /**
     * ✅ NEW ACCESSOR: Get total queues count untuk hari ini
     */
    public function getTotalQueueCountAttribute()
    {
        return Queue::where('service_id', $this->service_id)
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->count();
    }

    /**
     * ✅ NEW ACCESSOR: Get counter status untuk hari ini
     */
    public function getStatusLabelAttribute()
    {
        if (!$this->is_active) {
            return 'Tidak Aktif';
        }

        if ($this->activeQueue) {
            return 'Melayani: ' . $this->activeQueue->number;
        }

        if ($this->has_next_queue) {
            return 'Siap Panggil';
        }

        return 'Menunggu';
    }

    /**
     * ✅ NEW ACCESSOR: Get counter performance untuk hari ini
     */
    public function getPerformanceAttribute()
    {
        $total = $this->total_queue_count;
        $served = $this->served_queue_count;
        $waiting = $this->waiting_queue_count;

        return [
            'total' => $total,
            'served' => $served,
            'waiting' => $waiting,
            'completion_rate' => $total > 0 ? round(($served / $total) * 100, 1) : 0,
            'status' => $this->status_label,
            'is_busy' => $this->activeQueue !== null,
        ];
    }

    // ===== SCOPES =====

    /**
     * ✅ SCOPE: Active counters only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ SCOPE: Counters with waiting queues today
     */
    public function scopeWithWaitingQueues($query)
    {
        return $query->whereHas('service.queues', function ($subQuery) {
            $subQuery->where('status', 'waiting')
                     ->whereDate('tanggal_antrian', today()); // ✅ HANYA HARI INI
        });
    }

    /**
     * ✅ SCOPE: Counters by service
     */
    public function scopeByService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    // ===== METHODS =====

    /**
     * ✅ METHOD: Check if counter can call next queue (hanya hari ini)
     */
    public function canCallNextQueue()
    {
        return $this->is_active && 
               $this->has_next_queue && 
               !$this->activeQueue;
    }

    /**
     * ✅ METHOD: Get waiting queues untuk hari ini
     */
    public function getWaitingQueues()
    {
        return Queue::where('status', 'waiting')
            ->where('service_id', $this->service_id)
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->where(function ($query) {
                $query->whereNull('counter_id')
                      ->orWhere('counter_id', $this->id);
            })
            ->orderByRaw('doctor_id IS NULL ASC')
            ->orderBy('id')
            ->get();
    }

    /**
     * ✅ METHOD: Get today's statistics
     */
    public function getTodayStatistics()
    {
        $queues = Queue::where('service_id', $this->service_id)
            ->whereDate('tanggal_antrian', today()) // ✅ HANYA HARI INI
            ->get();

        return [
            'total' => $queues->count(),
            'waiting' => $queues->where('status', 'waiting')->count(),
            'serving' => $queues->where('status', 'serving')->count(),
            'finished' => $queues->where('status', 'finished')->count(),
            'canceled' => $queues->where('status', 'canceled')->count(),
            'served_by_counter' => $queues->where('counter_id', $this->id)->where('status', 'finished')->count(),
        ];
    }

    /**
     * ✅ METHOD: Reset counter (untuk testing/maintenance)
     */
    public function resetCounter()
    {
        // Batalkan antrian yang sedang dilayani
        if ($this->activeQueue) {
            $this->activeQueue->update([
                'status' => 'waiting',
                'counter_id' => null,
                'called_at' => null,
            ]);
        }

        return true;
    }

    /**
     * ✅ METHOD: Activate/Deactivate counter
     */
    public function toggleStatus()
    {
        $this->update(['is_active' => !$this->is_active]);

        // Jika dinonaktifkan dan ada antrian aktif, kembalikan ke waiting
        if (!$this->is_active && $this->activeQueue) {
            $this->resetCounter();
        }

        return $this->is_active;
    }
}