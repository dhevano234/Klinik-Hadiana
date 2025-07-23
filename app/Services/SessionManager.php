<?php
// app/Services/SessionManager.php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;


class SessionManager
{
    /**
     * Login user to specific guard with custom session
     */
    public static function loginToGuard($user, string $guard, bool $remember = false): bool
    {
        try {
            // Get guard config
            $guardConfig = config("session.guards.{$guard}");
            
            if (!$guardConfig) {
                return false;
            }

            // Validate role for guard
            if (!self::validateRoleForGuard($user, $guard)) {
                return false;
            }

            // Login to specific guard
            Auth::guard($guard)->login($user, $remember);
            
            // Set custom cookie with guard-specific name
            $cookieName = $guardConfig['cookie'];
            $lifetime = $guardConfig['lifetime'];
            
            Cookie::queue(
                Cookie::make(
                    $cookieName,
                    session()->getId(),
                    $lifetime,
                    '/',
                    config('session.domain'),
                    config('session.secure'),
                    config('session.http_only'),
                    false,
                    config('session.same_site')
                )
            );
            
            return true;
            
        } catch (\Exception $e) {
            logger('SessionManager loginToGuard error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout from specific guard
     */
    public static function logoutFromGuard(string $guard): void
    {
        try {
            $guardConfig = config("session.guards.{$guard}");
            
            if ($guardConfig && Auth::guard($guard)->check()) {
                $cookieName = $guardConfig['cookie'];
                
                // Logout from guard
                Auth::guard($guard)->logout();
                
                // Clear custom cookie
                Cookie::queue(Cookie::forget($cookieName));
            }
        } catch (\Exception $e) {
            logger('SessionManager loginToGuard error: ' . $e->getMessage());
        }
    }

    /**
     * Check active sessions
     */
    public static function getActiveSessions(): array
    {
        $sessions = [];
        $guards = ['web', 'admin', 'dokter'];
        
        foreach ($guards as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    $sessions[$guard] = [
                        'guard' => $guard,
                        'user' => $user->name,
                        'role' => $user->role,
                        'id' => $user->id,
                        'email' => $user->email,
                    ];
                }
            } catch (\Exception $e) {
                // Guard not available or error, skip
                continue;
            }
        }
        
        return $sessions;
    }

    /**
     * Clear all sessions
     */
    public static function clearAllSessions(): void
    {
        $guards = ['web', 'admin', 'dokter'];
        
        foreach ($guards as $guard) {
            self::logoutFromGuard($guard);
        }
        
        // Clear main session
        Session::flush();
    }

    /**
     * Validate role for specific guard
     */
    private static function validateRoleForGuard($user, string $guard): bool
    {
        if (!$user) {
            return false;
        }

        return match ($guard) {
            'admin' => $user->role === 'admin',
            'dokter' => $user->role === 'dokter',
            'web' => $user->role === 'user',
            default => false,
        };
    }

    /**
     * Get current active guard
     */
    public static function getCurrentGuard(): ?string
    {
        $guards = ['admin', 'dokter', 'web'];
        
        foreach ($guards as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    return $guard;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Switch between guards (logout from current, login to new)
     */
    public static function switchGuard($user, string $fromGuard, string $toGuard): bool
    {
        try {
            // Logout from current guard
            self::logoutFromGuard($fromGuard);
            
            // Login to new guard
            return self::loginToGuard($user, $toGuard);
            
        } catch (\Exception $e) {
            logger('SessionManager loginToGuard error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user can access specific guard
     */
    public static function canAccessGuard($user, string $guard): bool
    {
        return self::validateRoleForGuard($user, $guard);
    }
}