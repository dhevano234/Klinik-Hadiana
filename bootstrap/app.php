<?php
// File: bootstrap/app.php - Laravel 12 dengan Scheduler

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // ✅ LARAVEL 12: Define scheduler di bootstrap
        $schedule->command('whatsapp:send-reminders')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/whatsapp-reminders.log'));
                 
        $schedule->command('queue:update-overdue')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/queue-updates.log'));
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Register alias middleware untuk role checking
        $middleware->alias([
            'role.admin' => \App\Http\Middleware\EnsureAdminRole::class,
            'role.dokter' => \App\Http\Middleware\EnsureDokterRole::class,
            'role.user' => \App\Http\Middleware\EnsureUserRole::class,
        ]);
        
        $middleware->append([
            // Middleware yang diterapkan ke semua request
        ]);

        // Middleware untuk grup web
        $middleware->web(append: [
            // Middleware khusus untuk web routes
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();