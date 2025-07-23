<?php
// File: app/Console/Commands/UpdateQueueEstimations.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueueService;
use App\Models\Queue;
use Illuminate\Support\Facades\Log; // ✅ TAMBAH INI - Import Log facade

class UpdateQueueEstimations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:update-estimations {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update queue estimations and add extra delay for overdue queues';

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
        $this->info('🕐 Updating queue estimations...');
        
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
        }

        // Get semua antrian yang sudah lewat estimasi tapi belum dipanggil
        $overdueQueues = Queue::where('status', 'waiting')
            ->whereDate('created_at', today())
            ->where('estimated_call_time', '<', now())
            ->get();

        if ($overdueQueues->isEmpty()) {
            $this->info('✅ No overdue queues found');
            return 0;
        }

        $this->info("🔍 Found {$overdueQueues->count()} overdue queues");

        $table = [];
        $updatedCount = 0;

        foreach ($overdueQueues as $queue) {
            $oldEstimation = $queue->estimated_call_time;
            $oldExtraDelay = $queue->extra_delay_minutes;
            
            $newExtraDelay = $oldExtraDelay + 5;
            $newEstimation = now()->addMinutes(5);
            
            $table[] = [
                $queue->number,
                $queue->service->name ?? 'N/A',
                $queue->user->name ?? 'Walk-in',
                $oldEstimation->format('H:i'),
                $oldExtraDelay . 'min',
                $newEstimation->format('H:i'),
                $newExtraDelay . 'min',
                $isDryRun ? 'Would update' : 'Updated'
            ];

            if (!$isDryRun) {
                $queue->update([
                    'estimated_call_time' => $newEstimation,
                    'extra_delay_minutes' => $newExtraDelay
                ]);
                $updatedCount++;
            }
        }

        // Display results table
        $this->table([
            'Queue #',
            'Service',
            'Patient',
            'Old Est.',
            'Old Delay',
            'New Est.',
            'New Delay',
            'Status'
        ], $table);

        if ($isDryRun) {
            $this->warn("🧪 DRY RUN: Would update {$overdueQueues->count()} queues");
        } else {
            $this->info("✅ Successfully updated {$updatedCount} queue estimations");
            
            // ✅ OPTION 1: Dengan Log facade (sudah diimport)
            $this->logActivity($updatedCount);
        }

        return 0;
    }

    // ✅ OPTION 1: Menggunakan Log facade
    private function logActivity(int $updatedCount)
    {
        Log::info('Queue estimations updated', [
            'updated_count' => $updatedCount,
            'timestamp' => now(),
            'command' => 'queue:update-estimations'
        ]);
    }
}
