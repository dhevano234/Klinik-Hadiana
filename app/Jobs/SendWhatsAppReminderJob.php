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
    public $backoff = [10, 30, 60]; 
    
    public function __construct(
        public Queue $queue,
        public Carbon $scheduledFor
    ) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        
        $this->queue->refresh();
        
        
        if ($this->queue->status !== 'waiting') {
            return;
        }
        
        
        if ($this->queue->whatsapp_reminder_sent_at) {
            return;
        }
        
        
        if ($this->queue->estimated_call_time) {
            $timeDiff = abs($this->scheduledFor->diffInMinutes($this->queue->estimated_call_time->subMinutes(5)));
            if ($timeDiff > 10) {
                return;
            }
        }
        
        
        $success = $whatsAppService->sendReminder($this->queue);
        
        if ($success) {
            
            $this->queue->update(['whatsapp_reminder_sent_at' => now()]);
        } else {
            
            throw new \Exception("WhatsApp sending failed for queue {$this->queue->id}"); 
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        
        if (app()->environment('local', 'testing')) {
            Log::error("WhatsApp reminder failed for queue {$this->queue->id}: " . $exception->getMessage());
        }
        
        
        try {
            $this->queue->update([
                'whatsapp_reminder_failed_at' => now(),
                'whatsapp_error_message' => $exception->getMessage()
            ]);
        } catch (\Exception $e) {
            
        }
    }
    
    public function displayName(): string
    {
        return "WhatsApp Reminder: {$this->queue->number}";
    }
}