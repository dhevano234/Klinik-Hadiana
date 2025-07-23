{{-- File: resources/views/filament/dokter/pages/edit-profile.blade.php --}}

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Section --}}
        <div class="bg-white rounded-lg shadow border p-6">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-xl font-semibold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Edit Profile
                    </h1>
                    <p class="text-lg text-gray-600">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Kelola informasi profile Anda
                    </p>
                </div>
            </div>
        </div>

        {{-- Form Section --}}
        <div class="bg-white rounded-lg shadow border p-6">
            <form wire:submit="save">
                {{ $this->form }}
                
                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit" color="success">
                        <x-heroicon-o-check class="w-4 h-4 mr-2" />
                        Simpan Perubahan
                    </x-filament::button>
                </div>
            </form>
        </div>

        <x-filament-actions::modals />
    </div>
</x-filament-panels::page>