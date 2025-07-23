<?php
// File: app/Filament/Dokter/Resources/MedicalRecordResource.php - FINAL: AUTO-FILL dari Medical Record

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\MedicalRecordResource\Pages;
use App\Models\MedicalRecord;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Rekam Medis';
    protected static ?string $modelLabel = 'Rekam Medis';
    protected static ?string $pluralModelLabel = 'Rekam Medis';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Info jika dari antrian  
                Forms\Components\Placeholder::make('queue_info')
                    ->label('📋 Rekam Medis dari Antrian')
                    ->content(function () {
                        $queueNumber = request()->get('queue_number');
                        $serviceName = request()->get('service');
                        
                        if ($queueNumber) {
                            return "Antrian: {$queueNumber}" . ($serviceName ? " - {$serviceName}" : "");
                        }
                        return '';
                    })
                    ->visible(fn () => request()->has('queue_number')),

                // ✅ SECTION: Data Pasien - Grid 2 kolom
                Forms\Components\Section::make('Data Pasien')
                    ->description('Pilih pasien untuk membuat rekam medis')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // ✅ 1. Kolom Nama Pasien
                                Forms\Components\Select::make('user_id')
                                    ->label('Nama Pasien')
                                    ->options(function () {
                                        return User::where('role', 'user')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive() // ✅ REACTIVE untuk auto-fill
                                    ->disabled(fn () => request()->has('user_id'))
                                    ->helperText('Pilih nama pasien dari daftar')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // ✅ AUTO-FILL nomor rekam medis
                                        if ($state) {
                                            $user = User::find($state);
                                            if ($user && $user->medical_record_number) {
                                                $set('display_medical_record_number', $user->medical_record_number);
                                            } else {
                                                $set('display_medical_record_number', 'Belum ada nomor rekam medis');
                                            }

                                            // ✅ FIXED: AUTO-FILL KELUHAN dari MEDICAL RECORD terbaru (tanpa notifikasi)
                                            $latestMedicalRecord = \App\Models\MedicalRecord::where('user_id', $state)
                                                ->whereNotNull('chief_complaint')
                                                ->where('chief_complaint', '!=', '')
                                                ->latest('created_at')
                                                ->first();

                                            if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                                                $set('chief_complaint', $latestMedicalRecord->chief_complaint);
                                            }
                                        } else {
                                            $set('display_medical_record_number', '');
                                            $set('chief_complaint', ''); // Clear keluhan jika user di-deselect
                                        }
                                    }),

                                // ✅ 2. Kolom Nomor Rekam Medis (Display Only)
                                Forms\Components\TextInput::make('display_medical_record_number')
                                    ->label('Nomor Rekam Medis')
                                    ->disabled()
                                    ->dehydrated(false) // Tidak disimpan ke database
                                    ->placeholder('Akan terisi otomatis setelah pilih pasien')
                                    ->helperText('Nomor rekam medis akan terisi otomatis')
                                    ->default(''),
                            ]),
                    ])
                    ->collapsible(),

                // ✅ SECTION: Data Pemeriksaan
                Forms\Components\Section::make('Data Pemeriksaan')
                    ->description('Isi hasil pemeriksaan pasien')
                    ->schema([
                        // Gejala/Keluhan Utama (Required)
                        Forms\Components\Textarea::make('chief_complaint')
                            ->label('Gejala/Keluhan Utama')
                            ->required()
                            ->rows(3)
                            ->placeholder('Jelaskan gejala atau keluhan utama pasien...')
                            ->columnSpanFull()
                            ->reactive()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // ✅ FIXED: AUTO-FILL dari MEDICAL RECORD jika ada saat load form
                                if (!$state && !$record) { // Hanya saat create baru
                                    $userId = request()->get('user_id');
                                    $queueNumber = request()->get('queue_number');
                                    
                                    if ($userId) {
                                        // ✅ FIXED: Cari dari medical record terbaru
                                        $latestMedicalRecord = \App\Models\MedicalRecord::where('user_id', $userId)
                                            ->whereNotNull('chief_complaint')
                                            ->where('chief_complaint', '!=', '')
                                            ->latest('created_at')
                                            ->first();
                                        
                                        if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                                            $component->state($latestMedicalRecord->chief_complaint);
                                        }
                                    } elseif ($queueNumber) {
                                        // Jika dari queue, cari user dari queue tersebut
                                        $queue = \App\Models\Queue::where('number', $queueNumber)
                                            ->whereDate('created_at', today())
                                            ->with('user')
                                            ->first();
                                            
                                        if ($queue && $queue->user) {
                                            $latestMedicalRecord = \App\Models\MedicalRecord::where('user_id', $queue->user_id)
                                                ->whereNotNull('chief_complaint')
                                                ->where('chief_complaint', '!=', '')
                                                ->latest('created_at')
                                                ->first();
                                                
                                            if ($latestMedicalRecord && $latestMedicalRecord->chief_complaint) {
                                                $component->state($latestMedicalRecord->chief_complaint);
                                            }
                                        }
                                    }
                                }
                            })
                            ->helperText('Keluhan akan terisi otomatis dari rekam medis sebelumnya'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Tanda Vital (Optional)
                                Forms\Components\Textarea::make('vital_signs')
                                    ->label('Tanda Vital')
                                    ->rows(3)
                                    ->placeholder('TD: 120/80 mmHg' . "\n" . 'Nadi: 80x/menit' . "\n" . 'Suhu: 36.5°C' . "\n" . 'RR: 20x/menit'),

                                // Diagnosis (Required)
                                Forms\Components\Textarea::make('diagnosis')
                                    ->label('Diagnosis')
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Tuliskan diagnosis berdasarkan pemeriksaan...'),
                            ]),

                        // Resep Obat (Optional)
                        Forms\Components\Textarea::make('prescription')
                            ->label('Resep Obat')
                            ->rows(3)
                            ->placeholder('Contoh:' . "\n" . 'Paracetamol 500mg 3x1' . "\n" . 'Amoxicillin 250mg 3x1' . "\n" . 'Vitamin C 1x1')
                            ->columnSpanFull(),

                        // Catatan Tambahan (Optional)
                        Forms\Components\Textarea::make('additional_notes')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->placeholder('Catatan tambahan, instruksi khusus, atau follow-up yang diperlukan...')
                            ->columnSpanFull(),
                    ]),

                // Hidden fields
                Forms\Components\Hidden::make('doctor_id')
                    ->default(Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ✅ Kolom Nomor Rekam Medis
                Tables\Columns\TextColumn::make('user.medical_record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Belum ada')
                    ->copyable()
                    ->copyMessage('Nomor RM disalin!')
                    ->tooltip('Klik untuk copy nomor rekam medis'),

                // ✅ Kolom nama pasien dengan info tambahan
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(function (MedicalRecord $record): string {
                        $descriptions = [];
                        
                        if ($record->user && $record->user->phone) {
                            $descriptions[] = "📱 {$record->user->phone}";
                        }
                        
                        if ($record->user && $record->user->gender) {
                            $descriptions[] = "⚥ {$record->user->gender_label}";
                        }
                        
                        if ($record->user && $record->user->age) {
                            $descriptions[] = "🎂 {$record->user->age} tahun";
                        }
                        
                        return implode(' | ', $descriptions) ?: 'Data belum lengkap';
                    }),

                Tables\Columns\TextColumn::make('chief_complaint')
                    ->label('Keluhan Utama')
                    ->limit(40)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnosis')
                    ->limit(40)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->searchable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pemeriksaan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state->format('l, d F Y - H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('doctor')
                    ->relationship('doctor', 'name')
                    ->label('Filter Dokter'),

                Tables\Filters\SelectFilter::make('user')
                    ->label('Filter Pasien')
                    ->options(function () {
                        return User::where('role', 'user')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable(),

                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->default(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn ($query) => $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),

                Tables\Filters\Filter::make('has_medical_record')
                    ->label('Punya No. RM')
                    ->query(fn ($query) => $query->whereHas('user', function ($q) {
                        $q->whereNotNull('medical_record_number');
                    })),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil-square'),
                        
                    Tables\Actions\Action::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (MedicalRecord $record) {
                            // Logic untuk print bisa ditambahkan di sini
                        }),

                    Tables\Actions\Action::make('view_mrn')
                        ->label('Info Pasien')
                        ->icon('heroicon-o-identification')
                        ->color('primary')
                        ->action(function (MedicalRecord $record) {
                            $user = $record->user;
                            $mrn = $user?->medical_record_number ?? 'Belum ada';
                            
                            $info = "Nama: {$user->name}\n";
                            $info .= "No. RM: {$mrn}\n";
                            if ($user->nomor_ktp) $info .= "NIK: {$user->nomor_ktp}\n";
                            if ($user->phone) $info .= "HP: {$user->phone}";
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Informasi Pasien')
                                ->body($info)
                                ->success()
                                ->duration(8000)
                                ->send();
                        }),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                        
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            // Logic export bisa ditambahkan di sini
                        }),
                ]),
            ])
            ->searchable()
            ->striped()
            ->paginated([10, 25, 50])
            ->modifyQueryUsing(function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('role', 'user');
                });
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedicalRecords::route('/'),
            'create' => Pages\CreateMedicalRecord::route('/create'),
            'view' => Pages\ViewMedicalRecord::route('/{record}'),
            'edit' => Pages\EditMedicalRecord::route('/{record}/edit'),
        ];
    }
}