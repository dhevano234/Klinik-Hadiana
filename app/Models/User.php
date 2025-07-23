<?php
// File: app/Models/User.php - UPDATED dengan Medical Record Number

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'gender',
        'birth_date',
        'address',
        'nomor_ktp',
        'medical_record_number', // ✅ TAMBAH INI
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
        ];
    }

    /**
     * ✅ BOOT METHOD - Auto-generate medical record number untuk user role 'user'
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set default role jika tidak diset
            if (empty($user->role)) {
                $user->role = 'user';
            }
            
            // ✅ GENERATE medical record number untuk user dengan role 'user'
            if ($user->role === 'user') {
                $user->medical_record_number = self::generateMedicalRecordNumber();
            }
        });

        // ✅ HANDLE UPDATE: Jika role berubah menjadi 'user' dan belum ada nomor RM
        static::updating(function ($user) {
            if ($user->role === 'user' && empty($user->medical_record_number)) {
                $user->medical_record_number = self::generateMedicalRecordNumber();
            }
        });
    }

    /**
     * ✅ GENERATE unique medical record number
     * Format: RM-YYYYMMDD-XXXX
     * Contoh: RM-20250629-0001
     */
    public static function generateMedicalRecordNumber(): string
    {
        $today = Carbon::now()->format('Ymd');
        $prefix = "RM-{$today}-";
        
        // Cari nomor terakhir hari ini
        $lastRecord = self::where('medical_record_number', 'LIKE', $prefix . '%')
            ->orderBy('medical_record_number', 'desc')
            ->first();
        
        if ($lastRecord) {
            // Ambil 4 digit terakhir dan increment
            $lastNumber = (int) substr($lastRecord->medical_record_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Nomor pertama hari ini
            $newNumber = 1;
        }
        
        // Format dengan leading zeros (4 digit)
        $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $formattedNumber;
    }

    /**
     * ✅ METHOD untuk assign medical record number ke existing user
     */
    public function assignMedicalRecordNumber(): void
    {
        if (!$this->medical_record_number && $this->role === 'user') {
            $this->medical_record_number = self::generateMedicalRecordNumber();
            $this->save();
        }
    }

    /**
     * ✅ STATIC METHOD untuk get atau create user berdasarkan KTP
     * Digunakan saat user walk-in di kiosk atau registrasi manual
     */
    public static function getOrCreateByKtp(string $ktp, array $userData = []): self
    {
        // Cari user berdasarkan KTP
        $user = self::where('nomor_ktp', $ktp)->first();
        
        if (!$user) {
            // Buat user baru jika tidak ditemukan
            $user = self::create(array_merge([
                'nomor_ktp' => $ktp,
                'role' => 'user',
                'name' => $userData['name'] ?? 'Pasien - ' . substr($ktp, -4),
                'email' => $userData['email'] ?? 'patient' . substr($ktp, -4) . '@klinik.local',
                'password' => bcrypt('password123'), // Default password
                'address' => $userData['address'] ?? 'Alamat belum diisi',
                'phone' => $userData['phone'] ?? null,
                'gender' => $userData['gender'] ?? null,
                'birth_date' => $userData['birth_date'] ?? null,
            ], $userData));
        } else {
            // Jika user ada tapi belum punya nomor RM, generate
            if (!$user->medical_record_number) {
                $user->assignMedicalRecordNumber();
            }
        }
        
        return $user;
    }

    // ===== EXISTING RELATIONSHIPS =====
    
    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function medicalRecordsAsPatient(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'user_id');
    }

    public function medicalRecordsAsDoctor(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'doctor_id');
    }

    public function medicalRecords(): HasMany
    {
        return $this->medicalRecordsAsPatient();
    }

    // ===== ACCESSOR METHODS =====

    /**
     * Cek apakah data profil sudah lengkap untuk buat antrian
     */
    public function isProfileCompleteForQueue(): bool
    {
        return !empty($this->phone) && 
               !empty($this->gender) && 
               !empty($this->birth_date) && 
               !empty($this->address) &&
               $this->address !== 'Alamat belum diisi';
    }

    /**
     * Get missing profile data untuk buat antrian
     */
    public function getMissingProfileData(): array
    {
        $missing = [];
        
        if (empty($this->phone)) $missing[] = 'Nomor HP';
        if (empty($this->gender)) $missing[] = 'Jenis Kelamin';
        if (empty($this->birth_date)) $missing[] = 'Tanggal Lahir';
        if (empty($this->address) || $this->address === 'Alamat belum diisi') $missing[] = 'Alamat';
        
        return $missing;
    }

    /**
     * Check if user is admin/dokter/user
     */
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isDokter(): bool { return $this->role === 'dokter'; }
    public function isUser(): bool { return $this->role === 'user'; }

    /**
     * Get user's age
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->diffInYears(now()) : null;
    }

    /**
     * Get formatted gender
     */
    public function getGenderLabelAttribute(): string
    {
        return match($this->gender) {
            'Laki-laki' => 'Laki-laki',
            'Perempuan' => 'Perempuan',
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
            default => $this->gender ?? 'Tidak diketahui'
        };
    }

    /**
     * ✅ GET formatted medical record number untuk display
     */
    public function getFormattedMrnAttribute(): string
    {
        return $this->medical_record_number ?? 'Belum ada';
    }

    /**
     * ✅ CHECK apakah user punya nomor rekam medis
     */
    public function hasMedicalRecordNumber(): bool
    {
        return !empty($this->medical_record_number);
    }

    /**
     * ✅ GET display name untuk temporary patient
     */
    public function getDisplayNameAttribute(): string
    {
        // Jika nama seperti "Pasien - 1234", tampilkan dengan nomor RM
        if (str_contains($this->name, 'Pasien - ') && $this->medical_record_number) {
            return $this->medical_record_number . ' - ' . $this->name;
        }
        
        return $this->name;
    }

    // ===== SCOPES =====

    /**
     * Scope untuk user dengan role 'user' saja
     */
    public function scopePatients($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope untuk user yang sudah punya nomor rekam medis
     */
    public function scopeWithMedicalRecord($query)
    {
        return $query->whereNotNull('medical_record_number');
    }

    /**
     * Scope untuk user yang belum punya nomor rekam medis
     */
    public function scopeWithoutMedicalRecord($query)
    {
        return $query->whereNull('medical_record_number');
    }
}