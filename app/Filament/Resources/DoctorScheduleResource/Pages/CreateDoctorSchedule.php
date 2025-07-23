<?php
// File: app/Filament/Resources/DoctorScheduleResource/Pages/CreateDoctorSchedule.php
// UPDATED: Dropdown dokter dari database users

namespace App\Filament\Resources\DoctorScheduleResource\Pages;

use App\Filament\Resources\DoctorScheduleResource;
use App\Models\DoctorSchedule;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CreateDoctorSchedule extends CreateRecord
{
    protected static string $resource = DoctorScheduleResource::class;

    protected static ?string $title = 'Tambah Jadwal Dokter';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Jadwal'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ VALIDASI: Pastikan doctor_id adalah role 'dokter'
        if (isset($data['doctor_id'])) {
            $doctor = User::find($data['doctor_id']);
            if (!$doctor || $doctor->role !== 'dokter') {
                throw new \Exception('User yang dipilih bukan dokter');
            }
            
            // Set doctor_name dari database
            $data['doctor_name'] = $doctor->name;
        }

        // Validasi data sebelum proses
        if (empty($data['days'])) {
            throw new \Exception('Harap pilih minimal satu hari praktik');
        }

        if (empty($data['doctor_id'])) {
            throw new \Exception('Dokter harus dipilih');
        }

        if (empty($data['service_id'])) {
            throw new \Exception('Poli harus dipilih');
        }

        if (empty($data['start_time']) || empty($data['end_time'])) {
            throw new \Exception('Jam praktik harus diisi');
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // ✅ LOGIC: Validasi dan pembuatan jadwal dengan doctor_id
        
        $days = $data['days'] ?? [];
        $doctorId = $data['doctor_id'];
        $doctorName = $data['doctor_name'];
        $serviceId = $data['service_id'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        // ✅ VALIDASI: Basic validation
        if (empty($days)) {
            Notification::make()
                ->title('❌ Error')
                ->body('Harap pilih minimal satu hari praktik')
                ->danger()
                ->send();
            throw new \Exception('Harap pilih minimal satu hari praktik');
        }

        if ($startTime >= $endTime) {
            Notification::make()
                ->title('❌ Error')
                ->body('Jam mulai harus lebih awal dari jam selesai')
                ->danger()
                ->send();
            throw new \Exception('Jam mulai harus lebih awal dari jam selesai');
        }

        // ✅ CEK KONFLIK: Menggunakan doctor_id (bukan doctor_name)
        if (DoctorSchedule::hasConflict($doctorId, $serviceId, $days, $startTime, $endTime)) {
            Notification::make()
                ->title('⚠️ Konflik Jadwal')
                ->body('Jadwal bertabrakan dengan jadwal yang sudah ada untuk dokter ini')
                ->warning()
                ->send();
            throw new \Exception('Jadwal bertabrakan dengan jadwal yang sudah ada');
        }

        try {
            // ✅ CREATE: Buat jadwal dengan doctor_id dan doctor_name
            $schedule = DoctorSchedule::create([
                'doctor_id' => $doctorId,      // ✅ NEW: Foreign key ke users
                'doctor_name' => $doctorName,  // ✅ KEEP: Untuk backward compatibility
                'service_id' => $serviceId,
                'days' => $days, // Array of days
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_active' => $data['is_active'] ?? true,
                'foto' => $data['foto'] ?? null,
            ]);

            // ✅ SUCCESS NOTIFICATION
            $dayNames = array_map(function($day) {
                $names = [
                    'monday' => 'Senin',
                    'tuesday' => 'Selasa',
                    'wednesday' => 'Rabu',
                    'thursday' => 'Kamis',
                    'friday' => 'Jumat',
                    'saturday' => 'Sabtu',
                    'sunday' => 'Minggu',
                ];
                return $names[$day] ?? $day;
            }, $days);

            Notification::make()
                ->title('✅ Jadwal Berhasil Dibuat')
                ->body("Jadwal untuk {$doctorName} pada hari " . implode(', ', $dayNames) . " berhasil disimpan")
                ->success()
                ->duration(5000)
                ->send();

            return $schedule;

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body('Gagal menyimpan jadwal: ' . $e->getMessage())
                ->danger()
                ->send();
            
            throw $e;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Disable default notification karena sudah custom
    }

    protected function afterCreate(): void
    {
        // ✅ LOG: Optional logging untuk audit trail
        Log::info('Doctor schedule created', [
            'doctor_id' => $this->record->doctor_id,
            'doctor_name' => $this->record->doctor_name,
            'service_id' => $this->record->service_id,
            'days' => $this->record->days,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }
}