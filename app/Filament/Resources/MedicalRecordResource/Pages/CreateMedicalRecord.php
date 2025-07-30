<?php


namespace App\Filament\Resources\MedicalRecordResource\Pages;

use App\Filament\Resources\MedicalRecordResource;
use App\Models\User;
use App\Models\Queue;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMedicalRecord extends CreateRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected static ?string $title = 'Buat Rekam Medis';

    
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        
        if (isset($data['user_id'])) {
            $user = User::find($data['user_id']);
            if (!$user || $user->role !== 'user') {
                throw new \Exception('Hanya pasien dengan role user yang dapat dibuatkan rekam medis.');
            }
        }
        
        
        unset($data['display_medical_record_number']);
        
        
        
        return $data;
    }

    
    public function mount(): void
    {
        parent::mount();
        
        
        $userId = request()->get('user_id');
        $queueNumber = request()->get('queue_number');
        $serviceName = request()->get('service');
        
        if ($userId) {
            $user = User::find($userId);
            
            
            if ($user && $user->role === 'user') {
                
                $queueWithComplaint = Queue::where('user_id', $userId)
                    ->whereNotNull('chief_complaint')
                    ->where('chief_complaint', '!=', '')
                    ->whereIn('status', ['waiting', 'serving']) 
                    ->whereDate('created_at', today()) 
                    ->latest('created_at')
                    ->first();
                
                
                $formData = [
                    'user_id' => $userId,
                    'display_medical_record_number' => $user->medical_record_number ?? 'Belum ada nomor rekam medis',
                ];
                
                
                if ($queueWithComplaint && $queueWithComplaint->chief_complaint) {
                    $formData['chief_complaint'] = $queueWithComplaint->chief_complaint;
                }
                
                $this->form->fill($formData);
                
                
                $notificationBody = "Auto-selected: {$user->name}";
                
                if ($user->medical_record_number) {
                    $notificationBody .= " | No. RM: {$user->medical_record_number}";
                } else {
                    $notificationBody .= " | Belum ada No. RM";
                }
                
                if ($queueNumber) {
                    $notificationBody .= " (Antrian: {$queueNumber})";
                }
                
                
                if ($queueWithComplaint && $queueWithComplaint->chief_complaint) {
                    $complainLimit = 100;
                    $shortComplaint = strlen($queueWithComplaint->chief_complaint) > $complainLimit 
                        ? substr($queueWithComplaint->chief_complaint, 0, $complainLimit) . '...'
                        : $queueWithComplaint->chief_complaint;
                    $notificationBody .= "\n📝 Keluhan dari antrian #{$queueWithComplaint->number}: \"{$shortComplaint}\"";
                    $notificationBody .= "\n⏰ Diambil: {$queueWithComplaint->created_at->format('d/m/Y H:i')}";
                } else {
                    
                    $anyQueue = Queue::where('user_id', $userId)
                        ->whereIn('status', ['waiting', 'serving'])
                        ->whereDate('created_at', today())
                        ->latest('created_at')
                        ->first();
                    
                    if ($anyQueue) {
                        $notificationBody .= "\n💬 Pasien punya antrian #{$anyQueue->number} tapi tidak mengisi keluhan";
                    } else {
                        $notificationBody .= "\n💬 Tidak ada antrian aktif hari ini";
                    }
                }
                
                Notification::make()
                    ->title('Pasien Dari Antrian')
                    ->body($notificationBody)
                    ->success()
                    ->duration(10000) 
                    ->send();
                    
                
                if ($queueNumber) {
                    static::$title = "Rekam Medis - Antrian {$queueNumber}";
                }
                
            } elseif ($user && $user->role !== 'user') {
                
                Notification::make()
                    ->title('Peringatan')
                    ->body("User {$user->name} bukan pasien (role: {$user->role}). Silakan pilih pasien yang valid.")
                    ->warning()
                    ->duration(8000)
                    ->send();
            } else {
                
                Notification::make()
                    ->title('Error')
                    ->body("User dengan ID {$userId} tidak ditemukan.")
                    ->danger()
                    ->duration(5000)
                    ->send();
            }
        } elseif ($queueNumber) {
            
            $queue = Queue::where('number', $queueNumber)
                ->whereDate('created_at', today())
                ->with('user')
                ->first();
                
            if ($queue && $queue->user) {
                $user = $queue->user;
                
                $formData = [
                    'user_id' => $user->id,
                    'display_medical_record_number' => $user->medical_record_number ?? 'Belum ada nomor rekam medis',
                ];
                
                
                if ($queue->chief_complaint) {
                    $formData['chief_complaint'] = $queue->chief_complaint;
                }
                
                $this->form->fill($formData);
                
                $notificationBody = "Antrian {$queueNumber}: {$user->name}";
                if ($user->medical_record_number) {
                    $notificationBody .= " | No. RM: {$user->medical_record_number}";
                }
                
                if ($queue->chief_complaint) {
                    $shortComplaint = strlen($queue->chief_complaint) > 100 
                        ? substr($queue->chief_complaint, 0, 100) . '...'
                        : $queue->chief_complaint;
                    $notificationBody .= "\n📝 Keluhan: \"{$shortComplaint}\"";
                    $notificationBody .= "\n⏰ Status: {$queue->status_label}";
                } else {
                    $notificationBody .= "\n💬 Tidak ada keluhan dari antrian";
                }
                
                Notification::make()
                    ->title('Data dari Antrian')
                    ->body($notificationBody)
                    ->success()
                    ->duration(10000)
                    ->send();
            }
        }
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rekam medis berhasil dibuat';
    }
    
    // Auto-finish queue setelah rekam medis dibuat
    protected function afterCreate(): void
    {
        // Optional: Auto-finish queue jika ada parameter queue
        $queueNumber = request()->get('queue_number');
        
        if ($queueNumber) {
            // Find and finish the queue
            $queue = Queue::where('number', $queueNumber)
                ->whereDate('created_at', today())
                ->first();
                
            if ($queue && in_array($queue->status, ['waiting', 'serving'])) {
                $queue->update([
                    'status' => 'finished',
                    'finished_at' => now(),
                ]);
                
                Notification::make()
                    ->title('Antrian Selesai')
                    ->body("Antrian {$queueNumber} otomatis ditandai selesai")
                    ->success()
                    ->send();
            }
        }
    }
}