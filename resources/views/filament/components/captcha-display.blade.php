{{-- resources/views/filament/components/captcha-display.blade.php --}}

<div class="fi-fo-field-wrp">
    <div class="grid gap-y-2">
        <div class="flex items-center justify-between">
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Verifikasi Captcha
                </span>
            </label>
        </div>

        <div class="fi-fo-field-wrp-input-container">
            <div class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-white/5 rounded-lg border border-gray-300 dark:border-white/20">
                <div class="flex-shrink-0">
                    <img 
                        id="captcha-image" 
                        src="{{ route('captcha.generate') }}" 
                        alt="Captcha" 
                        class="border border-gray-200 dark:border-gray-600 rounded shadow-sm"
                        style="display: block; max-width: 180px; height: auto;"
                    >
                </div>
                
                <button 
                    type="button" 
                    onclick="refreshCaptcha()" 
                    class="inline-flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg border border-gray-300 dark:border-white/20 transition-colors duration-200"
                    title="Refresh Captcha"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
            
            <div class="fi-fo-field-wrp-hint mt-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Masukkan kode yang terlihat pada gambar di atas. Klik tombol refresh jika tidak jelas.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function refreshCaptcha() {
    document.getElementById('captcha-image').src = '{{ route("captcha.refresh") }}?' + Math.random();
}
</script>