{{-- File: resources/views/filament/dokter/pages/dashboard.blade.php --}}
{{-- UPDATED: Jadwal Dokter berdasarkan user yang login --}}

<x-filament-panels::page>
<div class="space-y-6">
    {{-- Welcome Section - Simple seperti Admin --}}
    <div class="bg-white rounded-lg shadow border p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                {{-- Avatar Simple --}}
                <div class="w-16 h-16 bg-gray-900 rounded-full flex items-center justify-center">
                    <span class="text-xl font-semibold text-white">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </span>
                </div>

                {{-- Welcome Text --}}
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Selamat Datang
                    </h1>
                    <p class="text-lg text-gray-600">
                        {{ $user->name }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Panel Dokter - {{ now()->format('l, d F Y') }}
                    </p>
                </div>
            </div>

            {{-- Logout Button Simple --}}
            <div>
                <form action="{{ route('filament.dokter.auth.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ✅ UPDATED: Jadwal Dokter Hari Ini berdasarkan user yang login --}}
    @php
        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
        
        // ✅ METODE 1: Cari berdasarkan doctor_id (jika kolom ada)
        $mySchedule = \App\Models\DoctorSchedule::where('doctor_id', $user->id)
            ->where('is_active', true)
            ->where(function($query) use ($today) {
                $query->whereJsonContains('days', $today);
            })
            ->with('service')
            ->first();
        
        // ✅ METODE 2: Fallback ke doctor_name jika doctor_id tidak ada
        if (!$mySchedule) {
            $mySchedule = \App\Models\DoctorSchedule::where('doctor_name', $user->name)
                ->where('is_active', true)
                ->where(function($query) use ($today) {
                    $query->whereJsonContains('days', $today);
                })
                ->with('service')
                ->first();
        }
        
        // ✅ METODE 3: Fallback ke day_of_week jika menggunakan kolom tunggal
        if (!$mySchedule) {
            $mySchedule = \App\Models\DoctorSchedule::where('doctor_name', $user->name)
                ->where('is_active', true)
                ->where('day_of_week', $today)
                ->with('service')
                ->first();
        }
        
        // Konversi nama hari ke Indonesia
        $dayNames = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu'
        ];
        
        $todayName = $dayNames[$today] ?? ucfirst($today);
        
        // ✅ TAMBAH: Hitung statistik antrian dokter hari ini
        $todayQueues = \App\Models\Queue::where('doctor_id', $user->id)
            ->whereDate('tanggal_antrian', today())
            ->get();
            
        // ✅ FALLBACK: Jika tidak ada doctor_id, cari berdasarkan doctor_name
        if ($todayQueues->isEmpty()) {
            $todayQueues = \App\Models\Queue::whereHas('doctorSchedule', function($query) use ($user) {
                $query->where('doctor_name', $user->name);
            })
            ->whereDate('tanggal_antrian', today())
            ->get();
        }
        
        $waitingCount = $todayQueues->where('status', 'waiting')->count();
        $servingCount = $todayQueues->where('status', 'serving')->count();
        $completedCount = $todayQueues->where('status', 'completed')->count();
        $totalQueues = $todayQueues->count();
        
        // ✅ TAMBAH: Hitung progress jadwal
        if ($mySchedule) {
            $currentTime = now();
            $startTime = $mySchedule->start_time;
            $endTime = $mySchedule->end_time;
            
            // Hitung progress berdasarkan waktu
            $totalMinutes = $startTime->diffInMinutes($endTime);
            $elapsedMinutes = $currentTime->format('H:i') >= $startTime->format('H:i') 
                ? $startTime->diffInMinutes($currentTime) 
                : 0;
            $progressPercentage = $totalMinutes > 0 ? min(($elapsedMinutes / $totalMinutes) * 100, 100) : 0;
            
            $mySchedule->time_range = $startTime->format('H:i') . ' - ' . $endTime->format('H:i');
            $mySchedule->day_name = $todayName;
        }
    @endphp

    {{-- ✅ UPDATED: Tampilan Jadwal yang Lebih Informatif --}}
    @if($mySchedule)
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-400 rounded-full mr-4"></div>
                    <div>
                        <h3 class="font-semibold text-green-800 text-lg">Jadwal Praktik Hari Ini</h3>
                        <div class="text-sm text-green-600 mt-1">
                            <strong>{{ $mySchedule->service->name }}</strong> • 
                            {{ $mySchedule->time_range }} • 
                            <span class="bg-green-100 px-2 py-1 rounded text-xs font-medium">{{ $mySchedule->day_name }}</span>
                        </div>
                        <div class="text-xs text-green-500 mt-1">
                            Dr. {{ $user->name }}
                        </div>
                    </div>
                </div>
                
                {{-- ✅ TAMBAH: Statistik Antrian Hari Ini --}}
                <div class="text-right">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-yellow-100 rounded-lg p-3">
                            <div class="text-lg font-bold text-yellow-800">{{ $waitingCount }}</div>
                            <div class="text-xs text-yellow-600">Menunggu</div>
                        </div>
                        <div class="bg-blue-100 rounded-lg p-3">
                            <div class="text-lg font-bold text-blue-800">{{ $servingCount }}</div>
                            <div class="text-xs text-blue-600">Sedang Dilayani</div>
                        </div>
                        <div class="bg-green-100 rounded-lg p-3">
                            <div class="text-lg font-bold text-green-800">{{ $completedCount }}</div>
                            <div class="text-xs text-green-600">Selesai</div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- ✅ TAMBAH: Progress Bar Jadwal --}}
            <div class="mt-4">
                <div class="flex justify-between text-xs text-green-600 mb-2">
                    <span class="font-medium">
                        @if($progressPercentage >= 100)
                            Jadwal Selesai
                        @elseif($progressPercentage > 0)
                            Sedang Berlangsung ({{ number_format($progressPercentage, 1) }}%)
                        @else
                            Belum Dimulai
                        @endif
                    </span>
                    
                </div>
                <div class="w-full bg-green-200 rounded-full h-3">
                    <div class="bg-green-500 h-3 rounded-full transition-all duration-500 flex items-center justify-end pr-2" 
                         style="width: {{ $progressPercentage }}%">
                        @if($progressPercentage > 20)
                            <div class="w-2 h-2 bg-white rounded-full"></div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- ✅ TAMBAH: Quick Actions --}}
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-green-600">
                    <span class="font-medium">Total Antrian Hari Ini: {{ $totalQueues }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-400 rounded-full mr-4"></div>
                    <div>
                        <h3 class="font-medium text-gray-600">Tidak Ada Jadwal Praktik Hari Ini</h3>
                        <div class="text-sm text-gray-500 mt-1">
                            <span class="bg-gray-100 px-2 py-1 rounded text-xs font-medium">{{ $todayName }}</span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            Dr. {{ $user->name }}
                        </div>
                    </div>
                </div>
                
                {{-- ✅ TAMBAH: Info jika tidak ada jadwal --}}
                <div class="text-right">
                    <div class="text-sm text-gray-500">
                        <span class="text-gray-400">Silakan hubungi admin untuk mengatur jadwal</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ✅ TAMBAH: Jadwal Minggu Ini (Preview) --}}
    @php
        // ✅ CARI JADWAL DENGAN MULTIPLE FALLBACK
        $weeklySchedules = \App\Models\DoctorSchedule::where('doctor_id', $user->id)
            ->where('is_active', true)
            ->with('service')
            ->get();
            
        // Fallback ke doctor_name jika doctor_id tidak ada
        if ($weeklySchedules->isEmpty()) {
            $weeklySchedules = \App\Models\DoctorSchedule::where('doctor_name', $user->name)
                ->where('is_active', true)
                ->with('service')
                ->get();
        }
    @endphp
    
    {{-- @if($weeklySchedules->isNotEmpty())
        <div class="bg-white rounded-lg shadow border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Jadwal Praktik Minggu Ini</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($weeklySchedules as $schedule)
                    @php
                        // ✅ HANDLE MULTIPLE STRUKTUR DATABASE
                        $scheduleDays = [];
                        
                        // Jika ada kolom 'days' (array)
                        if (isset($schedule->days) && is_array($schedule->days)) {
                            $scheduleDays = $schedule->days;
                        } 
                        // Jika ada kolom 'day_of_week' (string)
                        elseif (isset($schedule->day_of_week)) {
                            $scheduleDays = [$schedule->day_of_week];
                        }
                        // Fallback default
                        else {
                            $scheduleDays = ['monday']; // Default value
                        }
                    @endphp
                    
                    @foreach($scheduleDays as $day)
                        <div class="border rounded-lg p-4 {{ $day === $today ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium {{ $day === $today ? 'text-green-800' : 'text-gray-700' }}">
                                        {{ $dayNames[$day] ?? ucfirst($day) }}
                                    </h4>
                                    <p class="text-sm {{ $day === $today ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ $schedule->service->name ?? 'Layanan tidak tersedia' }}
                                    </p>
                                    <p class="text-xs {{ $day === $today ? 'text-green-500' : 'text-gray-400' }}">
                                        {{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}
                                    </p>
                                </div>
                                @if($day === $today)
                                    <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif --}}

    {{-- Navigation Cards - Existing content --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Kelola Antrian --}}
        <a href="{{ route('filament.dokter.resources.queues.index') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Kelola Antrian</h3>
                        <p class="text-sm text-gray-500">Lihat dan kelola antrian pasien</p>
                        @if($waitingCount > 0)
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $waitingCount }} pasien menunggu
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </a>

        {{-- Rekam Medis --}}
        <a href="{{ route('filament.dokter.resources.medical-records.index') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Rekam Medis</h3>
                        <p class="text-sm text-gray-500">Kelola rekam medis pasien</p>
                        @if($completedCount > 0)
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $completedCount }} pasien selesai hari ini
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Test Audio Button (hanya untuk development) --}}
    @if(app()->environment('local'))
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-yellow-800">
                    <strong>Development Mode:</strong> Test audio untuk antrian
                </div>
                <button 
                    onclick="testAudio()" 
                    class="inline-flex items-center px-3 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 border border-yellow-200 rounded hover:bg-yellow-200">
                    Test Audio
                </button>
            </div>
        </div>
    @endif
</div>

{{-- Audio Test Script --}}
<script>
function testAudio() {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance('Test audio dari panel dokter');
        utterance.lang = 'id-ID';
        utterance.rate = 0.8;
        utterance.pitch = 1;
        window.speechSynthesis.speak(utterance);
    } else {
        alert('Browser tidak mendukung speech synthesis');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    function replaceDasbor() {
        // Ubah di sidebar navigation
        const sidebarItems = document.querySelectorAll('.fi-sidebar-item-label');
        sidebarItems.forEach(item => {
            if (item.textContent.trim() === 'Dasbor') {
                item.textContent = 'Dashboard';
            }
        });
        
        // Ubah di page header  
        const pageHeaders = document.querySelectorAll('.fi-header-heading');
        pageHeaders.forEach(header => {
            if (header.textContent.trim() === 'Dasbor') {
                header.textContent = 'Dashboard';
            }
        });
        
        // Ubah title halaman
        if (document.title.includes('Dasbor')) {
            document.title = document.title.replace('Dasbor', 'Dashboard');
        }
    }
    
    // Jalankan immediate
    replaceDasbor();
    
    // Jalankan lagi setelah delay
    setTimeout(replaceDasbor, 100);
    setTimeout(replaceDasbor, 500);
});

</script>
</x-filament-panels::page>