{{-- File: resources/views/filament/pages/admin-dashboard.blade.php --}}
{{-- FIXED: Single root element untuk Livewire --}}

<x-filament-panels::page>
<div class="space-y-6">
    {{-- Welcome Section - Simple seperti Dokter --}}
    <div class="bg-white rounded-lg shadow border p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                {{-- Avatar Simple --}}
                <div class="w-12 h-12 bg-amber-600 rounded-full flex items-center justify-center">
                    <span class="text-lg font-semibold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                </div>

                {{-- Welcome Text --}}
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Selamat Datang
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ auth()->user()->name }}
                    </p>
                </div>
            </div>

            {{-- Logout Button Simple --}}
            <div>
                <form action="{{ route('filament.admin.auth.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Navigation Cards - Grid dengan Patient Management --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- Data Pasien --}}
        <a href="{{ url('/admin/patient-management') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Data Pasien</h3>
                        <p class="text-sm text-gray-500">Kelola data semua pasien</p>
                    </div>
                </div>
            </div>
        </a>

        {{-- Kelola Loket --}}
        <a href="{{ url('/admin/counters') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Kelola Loket</h3>
                        <p class="text-sm text-gray-500">Atur loket dan panggil antrian</p>
                    </div>
                </div>
            </div>
        </a>

        {{-- Antrian --}}
        <a href="{{ url('/admin/queues') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Antrian</h3>
                        <p class="text-sm text-gray-500">Kelola semua antrian</p>
                    </div>
                </div>
            </div>
        </a>

        {{-- Jadwal Dokter --}}
        <a href="{{ url('/admin/doctor-schedules') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-emerald-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m4 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h8m-8-4h8m-8 4h8"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Jadwal Dokter</h3>
                        <p class="text-sm text-gray-500">Atur jadwal praktik dokter</p>
                    </div>
                </div>
            </div>
        </a>

        {{-- Layanan --}}
        <a href="{{ url('/admin/services') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Layanan</h3>
                        <p class="text-sm text-gray-500">Atur jenis layanan</p>
                    </div>
                </div>
            </div>
        </a>

        {{-- Monitor Ruang Tunggu --}}
        <a href="{{ url('/admin/dashboardkiosantrian') }}" 
           class="block bg-white rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Ruang Tunggu</h3>
                        <p class="text-sm text-gray-500">Monitor display antrian</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Test Audio Button (hanya untuk development) --}}
    @if(app()->environment('local'))
    <div class="bg-gray-50 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <span class="font-medium">Debug Audio:</span> Test sistem audio admin
            </div>
            <button onclick="testAdminAudio('Test audio dari panel admin')" 
                    class="inline-flex items-center px-3 py-1 text-xs font-medium rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217z" clip-rule="evenodd"/>
                </svg>
                Test
            </button>
        </div>
    </div>
    @endif

    {{-- Simple Audio System untuk Admin --}}
    <script>
        // Simple Audio System untuk Panel Admin
        console.log('🎵 Admin Audio System Loading...');

        window.testAdminAudio = function(message) {
            playAdminAudioMessage(message || 'Test audio admin berhasil');
        };

        window.handleQueueCall = function(message) {
            playAdminAudioMessage(message);
        };

        window.playQueueAudio = function(message) {
            playAdminAudioMessage(message);
        };

        function playAdminAudioMessage(message) {
            if (!message) return;
            
            try {
                if ('speechSynthesis' in window) {
                    speechSynthesis.cancel();
                    
                    const utterance = new SpeechSynthesisUtterance(message);
                    utterance.lang = 'id-ID';
                    utterance.rate = 0.9;
                    utterance.volume = 1.0;
                    
                    const voices = speechSynthesis.getVoices();
                    const indonesianVoice = voices.find(voice => 
                        voice.lang.includes('id') || voice.name.toLowerCase().includes('indonesia')
                    );
                    if (indonesianVoice) {
                        utterance.voice = indonesianVoice;
                    }
                    
                    speechSynthesis.speak(utterance);
                    console.log('🔊 Admin Audio played:', message);
                } else {
                    console.warn('Speech synthesis not supported');
                }
            } catch (error) {
                console.error('Admin Audio error:', error);
            }
        }

        // Load voices
        if ('speechSynthesis' in window) {
            speechSynthesis.getVoices();
        }

        // Livewire events untuk cross-panel communication
        document.addEventListener('livewire:initialized', function() {
            if (window.Livewire && window.Livewire.on) {
                window.Livewire.on('queue-called', function(message) {
                    console.log('📡 Admin received queue-called:', message);
                    playAdminAudioMessage(message);
                });
                console.log('✅ Admin Livewire audio events ready');
            }
        });

        console.log('✅ Admin Audio System Ready');
    </script>
</div>
</x-filament-panels::page>