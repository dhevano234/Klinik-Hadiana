@extends('layouts.main')

@section('title', 'Buat Antrian')

@section('content')
<main class="main-content">
    <div class="page-header animate">
        <h1><i class="fas fa-plus-circle"></i> Buat Antrian</h1>
        <p>Isi form berikut untuk membuat antrian baru</p>
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

    <div class="form-card">
        <form action="{{ route('antrian.store') }}" method="POST" id="antrianForm">
            @csrf
            
            {{-- SECTION 1: Informasi Personal --}}
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-user"></i>
                    Informasi Personal
                    <small style="color: #7f8c8d; font-weight: normal; font-size: 0.85rem;">
                        (Data diambil dari profil Anda)
                    </small>
                </h6>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" 
                               class="form-input readonly-input" 
                               id="name" 
                               name="name" 
                               value="{{ Auth::user()->name }}" 
                               readonly
                               tabindex="-1">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <input type="text" 
                               class="form-input readonly-input" 
                               id="phone" 
                               name="phone" 
                               value="{{ Auth::user()->phone ?? 'Belum diisi di profil' }}" 
                               readonly
                               tabindex="-1">
                        @if(!Auth::user()->phone)
                            <div class="form-helper">
                                <i class="fas fa-info-circle"></i>
                                <span>Silakan update nomor HP Anda di <a href="{{ route('profile.edit') }}">profil</a></span>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label">Jenis Kelamin</label>
                        <input type="text" 
                               class="form-input readonly-input" 
                               id="gender_display" 
                               value="{{ Auth::user()->gender ?? 'Belum diisi di profil' }}" 
                               readonly
                               tabindex="-1">
                        <input type="hidden" name="gender" value="{{ Auth::user()->gender }}">
                        @if(!Auth::user()->gender)
                            <div class="form-helper">
                                <i class="fas fa-info-circle"></i>
                                <span>Silakan update jenis kelamin Anda di <a href="{{ route('profile.edit') }}">profil</a></span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Informasi Layanan --}}
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-stethoscope"></i>
                    Informasi Layanan
                </h6>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="service_id" class="form-label">Layanan <span class="required">*</span></label>
                        <div class="custom-dropdown" data-name="service_id">
                            <div class="dropdown-trigger @error('service_id') is-invalid @enderror" id="service-trigger">
                                <span class="dropdown-text">-- Pilih Layanan --</span>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                            <div class="dropdown-menu" id="service-menu">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Cari layanan..." class="search-input">
                                    <i class="fas fa-search search-icon"></i>
                                </div>
                                <div class="dropdown-options">
                                    @foreach($services as $service)
                                        <div class="dropdown-option" data-value="{{ $service->id }}" data-text="{{ $service->name }}">
                                            <div class="service-info">
                                                <span class="service-name">{{ $service->name }}</span>
                                                <span class="service-prefix">Kode: {{ $service->prefix }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="service_id" id="service_id" value="{{ old('service_id') }}" required>
                        </div>
                        @error('service_id')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ✅ FIXED: Dokter dropdown dengan event handling yang diperbaiki --}}
                    <div class="form-group">
                        <label for="doctor_id" class="form-label">Dokter <span class="required">*</span></label>
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
                                <div class="dropdown-options" id="doctor-options">
                                    {{-- Options akan diisi via JavaScript berdasarkan tanggal yang dipilih --}}
                                </div>
                            </div>
                            <input type="hidden" name="doctor_id" id="doctor_id" value="{{ old('doctor_id') }}" required>
                        </div>
                        @error('doctor_id')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group full-width">
                        <label for="tanggal_antrian_display" class="form-label">Tanggal Antrian <span class="required">*</span></label>
                        <div id="tanggal-antrian-picker" class="tanggal-antrian-picker">
                        </div>
                        <input type="hidden" name="tanggal" id="tanggal" value="{{ old('tanggal') }}" required>
                        @error('tanggal')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>


                </div>
            </div>

            {{-- SECTION 3: Keluhan Utama (OPSIONAL) --}}
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-comment-medical"></i>
                    Keluhan Utama (Opsional)
                </h6>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="chief_complaint" class="form-label">
                            Keluhan/Gejala yang Dialami 
                            <span class="optional">(Opsional)</span>
                        </label>
                        <textarea name="chief_complaint" 
                                  id="chief_complaint" 
                                  class="form-textarea @error('chief_complaint') is-invalid @enderror"
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="Contoh: Demam sejak 2 hari, sakit kepala, batuk berdahak, nyeri perut, dll.&#10;&#10;Jika diisi, keluhan ini akan otomatis muncul saat dokter membuat rekam medis Anda.">{{ old('chief_complaint') }}</textarea>
                        
                        <div class="form-helper">
                            <i class="fas fa-info-circle"></i>
                            <span>
                                <strong>Opsional:</strong> Anda boleh mengosongkan field ini. 
                                Jika diisi, keluhan akan otomatis muncul di rekam medis dokter.
                            </span>
                        </div>
                        
                        {{-- Character counter --}}
                        <div class="character-counter">
                            <small class="text-muted">
                                <span id="char-count">0</span>/1000 karakter
                            </small>
                        </div>
                        
                        @error('chief_complaint')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="fas fa-plus-circle"></i>
                    Buat Antrian
                </button>
                <a href="/antrian" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</main>

<style>
/* ✅ UPDATED: Simplified Doctor option styles */
.doctor-session-option {
    padding: 15px;
    border-bottom: 1px solid #ecf0f1;
    cursor: pointer;
    transition: all 0.3s ease;
}

.doctor-session-option:hover {
    background: #f8f9fa;
}

.doctor-session-option.unavailable {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f8f8f8;
}

.doctor-session-option.unavailable:hover {
    background: #f8f8f8;
}

.doctor-main-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.doctor-name-service {
    flex: 1;
}

.doctor-name-session {
    font-weight: 600;
    color: #2c3e50;
    font-size: 15px;
}

.doctor-service-session {
    color: #7f8c8d;
    font-size: 13px;
    margin-top: 2px;
}

.quota-status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.quota-status-badge.available {
    background: #d4edda;
    color: #155724;
}

.quota-status-badge.full {
    background: #f8d7da;
    color: #721c24;
}

.doctor-session-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #ecf0f1;
}

.session-time-info {
    color: #7f8c8d;
    font-size: 12px;
}

.loading-option, .error-option, .no-sessions-option {
    padding: 20px;
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
}

.error-option {
    color: #e74c3c;
}

@media (max-width: 768px) {
    .doctor-main-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .doctor-session-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}

/* Page Styles */
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

/* Alert Styles */
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

/* Form Styles */
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
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

/* Full width form group */
.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-label .required {
    color: #e74c3c;
    font-weight: bold;
}

.form-label .optional {
    color: #7f8c8d;
    font-weight: normal;
    font-size: 12px;
    font-style: italic;
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

/* Form textarea styling */
.form-textarea {
    padding: 12px 15px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    background: white;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
}

.form-textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-textarea.is-invalid {
    border-color: #e74c3c;
}

.form-textarea::placeholder {
    color: #95a5a6;
    line-height: 1.4;
}

.form-error {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
}

.form-helper {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 8px;
    display: flex;
    align-items: flex-start;
    gap: 6px;
    line-height: 1.4;
}

.form-helper i {
    margin-top: 2px;
    flex-shrink: 0;
}

.form-helper strong {
    color: #2c3e50;
}

.form-helper a {
    color: #3498db;
    text-decoration: none;
}

.form-helper a:hover {
    text-decoration: underline;
}

/* Character counter */
.character-counter {
    text-align: right;
    margin-top: 5px;
}

.character-counter small {
    font-size: 11px;
    color: #7f8c8d;
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

/* Date Picker Styles */
.tanggal-antrian-picker {
    display: flex !important;
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    min-height: 80px;
}

.date-option {
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    background-color: #f8f9fa;
    color: #34495e;
    font-size: 14px;
    text-align: center;
    transition: all 0.3s ease;
    flex: 1 1 auto;
    min-width: 90px;
    font-weight: 500;
}

.date-option:hover {
    background-color: #e9ecef;
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.date-option.selected {
    background-color: #3498db;
    color: white;
    border-color: #2980b9;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    transform: translateY(-2px);
}

.date-option.disabled {
    background-color: #e9ecef;
    color: #adb5bd;
    cursor: not-allowed;
    opacity: 0.7;
}

.form-group:has(.tanggal-antrian-picker) {
    grid-column: span 3;
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
    background: white !important;
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
    color: #2c3e50 !important;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    background: transparent !important;
}

.custom-dropdown .dropdown-text.placeholder {
    color: #95a5a6 !important;
}

.custom-dropdown .dropdown-icon {
    color: #95a5a6;
    transition: transform 0.3s ease;
    font-size: 12px;
}

.custom-dropdown .dropdown-trigger.active .dropdown-icon {
    transform: rotate(180deg);
}

.custom-dropdown .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    z-index: 2001;
    display: none;
    max-height: 300px;
    overflow: hidden;
}

.custom-dropdown .dropdown-menu.show {
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
    -webkit-overflow-scrolling: touch;
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

.custom-dropdown .dropdown-option.disabled {
    cursor: not-allowed;
    opacity: 0.6;
    background-color: #f8f9fa;
}

.custom-dropdown .service-info,
.custom-dropdown .doctor-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    width: 100%;
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
.custom-dropdown .doctor-time,
.custom-dropdown .doctor-day {
    font-size: 12px;
    color: #7f8c8d;
}

.custom-dropdown .dropdown-option.selected .service-prefix,
.custom-dropdown .dropdown-option.selected .doctor-specialization,
.custom-dropdown .dropdown-option.selected .doctor-time,
.custom-dropdown .dropdown-option.selected .doctor-day {
    color: rgba(255, 255, 255, 0.8);
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

/* Backdrop for mobile */
.dropdown-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.dropdown-backdrop.show {
    opacity: 1;
    visibility: visible;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-card {
        padding: 20px;
    }

    .form-group:has(.tanggal-antrian-picker) {
        grid-column: span 1;
    }

    .custom-dropdown .dropdown-menu {
        max-height: 250px;
        position: absolute;
        top: 100%;
        left: 0;
        right: auto;
        transform: none;
        width: 100%;
        max-width: none;
    }

    .custom-dropdown .dropdown-menu.show {
        position: absolute;
        top: 100%;
        left: 0;
        right: auto;
        transform: none;
        width: 100%;
        max-width: none;
        border-radius: 8px;
        border: 1px solid #ecf0f1;
    }

    .custom-dropdown .dropdown-options {
        max-height: 160px;
    }

    .custom-dropdown .dropdown-trigger {
        min-height: 52px;
        padding: 15px 45px 15px 15px;
    }

    .custom-dropdown .dropdown-option {
        padding: 15px;
        min-height: 60px;
    }

    .custom-dropdown .service-name,
    .custom-dropdown .doctor-name {
        font-size: 15px;
    }

    .custom-dropdown .service-prefix,
    .custom-dropdown .doctor-specialization,
    .custom-dropdown .doctor-time,
    .custom-dropdown .doctor-day {
        font-size: 13px;
    }

    .form-textarea {
        min-height: 120px;
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .form-helper {
        font-size: 11px;
    }
}

@media (max-width: 480px) {
    .custom-dropdown .dropdown-trigger {
        min-height: 56px;
        padding: 18px 50px 18px 18px;
    }

    .custom-dropdown .dropdown-option {
        padding: 18px;
        min-height: 70px;
    }

    .custom-dropdown .search-input {
        padding: 12px 40px 12px 15px;
        font-size: 16px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalInput = document.getElementById('tanggal');
    const doctorDropdown = document.querySelector('[data-name="doctor_id"]');
    const doctorOptions = document.getElementById('doctor-options');
    
    // ✅ CORE FIX: Dedicated doctor option click handler
    function handleDoctorOptionClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const sessionInfo = JSON.parse(this.getAttribute('data-session-info'));
        
        console.log('Doctor option clicked!', sessionInfo.doctor_name);
        
        // Select doctor
        selectDoctorSession(this, sessionInfo);
        
        // ✅ CRITICAL: Force close dropdown
        forceCloseDropdown(doctorDropdown);
    }
    
    // ✅ CORE FIX: Manual event attachment after AJAX
    function attachDoctorClickListeners() {
        const doctorSessionOptions = doctorOptions.querySelectorAll('.doctor-session-option:not(.unavailable)');
        
        console.log('Attached listeners to', doctorSessionOptions.length, 'options');
        
        doctorSessionOptions.forEach(option => {
            // ✅ Prevent double binding
            option.removeEventListener('click', handleDoctorOptionClick);
            option.addEventListener('click', handleDoctorOptionClick);
        });
    }
    
    // ✅ CORE FIX: Aggressive force close function
    function forceCloseDropdown(dropdown) {
        if (!dropdown) return;
        
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        const backdrop = document.querySelector('.dropdown-backdrop');
        
        // Force remove classes
        trigger.classList.remove('active');
        menu.classList.remove('show');
        
        if (backdrop) {
            backdrop.classList.remove('show');
        }
        
        // Clear search
        const searchInput = dropdown.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = '';
            // Show all options again
            dropdown.querySelectorAll('.dropdown-option').forEach(option => {
                option.classList.remove('hidden');
            });
        }
        
        console.log('Dropdown force closed');
    }
    
    // ✅ FIXED: Update doctor sessions dengan quota check
    function updateDoctorSessions(selectedDate) {
        if (!selectedDate) return;
        
        console.log('Updating doctor sessions for date:', selectedDate);
        
        // Show loading
        if (doctorOptions) {
            doctorOptions.innerHTML = '<div class="loading-option"><i class="fas fa-spinner fa-spin"></i> Memuat dokter tersedia...</div>';
        }
        
        // Fetch available sessions
        const url = `/api/antrian/available-sessions?tanggal=${encodeURIComponent(selectedDate)}`;
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Sessions response:', data);
            
            if (data.success && doctorOptions) {
                updateDoctorDropdownOptions(data.sessions, selectedDate);
            } else {
                if (doctorOptions) {
                    doctorOptions.innerHTML = '<div class="error-option"><i class="fas fa-exclamation-triangle"></i> Gagal memuat dokter</div>';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching sessions:', error);
            if (doctorOptions) {
                doctorOptions.innerHTML = '<div class="error-option"><i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan memuat dokter</div>';
            }
        });
    }
    
    // ✅ FIXED: Simplified dropdown options dengan manual event attach
    function updateDoctorDropdownOptions(sessions, selectedDate) {
        if (!doctorOptions) return;
        
        if (sessions.length === 0) {
            doctorOptions.innerHTML = `
                <div class="no-sessions-option">
                    <i class="fas fa-calendar-times"></i>
                    <span>Tidak ada dokter tersedia pada tanggal ${new Date(selectedDate).toLocaleDateString('id-ID')}</span>
                </div>
            `;
            return;
        }
        
        let optionsHTML = '';
        
        sessions.forEach(session => {
            const isAvailable = session.is_available;
            const quotaStatus = session.quota_status;
            
            optionsHTML += `
                <div class="dropdown-option doctor-session-option ${!isAvailable ? 'unavailable' : ''}" 
                     data-value="${session.id}" 
                     data-text="${session.doctor_name}"
                     data-session-info='${JSON.stringify(session)}'>
                    
                    <div class="doctor-main-info">
                        <div class="doctor-name-service">
                            <div class="doctor-name-session">${session.doctor_name}</div>
                            <div class="doctor-service-session">${session.service_name}</div>
                        </div>
                        <div class="quota-status-badge ${isAvailable ? 'available' : 'full'}">${quotaStatus}</div>
                    </div>
                    
                    <div class="doctor-session-details">
                        <div class="session-time-info">
                            <i class="fas fa-clock"></i> ${session.time_range}
                        </div>
                        ${session.quota_info ? `
                            <div class="session-time-info">
                                <i class="fas fa-users"></i> ${session.quota_info.remaining}/${session.quota_info.total} slot tersisa
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        doctorOptions.innerHTML = optionsHTML;
        
        // ✅ CRITICAL: Manual attach event listeners
        attachDoctorClickListeners();
    }
    
    // ✅ FIXED: Select doctor session (simplified - no session info display)
    function selectDoctorSession(optionElement, sessionInfo) {
        const hiddenInput = doctorDropdown.querySelector('input[type="hidden"]');
        const dropdownText = doctorDropdown.querySelector('.dropdown-text');
        
        // Update hidden input dan display
        hiddenInput.value = sessionInfo.id;
        dropdownText.textContent = sessionInfo.doctor_name;
        dropdownText.classList.remove('placeholder');
        
        // Update selected state
        doctorOptions.querySelectorAll('.dropdown-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        optionElement.classList.add('selected');
        
        console.log('Doctor selected:', sessionInfo.doctor_name);
    }
    
    // ✅ REMOVED: showSessionInfo function - tidak perlu lagi
    
    // ✅ FIXED: Reset doctor selection (simplified)
    function resetDoctorSelection() {
        const hiddenInput = doctorDropdown.querySelector('input[type="hidden"]');
        const dropdownText = doctorDropdown.querySelector('.dropdown-text');
        
        hiddenInput.value = '';
        dropdownText.textContent = '-- Pilih Dokter --';
        dropdownText.classList.add('placeholder');
        
        doctorOptions.querySelectorAll('.dropdown-option').forEach(opt => {
            opt.classList.remove('selected');
        });
    }
    
    // ✅ FIXED: Close dropdown function (untuk service dropdown)
    function closeDropdown(dropdown) {
        if (!dropdown) return;
        
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        const backdrop = document.querySelector('.dropdown-backdrop');
        
        // Remove active states
        trigger.classList.remove('active');
        menu.classList.remove('show');
        
        // Hide backdrop
        if (backdrop) {
            backdrop.classList.remove('show');
        }
        
        // Clear search if exists
        const searchInput = dropdown.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = '';
            dropdown.querySelectorAll('.dropdown-option').forEach(option => {
                option.classList.remove('hidden');
            });
        }
    }
    
    // ✅ Close all dropdowns
    function closeAllDropdowns() {
        const dropdowns = document.querySelectorAll('.custom-dropdown');
        dropdowns.forEach(dropdown => {
            const dropdownName = dropdown.getAttribute('data-name');
            if (dropdownName === 'doctor_id') {
                // Use force close for doctor dropdown
                forceCloseDropdown(dropdown);
            } else {
                // Use normal close for other dropdowns
                closeDropdown(dropdown);
            }
        });
    }
    
    // ✅ FIXED: Date change handler
    function initDateChangeHandler() {
        const tanggalAntrianPicker = document.getElementById('tanggal-antrian-picker');
        const hiddenTanggalInput = document.getElementById('tanggal');
        
        if (tanggalAntrianPicker) {
            tanggalAntrianPicker.addEventListener('click', function(e) {
                const dateOption = e.target.closest('.date-option');
                if (!dateOption) return;
                
                // Update selected state
                this.querySelectorAll('.date-option').forEach(option => {
                    option.classList.remove('selected');
                });
                dateOption.classList.add('selected');
                
                // Update hidden input
                const selectedDate = dateOption.getAttribute('data-date');
                if (hiddenTanggalInput) {
                    hiddenTanggalInput.value = selectedDate;
                }
                
                console.log('Date changed to:', selectedDate);
                
                // Reset doctor selection and update sessions
                resetDoctorSelection();
                updateDoctorSessions(selectedDate);
            });
        }
        
        if (hiddenTanggalInput) {
            hiddenTanggalInput.addEventListener('change', function() {
                const selectedDate = this.value;
                console.log('Hidden input date changed to:', selectedDate);
                
                // Update visual picker
                if (tanggalAntrianPicker) {
                    tanggalAntrianPicker.querySelectorAll('.date-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    
                    const matchingOption = tanggalAntrianPicker.querySelector(`[data-date="${selectedDate}"]`);
                    if (matchingOption) {
                        matchingOption.classList.add('selected');
                    }
                }
                
                // Reset doctor dan update sessions
                resetDoctorSelection();
                updateDoctorSessions(selectedDate);
            });
        }
    }

    // Initialize form
    const form = document.getElementById('antrianForm');
    const submitBtn = document.getElementById('submitBtn');

    // ✅ FIXED: Initialize Custom Dropdowns (service dropdown menggunakan static handling)
    initCustomDropdowns();

    // Initialize character counter for chief_complaint
    initCharacterCounter();
    
    // Initialize date change handler
    initDateChangeHandler();

    // Form validation and submission
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validasi service_id wajib diisi
            const serviceIdInput = document.getElementById('service_id');
            if (!serviceIdInput || !serviceIdInput.value) {
                e.preventDefault();
                alert('Harap pilih layanan terlebih dahulu!');
                return;
            }

            // Validasi tanggal wajib diisi
            const tanggalInput = document.getElementById('tanggal');
            if (!tanggalInput || !tanggalInput.value) {
                e.preventDefault();
                alert('Harap pilih tanggal antrian!');
                renderDateOptions();
                return;
            }

            // Validasi doctor_id wajib diisi
            const doctorIdInput = document.getElementById('doctor_id');
            if (!doctorIdInput || !doctorIdInput.value) {
                e.preventDefault();
                alert('Harap pilih dokter terlebih dahulu!');
                return;
            }

            // Validasi chief_complaint length
            const chiefComplaintTextarea = document.getElementById('chief_complaint');
            const chiefComplaint = chiefComplaintTextarea?.value?.trim();
            
            if (chiefComplaint && chiefComplaint.length > 1000) {
                e.preventDefault();
                alert('Keluhan terlalu panjang! Maksimal 1000 karakter.');
                chiefComplaintTextarea.focus();
                return false;
            }

            // Disable submit button untuk prevent double submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Memproses...';
            }
        });
    }

    // Close alert functionality
    document.querySelectorAll('.alert-close').forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.style.display = 'none';
        });
    });

    function initCharacterCounter() {
        const chiefComplaintTextarea = document.getElementById('chief_complaint');
        const charCountElement = document.getElementById('char-count');
        
        if (chiefComplaintTextarea && charCountElement) {
            chiefComplaintTextarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                charCountElement.textContent = currentLength;
                
                if (currentLength > 900) {
                    charCountElement.style.color = '#e74c3c';
                } else if (currentLength > 700) {
                    charCountElement.style.color = '#f39c12';
                } else {
                    charCountElement.style.color = '#7f8c8d';
                }
            });
            
            chiefComplaintTextarea.dispatchEvent(new Event('input'));
            
            chiefComplaintTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });
        }
    }

    // ✅ FIXED: Custom dropdown initialization dengan pemisahan sistem
    function initCustomDropdowns() {
        const dropdowns = document.querySelectorAll('.custom-dropdown');
        let backdrop = null;

        dropdowns.forEach(dropdown => {
            const dropdownName = dropdown.getAttribute('data-name');
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');
            const options = dropdown.querySelectorAll('.dropdown-option');
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const searchInput = dropdown.querySelector('.search-input');
            const dropdownText = dropdown.querySelector('.dropdown-text');

            // Create backdrop for mobile
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'dropdown-backdrop';
                document.body.appendChild(backdrop);
            }

            // Set initial state
            const currentValue = hiddenInput.value;
            if (currentValue) {
                const selectedOption = dropdown.querySelector(`[data-value="${currentValue}"]`);
                if (selectedOption && !selectedOption.classList.contains('disabled')) {
                    selectOption(selectedOption, dropdown);
                }
            }

            // ✅ FIXED: Trigger click dengan prevent double binding
            trigger.removeEventListener('click', handleTriggerClick);
            trigger.addEventListener('click', handleTriggerClick);
            
            function handleTriggerClick(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                closeAllDropdowns();
                
                // Toggle current dropdown
                const isOpen = menu.classList.contains('show');
                if (!isOpen) {
                    openDropdown(dropdown);
                }
            }

            // ✅ FIXED: Pisahkan event handling berdasarkan dropdown type
            if (dropdownName === 'service_id') {
                // Service dropdown: Static options, normal event handling
                options.forEach(option => {
                    if (!option.classList.contains('disabled')) {
                        option.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            selectOption(this, dropdown);
                            closeDropdown(dropdown);
                        });
                    }
                });
            }
            // Doctor dropdown akan menggunakan event delegation yang sudah diatur di attachDoctorClickListeners()

            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const searchOptions = dropdown.querySelectorAll('.dropdown-option');
                    searchOptions.forEach(option => {
                        if (!option.classList.contains('disabled')) {
                            const text = option.textContent.toLowerCase();
                            if (text.includes(query)) {
                                option.classList.remove('hidden');
                            } else {
                                option.classList.add('hidden');
                            }
                        }
                    });
                });

                searchInput.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            // Backdrop click
            backdrop.addEventListener('click', function() {
                closeAllDropdowns();
            });
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
            
            // Show backdrop on mobile
            if (window.innerWidth <= 768) {
                backdrop.classList.add('show');
            }

            // Focus search input
            if (searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            }
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

    // Date Picker Logic
    const tanggalAntrianPicker = document.getElementById('tanggal-antrian-picker');
    const hiddenTanggalInput = document.getElementById('tanggal');

    function getCurrentDate() {
        return new Date();
    }

    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDisplayDate(date) {
        const options = { weekday: 'short', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('id-ID', options);
    }

    function renderDateOptions() {
        if (!tanggalAntrianPicker) {
            console.error('Date picker element not found!');
            return;
        }

        const today = getCurrentDate();
        tanggalAntrianPicker.innerHTML = '';
        
        for (let i = 0; i < 7; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);

            const dateString = formatDateForInput(date);
            const displayString = formatDisplayDate(date);

            const dateOption = document.createElement('div');
            dateOption.classList.add('date-option');
            dateOption.setAttribute('data-date', dateString);
            dateOption.textContent = displayString;

            if (i === 0) {
                dateOption.style.borderColor = '#3498db';
                dateOption.style.fontWeight = '600';
            }

            if (hiddenTanggalInput && hiddenTanggalInput.value === dateString) {
                dateOption.classList.add('selected');
            } else if ((!hiddenTanggalInput || !hiddenTanggalInput.value) && i === 0) {
                dateOption.classList.add('selected');
                if (hiddenTanggalInput) hiddenTanggalInput.value = dateString;
            }
            
            tanggalAntrianPicker.appendChild(dateOption);
        }
    }

    function refreshDatePickerIfNeeded() {
        if (!tanggalAntrianPicker || !hiddenTanggalInput) return;
        
        const today = getCurrentDate();
        const todayString = formatDateForInput(today);
        
        const todayOption = tanggalAntrianPicker.querySelector(`[data-date="${todayString}"]`);
        
        if (!todayOption) {
            console.log('Today option not found, refreshing date picker...');
            renderDateOptions();
        }
    }

    // Event listeners untuk auto-refresh
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(refreshDatePickerIfNeeded, 100);
        }
    });

    window.addEventListener('focus', function() {
        setTimeout(refreshDatePickerIfNeeded, 100);
    });

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            setTimeout(renderDateOptions, 100);
        }
    });

    setInterval(function() {
        refreshDatePickerIfNeeded();
    }, 60000);

    // Initial render of date options
    renderDateOptions();

    // Re-select the old value if it exists after rendering options
    if (hiddenTanggalInput && hiddenTanggalInput.value) {
        setTimeout(() => {
            const previouslySelected = tanggalAntrianPicker.querySelector(`[data-date="${hiddenTanggalInput.value}"]`);
            if (previouslySelected) {
                tanggalAntrianPicker.querySelectorAll('.date-option').forEach(option => {
                    option.classList.remove('selected');
                });
                previouslySelected.classList.add('selected');
            } else {
                renderDateOptions();
            }
        }, 100);
    }

    // Validasi real-time untuk tanggal
    if (hiddenTanggalInput) {
        hiddenTanggalInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = getCurrentDate();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                this.value = formatDateForInput(today);
                renderDateOptions();
            }
        });
    }

    // Initial load dengan tanggal yang sudah dipilih atau hari ini
    const initialDate = hiddenTanggalInput?.value || formatDateForInput(getCurrentDate());
    console.log('Initial date load:', initialDate);
    updateDoctorSessions(initialDate);
});
</script>
@endsection