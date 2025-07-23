@extends('layouts.main')

@section('title', 'Edit Antrian')

@section('content')
<!-- Main Content -->
<main class="main-content">
    <!-- Page Header -->
    <div class="page-header animate">
        <h1><i class="fas fa-edit"></i> Edit Antrian</h1>
        <p>Ubah informasi antrian Anda</p>
    </div>

    {{-- Alert untuk error --}}
    @if ($errors->any())
        <div class="alert alert-danger animate">
            <i class="fas fa-exclamation-circle"></i>
            <div class="alert-content">
                <strong>Oops!</strong> Ada masalah dengan input Anda:
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="alert-close">&times;</button>
        </div>
    @endif

    {{-- Alert untuk success --}}
    @if (session('success'))
        <div class="alert alert-success animate">
            <i class="fas fa-check-circle"></i>
            <div class="alert-content">
                {{ session('success') }}
            </div>
            <button type="button" class="alert-close">&times;</button>
        </div>
    @endif

    <!-- Form Card -->
    <div class="form-card animate">
        <form action="{{ route('antrian.update', $queue->id) }}" method="POST" id="editAntrianForm">
            @csrf
            @method('PUT')

            <!-- Queue Information Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-ticket-alt"></i>
                    Informasi Antrian
                </h6>

                <div class="form-grid">
                    <!-- Nomor Antrian - READONLY -->
                    <div class="form-group full-width">
                        <label for="no_antrian" class="form-label">Nomor Antrian</label>
                        <input type="text"
                               class="form-input readonly-input"
                               id="no_antrian"
                               value="{{ $queue->number }}"
                               readonly
                               tabindex="-1">
                        <small class="form-help">Nomor antrian akan berubah jika Anda mengubah layanan</small>
                    </div>
                </div>
            </div>

            <!-- Personal Information Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-user"></i>
                    Informasi Personal
                </h6>

                <div class="form-grid">
                    <!-- Nama Lengkap - READONLY -->
                    <div class="form-group">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text"
                               class="form-input readonly-input"
                               id="name"
                               name="name"
                               value="{{ $queue->user->name }}"
                               readonly
                               tabindex="-1">
                    </div>

                    <!-- Nomor HP - READONLY -->
                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <input type="text"
                               class="form-input readonly-input"
                               id="phone"
                               name="phone"
                               value="{{ $queue->user->phone ?? 'Belum diisi' }}"
                               readonly
                               tabindex="-1">
                    </div>

                    <!-- Jenis Kelamin - READONLY -->
                    <div class="form-group">
                        <label for="gender" class="form-label">Jenis Kelamin</label>
                        <input type="text"
                               class="form-input readonly-input"
                               id="gender"
                               name="gender"
                               value="{{ $queue->user->gender ?? 'Belum diisi' }}"
                               readonly
                               tabindex="-1">
                    </div>
                </div>
            </div>

            <!-- Medical Information Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-stethoscope"></i>
                    Informasi Layanan
                </h6>

                <div class="form-grid">
                    <!-- Layanan - EDITABLE -->
                    <div class="form-group">
                        <label for="service_id" class="form-label">Layanan</label>
                        <div class="custom-dropdown" data-name="service_id">
                            <div class="dropdown-trigger @error('service_id') is-invalid @enderror" id="service-trigger">
                                <span class="dropdown-text">{{ old('service_id', $queue->service->name ?? '-- Pilih Layanan --') }}</span>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                            <div class="dropdown-menu" id="service-menu">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Cari layanan..." class="search-input">
                                    <i class="fas fa-search search-icon"></i>
                                </div>
                                <div class="dropdown-options">
                                    @foreach($services as $service)
                                        <div class="dropdown-option" 
                                             data-value="{{ $service->id }}" 
                                             data-text="{{ $service->name }}"
                                             {{ old('service_id', $queue->service_id) == $service->id ? 'data-selected="true"' : '' }}>
                                            <div class="service-info">
                                                <span class="service-name">{{ $service->name }}</span>
                                                <span class="service-prefix">Kode: {{ $service->prefix }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="service_id" id="service_id" value="{{ old('service_id', $queue->service_id) }}" required>
                        </div>
                        @error('service_id')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Dokter - EDITABLE (OPSIONAL) -->
                    <div class="form-group">
                        <label for="doctor_id" class="form-label">Dokter (Opsional)</label>
                        <div class="custom-dropdown" data-name="doctor_id">
                            <div class="dropdown-trigger @error('doctor_id') is-invalid @enderror" id="doctor-trigger">
                                <span class="dropdown-text">-- Pilih Dokter --</span>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                            <div class="dropdown-menu" id="doctor-menu">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Cari dokter..." class="search-input">
                                    <i class="fas fa-search search-icon"></i>
                                </div>
                                <div class="dropdown-options">
                                    <div class="dropdown-option" data-value="" data-text="Tidak memilih dokter khusus">
                                        <span>Tidak memilih dokter khusus</span>
                                    </div>
                                    @if(isset($doctors) && $doctors->count() > 0)
                                        @foreach($doctors as $doctor)
                                            <div class="dropdown-option" 
                                                 data-value="{{ $doctor->id }}" 
                                                 data-text="{{ $doctor->doctor_name }}">
                                                <div class="doctor-info">
                                                    <span class="doctor-name">{{ $doctor->doctor_name }}</span>
                                                    @if(isset($doctor->service))
                                                        <span class="doctor-specialization">{{ $doctor->service->name }}</span>
                                                    @endif
                                                    @if(isset($doctor->start_time) && isset($doctor->end_time))
                                                        <span class="doctor-time">{{ $doctor->start_time }} - {{ $doctor->end_time }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" name="doctor_id" id="doctor_id" value="{{ old('doctor_id') }}">
                        </div>
                        @error('doctor_id')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg" id="updateBtn">
                    <i class="fas fa-save"></i>
                    Update Antrian
                </button>
                <a href="{{ route('antrian.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</main>

<!-- Form Styles -->
<style>
/* Existing styles from the original file */
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

.alert {
    background: white;
    border: none;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: flex-start;
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

.alert-content {
    flex: 1;
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

.form-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.form-section {
    margin-bottom: 30px;
}

.form-section:last-of-type {
    margin-bottom: 20px;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-title i {
    color: #3498db;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-input,
.form-select {
    padding: 12px 15px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    background: white;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.readonly-input {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    border-color: #dee2e6 !important;
    cursor: not-allowed !important;
    opacity: 0.8;
    pointer-events: none;
}

.form-help {
    color: #7f8c8d;
    font-size: 12px;
    margin-top: 5px;
    font-style: italic;
}

.form-error {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ecf0f1;
}

.btn-lg {
    padding: 15px 30px;
    font-size: 16px;
}

/* Custom Dropdown Styles */
.custom-dropdown {
    position: relative;
    width: 100%;
}

.custom-dropdown .dropdown-trigger {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s ease;
    user-select: none;
    min-height: 48px;
}

.custom-dropdown .dropdown-trigger:hover {
    border-color: #bdc3c7;
}

.custom-dropdown .dropdown-trigger.active {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.custom-dropdown .dropdown-trigger.is-invalid {
    border-color: #e74c3c;
}

.custom-dropdown .dropdown-text {
    flex: 1;
    color: #2c3e50;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.custom-dropdown .dropdown-text.placeholder {
    color: #95a5a6;
}

.custom-dropdown .dropdown-icon {
    color: #95a5a6;
    transition: transform 0.3s ease;
    font-size: 12px;
}

.custom-dropdown .dropdown-trigger.active .dropdown-icon {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 50px;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 10px 0;
    display: none;
    z-index: 2001;
    max-height: 300px;
    overflow-y: auto;
}

.dropdown-menu.show {
    display: block;
}

.custom-dropdown .dropdown-search {
    position: relative;
    padding: 10px;
    border-bottom: 1px solid #ecf0f1;
    background: #f8f9fa;
}

.custom-dropdown .search-input {
    width: 100%;
    padding: 8px 35px 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
}

.custom-dropdown .search-input:focus {
    border-color: #3498db;
}

.custom-dropdown .search-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
    font-size: 12px;
}

.custom-dropdown .dropdown-options {
    max-height: 200px;
    overflow-y: auto;
}

.custom-dropdown .dropdown-option {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
}

.custom-dropdown .dropdown-option:hover {
    background-color: #f8f9fa;
}

.custom-dropdown .dropdown-option.selected {
    background-color: #3498db;
    color: white;
}

.custom-dropdown .dropdown-option.hidden {
    display: none;
}

.custom-dropdown .service-info,
.custom-dropdown .doctor-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.custom-dropdown .service-name,
.custom-dropdown .doctor-name {
    font-weight: 500;
    font-size: 14px;
}

.custom-dropdown .service-prefix {
    font-size: 12px;
    color: #7f8c8d;
}

.custom-dropdown .doctor-specialization,
.custom-dropdown .doctor-time {
    font-size: 12px;
    color: #7f8c8d;
}

.custom-dropdown .dropdown-option.selected .service-prefix,
.custom-dropdown .dropdown-option.selected .doctor-specialization,
.custom-dropdown .dropdown-option.selected .doctor-time {
    color: rgba(255, 255, 255, 0.8);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr !important;
        gap: 15px;
    }

    .form-card {
        padding: 20px;
        margin: 0 10px;
        border-radius: 10px;
    }

    .form-actions {
        flex-direction: column;
        gap: 12px;
    }

    .btn-lg {
        width: 100%;
        justify-content: center;
    }
}

/* Loading state */
.btn-loading {
    opacity: 0.6;
    pointer-events: none;
}

.btn-loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editAntrianForm');
    const updateBtn = document.getElementById('updateBtn');

    // Initialize Custom Dropdowns
    initCustomDropdowns();

    // Prevent double submission
    if (form) {
        form.addEventListener('submit', function() {
            updateBtn.disabled = true;
            updateBtn.classList.add('btn-loading');
            updateBtn.innerHTML = '<i class="fas fa-spinner"></i> Memproses...';
        });
    }

    // Close alert functionality
    document.querySelectorAll('.alert-close').forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.style.display = 'none';
        });
    });

    function initCustomDropdowns() {
        const dropdowns = document.querySelectorAll('.custom-dropdown');

        dropdowns.forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');
            const options = dropdown.querySelectorAll('.dropdown-option');
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const searchInput = dropdown.querySelector('.search-input');
            const dropdownText = dropdown.querySelector('.dropdown-text');

            // Set initial state
            const currentValue = hiddenInput.value;
            if (currentValue) {
                const selectedOption = dropdown.querySelector(`[data-value="${currentValue}"]`);
                if (selectedOption) {
                    selectOption(selectedOption, dropdown);
                }
            }

            // Trigger click
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                closeAllDropdowns();
                
                // Toggle current dropdown
                const isOpen = menu.classList.contains('show');
                if (!isOpen) {
                    openDropdown(dropdown);
                } else {
                    closeDropdown(dropdown);
                }
            });

            // Option click
            options.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectOption(this, dropdown);
                    closeDropdown(dropdown);
                });
            });

            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        if (text.includes(query)) {
                            option.classList.remove('hidden');
                        } else {
                            option.classList.add('hidden');
                        }
                    });
                });

                searchInput.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-dropdown')) {
                closeAllDropdowns();
            }
        });

        // Close dropdowns on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });

        function openDropdown(dropdown) {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');
            const searchInput = dropdown.querySelector('.search-input');

            trigger.classList.add('active');
            menu.classList.add('show');

            // Focus search input
            if (searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            }
        }

        function closeDropdown(dropdown) {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');
            const searchInput = dropdown.querySelector('.search-input');

            trigger.classList.remove('active');
            menu.classList.remove('show');

            // Clear search
            if (searchInput) {
                searchInput.value = '';
                dropdown.querySelectorAll('.dropdown-option').forEach(option => {
                    option.classList.remove('hidden');
                });
            }
        }

        function closeAllDropdowns() {
            dropdowns.forEach(dropdown => {
                closeDropdown(dropdown);
            });
        }

        function selectOption(option, dropdown) {
            const value = option.getAttribute('data-value');
            const text = option.getAttribute('data-text') || option.textContent.trim();
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const dropdownText = dropdown.querySelector('.dropdown-text');

            // Update hidden input
            hiddenInput.value = value;

            // Update display text
            dropdownText.textContent = text;
            dropdownText.classList.remove('placeholder');

            // Update selected state
            dropdown.querySelectorAll('.dropdown-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            option.classList.add('selected');

            // Remove invalid state
            const trigger = dropdown.querySelector('.dropdown-trigger');
            trigger.classList.remove('is-invalid');
        }
    }
});
</script>
@endsection