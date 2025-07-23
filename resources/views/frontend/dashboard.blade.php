@extends('layouts.main')

@section('title', 'Dashboard')

@section('content')
<main class="main-content">
    <!-- Welcome Card -->
    <div class="welcome-card animate">
        <h1>Selamat Datang, {{ Auth::user()->name }}! </h1>
        <p>Monitoring Antrian Dengan Mudah</p>
    </div>
    
    <!-- ✅ ANTRIAN AKTIF CARD dengan estimasi realtime (tidak berubah) -->
    @if($antrianAktif)
    <div class="antrian-aktif-card animate" id="antrianAktifCard">
        <div class="card-header">
            <h5><i class="fas fa-clock" style="color: #f39c12;"></i> Antrian Aktif Anda</h5>
            <div class="status-badge status-{{ strtolower($antrianAktif->status) }}">
                {{ ucfirst($antrianAktif->status) }}
            </div>
        </div>
        
        <div class="antrian-content">
            <div class="antrian-info">
                <div class="queue-number">{{ $antrianAktif->number }}</div>
                <div class="queue-details">
                    <p><strong>Layanan:</strong> {{ $antrianAktif->service->name ?? 'Unknown' }}</p>
                    
                    @if($antrianAktif->doctor_id && $antrianAktif->doctorSchedule)
                        <p><strong>Dokter:</strong> {{ $antrianAktif->doctorSchedule->doctor_name ?? 'Unknown' }}</p>
                    @endif
                    
                    <p><strong>Tanggal:</strong> {{ $antrianAktif->tanggal_antrian ? $antrianAktif->tanggal_antrian->format('d F Y') : $antrianAktif->created_at->format('d F Y') }}</p>
                    <p><strong>Jam Ambil:</strong> {{ $antrianAktif->created_at->format('H:i') }} WIB</p>
                </div>
            </div>
            
            @if($antrianAktif->status === 'waiting' && $estimasiInfo)
            <div class="estimasi-card" id="estimasiCard">
                <h6><i class="fas fa-hourglass-half"></i> Estimasi Waktu Tunggu</h6>
                <div class="estimasi-content">
                    <div class="estimasi-time" id="estimasiTime">
                        <span class="time-value">{{ $estimasiInfo['estimasi_menit'] }}</span>
                        <span class="time-unit">menit</span>
                    </div>
                    <div class="estimasi-details">
                        <p>📍 Posisi dalam antrian: <strong id="posisiAntrian">{{ $estimasiInfo['posisi'] }}</strong></p>
                        <p>⏰ Estimasi dipanggil: <strong id="waktuEstimasi">{{ $estimasiInfo['waktu_estimasi'] }}</strong> WIB</p>
                        <p>👥 Antrian di depan: <strong id="antrianDidepan">{{ $estimasiInfo['antrian_didepan'] }}</strong> orang</p>
                    </div>
                    <div class="estimasi-status status-{{ $estimasiInfo['status'] }}" id="estimasiStatus">
                        @if($estimasiInfo['status'] === 'delayed')
                            <i class="fas fa-exclamation-triangle"></i> Terlambat dari estimasi
                        @else
                            <i class="fas fa-check-circle"></i> Dalam estimasi waktu
                        @endif
                    </div>
                </div>
            </div>
            @elseif($antrianAktif->status === 'serving')
            <div class="serving-card">
                <h6><i class="fas fa-user-md" style="color: #27ae60;"></i> Sedang Dilayani</h6>
                <p>Silakan menuju ke loket yang telah ditentukan</p>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="no-antrian-card animate">
        <div class="card-content">
            <i class="fas fa-plus-circle" style="font-size: 3rem; color: #3498db; margin-bottom: 15px;"></i>
            <h5>Belum Ada Antrian Aktif</h5>
            <p>Buat antrian baru untuk mulai mendapatkan layanan</p>
            <a href="/antrian/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Antrian
            </a>
        </div>
    </div>
    @endif
    
    <!-- ✅ UPDATED: Stats Row dengan kuota dokter menggantikan total pasien -->
    <div class="stats-row">
        <!-- Total Antrian Hari Ini (tetap) -->
        <div class="stat-card blue animate">
            <div class="stat-icon blue">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number">{{ $stats['antrian_hari_ini'] }}</div>
            <div class="stat-label">Antrian Hari Ini</div>
        </div>
        
        <!-- ✅ NEW: Kuota Dokter Cards (menggantikan total pasien & dokter aktif) -->
        @if($quotaInfo['has_quotas'])
            @foreach($quotaInfo['quotas']->take(2) as $quota)
            <div class="stat-card quota-card {{ $quota['status_color'] }} animate">
                <div class="stat-icon {{ $quota['status_color'] }}">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-number">{{ $quota['formatted_quota'] }}</div>
                <div class="stat-label">
                    <div class="doctor-name">{{ $quota['doctor_name'] }}</div>
                    <div class="quota-status">{{ $quota['status_label'] }}</div>
                </div>
                <div class="quota-bar">
                    <div class="quota-progress" style="width: {{ $quota['usage_percentage'] }}%; background-color: var(--color-{{ $quota['status_color'] }});"></div>
                </div>
            </div>
            @endforeach
            
            <!-- Jika ada lebih dari 2 dokter, tampilkan ringkasan -->
            @if($quotaInfo['quotas']->count() > 2)
            <div class="stat-card summary-card animate">
                <div class="stat-icon gray">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">+{{ $quotaInfo['quotas']->count() - 2 }}</div>
                <div class="stat-label">
                    <div class="doctor-name">Dokter Lainnya</div>
                    <div class="quota-status">{{ $quotaInfo['quotas']->skip(2)->sum('available_quota') }} tersisa</div>
                </div>
            </div>
            @endif
        @else
            <!-- Fallback jika tidak ada quota hari ini -->
            <div class="stat-card gray animate">
                <div class="stat-icon gray">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-number">0</div>
                <div class="stat-label">
                    <div class="doctor-name">Belum Ada Kuota</div>
                    <div class="quota-status">Hari Ini</div>
                </div>
            </div>
            
            <!-- Placeholder untuk grid balance -->
            <div class="stat-card gray animate">
                <div class="stat-icon gray">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="stat-number">-</div>
                <div class="stat-label">
                    <div class="doctor-name">Info Kuota</div>
                    <div class="quota-status">Belum tersedia</div>
                </div>
            </div>
        @endif
    </div>
</main>

<style>
/* ✅ CSS Variables untuk warna */
:root {
    --color-success: #27ae60;
    --color-warning: #f39c12;
    --color-danger: #e74c3c;
    --color-info: #3498db;
    --color-gray: #95a5a6;
}

/* ✅ STYLES untuk antrian aktif card */
.antrian-aktif-card, .no-antrian-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border-left: 5px solid #3498db;
}

.antrian-aktif-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.antrian-aktif-card .card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-waiting {
    background: #fef3cd;
    color: #856404;
}

.status-serving {
    background: #d4edda;
    color: #155724;
}

.antrian-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    align-items: start;
}

.antrian-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.queue-number {
    font-size: 3rem;
    font-weight: bold;
    color: #3498db;
    background: #ecf0f1;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    min-width: 100px;
}

.queue-details p {
    margin: 8px 0;
    color: #7f8c8d;
}

.queue-details strong {
    color: #2c3e50;
}

/* ✅ STYLES untuk estimasi card */
.estimasi-card, .serving-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #3498db;
    position: relative;
}

.estimasi-card h6, .serving-card h6 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.estimasi-time {
    text-align: center;
    margin-bottom: 15px;
    position: relative;
}

.time-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #3498db;
    transition: all 0.3s ease;
}

.time-unit {
    font-size: 1.2rem;
    color: #7f8c8d;
    margin-left: 5px;
}

.estimasi-details p {
    margin: 8px 0;
    font-size: 14px;
    color: #7f8c8d;
}

.estimasi-details strong {
    color: #2c3e50;
}

.estimasi-status {
    margin-top: 15px;
    padding: 10px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
}

.estimasi-status.status-on_time {
    background: #d4edda;
    color: #155724;
}

.estimasi-status.status-delayed {
    background: #f8d7da;
    color: #721c24;
}

.no-antrian-card .card-content {
    text-align: center;
    padding: 30px;
}

.no-antrian-card h5 {
    color: #2c3e50;
    margin: 15px 0 10px 0;
}

.no-antrian-card p {
    color: #7f8c8d;
    margin-bottom: 20px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

/* ✅ UPDATED: Stats row dengan quota cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.quota-card {
    border-left: 4px solid var(--color-info);
}

.stat-card.quota-card.success {
    border-left-color: var(--color-success);
}

.stat-card.quota-card.warning {
    border-left-color: var(--color-warning);
}

.stat-card.quota-card.danger {
    border-left-color: var(--color-danger);
}

.stat-card.summary-card {
    border-left: 4px solid var(--color-gray);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    font-size: 20px;
    color: white;
}

.stat-icon.blue { background: #3498db; }
.stat-icon.success { background: var(--color-success); }
.stat-icon.warning { background: var(--color-warning); }
.stat-icon.danger { background: var(--color-danger); }
.stat-icon.gray { background: var(--color-gray); }

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 14px;
}

.stat-label .doctor-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 2px;
}

.stat-label .quota-status {
    font-size: 12px;
    color: #7f8c8d;
}

.quota-bar {
    margin-top: 10px;
    height: 4px;
    background: #ecf0f1;
    border-radius: 2px;
    overflow: hidden;
}

.quota-progress {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
}

/* ✅ CSS ANIMATIONS */
@keyframes fadeInOut {
    0%, 100% { opacity: 0; transform: scale(0.8); }
    15%, 85% { opacity: 1; transform: scale(1); }
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.updating {
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

/* ✅ RESPONSIVE */
@media (max-width: 768px) {
    .antrian-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .antrian-info {
        flex-direction: column;
        text-align: center;
    }
    
    .queue-number {
        font-size: 2.5rem;
        padding: 15px;
        min-width: 80px;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .stat-card {
        padding: 15px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>