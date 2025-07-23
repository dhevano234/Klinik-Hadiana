<?php
// File: app/Filament/Resources/DoctorScheduleResource.php
// FINAL: Dropdown dokter dari database users + semua fitur existing

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorScheduleResource\Pages;
use App\Models\DoctorSchedule;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class DoctorScheduleResource extends Resource
{
    protected static ?string $model = DoctorSchedule::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Administrasi';
    
    protected static ?string $navigationLabel = 'Jadwal Dokter';
    
    protected static ?string $modelLabel = 'Jadwal Dokter';
    
    protected static ?string $pluralModelLabel = 'Jadwal Dokter';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✅ SECTION 1: FOTO DOKTER
                Forms\Components\Section::make('Foto Dokter')
                    ->description('Upload foto profil dokter untuk ditampilkan di jadwal')
                    ->schema([
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto Dokter')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '3:4',
                            ])
                            ->maxSize(2048) // Max 2MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                            ->directory('doctor-photos')
                            ->visibility('public')
                            ->imagePreviewHeight('200')
                            ->columnSpanFull()
                            ->helperText('Format: JPG, JPEG, PNG, WebP. Maksimal: 2MB. Rasio yang disarankan: 1:1 (persegi) atau 3:4 (portrait)')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->panelLayout('compact'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                // ✅ SECTION 2: INFORMASI DOKTER (UPDATED)
                Forms\Components\Section::make('Informasi Dokter')
                    ->description('Data dokter dan poli praktik')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // ✅ DROPDOWN: Pilih dokter dari database users (role: dokter)
                                Forms\Components\Select::make('doctor_id')
                                    ->label('Pilih Dokter')
                                    ->required()
                                    ->options(function () {
                                        return User::where('role', 'dokter')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('-- Pilih Dokter --')
                                    ->helperText('Pilih dokter dari daftar users dengan role dokter, atau buat dokter baru')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Auto-fill doctor_name berdasarkan pilihan
                                        if ($state) {
                                            $doctor = User::find($state);
                                            if ($doctor) {
                                                $set('doctor_name', $doctor->name);
                                            }
                                        } else {
                                            $set('doctor_name', null);
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Lengkap Dokter')
                                            ->required()
                                            ->placeholder('dr. Nama Dokter, Sp.XX')
                                            ->helperText('Contoh: dr. Ahmad Wijaya, Sp.PD'),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->unique('users', 'email')
                                            ->placeholder('dokter@klinik.com'),
                                        Forms\Components\TextInput::make('password')
                                            ->label('Password')
                                            ->password()
                                            ->required()
                                            ->minLength(8)
                                            ->default('password123')
                                            ->helperText('Minimal 8 karakter'),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('No. Telepon')
                                            ->tel()
                                            ->placeholder('081234567890'),
                                        Forms\Components\Select::make('gender')
                                            ->label('Jenis Kelamin')
                                            ->options([
                                                'Laki-laki' => 'Laki-laki',
                                                'Perempuan' => 'Perempuan',
                                            ])
                                            ->placeholder('Pilih jenis kelamin'),
                                        Forms\Components\DatePicker::make('birth_date')
                                            ->label('Tanggal Lahir')
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),
                                        Forms\Components\Textarea::make('address')
                                            ->label('Alamat')
                                            ->rows(3)
                                            ->placeholder('Alamat lengkap dokter')
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $doctor = User::create([
                                            'name' => $data['name'],
                                            'email' => $data['email'],
                                            'password' => Hash::make($data['password']),
                                            'role' => 'dokter',
                                            'phone' => $data['phone'] ?? null,
                                            'gender' => $data['gender'] ?? null,
                                            'birth_date' => $data['birth_date'] ?? null,
                                            'address' => $data['address'] ?? null,
                                            'email_verified_at' => now(),
                                        ]);
                                        
                                        Notification::make()
                                            ->title('✅ Dokter Baru Berhasil Dibuat')
                                            ->body("Akun dokter {$data['name']} telah dibuat dengan email {$data['email']}")
                                            ->success()
                                            ->send();
                                            
                                        return $doctor->id;
                                    }),

                                // ✅ HIDDEN: Field doctor_name untuk backward compatibility
                                Forms\Components\Hidden::make('doctor_name'),
                                    
                                Forms\Components\Select::make('service_id')
                                    ->label('Poli')
                                    ->required()
                                    ->relationship('service', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Pilih poli/layanan dari data layanan yang sudah ada')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Poli/Layanan')
                                            ->required(),
                                        Forms\Components\TextInput::make('prefix')
                                            ->label('Prefix Antrian')
                                            ->required()
                                            ->default('A')
                                            ->maxLength(3),
                                        Forms\Components\TextInput::make('padding')
                                            ->label('Padding Nomor')
                                            ->required()
                                            ->numeric()
                                            ->default(3),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Status Aktif')
                                            ->default(true),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $service = Service::create($data);
                                        return $service->id;
                                    }),
                            ]),
                    ]),

                // ✅ SECTION 3: JADWAL PRAKTIK
                Forms\Components\Section::make('Jadwal Praktik')
                    ->description('Atur hari dan jam praktik dokter')
                    ->schema([
                        Forms\Components\CheckboxList::make('days')
                            ->label('Hari Praktik')
                            ->options([
                                'monday' => 'Senin',
                                'tuesday' => 'Selasa',
                                'wednesday' => 'Rabu',
                                'thursday' => 'Kamis',
                                'friday' => 'Jumat',
                                'saturday' => 'Sabtu',
                                'sunday' => 'Minggu',
                            ])
                            ->columns(3)
                            ->required()
                            ->default(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                            ->helperText('Pilih hari-hari praktik dokter'),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Jam Mulai')
                                    ->required()
                                    ->seconds(false)
                                    ->format('H:i')
                                    ->default('08:00')
                                    ->helperText('Format 24 jam (contoh: 08:00)'),
                                    
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Jam Selesai')
                                    ->required()
                                    ->seconds(false)
                                    ->format('H:i')
                                    ->default('16:00')
                                    ->after('start_time')
                                    ->helperText('Format 24 jam (contoh: 16:00)'),
                            ]),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Jadwal hanya berlaku jika status aktif'),
                    ]),

                // Hidden fields untuk backward compatibility
                Forms\Components\Hidden::make('day_of_week'),
                Forms\Components\Hidden::make('user_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ✅ KOLOM FOTO
                Tables\Columns\ImageColumn::make('foto')
                    ->label('Foto')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(asset('assets/img/default-doctor.png'))
                    ->extraAttributes(['style' => 'object-fit: cover;'])
                    ->toggleable(),

                // ✅ KOLOM NAMA DOKTER (dari relationship atau fallback)
                Tables\Columns\TextColumn::make('doctor_name')
                    ->label('Nama Dokter')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(30)
                    ->description(fn (DoctorSchedule $record): string => 
                        $record->service ? "Poli: {$record->service->name}" : ''
                    )
                    ->formatStateUsing(function ($state, $record) {
                        // Prioritas: nama dari relationship user, fallback ke doctor_name
                        if ($record->doctor_id && $record->doctor) {
                            return $record->doctor->name;
                        }
                        return $state ?? 'Nama tidak diketahui';
                    }),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Poli')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->limit(20)
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('formatted_days')
                    ->label('Hari Praktik')
                    ->badge()
                    ->separator(',')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('time_range')
                    ->label('Jam Praktik')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Poli')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Dokter')
                    ->options(function () {
                        return User::where('role', 'dokter')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Jadwal')
                    ->trueLabel('Hanya Aktif')
                    ->falseLabel('Hanya Tidak Aktif'),

                Tables\Filters\TernaryFilter::make('has_photo')
                    ->label('Foto')
                    ->placeholder('Semua Dokter')
                    ->trueLabel('Punya Foto')
                    ->falseLabel('Belum Ada Foto')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('foto'),
                        false: fn ($query) => $query->whereNull('foto'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Jadwal Dokter')
                    ->modalDescription('Apakah Anda yakin ingin menghapus jadwal dokter ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDoctorSchedules::route('/'),
            'create' => Pages\CreateDoctorSchedule::route('/create'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}