<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\SessionManager;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login.index');
    }

    public function login(Request $request)
    {
        // Manual rate limiting
        $key = 'patient_login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return redirect()->back()
                ->withErrors(['email' => 'Terlalu banyak percobaan. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.'])
                ->withInput();
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'captcha' => 'required|string|max:5',
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password harus diisi',
            'captcha.required' => 'Captcha harus diisi',
        ]);

        if ($validator->fails()) {
            // Hit rate limiter on validation failure
            RateLimiter::hit($key, $decayMinutes * 60);
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate captcha first
        if (!$this->validateCaptcha($request->input('captcha'))) {
            // Hit rate limiter on failed captcha
            RateLimiter::hit($key, $decayMinutes * 60);
            
            return redirect()->back()
                ->withErrors(['captcha' => 'Captcha tidak valid.'])
                ->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        // Attempt login dengan web guard dulu untuk validasi kredensial
        if (Auth::guard('web')->attempt($credentials)) {
            $user = Auth::guard('web')->user();
            
            // Logout dari web guard (temporary login untuk validasi)
            Auth::guard('web')->logout();
            
            // Clear rate limiter on successful login
            RateLimiter::clear($key);
            
            // Redirect berdasarkan role ke guard yang sesuai
            return $this->redirectUserByRole($user, $remember);
        }

        // Hit rate limiter on failed login
        RateLimiter::hit($key, $decayMinutes * 60);

        return redirect()->back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->withInput();
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

    private function redirectUserByRole($user, bool $remember = false)
    {
        switch ($user->role) {
            case 'admin':
                if (SessionManager::loginToGuard($user, 'admin', $remember)) {
                    return redirect('/admin')->with('success', 'Login sebagai Admin berhasil');
                } else {
                    return redirect()->back()->withErrors(['email' => 'Gagal login sebagai admin']);
                }
                
            case 'dokter':
                if (SessionManager::loginToGuard($user, 'dokter', $remember)) {
                    return redirect('/dokter')->with('success', 'Login sebagai Dokter berhasil');
                } else {
                    return redirect()->back()->withErrors(['email' => 'Gagal login sebagai dokter']);
                }
                
            case 'user':
            default:
                if (SessionManager::loginToGuard($user, 'web', $remember)) {
                    return redirect()->route('dashboard')->with('success', 'Login berhasil');
                } else {
                    return redirect()->back()->withErrors(['email' => 'Gagal login sebagai user']);
                }
        }
    }

    public function logout(Request $request)
    {
        try {
            // Clear all sessions
            SessionManager::clearAllSessions();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('success', 'Logout berhasil dari semua panel');
            
        } catch (\Exception $e) {
            logger('SessionManager logout error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Terjadi kesalahan saat logout');
        }
    }
}