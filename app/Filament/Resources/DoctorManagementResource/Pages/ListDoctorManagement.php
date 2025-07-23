<?php
// File: app/Filament/Resources/DoctorManagementResource/Pages/ListDoctorManagement.php

namespace App\Filament\Resources\DoctorManagementResource\Pages;

use App\Filament\Resources\DoctorManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDoctorManagement extends ListRecords
{
    protected static string $resource = DoctorManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Dokter')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Data Dokter';
    }
}