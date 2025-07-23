<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalRecordResource\Pages;
use App\Models\MedicalRecord;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Rekam Medis';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?string $modelLabel = 'Rekam Medis';
    protected static ?string $pluralModelLabel = 'Rekam Medis';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('user', function ($query) {
                $query->where('role', 'user');
            })
            ->with(['user', 'doctor']);
    }
    
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
                Forms\Components\Section::make('Data Periksa')
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

                                            // ✅ AUTO-FILL KELUHAN dari antrian terbaru yang aktif
                                            $latestComplaint = \App\Models\Queue::where('user_id', $state)
                                                ->whereNotNull('chief_complaint')
                                                ->where('chief_complaint', '!=', '')
                                                ->whereIn('status', ['waiting', 'serving']) // ✅ Hanya antrian aktif
                                                ->whereDate('created_at', today()) // ✅ Hanya hari ini
                                                ->latest('created_at')
                                                ->first();

                                            if ($latestComplaint && $latestComplaint->chief_complaint) {
                                                $set('chief_complaint', $latestComplaint->chief_complaint);
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

                                // ✅ DROPDOWN PILIH DOKTER - REVISI MINIMAL
                                Forms\Components\Select::make('doctor_id')
                                    ->label('Dokter yang Menangani')
                                    ->options(function () {
                                        return User::where('role', 'dokter')           // UBAH: whereIn jadi where
                                            ->whereNotNull('name')                      // TAMBAH: filter nama tidak null
                                            ->where('name', '!=', '')                   // TAMBAH: filter nama tidak kosong
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Pilih dokter yang menangani pasien')
                                    ->helperText('Pilih dokter yang menangani pemeriksaan ini')  // UBAH: hapus kata "admin"
                                    // ->default(Auth::id()) // HAPUS BARIS INI
                                    ->columnSpanFull(),
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
                                // ✅ AUTO-FILL dari antrian jika ada saat load form
                                if (!$state && !$record) { // Hanya saat create baru
                                    $userId = request()->get('user_id');
                                    $queueNumber = request()->get('queue_number');
                                    
                                    if ($userId) {
                                        // ✅ Cari antrian terbaru user yang ada keluhan DAN masih aktif
                                        $queueWithComplaint = \App\Models\Queue::where('user_id', $userId)
                                            ->whereNotNull('chief_complaint')
                                            ->where('chief_complaint', '!=', '')
                                            ->whereIn('status', ['waiting', 'serving']) // ✅ hanya antrian aktif
                                            ->whereDate('created_at', today()) // ✅ hanya hari ini
                                            ->latest('created_at')
                                            ->first();
                                        
                                        if ($queueWithComplaint && $queueWithComplaint->chief_complaint) {
                                            $component->state($queueWithComplaint->chief_complaint);
                                        }
                                    } elseif ($queueNumber) {
                                        // Cari berdasarkan nomor antrian
                                        $queue = \App\Models\Queue::where('number', $queueNumber)
                                            ->whereDate('created_at', today())
                                            ->first();
                                            
                                        if ($queue && $queue->chief_complaint) {
                                            $component->state($queue->chief_complaint);
                                        }
                                    }
                                }
                            })
                            ->helperText(function () {
                                $userId = request()->get('user_id');
                                $queueNumber = request()->get('queue_number');
                                
                                if ($userId || $queueNumber) {
                                    // ✅ Check apakah ada keluhan dari antrian yang aktif
                                    if ($userId) {
                                        $queue = \App\Models\Queue::where('user_id', $userId)
                                            ->whereNotNull('chief_complaint')
                                            ->where('chief_complaint', '!=', '')
                                            ->whereIn('status', ['waiting', 'serving'])
                                            ->whereDate('created_at', today())
                                            ->latest('created_at')
                                            ->first();
                                    } else {
                                        $queue = \App\Models\Queue::where('number', $queueNumber)
                                            ->whereDate('created_at', today())
                                            ->first();
                                    }
                                    
                                    if ($queue && $queue->chief_complaint) {
                                        return "✅ Keluhan diambil dari antrian #{$queue->number}: \"" . \Illuminate\Support\Str::limit($queue->chief_complaint, 80) . "\"";
                                    } else {
                                        return "ℹ️ Pasien tidak mengisi keluhan saat ambil antrian. Silakan tanyakan langsung kepada pasien.";
                                    }
                                }
                                
                                return "Jelaskan gejala atau keluhan utama pasien secara detail.";
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Tanda Vital (Optional)
                                Forms\Components\Textarea::make('vital_signs')
                                    ->label('Tanda Vital')
                                    ->rows(3)
                                    ->placeholder('TD: 120/80 mmHg' . "\n" . 'Nadi: 80x/menit' . "\n" . 'Suhu: 36.5°C' . "\n" . 'RR: 20x/menit'),

                                // ========================================
                                // 🔥 DIAGNOSIS - READONLY UNTUK ADMIN
                                // ========================================
                                Forms\Components\Textarea::make('diagnosis')
                                    ->label('Diagnosis')
                                    ->rows(3)
                                    ->placeholder('Tuliskan diagnosis berdasarkan pemeriksaan...')
                                    ->disabled(fn () => Auth::user()->role === 'admin')
                                    ->dehydrated()
                                    ->helperText(function () {
                                        if (Auth::user()->role === 'admin') {
                                            return;
                                        }
                                        return 'Diagnosis berdasarkan pemeriksaan medis';
                                    }),
                            ]),

                        // ========================================
                        // 🔥 RESEP OBAT - READONLY UNTUK ADMIN  
                        // ========================================
                        Forms\Components\Textarea::make('prescription')
                            ->label('Resep Obat')
                            ->rows(3)
                            ->placeholder('Contoh:' . "\n" . 'Paracetamol 500mg 3x1' . "\n" . 'Amoxicillin 250mg 3x1' . "\n" . 'Vitamin C 1x1')
                            ->columnSpanFull()
                            ->disabled(fn () => Auth::user()->role === 'admin')
                            ->dehydrated()
                            ->helperText(function () {
                                if (Auth::user()->role === 'admin') {
                                    return;
                                }
                                return 'Resep obat untuk pasien';
                            }),

                        // Catatan Tambahan (tetap bisa diisi admin)
                        Forms\Components\Textarea::make('additional_notes')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->placeholder('Catatan tambahan, instruksi khusus, atau follow-up yang diperlukan...')
                            ->columnSpanFull(),
                    ]),



                // ✅ HIDDEN FIELD SUDAH TIDAK DIPERLUKAN KARENA ADA DROPDOWN DOKTER
                // Forms\Components\Hidden::make('doctor_id')->default(Auth::id()),
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
                            $descriptions[] = "⚥ {$record->user->gender}";
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
                    ->label('Filter Petugas'),

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
                            // ✅ Logic untuk print - bisa dikembangkan nanti
                            \Filament\Notifications\Notification::make()
                                ->title('Print Feature')
                                ->body('Fitur print akan dikembangkan selanjutnya')
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->requiresConfirmation(),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation(),
                        
                    // ✅ HANYA BULK EXPORT - BISA PILIH SEMUA ATAU SEBAGIAN
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            // ✅ SIMPLE EXPORT LOGIC
                            $filename = 'rekam_medis_' . now()->format('Y-m-d_H-i-s') . '.csv';
                            
                            $headers = [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                            ];

                            $callback = function() use ($records) {
                                $file = fopen('php://output', 'w');
                                
                                // ✅ Add BOM for UTF-8
                                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                                
                                // Header CSV
                                fputcsv($file, [
                                    'No. Rekam Medis',
                                    'Nama Pasien',
                                    'No. Telepon',
                                    'Jenis Kelamin',
                                    'Umur',
                                    'Keluhan Utama',
                                    'Tanda Vital',
                                    'Diagnosis',
                                    'Resep Obat',
                                    'Catatan Tambahan',
                                    'Dokter/Petugas',
                                    'Tanggal Pemeriksaan',
                                    'Waktu Pemeriksaan'
                                ]);

                                // Data rows
                                foreach ($records as $record) {
                                    fputcsv($file, [
                                        $record->user->medical_record_number ?? 'Belum ada',
                                        $record->user->name ?? '-',
                                        $record->user->phone ?? '-',
                                        $record->user->gender ?? '-',
                                        $record->user->age ? $record->user->age . ' tahun' : '-',
                                        $record->chief_complaint ?? '-',
                                        $record->vital_signs ?? '-',
                                        $record->diagnosis ?? '-',
                                        $record->prescription ?? '-',
                                        $record->additional_notes ?? '-',
                                        $record->doctor->name ?? '-',
                                        $record->created_at->format('d/m/Y'),
                                        $record->created_at->format('H:i:s')
                                    ]);
                                }

                                fclose($file);
                            };

                            // ✅ Show success notification
                            \Filament\Notifications\Notification::make()
                                ->title('Export Berhasil')
                                ->body('Data rekam medis berhasil diekspor ke file CSV')
                                ->success()
                                ->duration(5000)
                                ->send();

                            return response()->stream($callback, 200, $headers);
                        })
                        ->tooltip('Pilih data yang ingin diekspor, lalu klik tombol ini'),
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
            'view' => Pages\ViewMedicalRecords::route('/{record}'),
            'edit' => Pages\EditMedicalRecord::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() ?: null;
    }
}