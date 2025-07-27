<?php
// File: app/Providers/AppServiceProvider.php
// Load audio system yang sesuai untuk setiap panel

namespace App\Providers;

use App\Services\QueueService;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        $this->app->singleton(QueueService::class, function ($app) {
            return new QueueService();
        });
    }

    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');

        
        FilamentAsset::register([
            Js::make('thermal-printer', asset('js/thermal-printer.js')),
            Js::make('queue-audio', asset('js/queue-audio.js')), 
        ]);
        
        
    }   
}