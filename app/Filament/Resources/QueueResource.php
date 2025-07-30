<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QueueResource\Pages;
use App\Models\Queue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static ?string $label = 'Antrian';

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Administrasi';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canUpdate(): bool
    {
        return false;
    }
    
    public static function canDeleteAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('service_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('counter_id')
                    ->numeric(),
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('waiting'),
                Forms\Components\DateTimePicker::make('called_at'),
                Forms\Components\DateTimePicker::make('served_at'),
                Forms\Components\DateTimePicker::make('canceled_at'),
                Forms\Components\DateTimePicker::make('finished_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor Antrian')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('primary')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->limit(20),

                Tables\Columns\TextColumn::make('user.medical_record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->placeholder('Belum ada')
                    ->copyable()
                    ->copyMessage('No. RM disalin!')
                    ->tooltip('Klik untuk copy nomor rekam medis'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Pasien')
                    ->default('Walk-in')
                    ->searchable()
                    ->limit(25)
                    ->weight('medium')
                    ->description(function (Queue $record): string {
                        if ($record->user_id && $record->user) {
                            $details = [];
                            
                            if ($record->user->email) {
                                $details[] = "Email: {$record->user->email}";
                            }
                            
                            if ($record->user->nomor_ktp) {
                                $details[] = "KTP: {$record->user->nomor_ktp}";
                            }
                            
                            return implode(' | ', $details);
                        }
                        return 'Antrian tanpa akun terdaftar';
                    }),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('No. HP')
                    ->default('-')
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Nomor HP disalin')
                    ->placeholder('Tidak ada')
                    ->formatStateUsing(function ($state, Queue $record) {
                        if ($record->user_id && $record->user && $record->user->phone) {
                            return $record->user->phone;
                        }
                        return '-';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
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
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'waiting' => 'heroicon-m-clock',
                        'serving' => 'heroicon-m-play',
                        'finished' => 'heroicon-m-check-circle',
                        'canceled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->searchable()
                    ->badge()
                    ->color('purple')
                    ->placeholder('Belum ditentukan')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('l, d F Y - H:i:s')),

                Tables\Columns\TextColumn::make('timeline_status')
                    ->label('Timeline')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function (Queue $record): string {
                        $timeline = [];
                        
                        $timeline[] = '📝 ' . $record->created_at->format('H:i');
                        
                        if ($record->called_at) {
                            $timeline[] = '📢 ' . $record->called_at->format('H:i');
                        }
                        
                        if ($record->served_at) {
                            $timeline[] = '👨‍⚕️ ' . $record->served_at->format('H:i');
                        }
                        
                        if ($record->finished_at) {
                            $timeline[] = '✅ ' . $record->finished_at->format('H:i');
                        }
                        
                        if ($record->canceled_at) {
                            $timeline[] = '❌ ' . $record->canceled_at->format('H:i');
                        }
                        
                        return implode(' → ', $timeline);
                    })
                    ->tooltip('Timeline: Dibuat → Dipanggil → Dilayani → Selesai'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'waiting' => 'Menunggu',
                        'serving' => 'Dilayani',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('service')
                    ->label('Layanan')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->label('Hari Ini')
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->default(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn ($query) => $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),
                        
                    Tables\Actions\Action::make('change_status')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status Baru')
                                ->options([
                                    'waiting' => 'Menunggu',
                                    'serving' => 'Dilayani',
                                    'finished' => 'Selesai',
                                    'canceled' => 'Dibatalkan',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Queue $record, array $data) {
                            $oldStatus = $record->status;
                            $newStatus = $data['status'];
                            
                            $updateData = ['status' => $newStatus];
                            
                            switch ($newStatus) {
                                case 'serving':
                                    $updateData['served_at'] = now();
                                    break;
                                case 'finished':
                                    $updateData['finished_at'] = now();
                                    break;
                                case 'canceled':
                                    $updateData['canceled_at'] = now();
                                    break;
                            }
                            
                            $record->update($updateData);
                            
                            Notification::make()
                                ->title('Status Berhasil Diubah')
                                ->body("Antrian {$record->number}: {$oldStatus} → {$newStatus}")
                                ->success()
                                ->send();
                        })
                        ->successNotificationTitle('Status antrian berhasil diubah'),

                    Tables\Actions\Action::make('view_medical_record')
                        ->label('Lihat No. RM')
                        ->icon('heroicon-o-identification')
                        ->color('success')
                        ->visible(fn (Queue $record) => $record->user_id && $record->user)
                        ->action(function (Queue $record) {
                            $user = $record->user;
                            $mrn = $user->medical_record_number ?? 'Belum ada';
                            
                            Notification::make()
                                ->title('Informasi Rekam Medis')
                                ->body("Pasien: {$user->name}\nNo. RM: {$mrn}\nKTP: " . ($user->nomor_ktp ?? 'Belum ada'))
                                ->success()
                                ->duration(10000)
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Antrian')
                        ->modalDescription(fn (Queue $record) => "Apakah Anda yakin ingin menghapus antrian {$record->number}? Tindakan ini tidak dapat dibatalkan.")
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Antrian dihapus')
                                ->body('Antrian berhasil dihapus dari sistem.')
                        ),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_served')
                        ->label('Tandai Dilayani')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $updated = 0;
                            $records->each(function ($record) use (&$updated) {
                                if ($record->status === 'waiting') {
                                    $record->update([
                                        'status' => 'serving',
                                        'served_at' => now(),
                                    ]);
                                    $updated++;
                                }
                            });
                            
                            Notification::make()
                                ->title('Berhasil')
                                ->body("{$updated} antrian ditandai sebagai sedang dilayani")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Tandai sebagai dilayani')
                        ->modalDescription('Apakah Anda yakin ingin menandai antrian yang dipilih sebagai sedang dilayani?'),

                    Tables\Actions\BulkAction::make('mark_as_finished')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-badge')
                        ->color('primary')
                        ->action(function ($records) {
                            $updated = 0;
                            $records->each(function ($record) use (&$updated) {
                                if (in_array($record->status, ['waiting', 'serving'])) {
                                    $record->update([
                                        'status' => 'finished',
                                        'finished_at' => now(),
                                    ]);
                                    $updated++;
                                }
                            });
                            
                            Notification::make()
                                ->title('Berhasil')
                                ->body("{$updated} antrian ditandai sebagai selesai")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $filename = 'antrian_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
                            
                            $headers = [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                            ];

                            $callback = function() use ($records) {
                                $file = fopen('php://output', 'w');
                                
                                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                                
                                fputcsv($file, [
                                    'Nomor Antrian',
                                    'No. Rekam Medis',
                                    'Nama Pasien',
                                    'No. HP',
                                    'Layanan',
                                    'Status',
                                    'Dokter',
                                    'Loket',
                                    'Dibuat',
                                    'Dipanggil',
                                    'Selesai'
                                ]);

                                foreach ($records as $record) {
                                    fputcsv($file, [
                                        $record->number ?? '-',
                                        $record->user->medical_record_number ?? 'Walk-in',
                                        $record->user->name ?? 'Walk-in',
                                        $record->user->phone ?? '-',
                                        $record->service->name ?? '-',
                                        match($record->status) {
                                            'waiting' => 'Menunggu',
                                            'serving' => 'Dilayani',
                                            'finished' => 'Selesai',
                                            'canceled' => 'Dibatalkan',
                                            default => $record->status,
                                        },
                                        $record->doctor->name ?? '-',
                                        $record->counter->name ?? '-',
                                        $record->created_at->format('d/m/Y H:i'),
                                        $record->called_at?->format('d/m/Y H:i') ?? '-',
                                        $record->finished_at?->format('d/m/Y H:i') ?? '-',
                                    ]);
                                }

                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Antrian')
                        ->modalDescription('Apakah Anda yakin ingin menghapus antrian yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Antrian dihapus')
                                ->body('Antrian yang dipilih berhasil dihapus.')
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('5s')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateHeading('Belum ada antrian')
            ->emptyStateDescription('Antrian akan muncul di sini setelah pasien mengambil nomor antrian.')
            ->emptyStateIcon('heroicon-o-queue-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageQueues::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'waiting')->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah antrian yang sedang menunggu';
    }
}