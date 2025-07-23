{{-- File: resources/views/jadwaldokter.blade.php --}}
{{-- SIMPLE VERSION: Same design, simplified logic --}}

@extends('layouts.main')

@section('title', 'Jadwal Dokter')

@section('content')
<main class="main-content">
    <!-- Page Header -->
    <div class="page-header animate">
        <h1><i class="fas fa-user-md"></i> Jadwal Dokter</h1>
        <p>Lihat jadwal praktik dokter di Klinik Pratama Hadiana Sehat</p>
    </div>

    <!-- Doctor Cards Grid -->
    <div class="doctors-grid">
        @forelse($doctors as $doctor)
            <div class="doctor-card animate">
                <!-- Doctor Photo -->
                <div class="doctor-photo">
                    @if($doctor['foto'])
                        <img src="{{ asset('storage/' . $doctor['foto']) }}" 
                             alt="Foto {{ $doctor['doctor_name'] }}"
                             class="doctor-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="doctor-placeholder" style="display: none;">
                            <i class="fas fa-user-md"></i>
                        </div>
                    @else
                        <div class="doctor-placeholder">
                            <i class="fas fa-user-md"></i>
                        </div>
                    @endif
                    
                    <!-- ✅ SIMPLE Status Badge -->
                    @php
                        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
                        $currentTime = now()->format('H:i');
                        $isPracticingToday = in_array($today, $doctor['all_days']->toArray());
                        
                        // Simple status logic
                        if (!$isPracticingToday) {
                            $status = 'not_today';
                            $label = 'Tidak Praktik';
                            $class = 'status-inactive';
                        } else {
                            $startTime = $doctor['schedules']->first()->start_time->format('H:i');
                            $endTime = $doctor['schedules']->first()->end_time->format('H:i');
                            
                            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                                $status = 'practicing';
                                $label = 'Sedang Praktik';
                                $class = 'status-active';
                            } elseif ($currentTime < $startTime) {
                                $status = 'upcoming';
                                $label = 'Akan Praktik';
                                $class = 'status-upcoming';
                            } else {
                                $status = 'finished';
                                $label = 'Praktik Selesai';
                                $class = 'status-finished';
                            }
                        }
                    @endphp
                    
                    <div class="status-badge {{ $class }}">
                        {{ $label }}
                    </div>
                </div>

                <!-- Doctor Info -->
                <div class="doctor-info">
                    <h3 class="doctor-name">{{ $doctor['doctor_name'] }}</h3>
                    
                    <div class="doctor-specialty">
                        <i class="fas fa-stethoscope"></i>
                        <span>{{ $doctor['service']->name }}</span>
                    </div>

                    <div class="doctor-schedule">
                        <div class="schedule-section">
                            <h4><i class="fas fa-calendar-alt"></i> Hari Praktik</h4>
                            <div class="days-list">
                                @foreach($doctor['all_days'] as $day)
                                    @php
                                        $dayNames = [
                                            'monday' => 'Senin',
                                            'tuesday' => 'Selasa', 
                                            'wednesday' => 'Rabu',
                                            'thursday' => 'Kamis',
                                            'friday' => 'Jumat',
                                            'saturday' => 'Sabtu',
                                            'sunday' => 'Minggu'
                                        ];
                                        $isToday = $today === $day;
                                    @endphp
                                    <span class="day-badge {{ $isToday ? 'day-today' : '' }}">
                                        {{ $dayNames[$day] ?? ucfirst($day) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="schedule-section">
                            <h4><i class="fas fa-clock"></i> Jam Praktik</h4>
                            <div class="time-info">
                                {{ $doctor['time_range'] }}
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SIMPLE Status Info -->
                    <div class="doctor-status-info">
                        @if($status === 'practicing')
                            <div class="status-info-active">
                                <i class="fas fa-check-circle"></i>
                                <span>Sedang Praktik Hari Ini</span>
                            </div>
                        @elseif($status === 'upcoming')
                            <div class="status-info-upcoming">
                                <i class="fas fa-clock"></i>
                                <span>Akan Praktik Hari Ini</span>
                            </div>
                        @elseif($status === 'finished')
                            <div class="status-info-finished">
                                <i class="fas fa-moon"></i>
                                <span>Praktik Hari Ini Selesai</span>
                            </div>
                        @else
                            <div class="status-info-inactive">
                                <i class="fas fa-calendar-times"></i>
                                <span>Tidak Praktik Hari Ini</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>Belum Ada Jadwal Dokter</h3>
                <p>Jadwal dokter belum tersedia. Silakan hubungi klinik untuk informasi lebih lanjut.</p>
            </div>
        @endforelse
    </div>

    <!-- Info Section -->
    <div class="info-section animate">
        <div class="info-card">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-content">
                <h4>Informasi Penting</h4>
                <ul>
                    <li>Jadwal dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya</li>
                    <li>Harap datang 15 menit sebelum jam praktik</li>
                    <li>Untuk informasi lebih lanjut, hubungi: <strong>0896-7878-4190</strong></li>
                    <li>Untuk membuat antrian, silakan gunakan menu <strong>"Buat Antrian"</strong> di sidebar</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<style>
/* Same design as before, but simplified status classes */
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

/* Doctor Cards Grid */
.doctors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.doctor-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid #f1f2f6;
}

.doctor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

/* Doctor Photo Section */
.doctor-photo {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.doctor-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.doctor-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid white;
    backdrop-filter: blur(10px);
}

.doctor-placeholder i {
    font-size: 3rem;
    color: white;
    opacity: 0.8;
}

/* ✅ SIMPLE Status Badge */
.status-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: #2ecc71;
    color: white;
    animation: pulse 2s infinite;
}

.status-upcoming {
    background: #f39c12;
    color: white;
}

.status-finished {
    background: #3498db;
    color: white;
}

.status-inactive {
    background: rgba(0,0,0,0.3);
    color: white;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); }
    100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
}

/* Doctor Info Section */
.doctor-info {
    padding: 25px;
}

.doctor-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 10px;
    text-align: center;
}

.doctor-specialty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #3498db;
    font-weight: 500;
    margin-bottom: 20px;
    padding: 8px 15px;
    background: #ebf3fd;
    border-radius: 20px;
    font-size: 14px;
}

.doctor-schedule {
    margin-bottom: 20px;
}

.schedule-section {
    margin-bottom: 15px;
}

.schedule-section h4 {
    font-size: 14px;
    font-weight: 600;
    color: #34495e;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.days-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.day-badge {
    background: #f8f9fa;
    color: #6c757d;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.day-today {
    background: #2ecc71;
    color: white;
    font-weight: 600;
}

.time-info {
    background: #2c3e50;
    color: white;
    padding: 10px 15px;
    border-radius: 10px;
    text-align: center;
    font-weight: 600;
    font-size: 15px;
}

/* ✅ SIMPLE Status Info */
.doctor-status-info {
    margin-top: 20px;
    text-align: center;
}

.status-info-active {
    background: #d4edda;
    color: #155724;
    padding: 12px 15px;
    border-radius: 10px;
    border: 1px solid #c3e6cb;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
}

.status-info-upcoming {
    background: #fff3cd;
    color: #856404;
    padding: 12px 15px;
    border-radius: 10px;
    border: 1px solid #ffeaa7;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
}

.status-info-finished {
    background: #d1ecf1;
    color: #0c5460;
    padding: 12px 15px;
    border-radius: 10px;
    border: 1px solid #bee5eb;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
}

.status-info-inactive {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 10px;
    border: 1px solid #f5c6cb;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
}

/* Info Section */
.info-section {
    margin-top: 40px;
}

.info-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 25px;
    border-radius: 15px;
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.info-icon {
    font-size: 2rem;
    opacity: 0.9;
}

.info-content h4 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.info-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-content li {
    margin-bottom: 8px;
    padding-left: 20px;
    position: relative;
}

.info-content li::before {
    content: '✓';
    position: absolute;
    left: 0;
    font-weight: bold;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #2c3e50;
}

/* Responsive Design */
@media (max-width: 768px) {
    .doctors-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .doctor-card {
        border-radius: 15px;
    }
    
    .doctor-photo {
        height: 180px;
    }
    
    .doctor-image,
    .doctor-placeholder {
        width: 100px;
        height: 100px;
    }
    
    .doctor-placeholder i {
        font-size: 2.5rem;
    }
    
    .doctor-info {
        padding: 20px;
    }
    
    .doctor-name {
        font-size: 1.2rem;
    }
    
    .info-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .page-header {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 15px;
    }
    
    .doctors-grid {
        gap: 15px;
    }
    
    .doctor-photo {
        height: 150px;
    }
    
    .doctor-image,
    .doctor-placeholder {
        width: 80px;
        height: 80px;
    }
    
    .doctor-placeholder i {
        font-size: 2rem;
    }
    
    .status-badge {
        font-size: 10px;
        padding: 4px 8px;
    }
    
    .doctor-info {
        padding: 15px;
    }
    
    .days-list {
        justify-content: center;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate {
    animation: fadeIn 0.6s ease-out;
}

.animate:nth-child(1) { animation-delay: 0.1s; }
.animate:nth-child(2) { animation-delay: 0.2s; }
.animate:nth-child(3) { animation-delay: 0.3s; }
.animate:nth-child(4) { animation-delay: 0.4s; }
</style>
@endsection