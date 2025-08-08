<?php

namespace App\Filament\Dokter\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Services\SessionManager;

class Login extends SimplePage
{
    use InteractsWithFormActions;

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::pages.auth.login';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        if (Auth::guard('dokter')->check()) {
            redirect()->intended(filament()->getUrl());
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getCaptchaDisplayComponent(),
                $this->getCaptchaInputComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getCaptchaDisplayComponent(): View
    {
        return View::make('filament.components.captcha-display');
    }

    protected function getCaptchaInputComponent(): TextInput
    {
        return TextInput::make('captcha')
            ->label('Captcha')
            ->required()
            ->maxLength(5)
            ->placeholder('Masukkan kode captcha')
            ->extraInputAttributes([
                'tabindex' => 3,
                'autocomplete' => 'off',
            ])
            ->validationAttribute('Captcha');
    }

    protected function getRememberFormComponent(): Checkbox
    {
        return Checkbox::make('remember')
            ->label('Remember me')
            ->extraInputAttributes(['tabindex' => 4]);
    }

    public function authenticate(): ?LoginResponse
    {
        // Manual rate limiting
        $key = 'login_attempts:' . request()->ip();
        $maxAttempts = 5;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            Notification::make()
                ->title('Terlalu Banyak Percobaan')
                ->body('Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.')
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        // Validate captcha first
        if (!$this->validateCaptcha($data['captcha'] ?? '')) {
            // Hit rate limiter on failed captcha
            RateLimiter::hit($key, $decayMinutes * 60);
            
            throw ValidationException::withMessages([
                'data.captcha' => 'Captcha tidak valid.',
            ]);
        }

        // Attempt authentication
        if (!Auth::guard('dokter')->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            // Hit rate limiter on failed login
            RateLimiter::hit($key, $decayMinutes * 60);
            
            throw ValidationException::withMessages([
                'data.email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
        }

        $user = Auth::guard('dokter')->user();

        // Validate role
        if ($user->role !== 'dokter') {
            Auth::guard('dokter')->logout();
            
            // Hit rate limiter on unauthorized access
            RateLimiter::hit($key, $decayMinutes * 60);
            
            throw ValidationException::withMessages([
                'data.email' => 'Akses ditolak. Hanya dokter yang diizinkan.',
            ]);
        }

        // Use SessionManager to properly login
        if (!SessionManager::loginToGuard($user, 'dokter', $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => 'Gagal login sebagai dokter.',
            ]);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function validateCaptcha(string $input): bool
    {
        // Get the captcha from session and compare
        $sessionCaptcha = session('captcha_text');
        
        if (!$sessionCaptcha) {
            return false;
        }

        // Remove captcha from session after validation
        session()->forget('captcha_text');

        return strtolower(trim($input)) === strtolower(trim($sessionCaptcha));
    }

    public function getTitle(): string|Htmlable
    {
        return 'Login Dokter';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Masuk ke Panel Dokter';
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk')
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}