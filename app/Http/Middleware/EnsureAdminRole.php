<?php
// app/Http/Middleware/EnsureAdminRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check() || Auth::guard('admin')->user()->role !== 'admin') {
            Auth::guard('admin')->logout();
            
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'Akses ditolak. Hanya admin yang diizinkan.']);
        }

        return $next($request);
    }
}