<?php
// File: app/Filament/Resources/DoctorManagementResource/Pages/EditDoctorManagement.php

namespace App\Filament\Resources\DoctorManagementResource\Pages;

use App\Filament\Resources\DoctorManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDoctorManagement extends EditRecord
{
    protected static string $resource = DoctorManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Dokter')
                ->modalDescription('Apakah Anda yakin ingin menghapus dokter ini?')
                ->modalSubmitActionLabel('Ya, Hapus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Dokter: ' . $this->record->name;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data dokter berhasil diperbarui')
            ->body('Perubahan telah disimpan.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan role tetap dokter
        $data['role'] = 'dokter';
        
        return $data;
    }
}