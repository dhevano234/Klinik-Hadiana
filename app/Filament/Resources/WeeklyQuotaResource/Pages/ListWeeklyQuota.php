<?php

namespace App\Filament\Resources\WeeklyQuotaResource\Pages;

use App\Filament\Resources\WeeklyQuotaResource;
use App\Models\WeeklyQuota;
use App\Models\DoctorSchedule;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;

class ListWeeklyQuotas extends ListRecords
{
    protected static string $resource = WeeklyQuotaResource::class;

    protected static ?string $title = 'Kuota Antrian';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_doctor_quota')
                ->label('Buat Kuota Dokter')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->form([
                    Section::make('Pilih Dokter dan Pengaturan')
                        ->schema([
                            Select::make('doctor_id')
                                ->label('Pilih Dokter')
                                ->options(
                                    DoctorSchedule::where('is_active', true)
                                        ->with('service')
                                        ->get()
                                        ->mapWithKeys(function ($doctor) {
                                            return [
                                                $doctor->id => $doctor->doctor_name . ' - ' . 
                                                    ($doctor->service->name ?? 'Unknown') . 
                                                    ' (' . implode(', ', array_map(function($day) {
                                                        $days = [
                                                            'monday' => 'Sen',
                                                            'tuesday' => 'Sel',
                                                            'wednesday' => 'Rab',
                                                            'thursday' => 'Kam',
                                                            'friday' => 'Jum',
                                                            'saturday' => 'Sab',
                                                            'sunday' => 'Min',
                                                        ];
                                                        return $days[$day] ?? $day;
                                                    }, $doctor->days ?? [])) . ')'
                                            ];
                                        })
                                        ->toArray()
                                )
                                ->required()
                                ->searchable()
                                ->helperText('Pilih dokter yang akan dibuatkan kuota'),
                            
                            TextInput::make('total_quota')
                                ->label('Jumlah Kuota per Hari')
                                ->numeric()
                                ->default(20)
                                ->required()
                                ->minValue(1)
                                ->maxValue(100)
                                ->step(1)
                                ->helperText('Kuota ini akan diterapkan untuk setiap hari praktik dokter'),
                            
                            Select::make('selected_days')
                                ->label('Pilih Hari (Opsional)')
                                ->options([
                                    'monday' => 'Senin',
                                    'tuesday' => 'Selasa',
                                    'wednesday' => 'Rabu',
                                    'thursday' => 'Kamis',
                                    'friday' => 'Jumat',
                                    'saturday' => 'Sabtu',
                                    'sunday' => 'Minggu',
                                ])
                                ->multiple()
                                ->helperText('Kosongkan untuk membuat kuota di semua hari praktik dokter'),
                        ])
                ])
                ->action(function (array $data) {
                    $doctorId = $data['doctor_id'];
                    $totalQuota = $data['total_quota'];
                    $selectedDays = $data['selected_days'] ?? [];
                    
                    $doctor = DoctorSchedule::find($doctorId);
                    if (!$doctor) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('Dokter tidak ditemukan')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $daysToProcess = empty($selectedDays) ? $doctor->days : $selectedDays;
                    
                    $created = 0;
                    $updated = 0;
                    $existing = 0;
                    
                    foreach ($daysToProcess as $day) {
                        $quota = WeeklyQuota::where('doctor_schedule_id', $doctorId)
                            ->where('day_of_week', $day)
                            ->first();
                        
                        if (!$quota) {
                            WeeklyQuota::create([
                                'doctor_schedule_id' => $doctorId,
                                'day_of_week' => $day,
                                'total_quota' => $totalQuota,
                                'is_active' => true,
                            ]);
                            $created++;
                        } else {
                            if ($quota->total_quota !== $totalQuota) {
                                $quota->update(['total_quota' => $totalQuota]);
                                $updated++;
                            } else {
                                $existing++;
                            }
                        }
                    }
                    
                    $message = [];
                    if ($created > 0) $message[] = "Dibuat: {$created} kuota";
                    if ($updated > 0) $message[] = "Diperbarui: {$updated} kuota";
                    if ($existing > 0) $message[] = "Sudah ada: {$existing} kuota";
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Kuota Dokter Berhasil Diproses')
                        ->body(implode(', ', $message))
                        ->success()
                        ->send();
                })
                ->modalHeading('Buat Kuota Dokter')
                ->modalSubmitActionLabel('Buat Kuota')
                ->modalWidth('lg'),
        ];
    }
}