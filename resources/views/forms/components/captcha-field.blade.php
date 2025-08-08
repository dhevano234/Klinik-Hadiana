{{-- resources/views/forms/components/captcha-field.blade.php --}}

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="space-y-3">
        {{-- Captcha Image and Refresh Button --}}
        <div class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <img 
                id="captcha-image" 
                src="{{ route('captcha.generate') }}" 
                alt="Captcha" 
                class="border rounded shadow-sm"
                style="display: block; max-width: 180px; height: auto;"
            >
            
            <button 
                type="button" 
                onclick="document.getElementById('captcha-image').src = '{{ route('captcha.refresh') }}?' + Math.random();" 
                class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-200"
                title="Refresh Captcha"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </button>
        </div>

        {{-- Captcha Input --}}
        <div>
            <label for="{{ $field->getId() }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $field->getLabel() }}
                @if($field->isRequired())
                    <span class="text-red-500">*</span>
                @endif
            </label>
            
            <input
                id="{{ $field->getId() }}"
                name="{{ $field->getStatePath() }}"
                type="text"
                placeholder="Masukkan kode captcha"
                autocomplete="off"
                maxlength="5"
                wire:model="{{ $field->getStatePath() }}"
                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 sm:text-sm"
                @if($field->isRequired()) required @endif
            />
        </div>

        {{-- Help Text --}}
        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-start">
            <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Masukkan kode yang terlihat pada gambar di atas. Klik tombol refresh jika gambar tidak jelas.
        </p>
    </div>
</x-dynamic-component>