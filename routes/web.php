<?php
// File: routes/web.php - ADD route untuk realtime estimation

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ProfileController;
use App\Services\SessionManager;

// Halaman Utama
Route::get('/', fn () => view('welcome'))->name('welcome');

// Auth: Login & Register
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->name('logout');
});

Route::controller(RegisterController::class)->group(function () {
    Route::get('/register', 'showRegisterForm')->name('register');
    Route::post('/register', 'register');
});

// Password Reset Routes
Route::middleware('guest:web')->group(function () {
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});


Route::middleware(['auth:web', 'role.user'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/panduan-user', function () {
        return view('panduan.user-guide');
    })->name('panduan.user');
    
    // ✅ session api
    Route::prefix('api/antrian')->name('api.antrian.')->group(function () {
        Route::get('/available-sessions', [AntrianController::class, 'getAvailableSessions'])
             ->name('available-sessions');
        
        Route::post('/validate-doctor-session', [AntrianController::class, 'validateDoctorSession'])
             ->name('validate-doctor-session');
        
        Route::get('/preview-queue-number', [AntrianController::class, 'previewQueueNumber'])
             ->name('preview-queue-number');
        
        Route::get('/check-available-slots', [AntrianController::class, 'checkAvailableSlots'])
             ->name('check-available-slots');
    });

    
    Route::get('/editprofile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    
    Route::prefix('riwayatkunjungan')->name('riwayat.')->controller(RiwayatController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('statistics');
        Route::get('/statistics', 'statistics')->name('statistics');
        Route::get('/{queue}', 'show')->name('show');
    });

    // Antrian Routes
    Route::prefix('antrian')->name('antrian.')->controller(AntrianController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{queue}', 'show')->name('show');
        Route::get('/{queue}/edit', 'edit')->name('edit');
        Route::put('/{queue}', 'update')->name('update');
        Route::delete('/{queue}', 'destroy')->name('destroy');
        Route::get('/{queue}/print', 'print')->name('print');
    });

    
    Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/{schedule}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::get('/jadwaldokter', [DoctorController::class, 'jadwaldokter'])->name('jadwaldokter');
});

// Test Route untuk Multi-Session (Development only)
Route::get('/test-sessions', function () {
    return response()->json([
        'active_sessions' => SessionManager::getActiveSessions(),
        'cookies' => request()->cookies->all(),
        'session_id' => session()->getId(),
    ]);
});