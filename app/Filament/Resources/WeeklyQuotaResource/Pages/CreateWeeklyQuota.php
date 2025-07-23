<?php
// File: app/Filament/Resources/WeeklyQuotaResource/Pages/CreateWeeklyQuota.php

namespace App\Filament\Resources\WeeklyQuotaResource\Pages;

use App\Filament\Resources\WeeklyQuotaResource;
use App\Models\WeeklyQuota;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateWeeklyQuota extends CreateRecord
{
    protected static string $resource = WeeklyQuotaResource::class;

    protected static ?string $title = 'Buat Kuota Mingguan';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Kuota'),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterCreate(): void
    {
        $quota = $this->record;
        
        Notification::make()
            ->title('Kuota Berhasil Dibuat')
            ->body("Kuota untuk {$quota->doctorSchedule->doctor_name} pada hari {$quota->day_name_indonesia}: {$quota->total_quota}")
            ->success()
            ->duration(5000)
            ->send();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Disable default notification
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if quota already exists
        $existingQuota = WeeklyQuota::where('doctor_schedule_id', $data['doctor_schedule_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->first();

        if ($existingQuota) {
            $dayNames = [
                'monday' => 'Senin',
                'tuesday' => 'Selasa',
                'wednesday' => 'Rabu',
                'thursday' => 'Kamis',
                'friday' => 'Jumat',
                'saturday' => 'Sabtu',
                'sunday' => 'Minggu',
            ];
            
            throw new \Exception('Kuota untuk dokter ini pada hari ' . ($dayNames[$data['day_of_week']] ?? $data['day_of_week']) . ' sudah ada');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}