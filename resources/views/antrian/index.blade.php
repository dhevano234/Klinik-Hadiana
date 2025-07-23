@extends('layouts.main')

@section('content')
<!-- Main Content -->
<main class="main-content">
    <!-- Page Header -->
    <div class="page-header animate">
        <h1><i class="fas fa-plus-circle"></i>Antrian Klinik</h1>
        <p>Ambil Nomor Antrian Untuk Melakukan Kunjungan</p>
    </div>

    {{-- Alert Success --}}
    @if (session('success'))
        <div class="alert alert-success animate">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
            <button type="button" class="alert-close">&times;</button>
        </div>
    @endif

    {{-- Alert Error --}}
    @if ($errors->any())
        <div class="alert alert-danger animate">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Error:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="alert-close">&times;</button>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="action-buttons animate">
        <a href="{{ route('antrian.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ambil Antrian Baru
        </a>
    </div>

    <!-- Antrian Terbaru User -->
    @if ($antrianTerbaru)
        <div class="content-card animate">
            <div class="card-header">
                <i class="fas fa-clock" style="color: #3498db;"></i>
                <h5>Antrian Terbaru Anda</h5>
            </div>
            
            <div class="antrian-item">
                <div class="antrian-info">
                    <div class="antrian-number">
                        <span class="badge badge-primary">{{ $antrianTerbaru->number }}</span>
                    </div>
                    <div class="antrian-details">
                        <h6>{{ $antrianTerbaru->user->name ?? '-' }}</h6>
                        <p><i class="fas fa-map-marker-alt"></i> {{ $antrianTerbaru->user->address ?? 'Alamat belum diisi' }}</p>
                        <div class="detail-row">
                            <span><i class="fas fa-venus-mars"></i> {{ $antrianTerbaru->user->gender ?? 'Belum diisi' }}</span>
                            <span><i class="fas fa-phone"></i> {{ $antrianTerbaru->user->phone ?? 'Belum diisi' }}</span>
                        </div>
                        <div class="detail-row">
                            <span><i class="fas fa-id-card"></i> {{ $antrianTerbaru->user->nomor_ktp ?? 'Belum diisi' }}</span>
                        </div>
                        {{-- ✅ TAMBAHAN: Informasi Nomor Rekam Medis --}}
                        <div class="detail-row">
                            <span><i class="fas fa-file-medical"></i> No. RM: {{ $antrianTerbaru->user->medical_record_number ?? 'Belum ada' }}</span>
                        </div>
                        {{-- ✅ TAMBAHAN: Tanggal Antrian dengan styling konsisten --}}
                        <div class="detail-row tanggal-antrian-highlight">
                            <span class="tanggal-antrian-info">
                                <i class="fas fa-calendar-day"></i> 
                                <strong>Tanggal Antrian: {{ $antrianTerbaru->tanggal_antrian ? $antrianTerbaru->tanggal_antrian->format('d F Y') : 'Tidak diketahui' }}</strong>
                            </span>
                        </div>
                        {{-- ✅ TAMBAHAN: Nama Dokter di bagian kiri --}}
                        @if(isset($antrianTerbaru->doctor_name) || isset($antrianTerbaru->doctor->name))
                        <div class="detail-row">
                            <span class="doctor-info-left">
                                <i class="fas fa-user-md"></i> 
                                <strong>Dokter: {{ $antrianTerbaru->doctor_name ?? $antrianTerbaru->doctor->name ?? 'Dokter belum ditentukan' }}</strong>
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="antrian-meta">
                    <div class="poli-info">
                        <span class="badge badge-info">
                            {{ $antrianTerbaru->service->name ?? 'Layanan tidak ditemukan' }}
                        </span>
                        <small>Layanan Klinik</small>
                    </div>
                    
                    {{-- ✅ PERBAIKAN WAKTU: Tambahkan jam ambil antrian --}}
                    <div class="time-info">
                        <div class="date-info">
                            <small><i class="fas fa-calendar"></i> Tanggal Input: {{ $antrianTerbaru->created_at->setTimezone('Asia/Jakarta')->format('d F Y') }}</small>
                        </div>
                        <div class="time-detail">
                            <small><i class="fas fa-clock"></i> Jam Input: {{ $antrianTerbaru->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</small>
                        </div>
                        {{-- Tampilkan waktu dipanggil jika ada --}}
                        @if($antrianTerbaru->called_at)
                        <div class="time-detail">
                            <small><i class="fas fa-bell"></i> Dipanggil: {{ $antrianTerbaru->called_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</small>
                        </div>
                        @endif
                        {{-- Tampilkan waktu selesai jika ada --}}
                        @if($antrianTerbaru->finished_at)
                        <div class="time-detail">
                            <small><i class="fas fa-check-circle"></i> Selesai: {{ $antrianTerbaru->finished_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</small>
                        </div>
                        @endif
                    </div>
                    
                    <div class="status-info">
                        <span class="badge badge-{{ $antrianTerbaru->status_badge }}">
                            {{ $antrianTerbaru->status_label }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="antrian-actions">
                @if($antrianTerbaru->canEdit())
                    <a href="{{ route('antrian.edit', $antrianTerbaru->id) }}" 
                       class="btn btn-warning btn-sm" 
                       title="Edit Antrian">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endif
                
                @if($antrianTerbaru->canPrint())
                <a href="{{ route('antrian.print', $antrianTerbaru->id) }}" 
                   class="btn btn-info btn-sm" 
                   target="_blank"
                   title="Print Tiket">
                    <i class="fas fa-print"></i> Print
                </a>
                @endif
                
                @if($antrianTerbaru->canCancel())
                    <form action="{{ route('antrian.destroy', $antrianTerbaru->id) }}" 
                          method="POST" 
                          style="display: inline;"
                          onsubmit="return confirm('Yakin ingin membatalkan antrian?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="btn btn-danger btn-sm"
                                title="Batalkan Antrian">
                            <i class="fas fa-trash"></i> Batalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <!-- Jika tidak ada antrian -->
        <div class="content-card animate">
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Belum Ada Antrian</h5>
                <p class="text-muted">Anda belum memiliki antrian hari ini. Silakan ambil antrian baru untuk melakukan kunjungan.</p>
                <a href="{{ route('antrian.create') }}" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Ambil Antrian Sekarang
                </a>
            </div>
        </div>
    @endif
</main>

<!-- Additional Styles -->
<style>
.page-header {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.page-header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
}

.page-header p {
    color: #7f8c8d;
    margin: 0;
}

.action-buttons {
    margin-bottom: 30px;
}

.alert {
    background: white;
    border: none;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.alert-success {
    border-left: 5px solid #27ae60;
    color: #2e7d32;
}

.alert-danger {
    border-left: 5px solid #e74c3c;
    color: #d32f2f;
}

.alert-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #7f8c8d;
}

.content-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #2c3e50;
}

.antrian-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 20px;
}

.antrian-info {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.antrian-number {
    min-width: 60px;
}

.antrian-details h6 {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.antrian-details p {
    color: #7f8c8d;
    margin-bottom: 8px;
    font-size: 14px;
}

.detail-row {
    display: flex;
    gap: 20px;
    margin-bottom: 5px;
}

.detail-row span {
    color: #7f8c8d;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.detail-row i {
    width: 15px;
    margin-right: 5px;
    flex-shrink: 0;
}

/* ✅ TAMBAHAN: Styling khusus untuk tanggal antrian */
.tanggal-antrian-highlight {
    margin-top: 8px !important;
    margin-bottom: 8px !important;
}

.tanggal-antrian-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
    color: #1976d2 !important;
    padding: 8px 12px !important;
    border-radius: 8px !important;
    border-left: 4px solid #2196f3 !important;
    font-size: 13px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15) !important;
    border: 1px solid rgba(33, 150, 243, 0.2) !important;
}

.tanggal-antrian-info i {
    color: #1976d2 !important;
    font-size: 14px !important;
    width: 16px !important;
}

.tanggal-antrian-info strong {
    font-weight: 600;
    color: #1976d2;
}

.antrian-meta {
    text-align: right;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 200px;
}

.poli-info small {
    display: block;
    color: #7f8c8d;
    margin-top: 5px;
}

/* ✅ PERBAIKAN: Styling untuk informasi waktu yang lebih baik */
.time-info {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    border-left: 3px solid #3498db;
    margin: 5px 0;
}

/* ✅ TAMBAHAN: Styling khusus untuk tanggal antrian */
.tanggal-antrian-highlight {
    margin-top: 8px !important;
    margin-bottom: 8px !important;
}

.tanggal-antrian-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
    color: #1976d2 !important;
    padding: 8px 12px !important;
    border-radius: 8px !important;
    border-left: 4px solid #2196f3 !important;
    font-size: 13px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15) !important;
    border: 1px solid rgba(33, 150, 243, 0.2) !important;
}

.tanggal-antrian-info i {
    color: #1976d2 !important;
    font-size: 14px !important;
    width: 16px !important;
}

.tanggal-antrian-info strong {
    font-weight: 600;
    color: #1976d2;
}

/* ✅ TAMBAHAN: Styling untuk nama dokter di bagian kiri */
.doctor-info-left {
    background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%) !important;
    color: #2e7d32 !important;
    padding: 8px 12px !important;
    border-radius: 8px !important;
    border-left: 4px solid #4caf50 !important;
    font-size: 13px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15) !important;
    border: 1px solid rgba(76, 175, 80, 0.2) !important;
}

.doctor-info-left i {
    color: #2e7d32 !important;
    font-size: 14px !important;
    width: 16px !important;
}

.doctor-info-left strong {
    font-weight: 600;
    color: #2e7d32;
}

.date-info, .time-detail {
    margin-bottom: 3px;
}

.date-info small {
    font-weight: 600;
    color: #2c3e50;
}

.time-detail small {
    color: #7f8c8d;
    display: block;
}

.time-detail i {
    width: 12px;
    margin-right: 5px;
    color: #3498db;
}

.status-info {
    margin-top: 5px;
}

.antrian-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
}

.btn-sm {
    padding: 8px 15px;
    font-size: 13px;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
}

.btn-secondary {
    background: #ecf0f1;
    color: #7f8c8d;
}

.btn-warning {
    background: linear-gradient(45deg, #f39c12, #e67e22);
    color: white;
}

.btn-info {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
}

.btn-danger {
    background: linear-gradient(45deg, #e74c3c, #c0392b);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.badge-primary { 
    background: #e3f2fd; 
    color: #1976d2; 
}

.badge-success { 
    background: #e8f5e8; 
    color: #2e7d32; 
}

.badge-warning { 
    background: #fff3e0; 
    color: #f57c00; 
}

.badge-danger { 
    background: #ffebee; 
    color: #d32f2f; 
}

.badge-info { 
    background: #e3f2fd; 
    color: #1976d2; 
}

@media (max-width: 768px) {
    .antrian-item {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .antrian-meta {
        text-align: left;
        min-width: auto;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .antrian-actions {
        justify-content: center;
    }

    .time-info {
        margin: 10px 0;
    }

    /* ✅ Responsive untuk tanggal antrian */
    .tanggal-antrian-info {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
    }

    .date-antrian-meta {
        text-align: left;
    }

    /* ✅ Responsive untuk tanggal antrian dan dokter */
    .tanggal-antrian-info,
    .doctor-info-left {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
    }
}

/* ============================================================================ */
/* ANIMATIONS */
/* ============================================================================ */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate {
    animation: fadeIn 0.5s ease-out;
}

.animate:nth-child(1) { animation-delay: 0.1s; }
.animate:nth-child(2) { animation-delay: 0.2s; }
.animate:nth-child(3) { animation-delay: 0.3s; }

/* ✅ TAMBAHAN: Animasi hover untuk elemen interaktif */
.tanggal-antrian-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.25) !important;
    transition: all 0.3s ease;
}

.doctor-info-left:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.25) !important;
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close alert functionality
    document.querySelectorAll('.alert-close').forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.style.display = 'none';
        });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        });
    }, 5000);
});
</script>
@endsection