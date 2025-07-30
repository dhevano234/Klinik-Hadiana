<?php

namespace App\Filament\Dokter\Resources\MedicalRecordResource\Pages;

use App\Filament\Dokter\Resources\MedicalRecordResource;
use App\Models\User;
use App\Models\Queue;
use App\Models\MedicalRecord;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

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
        $data['doctor_id'] = Auth::id();
        
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
                $latestMedicalRecord = MedicalRecord::where('user_id', $userId)
                    ->whereNotNull('chief_complaint')
                    ->where('chief_complaint', '!=', '')
                    ->latest('created_at')
                    ->first();
                
                $formData = [
                    'user_id' => $userId,
                    'display_medical_record_number' => $user->medical_record_number ?? 'Belum ada nomor rekam medis',
                ];
                
                if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                    $formData['chief_complaint'] = $latestMedicalRecord->chief_complaint;
                    if (!empty($latestMedicalRecord->vital_signs)) {
                        $formData['vital_signs'] = $latestMedicalRecord->vital_signs;
                    }
                    if (!empty($latestMedicalRecord->additional_notes)) {
                        $formData['additional_notes'] = $latestMedicalRecord->additional_notes;
                    }
                }
                
                $this->form->fill($formData);
                
                    
                if ($queueNumber) {
                    static::$title = "Rekam Medis - Antrian {$queueNumber}";
                }
                
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
                
                $latestMedicalRecord = MedicalRecord::where('user_id', $user->id)
                    ->whereNotNull('chief_complaint')
                    ->where('chief_complaint', '!=', '')
                    ->latest('created_at')
                    ->first();
                
                $formData = [
                    'user_id' => $user->id,
                    'display_medical_record_number' => $user->medical_record_number ?? 'Belum ada nomor rekam medis',
                ];
                
                if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                    $formData['chief_complaint'] = $latestMedicalRecord->chief_complaint;
                    if (!empty($latestMedicalRecord->vital_signs)) {
                        $formData['vital_signs'] = $latestMedicalRecord->vital_signs;
                    }
                    if (!empty($latestMedicalRecord->additional_notes)) {
                        $formData['additional_notes'] = $latestMedicalRecord->additional_notes;
                    }
                }
                
                $this->form->fill($formData);
                
                $notificationBody = "Antrian {$queueNumber}: {$user->name}";
                if ($user->medical_record_number) {
                    $notificationBody .= " | No. RM: {$user->medical_record_number}";
                }
                
                if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                    $shortComplaint = strlen($latestMedicalRecord->chief_complaint) > 100 
                        ? substr($latestMedicalRecord->chief_complaint, 0, 100) . '...'
                        : $latestMedicalRecord->chief_complaint;
                    $notificationBody .= "\n📝 Keluhan dari rekam medis terakhir: \"{$shortComplaint}\"";
                    $notificationBody .= "\n⏰ Tanggal: {$latestMedicalRecord->created_at->format('d/m/Y H:i')}";
                } else {
                    $notificationBody .= "\n💬 Tidak ada keluhan dari rekam medis sebelumnya";
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

    public function afterStateUpdated($component, $state): void
    {
        if ($component === 'user_id' && $state) {
            $latestMedicalRecord = MedicalRecord::where('user_id', $state)
                ->whereNotNull('chief_complaint')
                ->where('chief_complaint', '!=', '')
                ->latest('created_at')
                ->first();

            if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                $this->form->fill([
                    'chief_complaint' => $latestMedicalRecord->chief_complaint,
                    'vital_signs' => $latestMedicalRecord->vital_signs,
                    'additional_notes' => $latestMedicalRecord->additional_notes,
                ]);

                Notification::make()
                    ->title('Auto-fill Keluhan')
                    ->body("Keluhan diisi otomatis dari rekam medis terakhir ({$latestMedicalRecord->created_at->format('d/m/Y')})")
                    ->success()
                    ->duration(5000)
                    ->send();
            }
        }
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rekam medis berhasil dibuat';
    }
    
    protected function afterCreate(): void
    {
        $queueNumber = request()->get('queue_number');
        
        if ($queueNumber) {
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