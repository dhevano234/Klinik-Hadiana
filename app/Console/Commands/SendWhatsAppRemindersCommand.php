<?php
// File: app/Console/Commands/SendWhatsAppRemindersCommand.php
// UPDATED: 15 minutes before reminder

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Queue;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendWhatsAppRemindersCommand extends Command
{
    protected $signature = 'whatsapp:send-reminders {--dry-run : Only show what would be sent}';
    protected $description = 'Send WhatsApp reminders 10 minutes before estimated call time';

    public function handle(WhatsAppService $whatsAppService)
    {
        $isDryRun = $this->option('dry-run');
        $now = Carbon::now();
        
        $this->info("🕐 Current time: {$now->format('Y-m-d H:i:s')}");
        
        // ✅ UPDATED LOGIC: Cari antrian yang 10 menit lagi akan dipanggil
        // Jika sekarang 02:07, cari antrian estimasi 02:16-02:18 (10±1 menit dari sekarang)
        $tenMinutesFromNow = $now->copy()->addMinutes(10);
        $toleranceStart = $tenMinutesFromNow->copy()->subMinutes(1); // 9 menit dari sekarang
        $toleranceEnd = $tenMinutesFromNow->copy()->addMinutes(1);   // 11 menit dari sekarang
        
        $this->info("🎯 Looking for queues with estimated call time between:");
        $this->info("   Start: {$toleranceStart->format('H:i:s')} (9 minutes from now)");
        $this->info("   End: {$toleranceEnd->format('H:i:s')} (11 minutes from now)");
        
        // Cari antrian yang perlu dikirim reminder
        $queues = Queue::where('status', 'waiting')
            ->whereNull('whatsapp_reminder_sent_at')
            ->whereBetween('estimated_call_time', [$toleranceStart, $toleranceEnd])
            ->whereDate('tanggal_antrian', $now->toDateString()) // Hanya untuk hari ini
            ->with('user')
            ->get();
            
        if ($queues->isEmpty()) {
            $this->info("📭 No queues found that need WhatsApp reminders at this time.");
            Log::info("WhatsApp reminder check completed - no queues found for reminder window");
            return;
        }
        
        $this->info("📋 Found {$queues->count()} queue(s) that need WhatsApp reminders:");
        
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($queues as $queue) {
            $this->info("----------------------------------------");
            $this->info("Queue: {$queue->number}");
            $this->info("Patient: {$queue->user->name}");
            $this->info("Estimated call: {$queue->estimated_call_time->format('H:i:s')}");
            $this->info("Phone: " . ($queue->user->phone ?? 'NULL'));
            
            // Skip jika user tidak punya nomor telepon
            if (!$queue->user || !$queue->user->phone) {
                $this->warn("⚠️  Skipped - No phone number");
                $skipped++;
                continue;
            }
            
            if ($isDryRun) {
                $this->info("🧪 DRY RUN - Would send WhatsApp to: {$queue->user->phone}");
                $sent++;
                continue;
            }
            
            try {
                $this->info("📱 Sending WhatsApp reminder...");
                
                $success = $whatsAppService->sendReminder($queue);
                
                if ($success) {
                    // Update database bahwa reminder sudah dikirim
                    $queue->update(['whatsapp_reminder_sent_at' => $now]);
                    
                    $this->info("✅ WhatsApp reminder sent for Queue {$queue->number} (10 minutes before estimated time)");
                    Log::info("✅ WhatsApp reminder sent successfully", [
                        'queue_id' => $queue->id,
                        'queue_number' => $queue->number,
                        'user_name' => $queue->user->name,
                        'phone' => $queue->user->phone,
                        'estimated_call_time' => $queue->estimated_call_time,
                        'sent_at' => $now
                    ]);
                    
                    $sent++;
                } else {
                    $this->error("❌ Failed to send WhatsApp for Queue {$queue->number}");
                    Log::error("❌ Failed to send WhatsApp reminder", [
                        'queue_id' => $queue->id,
                        'queue_number' => $queue->number,
                        'user_name' => $queue->user->name,
                        'phone' => $queue->user->phone,
                        'error' => 'WhatsApp service returned false'
                    ]);
                    
                    $failed++;
                }
                
                // Delay 1 detik antar pengiriman untuk menghindari spam
                sleep(1);
                
            } catch (\Exception $e) {
                $this->error("❌ Exception sending WhatsApp for Queue {$queue->number}: {$e->getMessage()}");
                Log::error("❌ Exception sending WhatsApp reminder", [
                    'queue_id' => $queue->id,
                    'queue_number' => $queue->number,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $failed++;
            }
        }
        
        $this->info("========================================");
        $this->info("📊 SUMMARY:");
        $this->info("✅ Sent: {$sent}");
        $this->info("⚠️  Skipped: {$skipped}");
        $this->info("❌ Failed: {$failed}");
        $this->info("📱 Total processed: " . ($sent + $skipped + $failed));
        
        Log::info("WhatsApp reminder batch completed", [
            'sent' => $sent,
            'skipped' => $skipped, 
            'failed' => $failed,
            'total' => $sent + $skipped + $failed,
            'executed_at' => $now
        ]);
        
        return $sent > 0 ? 0 : 1;
    }
}