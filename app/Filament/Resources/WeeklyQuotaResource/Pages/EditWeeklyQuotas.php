<?php

namespace App\Filament\Resources\WeeklyQuotaResource\Pages;

use App\Filament\Resources\WeeklyQuotaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWeeklyQuota extends EditRecord
{
    protected static string $resource = WeeklyQuotaResource::class;

    protected static ?string $title = 'Edit Kuota Mingguan';

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan Perubahan'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat Detail'),

            Actions\DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    protected function afterSave(): void
    {
        $quota = $this->record;
        
        Notification::make()
            ->title('Kuota Berhasil Diperbarui')
            ->body("Kuota untuk {$quota->doctorSchedule->doctor_name} pada hari {$quota->day_name_indonesia}: {$quota->total_quota}")
            ->success()
            ->duration(5000)
            ->send();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null; // Disable default notification
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}