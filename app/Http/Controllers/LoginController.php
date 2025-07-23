<?php
// app/Http/Controllers/LoginController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\SessionManager;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login.index');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password harus diisi',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        // Attempt login dengan web guard dulu untuk validasi kredensial
        if (Auth::guard('web')->attempt($credentials)) {
            $user = Auth::guard('web')->user();
            
            // Logout dari web guard (temporary login untuk validasi)
            Auth::guard('web')->logout();
            
            // Redirect berdasarkan role ke guard yang sesuai
            return $this->redirectUserByRole($user, $remember);
        }

        return redirect()->back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->withInput();
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
            logger('SessionManager loginToGuard error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Terjadi kesalahan saat logout');
        }
    }
}