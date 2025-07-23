<?php
// File: app/Filament/Resources/DoctorManagementResource.php
// FITUR BARU: Data Dokter untuk Admin Panel

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorManagementResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class DoctorManagementResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Data Dokter';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?string $modelLabel = 'Dokter';
    protected static ?string $pluralModelLabel = 'Data Dokter';
    protected static ?int $navigationSort = 2; // Posisi setelah Data Pasien

    // Query hanya untuk dokter
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'dokter');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dokter')
                    ->description('Data dasar dokter untuk sistem')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Dokter')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('dr. Nama Lengkap'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('dokter@clinic.com'),

                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->minLength(8)
                                    ->rules([
                                        'min:8',
                                        'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-])/',
                                    ])
                                    ->placeholder('Masukkan password yang kuat')
                                    ->hint('Password harus memenuhi kriteria:')
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->helperText('
                                        ✅ Minimal 8 karakter
                                        ✅ Huruf kapital (A-Z)  
                                        ✅ Angka (0-9)
                                        ✅ Karakter spesial (!@#$%^&*_)
                                        
                                        💡 Kosongkan jika tidak ingin mengubah password
                                    ')
                                    ->validationMessages([
                                        'min' => 'Password minimal 8 karakter.',
                                        'regex' => 'Password harus mengandung huruf besar, angka, dan simbol khusus.',
                                    ]),

                                Forms\Components\Hidden::make('role')
                                    ->default('dokter'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Dokter')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email disalin'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Dokter')
                    ->modalDescription('Apakah Anda yakin ingin menghapus dokter ini? Data rekam medis yang sudah dibuat akan tetap ada.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorManagement::route('/'),
            'create' => Pages\CreateDoctorManagement::route('/create'),
            'edit' => Pages\EditDoctorManagement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('role', 'dokter')->count();
    }
}