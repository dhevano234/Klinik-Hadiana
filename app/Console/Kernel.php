<?php
// File: app/Console/Kernel.php - FINAL WORKING VERSION

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        \App\Console\Commands\SendWhatsAppRemindersCommand::class,
        \App\Console\Commands\UpdateOverdueQueues::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // ✅ WORKING: WhatsApp reminder setiap menit
        $schedule->command('whatsapp:send-reminders')
                 ->everyMinute()
                 ->between('00:00', '23:59') // Testing: allow all hours
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/whatsapp-reminders.log'))
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('WhatsApp reminders check completed successfully');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('WhatsApp reminders check failed');
                 });

        // ✅ WORKING: Update overdue queues setiap 5 menit
        $schedule->command('queue:update-overdue')
                 ->everyFiveMinutes()
                 ->between('00:00', '23:59') // Testing: allow all hours
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/queue-updates.log'))
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('Queue overdue update completed successfully');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('Queue overdue update failed');
                 });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}