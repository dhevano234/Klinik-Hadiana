<?php
// File: app/Filament/Resources/DoctorManagementResource/Pages/CreateDoctorManagement.php

namespace App\Filament\Resources\DoctorManagementResource\Pages;

use App\Filament\Resources\DoctorManagementResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDoctorManagement extends CreateRecord
{
    protected static string $resource = DoctorManagementResource::class;

    public function getTitle(): string
    {
        return 'Tambah Dokter';
    }

    // Override method untuk menghilangkan tombol "Create & Create Another"
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

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Dokter berhasil ditambahkan')
            ->body('Akun dokter telah dibuat dan dapat login ke panel dokter.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan role selalu dokter
        $data['role'] = 'dokter';
        
        return $data;
    }
}