<?php
// File: app/Console/Commands/UpdateOverdueQueues.php - UPDATE untuk session support

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Queue;
use App\Models\DoctorSchedule;
use App\Services\QueueService;
use Carbon\Carbon;

class UpdateOverdueQueues extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:update-overdue {--date= : Specific date to update (Y-m-d format)}';

    /**
     * The console command description.
     */
    protected $description = 'Update overdue queues with session support (session-based and non-session)';

    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        parent::__construct();
        $this->queueService = $queueService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        
        $this->info("🕐 Checking for overdue queues on {$targetDate->format('Y-m-d')}...");
        
        // ✅ NEW: Cari antrian overdue berdasarkan session dan non-session
        $overdueQueues = Queue::where('status', 'waiting')
            ->whereDate('tanggal_antrian', $targetDate)
            ->where('estimated_call_time', '<', now())
            ->get();

        if ($overdueQueues->isEmpty()) {
            $this->info('✅ No overdue queues found');
            return 0;
        }

        $this->info("🔍 Found {$overdueQueues->count()} overdue queues on {$targetDate->format('d F Y')}");

        // ✅ NEW: Group by session type
        $sessionBasedQueues = $overdueQueues->where('doctor_id', '!=', null);
        $nonSessionQueues = $overdueQueues->where('doctor_id', null);

        $updatedCount = 0;

        // ✅ UPDATE: Session-based queues (per dokter)
        if ($sessionBasedQueues->isNotEmpty()) {
            $this->info("📋 Processing session-based queues...");
            
            $sessionGroups = $sessionBasedQueues->groupBy('doctor_id');
            
            foreach ($sessionGroups as $doctorId => $queues) {
                $doctor = DoctorSchedule::find($doctorId);
                $doctorName = $doctor ? $doctor->doctor_name : "Doctor ID {$doctorId}";
                
                $this->line("  👨‍⚕️ Updating session for {$doctorName} ({$queues->count()} queues)");
                
                $sessionUpdated = $this->updateDoctorSession($doctorId, $targetDate, $queues);
                $updatedCount += $sessionUpdated;
                
                $this->line("     ✅ Updated {$sessionUpdated} queues");
            }
        }

        // ✅ UPDATE: Non-session queues (sistem lama)
        if ($nonSessionQueues->isNotEmpty()) {
            $this->info("📋 Processing non-session queues...");
            
            foreach ($nonSessionQueues as $queue) {
                $newExtraDelay = $queue->extra_delay_minutes + 5;
                $newEstimation = now()->addMinutes(5);
                
                $queue->update([
                    'estimated_call_time' => $newEstimation,
                    'extra_delay_minutes' => $newExtraDelay
                ]);
                
                $updatedCount++;
            }
            
            $this->line("  ✅ Updated {$nonSessionQueues->count()} non-session queues");
        }

        if ($updatedCount > 0) {
            $this->info("✅ Total updated: {$updatedCount} queues on {$targetDate->format('d F Y')}");
            $this->showDetailedBreakdown($targetDate);
        } else {
            $this->warn("⚠️ No queues were updated on {$targetDate->format('d F Y')}");
        }
        
        return 0;
    }

    /**
     * ✅ NEW: Update session dokter tertentu
     */
    private function updateDoctorSession($doctorId, $targetDate, $sessionQueues)
    {
        $doctor = DoctorSchedule::find($doctorId);
        if (!$doctor) {
            return 0;
        }

        $updatedCount = 0;
        $sessionStartTime = $doctor->start_time;

        // Update semua antrian dalam session ini
        $allSessionQueues = Queue::where('doctor_id', $doctorId)
            ->where('status', 'waiting')
            ->whereDate('tanggal_antrian', $targetDate)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($allSessionQueues as $index => $queue) {
            $newExtraDelay = $queue->extra_delay_minutes + 5;
            $queuePosition = $index + 1;
            $baseMinutes = $queuePosition * 15;
            $totalMinutes = $baseMinutes + $newExtraDelay;
            
            if (Carbon::parse($targetDate)->isToday()) {
                $sessionStartDateTime = Carbon::parse($targetDate)->setTimeFromTimeString($sessionStartTime->format('H:i'));
                $startTime = now()->max($sessionStartDateTime);
                $newEstimation = $startTime->addMinutes($totalMinutes - $baseMinutes + 5);
            } else {
                $startTime = Carbon::parse($targetDate)->setTimeFromTimeString($sessionStartTime->format('H:i'));
                $newEstimation = $startTime->addMinutes($totalMinutes);
            }
            
            $queue->update([
                'estimated_call_time' => $newEstimation,
                'extra_delay_minutes' => $newExtraDelay
            ]);
            
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * ✅ UPDATED: Show detailed breakdown dengan session info
     */
    private function showDetailedBreakdown($targetDate): void
    {
        $this->newLine();
        $this->info("📊 Detailed Breakdown for {$targetDate->format('d F Y')}:");
        
        // Session-based breakdown
        $sessionBreakdown = Queue::with(['doctorSchedule', 'service'])
            ->where('status', 'waiting')
            ->whereDate('tanggal_antrian', $targetDate)
            ->whereNotNull('doctor_id')
            ->get()
            ->groupBy('doctor_id')
            ->map(function ($queues, $doctorId) {
                $firstQueue = $queues->first();
                $doctor = $firstQueue->doctorSchedule;
                
                return [
                    'doctor_name' => $doctor ? $doctor->doctor_name : "Doctor ID {$doctorId}",
                    'service' => $firstQueue->service->name ?? 'Unknown',
                    'session_time' => $doctor ? $doctor->start_time->format('H:i') . '-' . $doctor->end_time->format('H:i') : 'Unknown',
                    'total_queues' => $queues->count(),
                    'avg_delay' => round($queues->avg('extra_delay_minutes'), 1),
                    'max_delay' => $queues->max('extra_delay_minutes'),
                    'overdue_count' => $queues->where('estimated_call_time', '<', now())->count(),
                ];
            });

        if ($sessionBreakdown->isNotEmpty()) {
            $this->line("🏥 Session-based Queues:");
            $headers = ['Doctor', 'Service', 'Session Time', 'Total', 'Avg Delay', 'Max Delay', 'Overdue'];
            $rows = [];
            
            foreach ($sessionBreakdown as $data) {
                $rows[] = [
                    $data['doctor_name'],
                    $data['service'],
                    $data['session_time'],
                    $data['total_queues'],
                    $data['avg_delay'] . ' min',
                    $data['max_delay'] . ' min',
                    $data['overdue_count'],
                ];
            }
            
            $this->table($headers, $rows);
        }

        // Non-session breakdown
        $nonSessionCount = Queue::where('status', 'waiting')
            ->whereDate('tanggal_antrian', $targetDate)
            ->whereNull('doctor_id')
            ->count();

        if ($nonSessionCount > 0) {
            $this->line("📋 Non-session Queues: {$nonSessionCount} queues");
        }
    }
}