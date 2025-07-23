@extends('layouts.main')

@section('title', 'Buku Panduan User')

@section('content')
<main class="main-content">
    <div class="faq-container">
        <!-- Header -->
        <div class="faq-header">
            <h1>Panduan Penggunaan Sistem Antrian</h1>
            <p>Klik pertanyaan di bawah untuk melihat jawabannya</p>
        </div>

        <!-- FAQ Items -->
        <div class="faq-list">
            <!-- Dashboard -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Apa itu Dashboard dan apa saja yang ada di dalamnya?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Dashboard</strong> adalah halaman utama setelah login. Di sini Anda akan melihat:</p>
                    <ul>
                        <li><strong>Estimasi</strong>: Status antrian yang sedang berjalan (jika ada), seperti posisi anda saat ini dan kapan dipanggil sesuai estimasi</li>
                        <li><strong>Statistik Hari Ini</strong>: Informasi jumlah antrian hari ini</li>
                        <li><strong>Statistik Hari Ini</strong>: Informasi jumlah kuota antrian pada dokter hari ini</li>
                    </ul>
                </div>
            </div>

            <!-- Cara Ambil Antrian -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana cara mengambil antrian?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Langkah-langkah:</strong></p>
                    <ol>
                        <li>Klik menu <strong>"Buat Kunjungan"</strong> di sidebar kiri</li>
                        <li>Pilih jenis layanan (Poli)</li>
                        <li>Pilih tanggal kunjungan dan dokter</li>
                        <li>Periksa detail antrian, lalu klik <strong>"Buat Antrian"</strong></li>
                        <li>Anda akan mendapat nomor antrian dan estimasi waktu tunggu</li>
                        <li>Anda dapat mengedit,membatalkan,print antrian anda</li>
                    </ol>
                </div>
            </div>

            <!-- Terlambat Datang -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Apa yang terjadi jika saya terlambat datang setelah dipanggil?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Jika terlambat:</strong></p>
                    <ul>
                        <li>Antrian mungkin akan dilewati ke pasien berikutnya</li>
                        <li>Anda perlu konfirmasi ulang ke resepsionis</li>
                        <li>Mungkin perlu menunggu di akhir antrian</li>
                    </ul>
                    <p><strong>Tips:</strong> Selalu datang tepat waktu atau hubungi klinik jika ada kendala.</p>
                </div>
            </div>

            <!-- Multiple Device -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bisakah saya login di beberapa HP/komputer sekaligus?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Ya, bisa.</strong> Anda dapat:</p>
                    <ul>
                        <li>Login di HP dan komputer bersamaan</li>
                        <li>Memantau antrian dari device manapun</li>
                        <li>Status antrian akan sama di semua device</li>
                    </ul>
                    <p><strong>Catatan:</strong> Pastikan logout dari device umum untuk keamanan.</p>
                </div>
            </div>

            <!-- Internet Bermasalah -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana jika internet bermasalah saat memantau antrian?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Solusi jika internet bermasalah:</strong></p>
                    <ol>
                        <li>Catat nomor antrian Anda</li>
                        <li>Datang ke klinik dan tanyakan langsung ke resepsionis</li>
                        <li>Gunakan WiFi klinik jika tersedia</li>
                        <li>Minta bantuan petugas untuk cek status antrian</li>
                    </ol>
                </div>
            </div>

            <!-- Cara Tahu Dipanggil -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana cara mengetahui saat antrian dipanggil?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>3 Cara Mengetahui:</strong></p>
                    <ol>
                        <li><strong>Cek Dashboard:</strong> Status berubah menjadi "Dipanggil", card antrian berubah warna</li>
                        <li><strong>Notifikasi WhatsApp :</strong> Notifikasi 10 menit sebelum dipanggil</li>
                        <li><strong>Audio di Klinik:</strong> Pengumuman speaker, nomor antrian disebutkan</li>
                    </ol>
                    <p><strong>Tips:</strong> Datang 15 menit sebelum estimasi untuk berjaga-jaga.</p>
                </div>
            </div>

            <!-- Print Bermasalah -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Tombol print antrian tidak berfungsi, bagaimana?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Jika print bermasalah:</strong></p>
                    <ol>
                        <li>Screenshot halaman antrian sebagai backup</li>
                        <li>Catat nomor antrian, tanggal, dan dokter</li>
                        <li>Tunjukkan screenshot ke resepsionis</li>
                        <li>Minta petugas untuk print ulang jika perlu</li>
                    </ol>
                </div>
            </div>

            <!-- Riwayat -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana cara melihat riwayat kunjungan?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Cara Akses:</strong></p>
                    <ol>
                        <li>Klik <strong>"Riwayat Kunjungan"</strong> di sidebar</li>
                        <li>Lihat semua kunjungan Anda</li>
                    </ol>
                    <p><strong>Info yang tersedia:</strong> Tanggal & waktu, nomor antrian, layanan, dokter, status.</p>
                </div>
            </div>

            <!-- Jadwal Dokter -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana cara cek jadwal dokter?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Cara Cek:</strong></p>
                    <ol>
                        <li>Klik <strong>"Jadwal Dokter"</strong> di sidebar</li>
                        <li>Menampilkan status dokter,hari praktik,jam praktik</li>
                        <li>kuota antrian dokter bisa dilihat di dashboard</li>
                    </ol>
                </div>
            </div>

            <!-- Edit Profile -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana cara edit profile dan ganti password?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Edit Profile:</strong></p>
                    <ol>
                        <li>Klik nama Anda di pojok kanan atas</li>
                        <li>Pilih <strong>"Edit Profile"</strong></li>
                        <li>Minta Admin Untuk Melakukan Perubahan Profil</li>
                    </ol>
                    <p><strong>Ganti Password:</strong></p>
                    <ol>
                        <li>Isi password lama</li>
                        <li>Masukkan password baru</li>
                        <li>Konfirmasi password baru</li>
                        <li>Klik <strong>"Update"</strong></li>
                    </ol>
                </div>
            </div>

            <!-- Lupa Password -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Saya lupa password. Apa yang harus saya lakukan?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Cara Reset Password:</strong></p>
                    <ol>
                        <li>Di halaman login, klik <strong>"Lupa Password?"</strong></li>
                        <li>Masukkan email yang terdaftar</li>
                        <li>Cek email Anda untuk link reset password</li>
                        <li>Ikuti instruksi di email untuk membuat password baru</li>
                    </ol>
                </div>
            </div>

            <!-- Antrian untuk Keluarga -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Apakah saya bisa membuat antrian untuk keluarga?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Tidak bisa.</strong> Setiap pasien harus registrasi dengan akun sendiri menggunakan KTP masing-masing untuk keamanan data medis.</p>
                    <p>Setiap anggota keluarga perlu:</p>
                    <ul>
                        <li>Registrasi akun terpisah</li>
                        <li>Menggunakan KTP sendiri</li>
                        <li>Login dengan akun masing-masing</li>
                    </ul>
                </div>
            </div>

            <!-- Batalkan Antrian -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Apakah bisa membatalkan antrian?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Ya, bisa.</strong> Ada 2 cara:</p>
                    <ol>
                        <li><strong>Melalui Dashboard:</strong> Jika tersedia tombol "Batalkan" di card antrian aktif</li>
                        <li><strong>Hubungi Klinik:</strong> Datang langsung atau telepon resepsionis</li>
                    </ol>
                    <p><strong>Catatan:</strong> Sebaiknya batalkan jauh-jauh hari agar kuota bisa digunakan pasien lain.</p>
                </div>
            </div>

            <!-- Kuota Habis -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Bagaimana jika kuota dokter sudah habis?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Jika kuota dokter habis:</strong></p>
                    <ol>
                        <li>Pilih tanggal lain yang masih tersedia</li>
                        <li>Pilih dokter lain di tanggal yang sama</li>
                        <li>Coba buat antrian di malam hari untuk hari berikutnya</li>
                    </ol>
                    <p><strong>Tips:</strong> Kuota direset setiap hari sesuai jadwal dokter. Buat antrian lebih awal untuk menghindari kehabisan.</p>
                </div>
            </div>

            <!-- Estimasi Tidak Akurat -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Mengapa estimasi waktu tidak akurat?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Estimasi berdasarkan rata-rata 15 menit per pasien.</strong> Waktu aktual bisa berbeda karena:</p>
                    <ul>
                        <li>Kompleksitas kasus pasien berbeda-beda</li>
                        <li>Dokter mungkin terlambat atau ada emergency</li>
                        <li>Beberapa pasien membutuhkan waktu pemeriksaan lebih lama</li>
                    </ul>
                    <p><strong>Solusi:</strong> Pantau terus dashboard untuk update estimasi real-time.</p>
                </div>
            </div>

            <!-- Sistem 24 Jam -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Apakah sistem buka 24 jam?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Ya, sistem online tersedia 24/7.</strong> Anda bisa:</p>
                    <ul>
                        <li>Buat antrian kapan saja</li>
                        <li>Cek riwayat kunjungan</li>
                        <li>Lihat jadwal dokter</li>
                        <li>Update profile</li>
                    </ul>
                    <p><strong>Tapi ingat:</strong> Pelayanan klinik tetap sesuai jam operasional normal (Senin-Sabtu 08:00-20:00).</p>
                </div>
            </div>

            <!-- Bantuan -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleAnswer(this)">
                    <span>Jika masih ada masalah, kemana saya harus menghubungi?</span>
                    <i class="fas fa-plus faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>Jika masih butuh bantuan:</strong></p>
                    <ul>
                        <li><strong>Front Office Klinik:</strong> Datang langsung ke klinik</li>
                        <li><strong>Telepon:</strong> Hubungi nomor klinik</li>
                        <li><strong>WhatsApp:</strong> Customer service</li>
                    </ul>
                    <p><strong>Jam Operasional:</strong></p>
                    <ul>
                        <li>Senin-Sabtu: 08:00-20:00</li>
                        <li>Minggu: Tutup</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.faq-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.faq-header {
    text-align: center;
    padding: 30px 20px;
    margin-bottom: 30px;
}

.faq-header h1 {
    font-size: 2rem;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 600;
}

.faq-header p {
    color: #7f8c8d;
    font-size: 1rem;
}

.faq-item {
    background: white;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.faq-question {
    padding: 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    border-bottom: 1px solid #ecf0f1;
}

.faq-question:hover {
    background: #e9ecef;
}

.faq-question span {
    font-weight: 500;
    color: #2c3e50;
    font-size: 15px;
}

.faq-icon {
    color: #3498db;
    font-size: 14px;
}

.faq-answer {
    display: none;
    padding: 20px;
    background: white;
}

.faq-answer.show {
    display: block;
}

.faq-item.active .faq-icon {
    transform: rotate(45deg);
}

.faq-answer p {
    margin-bottom: 15px;
    color: #555;
    line-height: 1.6;
}

.faq-answer ul, .faq-answer ol {
    margin-bottom: 15px;
    padding-left: 20px;
}

.faq-answer li {
    margin-bottom: 8px;
    color: #666;
    line-height: 1.5;
}

.faq-answer strong {
    color: #2c3e50;
}

@media (max-width: 768px) {
    .faq-container {
        padding: 15px;
    }
    
    .faq-header {
        padding: 20px 15px;
    }
    
    .faq-header h1 {
        font-size: 1.6rem;
    }
    
    .faq-question {
        padding: 15px;
    }
    
    .faq-question span {
        font-size: 14px;
    }
}
</style>

<script>
function toggleAnswer(element) {
    const faqItem = element.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    
    // Close all other open items
    document.querySelectorAll('.faq-item').forEach(item => {
        if (item !== faqItem) {
            item.classList.remove('active');
            item.querySelector('.faq-answer').classList.remove('show');
        }
    });
    
    // Toggle current item
    faqItem.classList.toggle('active');
    answer.classList.toggle('show');
}
</script>
@endsection