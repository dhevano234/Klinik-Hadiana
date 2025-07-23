<?php
// File: app/Providers/Filament/AdminPanelProvider.php
// FINAL: AdminPanelProvider dengan Branding, Patient Management, dan DailyQuota Integration

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('admin') // ✅ Set guard admin
            ->colors([
                'primary' => Color::Amber,
            ])
            // ✅ BRANDING - GANTI NAMA ATAU LOGO DI SINI
            ->brandName('Klinik Pratama Hadiana Sehat') // ⬅️ Tampilkan tulisan
            // ->brandLogo(asset('assets/img/logo/logoklinikpratama.png')) // ⬅️ (Opsional) Ganti ke logo gambar

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\AdminDashboard::class,
            ])
            ->resources([
                // ✅ REGISTER RESOURCES: Urutan sesuai prioritas di dashboard
                \App\Filament\Resources\PatientManagementResource::class,  // Data Pasien
                \App\Filament\Resources\WeeklyQuotaResource::class,         // ✅ NEW: Kuota Antrian
                \App\Filament\Resources\CounterResource::class,            // Kelola Loket
                \App\Filament\Resources\QueueResource::class,              // Antrian
                \App\Filament\Resources\DoctorScheduleResource::class,     // Jadwal Dokter
                \App\Filament\Resources\ServiceResource::class,            // Layanan
                \App\Filament\Resources\MedicalRecordResource::class,      // Medical Records
                // Resource lain akan di-discover otomatis jika ada
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets akan di-discover otomatis
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureAdminRole::class,
            ])
            ->spa(); // ✅ OPTIONAL: Enable SPA mode untuk better performance
    }
}
