<?php
// File: app/Filament/Dokter/Resources/QueueResource.php - TAMBAH KOLOM KELUHAN

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\QueueResource\Pages;
use App\Models\Queue;
use App\Services\QueueService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Kelola Antrian';
    protected static ?string $modelLabel = 'Antrian';
    protected static ?string $pluralModelLabel = 'Antrian';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // ✅ PERUBAHAN UTAMA: Default tampilkan hanya antrian hari ini
                return $query->whereDate('tanggal_antrian', today())
                            ->orderBy('created_at', 'desc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor Antrian')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->size('sm'),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pasien')
                    ->default('Walk-in')
                    ->searchable()
                    ->limit(25)
                    ->formatStateUsing(function ($record) {
                        if ($record->user_id && $record->user) {
                            return $record->user->name . 
                                   ($record->user->phone ? "\n(" . $record->user->phone . ")" : "");
                        }
                        return 'Walk-in';
                    })
                    ->wrap(),

                // ✅ TAMBAH: Kolom Keluhan untuk Dokter
                Tables\Columns\TextColumn::make('chief_complaint')
                    ->label('Keluhan')
                    ->limit(50)
                    ->wrap()
                    ->placeholder('Tidak ada keluhan')
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->icon(function ($record) {
                        return $record->chief_complaint ? 'heroicon-m-chat-bubble-left-ellipsis' : 'heroicon-m-minus';
                    })
                    ->iconColor(function ($record) {
                        return $record->chief_complaint ? 'success' : 'gray';
                    })
                    ->description(function ($record): string {
                        if ($record->chief_complaint) {
                            return '📝 Keluhan diisi saat ambil antrian';
                        }
                        return '💬 Tidak ada keluhan dari antrian';
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Antrian')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'Menunggu',
                        'serving' => 'Dilayani',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'serving' => 'success',
                        'finished' => 'primary',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Daftar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
                    
                Tables\Columns\TextColumn::make('called_at')
                    ->label('Waktu Dipanggil')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Belum dipanggil'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'waiting' => 'Menunggu',
                        'serving' => 'Dilayani',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                    ]),
                    
                Tables\Filters\SelectFilter::make('service')
                    ->label('Layanan')
                    ->relationship('service', 'name'),
                    
                // ✅ PERUBAHAN: Filter tanggal dibatasi maksimal hari ini
                Tables\Filters\Filter::make('tanggal_antrian')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Antrian')
                            ->default(today())
                            ->maxDate(today()) // ✅ TIDAK BISA PILIH MASA DEPAN
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tanggal'],
                            fn (Builder $query, $date): Builder => $query->whereDate('tanggal_antrian', $date),
                        );
                    }),

                // ✅ TAMBAH: Filter untuk keluhan
                Tables\Filters\Filter::make('has_complaint')
                    ->label('Punya Keluhan')
                    ->query(fn ($query) => $query->whereNotNull('chief_complaint')
                        ->where('chief_complaint', '!=', '')),

                Tables\Filters\Filter::make('no_complaint')
                    ->label('Tanpa Keluhan')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('chief_complaint')
                          ->orWhere('chief_complaint', '');
                    })),
            ])
            ->actions([
                // ===== TOMBOL PANGGIL - DIBATASI HARI INI =====
                Action::make('call')
                    ->label('Panggil')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->size('sm')
                    ->visible(function (Queue $record) {
                        // ✅ PERUBAHAN UTAMA: Hanya tampilkan untuk antrian hari ini
                        return $record->status === 'waiting' && 
                               $record->tanggal_antrian->isToday(); // ✅ HANYA HARI INI
                    })
                    ->action(function (Queue $record, $livewire) {
                        try {
                            // ✅ VALIDASI TAMBAHAN: Pastikan antrian adalah hari ini
                            if (!$record->tanggal_antrian->isToday()) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Hanya antrian hari ini yang dapat dipanggil.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->update([
                                'status' => 'serving',
                                'called_at' => now(),
                            ]);

                            $serviceName = $record->service->name ?? 'ruang periksa';
                            $message = "Nomor antrian {$record->number} silakan menuju {$serviceName}";

                            Notification::make()
                                ->title("Antrian {$record->number} berhasil dipanggil!")
                                ->body($message)
                                ->success()
                                ->duration(5000)
                                ->send();

                            $livewire->dispatch('queue-called', $message);
                            
                            session()->flash('queue_called', [
                                'number' => $record->number,
                                'message' => $message
                            ]);

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Gagal memanggil antrian: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Panggil Antrian')
                    ->modalDescription(function (Queue $record) {
                        // ✅ PERUBAHAN: Tambah info tanggal
                        $tanggal = $record->tanggal_antrian->format('d F Y');
                        return "Apakah Anda yakin ingin memanggil antrian {$record->number} untuk tanggal {$tanggal}?";
                    })
                    ->modalSubmitActionLabel('Ya, Panggil')
                    ->modalCancelActionLabel('Batal'),

                // ✅ PERBAIKAN: TOMBOL REKAM MEDIS dengan info keluhan
                Action::make('create_medical_record')
                    ->label('Rekam Medis')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn (Queue $record) => in_array($record->status, ['serving', 'waiting']))
                    ->action(function (Queue $record) {
                        // Jika walk-in (tidak ada user_id), redirect ke form kosong
                        if (!$record->user_id) {
                            return redirect()->route('filament.dokter.resources.medical-records.create');
                        }

                        // Jika ada user_id, redirect dengan parameter untuk auto-populate
                        return redirect()->route('filament.dokter.resources.medical-records.create', [
                            'user_id' => $record->user_id,
                            'queue_number' => $record->number,
                            'service' => $record->service->name ?? null,
                        ]);
                    })
                    ->tooltip(function (Queue $record) {
                        if ($record->user_id) {
                            $tooltip = "Buat rekam medis untuk {$record->user->name}";
                            if ($record->chief_complaint) {
                                $tooltip .= "\n📝 Ada keluhan: " . \Illuminate\Support\Str::limit($record->chief_complaint, 50);
                            } else {
                                $tooltip .= "\n💬 Tidak ada keluhan";
                            }
                            return $tooltip;
                        }
                        return "Buat rekam medis baru";
                    }),

                // ===== TOMBOL LIHAT KELUHAN (BARU) =====
                Action::make('view_complaint')
                    ->label('Lihat Keluhan')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->size('sm')
                    ->visible(fn (Queue $record) => !empty($record->chief_complaint))
                    ->action(function (Queue $record) {
                        $complaint = $record->chief_complaint;
                        $patientName = $record->user->name ?? 'Walk-in';
                        
                        Notification::make()
                            ->title("Keluhan Pasien: {$patientName}")
                            ->body("Antrian #{$record->number}\n\n📝 Keluhan:\n{$complaint}")
                            ->info()
                            ->duration(15000) // 15 detik untuk baca keluhan
                            ->send();
                    })
                    ->tooltip('Lihat keluhan lengkap pasien'),

                // ===== TOMBOL SELESAI =====
                Action::make('finish')
                    ->label('Selesai')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->size('sm')
                    ->visible(fn (Queue $record) => $record->status === 'serving')
                    ->action(function (Queue $record) {
                        try {
                            app(QueueService::class)->finishQueue($record);
                            
                            Notification::make()
                                ->title("Antrian {$record->number} selesai dilayani")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Gagal menyelesaikan antrian: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Antrian')
                    ->modalDescription(fn (Queue $record) => "Tandai antrian {$record->number} sebagai selesai?"),

                // ===== TOMBOL LIHAT =====
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->size('sm'),
            ])
            ->bulkActions([
                // Hapus bulk actions untuk panel dokter - fokus pada individual actions
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('3s')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQueues::route('/'),
            'view' => Pages\ViewQueue::route('/{record}'),
        ];
    }
}