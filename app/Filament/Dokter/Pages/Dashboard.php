<?php
// ================================================================
// LOKASI 1: app/Filament/Dokter/Pages/Dashboard.php
// ================================================================

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.dokter.pages.dashboard';
    
    // ✅ UBAH DI SINI: Label yang muncul di Navigation Sidebar
    protected static ?string $navigationLabel = 'Dasbor'; // ⬅️ UBAH INI ke apapun yang Anda mau
    
    // ✅ UBAH DI SINI: Title yang muncul di halaman
    protected static ?string $title = 'Dasbor'; // ⬅️ UBAH INI juga
    
    // ✅ UBAH DI SINI: Icon navigation (opsional)
    protected static ?string $navigationIcon = 'heroicon-o-home'; // ⬅️ UBAH ICON jika perlu

    public function getViewData(): array
    {
        return [
            'user' => Auth::user(),
        ];
    }
}