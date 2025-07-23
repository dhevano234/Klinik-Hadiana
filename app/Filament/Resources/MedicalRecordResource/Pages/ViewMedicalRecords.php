<?php
// =================================================================
// File: app/Filament/Resources/MedicalRecordResource/Pages/ViewMedicalRecord.php
// Admin Panel - View Medical Record (Mengikuti style dokter)
// =================================================================

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use App\Filament\Resources\MedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMedicalRecords extends ViewRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected static ?string $title = 'Detail Rekam Medis';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil-square'),
            
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->action(function () {
                    // Logic untuk print
                }),
                
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Informasi Pasien
                Infolists\Components\Section::make('Informasi Pasien')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.medical_record_number')
                                    ->label('No. Rekam Medis')
                                    ->badge()
                                    ->color('primary')
                                    ->weight('bold')
                                    ->copyable()
                                    ->copyMessage('No. RM disalin!'),
                                    
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Nama Pasien')
                                    ->weight('semibold'),
                                    
                                Infolists\Components\TextEntry::make('user.gender')
                                    ->label('Jenis Kelamin')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'Laki-laki' => 'info',
                                        'Perempuan' => 'success',
                                        default => 'gray',
                                    })
                                    ->placeholder('Belum diisi'),
                                    
                                Infolists\Components\TextEntry::make('user.age')
                                    ->label('Umur')
                                    ->state(fn ($record) => $record->user->age ? $record->user->age . ' tahun' : 'Belum diisi')
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ])
                    ->collapsible(),

                // Detail Pemeriksaan
                Infolists\Components\Section::make('Detail Pemeriksaan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal & Waktu Pemeriksaan')
                            ->dateTime('l, d F Y - H:i:s')
                            ->badge()
                            ->color('info')
                            ->columnSpanFull(),
                            
                        Infolists\Components\TextEntry::make('chief_complaint')
                            ->label('Gejala/Keluhan Utama')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown(),
                            
                        Infolists\Components\TextEntry::make('vital_signs')
                            ->label('Tanda Vital')
                            ->columnSpanFull()
                            ->prose()
                            ->placeholder('Tanda vital tidak dicatat')
                            ->visible(fn ($record) => !empty($record->vital_signs)),
                    ]),

                // Diagnosis dan Pengobatan
                Infolists\Components\Section::make('Diagnosis & Pengobatan')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Infolists\Components\TextEntry::make('diagnosis')
                            ->label('Diagnosis')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->color('danger'),
                            
                        Infolists\Components\TextEntry::make('prescription')
                            ->label('Resep Obat')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->placeholder('Tidak ada resep obat')
                            ->visible(fn ($record) => !empty($record->prescription)),
                            
                        Infolists\Components\TextEntry::make('additional_notes')
                            ->label('Catatan Tambahan')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->placeholder('Tidak ada catatan tambahan')
                            ->visible(fn ($record) => !empty($record->additional_notes)),
                    ]),

                // Informasi Sistem
                Infolists\Components\Section::make('Informasi Sistem')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Terakhir Diubah')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->since(),
                                    
                                Infolists\Components\TextEntry::make('id')
                                    ->label('ID Rekam Medis')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}