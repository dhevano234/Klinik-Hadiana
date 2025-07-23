<?php
// File: app/Filament/Resources/PatientManagementResource/Pages/ListPatientManagement.php

namespace App\Filament\Resources\PatientManagementResource\Pages;

use App\Filament\Resources\PatientManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPatientManagement extends ListRecords
{
    protected static string $resource = PatientManagementResource::class;

    protected static ?string $title = 'Kelola Data Pasien';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pasien Manual')
                ->icon('heroicon-o-plus')
                ->tooltip('Tambah pasien baru secara manual'),
        ];
    }

    

            
}

// =================================================================

// File: app/Filament/Resources/PatientManagementResource/Pages/CreatePatientManagement.php

namespace App\Filament\Resources\PatientManagementResource\Pages;

use App\Filament\Resources\PatientManagementResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePatientManagement extends CreateRecord
{
    protected static string $resource = PatientManagementResource::class;

    protected static ?string $title = 'Tambah Pasien Baru';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Data Pasien'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set role sebagai user (pasien)
        $data['role'] = 'user';
        
        // Hash password jika diisi
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            // Set default password jika tidak diisi
            $data['password'] = bcrypt('password123');
        }
        
        // Set default address jika kosong
        if (empty($data['address'])) {
            $data['address'] = 'Alamat belum diisi';
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $patient = $this->record;
        
        // Generate medical record number jika belum ada
        if (!$patient->medical_record_number) {
            $patient->assignMedicalRecordNumber();
        }
        
        Notification::make()
            ->title('Pasien Berhasil Ditambahkan')
            ->body("Data pasien {$patient->name} telah disimpan dengan No. RM: {$patient->medical_record_number}")
            ->success()
            ->duration(5000)
            ->send();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Disable default notification
    }
}

// =================================================================

// File: app/Filament/Resources/PatientManagementResource/Pages/ViewPatientManagement.php

namespace App\Filament\Resources\PatientManagementResource\Pages;

use App\Filament\Resources\PatientManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPatientManagement extends ViewRecord
{
    protected static string $resource = PatientManagementResource::class;

    protected static ?string $title = 'Detail Data Pasien';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Data')
                ->icon('heroicon-o-pencil-square'),

            Actions\Action::make('view_medical_records')
                ->label('Lihat Rekam Medis')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => 
                    url("/admin/medical-records?user_id={$this->record->id}")
                )
                ->openUrlInNewTab(),

            Actions\Action::make('view_queues')
                ->label('Lihat Riwayat Antrian')
                ->icon('heroicon-o-queue-list')
                ->color('warning')
                ->url(fn () => 
                    url("/admin/queues?user_id={$this->record->id}")
                )
                ->openUrlInNewTab(),

            Actions\DeleteAction::make()
                ->label('Hapus Data')
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Informasi Rekam Medis
                Infolists\Components\Section::make('Informasi Rekam Medis')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('medical_record_number')
                                    ->label('Nomor Rekam Medis')
                                    ->badge()
                                    ->color('primary')
                                    ->weight('bold')
                                    ->placeholder('Belum ada nomor RM')
                                    ->copyable()
                                    ->copyMessage('No. RM disalin!')
                                    ->copyMessageDuration(1500),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Tanggal Registrasi')
                                    ->dateTime('l, d F Y - H:i')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible(),

                // Informasi Personal
                Infolists\Components\Section::make('Informasi Personal')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nama Lengkap')
                                    ->weight('semibold')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->copyable()
                                    ->copyMessage('Email disalin!')
                                    ->icon('heroicon-m-envelope'),

                                Infolists\Components\TextEntry::make('nomor_ktp')
                                    ->label('NIK/KTP')
                                    ->copyable()
                                    ->copyMessage('NIK disalin!')
                                    ->placeholder('Belum ada')
                                    ->icon('heroicon-m-identification'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('No. Telepon')
                                    ->copyable()
                                    ->copyMessage('Nomor HP disalin!')
                                    ->placeholder('Belum ada')
                                    ->icon('heroicon-m-phone'),

                                Infolists\Components\TextEntry::make('birth_date')
                                    ->label('Tanggal Lahir')
                                    ->date('d F Y')
                                    ->placeholder('Belum diisi')
                                    ->icon('heroicon-m-calendar-days'),

                                Infolists\Components\TextEntry::make('age')
                                    ->label('Umur')
                                    ->state(fn ($record) => $record->age ? $record->age . ' tahun' : 'Belum diisi')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Laki-laki' => 'blue',
                                        'Perempuan' => 'pink',
                                        default => 'gray',
                                    })
                                    ->placeholder('Belum diisi'),
                            ]),

                        Infolists\Components\TextEntry::make('address')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull()
                            ->prose()
                            ->placeholder('Alamat belum diisi')
                            ->icon('heroicon-m-map-pin'),
                    ])
                    ->collapsible(),

                // Statistik Pasien
                Infolists\Components\Section::make('Statistik Kunjungan')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_queues')
                                    ->label('Total Antrian')
                                    ->state(fn ($record) => $record->queues()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-m-queue-list'),

                                Infolists\Components\TextEntry::make('finished_queues')
                                    ->label('Kunjungan Selesai')
                                    ->state(fn ($record) => $record->queues()->where('status', 'finished')->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-m-check-circle'),

                                Infolists\Components\TextEntry::make('total_medical_records')
                                    ->label('Rekam Medis')
                                    ->state(fn ($record) => $record->medicalRecordsAsPatient()->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-m-document-text'),

                                Infolists\Components\TextEntry::make('last_visit')
                                    ->label('Kunjungan Terakhir')
                                    ->state(function ($record) {
                                        $lastQueue = $record->queues()->latest()->first();
                                        return $lastQueue ? $lastQueue->created_at->diffForHumans() : 'Belum pernah';
                                    })
                                    ->badge()
                                    ->color('warning'),

                                Infolists\Components\TextEntry::make('profile_completeness')
                                    ->label('Kelengkapan Data')
                                    ->state(function ($record) {
                                        return $record->isProfileCompleteForQueue() ? 'Lengkap' : 'Belum Lengkap';
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->isProfileCompleteForQueue() ? 'success' : 'warning'),

                                Infolists\Components\TextEntry::make('account_status')
                                    ->label('Status Akun')
                                    ->state('Aktif')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible(),

                // Informasi Sistem
                Infolists\Components\Section::make('Informasi Sistem')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('role')
                                    ->label('Role')
                                    ->badge()
                                    ->color('purple'),

                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Belum diverifikasi'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Terakhir Diupdate')
                                    ->dateTime('d/m/Y H:i')
                                    ->since(),
                            ]),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}

// =================================================================

// File: app/Filament/Resources/PatientManagementResource/Pages/EditPatientManagement.php

namespace App\Filament\Resources\PatientManagementResource\Pages;

use App\Filament\Resources\PatientManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPatientManagement extends EditRecord
{
    protected static string $resource = PatientManagementResource::class;

    protected static ?string $title = 'Edit Data Pasien';

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

            Actions\Action::make('generate_mrn')
                ->label('Generate No. RM')
                ->icon('heroicon-o-identification')
                ->color('success')
                ->visible(fn () => !$this->record->medical_record_number)
                ->action(function () {
                    $this->record->assignMedicalRecordNumber();
                    
                    Notification::make()
                        ->title('No. RM Berhasil Digenerate')
                        ->body("No. RM: {$this->record->medical_record_number}")
                        ->success()
                        ->send();
                        
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                })
                ->requiresConfirmation(),

            Actions\Action::make('reset_password')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('new_password')
                        ->label('Password Baru')
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->placeholder('Masukkan password baru'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'password' => bcrypt($data['new_password'])
                    ]);
                    
                    Notification::make()
                        ->title('Password Berhasil Direset')
                        ->body("Password untuk {$this->record->name} telah direset")
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->label('Hapus Data')
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pastikan role tetap user
        $data['role'] = 'user';
        
        // Hash password jika diisi (password baru)
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            // Jika password kosong, hapus dari data agar tidak mengoverwrite password lama
            unset($data['password']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Generate medical record number jika belum ada
        if (!$this->record->medical_record_number) {
            $this->record->assignMedicalRecordNumber();
        }
        
        Notification::make()
            ->title('Data Pasien Berhasil Diupdate')
            ->body("Data pasien {$this->record->name} telah diperbarui")
            ->success()
            ->duration(3000)
            ->send();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null; // Disable default notification
    }
}