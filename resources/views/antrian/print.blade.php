<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Antrian - {{ $antrian->number }}</title>
    
    {{-- Bootstrap CSS untuk Print --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        @media print {
            body { 
                margin: 0; 
                padding: 20px;
                font-size: 12px;
                background: white;
            }
            .no-print { display: none !important; }
            .print-container {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
            }
        }

        @media screen {
            body {
                background-color: #f8f9fa;
                padding: 20px;
            }
            .print-container {
                max-width: 400px;
                margin: 20px auto;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
        }

        .ticket-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            text-align: center;
            padding: 20px;
            position: relative;
        }

        .ticket-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 10px solid #0056b3;
        }

        .clinic-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .ticket-body {
            padding: 25px 20px;
        }

        .queue-number {
            font-size: 48px;
            font-weight: 900;
            color: #007bff;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border: 3px dashed #007bff;
            padding: 20px;
            border-radius: 12px;
            background: rgba(0, 123, 255, 0.05);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px dotted #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }

        .info-value {
            font-weight: 500;
            color: #212529;
            text-align: right;
            font-size: 13px;
        }

        .ticket-footer {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: center;
            font-size: 11px;
            color: #6c757d;
            border-top: 2px dashed #dee2e6;
        }

        .print-instructions {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            font-size: 12px;
            color: #495057;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .print-container {
            animation: fadeIn 0.5s ease-out;
        }

        /* ✅ Styling khusus untuk keluhan di tiket print */
        .complaint-row {
            border-top: 2px solid #007bff !important;
            margin-top: 8px !important;
            padding-top: 8px !important;
            background: rgba(0, 123, 255, 0.05);
        }

        .complaint-text {
            font-style: italic !important;
            color: #0056b3 !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
            text-align: left !important;
            display: block !important;
            margin-top: 4px !important;
        }

        /* Print specific styles */
        @media print {
            .complaint-row {
                border-top: 2px solid #000 !important;
                background: none !important;
            }
            
            .complaint-text {
                color: #000 !important;
            }
        }

        /* Screen specific styles */
        @media screen {
            .complaint-row {
                border-radius: 4px;
                padding: 12px 8px;
                margin: 8px -8px;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        {{-- Header Tiket --}}
        <div class="ticket-header">
            <img src="{{ asset('assets/img/logo/logoklinikpratama.png') }}" alt="Clinic Logo" style="width: 150px; height: auto;">
            <h4 class="mb-1 fw-bold">KLINIK PRATAMA</h4>
            <h5 class="mb-0">HADIANA SEHAT</h5>
            <small class="opacity-75">Jl. Raya Banjaran No. 658A, Pameungpeuk</small>
        </div>

        {{-- Body Tiket --}}
        <div class="ticket-body">
            {{-- Nomor Antrian Besar --}}
            <div class="queue-number">
                {{ $antrian->number }}
            </div>

            {{-- Informasi Detail --}}
            <div class="info-row">
                <span class="info-label">Nama Pasien:</span>
                <span class="info-value">{{ $antrian->user->name ?? 'Walk-in' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Nomor HP:</span>
                <span class="info-value">{{ $antrian->user->phone ?? '-' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Jenis Kelamin:</span>
                <span class="info-value">{{ $antrian->user->gender ?? '-' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Layanan:</span>
                <span class="info-value">{{ $antrian->service->name ?? '-' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Tanggal:</span>
                <span class="info-value">{{ $antrian->created_at->format('d F Y') }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Waktu Daftar:</span>
                <span class="info-value">{{ $antrian->created_at->format('H:i') }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    @switch($antrian->status)
                        @case('waiting')
                            Menunggu
                            @break
                        @case('serving')
                            Sedang Dilayani
                            @break
                        @case('finished')
                            Selesai
                            @break
                        @case('canceled')
                            Dibatalkan
                            @break
                        @default
                            {{ ucfirst($antrian->status) }}
                    @endswitch
                </span>
            </div>

            {{-- ✅ TAMBAH INFO KELUHAN JIKA ADA --}}
            @if(!empty($antrian->chief_complaint))
            <div class="info-row complaint-row">
                <span class="info-label">Keluhan:</span>
                <span class="info-value complaint-text">
                    "{{ $antrian->chief_complaint }}"
                </span>
            </div>
            @endif
        </div>

        {{-- Footer Tiket --}}
        <div class="ticket-footer">
            <strong>HARAP SIMPAN TIKET INI</strong><br>
            Tunjukkan tiket saat dipanggil<br>
            <small>Terima kasih atas kepercayaan Anda</small><br>
            <small class="mt-2 d-block">Dicetak: {{ now()->format('d/m/Y H:i:s') }}</small>
        </div>
    </div>

    {{-- Instruksi Print (hanya tampil di screen) --}}
    <div class="print-instructions no-print">
        <div class="text-center">
            <h6 class="fw-bold mb-2">Instruksi Print:</h6>
            <p class="mb-2">1. Pastikan printer dalam keadaan siap</p>
            <p class="mb-2">2. Gunakan kertas ukuran A4 atau sesuaikan dengan printer</p>
            <p class="mb-3">3. Klik tombol Print atau gunakan Ctrl+P</p>
            
            <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
                <i class="fas fa-print"></i> Print Tiket
            </button>
            <a href="{{ route('antrian.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Auto Print Script --}}
    <script>
        // Print function
        function printTicket() {
            window.print();
        }

        // Close after print (opsional)
        window.onafterprint = function() {
            // window.close();
        }

        // Auto focus untuk print
        window.onload = function() {
            // Optional: Auto focus on print button
            document.querySelector('.btn-primary').focus();
        }
    </script>
</body>
</html>