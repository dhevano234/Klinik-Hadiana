<?php
// File: app/Filament/Dokter/Pages/EditProfile.php
// Version: Final - Solusi terbaik untuk error

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class EditProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Edit Profile';
    protected static ?string $title = 'Edit Profile';
    protected static string $view = 'filament.dokter.pages.edit-profile';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Profile')
                    ->description('Data dasar akun dokter')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->disabled()
                            ->helperText('Nama tidak dapat diubah. Hubungi admin jika perlu diubah.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->helperText('Email tidak dapat diubah. Hubungi admin jika perlu diubah.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Ubah Password')
                    ->description('Ubah password akun untuk keamanan')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('Masukkan password lama untuk konfirmasi')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if (filled($state)) {
                                    /** @var User $user */
                                    $user = Auth::user();
                                    if (!Hash::check($state, $user->password)) {
                                        throw ValidationException::withMessages([
                                            'data.current_password' => 'Password saat ini tidak benar.',
                                        ]);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->rules([
                                'min:8',
                                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-])/',
                            ])
                            ->validationMessages([
                                'min' => 'Password minimal 8 karakter.',
                                'regex' => 'Password harus mengandung huruf besar, angka, dan simbol khusus.',
                            ])
                            ->helperText('
                                ✅ Minimal 8 karakter
                                ✅ Huruf kapital (A-Z)  
                                ✅ Angka (0-9)
                                ✅ Karakter spesial (!@#$%^&*_)
                            ')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password')
                            ->validationMessages([
                                'same' => 'Konfirmasi password tidak cocok dengan password baru.',
                            ])
                            ->helperText('Ulangi password baru untuk konfirmasi')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            /** @var User $user */
            $user = Auth::user();

            // Update password
            $user->password = Hash::make($data['password']);
            $user->save();

            Notification::make()
                ->title('Berhasil')
                ->body('Password berhasil diubah!')
                ->success()
                ->send();

            // Reset form password fields
            $this->form->fill([
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => '',
                'password' => '',
                'password_confirmation' => '',
            ]);

        } catch (ValidationException $e) {
            // Filament akan menangani error validasi secara otomatis
            throw $e;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Ubah Password')
                ->submit('save')
                ->icon('heroicon-o-key')
                ->color('primary'),
        ];
    }
}