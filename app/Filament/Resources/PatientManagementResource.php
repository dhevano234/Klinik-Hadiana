<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientManagementResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class PatientManagementResource extends Resource
{
   protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Pasien';
    protected static ?string $modelLabel = 'Pasien';
    protected static ?string $pluralModelLabel = 'Data Pasien';
    protected static ?string $navigationGroup = 'Administrasi';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'user')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Dasar')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Nomor HP')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('08xxxxxxxxxx'),

                                Forms\Components\Select::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'Laki-laki' => 'Laki-laki',
                                        'Perempuan' => 'Perempuan',
                                    ])
                                    ->placeholder('Pilih jenis kelamin'),

                                Forms\Components\DatePicker::make('birth_date')
                                    ->label('Tanggal Lahir')
                                    ->placeholder('Pilih tanggal lahir')
                                    ->before('today'),

                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat')
                                    ->rows(3)
                                    ->placeholder('Masukkan alamat lengkap'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Akun & Keamanan')
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->minLength(6),
                                Forms\Components\Hidden::make('role')->default('user'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('medical_record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (User $record) => $record->email),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable()
                    ->copyable()
                    ->placeholder('Belum ada'),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Gender')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Laki-laki' => 'blue',
                        'Perempuan' => 'pink',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->since(),
            ])
            
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Hanya filter gender saja - yang lainnya dihapus
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ])
                    ->placeholder('Semua')
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('generate_mrn')
                        ->label('Generate No. RM')
                        ->icon('heroicon-o-identification')
                        ->color('success')
                        ->visible(fn (User $record) => !$record->medical_record_number)
                        ->action(function (User $record) {
                            $record->assignMedicalRecordNumber();
                            Notification::make()
                                ->title('No. RM berhasil dibuat')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pasien')
                        ->modalDescription('Apakah Anda yakin ingin menghapus data pasien ini?')
                        ->modalSubmitActionLabel('Hapus')
                        ->successNotificationTitle('Pasien berhasil dihapus'),
                ])
                ->label('Aksi')
                ->color('gray')
                ->icon('heroicon-o-ellipsis-vertical')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListPatientManagement::route('/'),
            'create' => Pages\CreatePatientManagement::route('/create'),
            'view' => Pages\ViewPatientManagement::route('/{record}'),
            'edit' => Pages\EditPatientManagement::route('/{record}/edit'),
        ];
    }
}