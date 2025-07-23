<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Queue;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class SendWhatsAppReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Retry delay in seconds
    
    public function __construct(
        public Queue $queue,
        public Carbon $scheduledFor
    ) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        Log::info("WhatsApp reminder job started for queue {$this->queue->id}");
        
        // ✅ REFRESH: Ambil data terbaru dari database
        $this->queue->refresh();
        
        // ✅ VALIDASI: Cek apakah antrian masih valid
        if ($this->queue->status !== 'waiting') {
            Log::info("WhatsApp reminder cancelled - Queue {$this->queue->id} status changed to {$this->queue->status}");
            return;
        }
        
        if ($this->queue->whatsapp_reminder_sent_at) {
            Log::info("WhatsApp reminder already sent for queue {$this->queue->id}");
            return;
        }
        
        // ✅ VALIDASI: Cek estimasi waktu masih relevan (toleransi 10 menit)
        if ($this->queue->estimated_call_time) {
            $timeDiff = abs($this->scheduledFor->diffInMinutes($this->queue->estimated_call_time->subMinutes(5)));
            if ($timeDiff > 10) {
                Log::warning("WhatsApp reminder skipped - Estimated time changed significantly for queue {$this->queue->id}");
                return;
            }
        }
        
        // ✅ KIRIM WHATSAPP
        Log::info("Sending WhatsApp reminder to {$this->queue->user->name} for queue {$this->queue->id}");
        
        $success = $whatsAppService->sendReminder($this->queue);
        
        if ($success) {
            $this->queue->update(['whatsapp_reminder_sent_at' => now()]);
            Log::info("✅ WhatsApp reminder sent successfully for queue {$this->queue->id} to {$this->queue->user->phone}");
        } else {
            Log::error("❌ Failed to send WhatsApp reminder for queue {$this->queue->id}");
            throw new \Exception("WhatsApp sending failed"); // Will trigger retry
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error("WhatsApp reminder job failed permanently for queue {$this->queue->id}: " . $exception->getMessage());
        
        // Optional: Bisa mark di database bahwa reminder gagal
        try {
            $this->queue->update([
                'whatsapp_reminder_failed_at' => now(),
                'whatsapp_error_message' => $exception->getMessage()
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update queue failure status: " . $e->getMessage());
        }
    }
    
    /**
     * Get display name for monitoring
     */
    public function displayName(): string
    {
        return "WhatsApp Reminder for Queue {$this->queue->number} - {$this->queue->user->name}";
    }
}