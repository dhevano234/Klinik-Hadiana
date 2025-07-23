<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Queue;

class WhatsAppService
{
    private $token;
    private $baseUrl = 'https://api.fonnte.com';
    
    public function __construct()
    {
        $this->token = config('services.fonnte.token');
    }
    
    public function sendReminder(Queue $queue): bool
    {
        try {
            if (!$this->token) {
                Log::error("Fonnte token not configured");
                return false;
            }
            
            $phoneNumber = $this->formatPhoneNumber($queue->user->phone);
            $message = $this->buildReminderMessage($queue);
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->post($this->baseUrl . '/send', [
                    'target' => $phoneNumber,
                    'message' => $message,
                ]);
            
            $responseData = $response->json();
            
            if ($response->successful() && ($responseData['status'] ?? false)) {
                Log::info("WhatsApp reminder sent", [
                    'queue_id' => $queue->id,
                    'phone' => $phoneNumber,
                    'queue_number' => $queue->number
                ]);
                
                $queue->update(['whatsapp_reminder_sent_at' => now()]);
                return true;
            }
            
            Log::error("Fonnte API failed", [
                'queue_id' => $queue->id,
                'response' => $responseData,
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("WhatsApp service error", [
                'queue_id' => $queue->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }
        
        if (!str_starts_with($phone, '62')) {
            return '62' . $phone;
        }
        
        return $phone;
    }
    
    private function buildReminderMessage(Queue $queue): string
    {
        $estimatedTime = $queue->estimated_call_time_formatted ?? 'segera';
        $serviceName = $queue->service->name ?? 'layanan';
        
        return "🏥 *PENGINGAT ANTRIAN KLINIK*\n\n" .
               "Halo *{$queue->user->name}*,\n\n" .
               "Antrian Anda akan dipanggil dalam waktu ±10 menit!\n\n" .
               "📋 *Detail Antrian:*\n" .
               "• Nomor: *{$queue->number}*\n" .
               "• Layanan: *{$serviceName}*\n" .
               "• Estimasi: *{$estimatedTime} WIB*\n" .
               "• Tanggal: " . $queue->tanggal_antrian->format('d F Y') . "\n\n" .
               "⚠️ *Mohon bersiap dan menuju area tunggu.*\n\n" .
               "Terima kasih! 🙏";
    }
}