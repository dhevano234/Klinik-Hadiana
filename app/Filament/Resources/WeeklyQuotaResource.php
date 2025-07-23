<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeeklyQuotaResource\Pages;
use App\Models\WeeklyQuota;
use App\Models\DoctorSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class WeeklyQuotaResource extends Resource
{
    protected static ?string $model = WeeklyQuota::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kuota Antrian';

    protected static ?string $modelLabel = 'Kuota Mingguan';

    protected static ?string $pluralModelLabel = 'Kuota Antrian';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kuota')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('doctor_schedule_id')
                                    ->label('Dokter')
                                    ->required()
                                    ->options(
                                        DoctorSchedule::where('is_active', true)
                                            ->with('service')
                                            ->get()
                                            ->mapWithKeys(function ($doctor) {
                                                return [
                                                    $doctor->id => $doctor->doctor_name . ' - ' . 
                                                        ($doctor->service->name ?? 'Unknown') .
                                                        ' (' . $doctor->start_time->format('H:i') . ' - ' . 
                                                        $doctor->end_time->format('H:i') . ')'
                                                ];
                                            })
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Pilih dokter dan layanan'),

                                Forms\Components\Select::make('day_of_week')
                                    ->label('Hari Praktek')
                                    ->required()
                                    ->options([
                                        'monday' => 'Senin',
                                        'tuesday' => 'Selasa',
                                        'wednesday' => 'Rabu',
                                        'thursday' => 'Kamis',
                                        'friday' => 'Jumat',
                                        'saturday' => 'Sabtu',
                                        'sunday' => 'Minggu',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $doctorId = $get('doctor_schedule_id');
                                        if ($doctorId && $state) {
                                            $existingQuota = WeeklyQuota::where('doctor_schedule_id', $doctorId)
                                                ->where('day_of_week', $state)
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
                                                
                                                $set('existing_quota_warning', 
                                                    "Kuota untuk dokter ini pada hari {$dayNames[$state]} sudah ada: {$existingQuota->total_quota}"
                                                );
                                            } else {
                                                $set('existing_quota_warning', null);
                                            }
                                        }
                                    })
                                    ->helperText('Pilih hari dalam seminggu'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_quota')
                                    ->label('Jumlah Kuota')
                                    ->numeric()
                                    ->required()
                                    ->default(20)
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->step(1)
                                    ->helperText('Kuota ini akan digunakan setiap minggu pada hari yang sama'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->helperText('Kuota hanya berlaku jika dalam status aktif'),
                            ]),

                        Forms\Components\Placeholder::make('existing_quota_warning')
                            ->label('')
                            ->content(fn (callable $get) => $get('existing_quota_warning'))
                            ->visible(fn (callable $get) => !empty($get('existing_quota_warning')))
                            ->extraAttributes(['class' => 'text-danger-600 font-medium']),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tambahkan catatan jika diperlukan...')
                            ->rows(3)
                            ->helperText('Catatan optional untuk kuota ini'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doctorSchedule.doctor_name')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('doctorSchedule.service.name')
                    ->label('Layanan')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('day_name_indonesia')
                    ->label('Hari')
                    ->badge()
                    ->color(fn (WeeklyQuota $record): string => 
                        $record->day_of_week === strtolower(today()->format('l')) ? 'success' : 'gray'
                    )
                    ->sortable(['day_of_week']),

                Tables\Columns\TextColumn::make('doctorSchedule.time_range')
                    ->label('Jam Praktek')
                    ->getStateUsing(function (WeeklyQuota $record) {
                        $schedule = $record->doctorSchedule;
                        return $schedule ? $schedule->start_time->format('H:i') . ' - ' . $schedule->end_time->format('H:i') : '-';
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_quota')
                    ->label('Kuota Total')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                // ✅ FIXED: Tampilkan quota sesuai hari praktik, bukan selalu "hari ini"
                Tables\Columns\TextColumn::make('quota_for_practice_day')
                    ->label('Kuota Hari Praktik')
                    ->getStateUsing(function (WeeklyQuota $record) {
                        // ✅ Hitung untuk hari praktik yang sesuai, bukan selalu hari ini
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            // Jika hari ini adalah hari praktik, tampilkan quota hari ini
                            return $record->getFormattedQuotaForDate($today);
                        } else {
                            // Jika bukan hari praktik hari ini, cari tanggal terdekat untuk hari praktik tersebut
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return $record->getFormattedQuotaForDate($nextPracticeDate);
                        }
                    })
                    ->badge()
                    ->color(function (WeeklyQuota $record): string {
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            return $record->getStatusColorForDate($today);
                        } else {
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return $record->getStatusColorForDate($nextPracticeDate);
                        }
                    })
                    ->tooltip(function (WeeklyQuota $record) {
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            return "Hari ini ({$today->format('d/m/Y')}) - Penggunaan: {$record->getUsagePercentageForDate($today)}%";
                        } else {
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return "Hari praktik berikutnya ({$nextPracticeDate->format('d/m/Y')}) - Penggunaan: {$record->getUsagePercentageForDate($nextPracticeDate)}%";
                        }
                    }),

                Tables\Columns\TextColumn::make('usage_percentage_practice_day')
                    ->label('Penggunaan')
                    ->getStateUsing(function (WeeklyQuota $record): string {
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            return number_format($record->getUsagePercentageForDate($today), 1) . '%';
                        } else {
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return number_format($record->getUsagePercentageForDate($nextPracticeDate), 1) . '%';
                        }
                    })
                    ->color(function (WeeklyQuota $record): string {
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            return $record->getStatusColorForDate($today);
                        } else {
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return $record->getStatusColorForDate($nextPracticeDate);
                        }
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_practice_day')
                    ->label('Status')
                    ->getStateUsing(function (WeeklyQuota $record): string {
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            return $record->getStatusLabelForDate($today);
                        } else {
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return $record->getStatusLabelForDate($nextPracticeDate);
                        }
                    })
                    ->badge()
                    ->color(function (WeeklyQuota $record): string {
                        $today = today();
                        $practiceDay = $record->day_of_week;
                        $todayDayOfWeek = strtolower($today->format('l'));
                        
                        if ($practiceDay === $todayDayOfWeek) {
                            return $record->getStatusColorForDate($today);
                        } else {
                            $nextPracticeDate = static::getNextDateForDayOfWeek($practiceDay);
                            return $record->getStatusColorForDate($nextPracticeDate);
                        }
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Hari')
                    ->options([
                        'monday' => 'Senin',
                        'tuesday' => 'Selasa',
                        'wednesday' => 'Rabu',
                        'thursday' => 'Kamis',
                        'friday' => 'Jumat',
                        'saturday' => 'Sabtu',
                        'sunday' => 'Minggu',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                    
                Tables\Filters\Filter::make('today_practice')
                    ->label('Praktik Hari Ini')
                    ->query(fn ($query) => $query->where('day_of_week', strtolower(today()->format('l'))))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('day_of_week', 'asc');
    }

    /**
     * ✅ HELPER: Get next date for specific day of week
     */
    public static function getNextDateForDayOfWeek(string $dayOfWeek): Carbon
    {
        $today = today();
        $todayDayOfWeek = strtolower($today->format('l'));
        
        if ($todayDayOfWeek === $dayOfWeek) {
            return $today;
        }
        
        // Cari tanggal berikutnya untuk hari tersebut
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $currentDayIndex = array_search($todayDayOfWeek, $daysOfWeek);
        $targetDayIndex = array_search($dayOfWeek, $daysOfWeek);
        
        if ($targetDayIndex > $currentDayIndex) {
            // Hari target masih dalam minggu ini
            $daysToAdd = $targetDayIndex - $currentDayIndex;
        } else {
            // Hari target di minggu depan
            $daysToAdd = (7 - $currentDayIndex) + $targetDayIndex;
        }
        
        return $today->copy()->addDays($daysToAdd);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyQuotas::route('/'),
            'create' => Pages\CreateWeeklyQuota::route('/create'),
            'view' => Pages\ViewWeeklyQuota::route('/{record}'),
            'edit' => Pages\EditWeeklyQuota::route('/{record}/edit'),
        ];
    }
}