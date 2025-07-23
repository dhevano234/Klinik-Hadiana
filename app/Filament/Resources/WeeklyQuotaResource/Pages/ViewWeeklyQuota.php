<?php

namespace App\Filament\Resources\WeeklyQuotaResource\Pages;

use App\Filament\Resources\WeeklyQuotaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;

class ViewWeeklyQuota extends ViewRecord
{
    protected static string $resource = WeeklyQuotaResource::class;

    protected static ?string $title = 'Detail Kuota Mingguan';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Dokter')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('doctorSchedule.doctor_name')
                                    ->label('Nama Dokter')
                                    ->size('lg')
                                    ->weight('bold'),
                                
                                TextEntry::make('doctorSchedule.service.name')
                                    ->label('Layanan')
                                    ->badge()
                                    ->color('info'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('doctorSchedule.time_range')
                                    ->label('Jam Praktek')
                                    ->formatStateUsing(function ($record) {
                                        $schedule = $record->doctorSchedule;
                                        return $schedule 
                                            ? $schedule->start_time->format('H:i') . ' - ' . $schedule->end_time->format('H:i')
                                            : '-';
                                    })
                                    ->badge()
                                    ->color('gray'),
                                
                                TextEntry::make('doctorSchedule.formatted_days')
                                    ->label('Hari Praktek')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Informasi Kuota')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('day_name_indonesia')
                                    ->label('Hari')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary'),
                                
                                TextEntry::make('total_quota')
                                    ->label('Total Kuota')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),
                                
                                IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-s-check-circle')
                                    ->falseIcon('heroicon-s-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                    ]),

                Section::make('Penggunaan Hari Ini')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('used_quota_today')
                                    ->label('Terpakai')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('warning'),
                                
                                TextEntry::make('available_quota_today')
                                    ->label('Tersisa')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('info'),
                                
                                TextEntry::make('usage_percentage_today')
                                    ->label('Persentase')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color(fn ($record) => $record->getStatusColor()),
                                
                                TextEntry::make('status_label_today')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn ($record) => $record->getStatusColor()),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->day_of_week === strtolower(today()->format('l'))),

                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),

                Section::make('Informasi Sistem')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->dateTime('d/m/Y H:i'),
                                
                                TextEntry::make('updated_at')
                                    ->label('Diperbarui')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}