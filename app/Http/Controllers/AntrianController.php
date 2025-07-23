<?php
// File: app/Http/Controllers/AntrianController.php - UPDATED dengan Session Support

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service; 
use App\Models\User;
use App\Models\DoctorSchedule;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AntrianController extends Controller
{
    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        $antrianTerbaru = Queue::with(['service', 'counter', 'user', 'doctorSchedule'])
                              ->where('user_id', $user->id)
                              ->whereNotIn('status', ['canceled']) 
                              ->latest('created_at') 
                              ->first();

        return view('antrian.index', compact('antrianTerbaru'));
    }

    /**
     * ✅ UPDATED: Show available doctors berdasarkan session yang aktif
     */
    public function create()
    {
        $user = Auth::user();
        
        // Cek antrian aktif berdasarkan tanggal_antrian hari ini
        $existingQueue = Queue::where('user_id', $user->id)
                             ->whereIn('status', ['waiting', 'serving'])
                             ->whereDate('tanggal_antrian', today())
                             ->first();

        if ($existingQueue) {
            return redirect()->route('antrian.index')->withErrors([
                'error' => 'Anda masih memiliki antrian aktif. Harap selesaikan antrian tersebut terlebih dahulu.'
            ]);
        }

        $services = Service::where('is_active', true)->get();
        
        // ✅ NEW: Get available doctor sessions untuk hari ini (default)
        $availableSessions = $this->queueService->getAvailableDoctorSessions(today());
        
        return view('antrian.ambil', compact('services', 'availableSessions'));
    }

    /**
     * ✅ UPDATED: Store dengan validasi session dokter
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'doctor_id' => 'nullable|exists:doctor_schedules,id',
            'tanggal' => 'required|date|after_or_equal:today',
            'chief_complaint' => 'nullable|string|max:1000',
        ], [
            'service_id.required' => 'Layanan harus dipilih',
            'doctor_id.exists' => 'Dokter yang dipilih tidak valid',
            'tanggal.required' => 'Tanggal antrian harus dipilih',
            'tanggal.date' => 'Format tanggal tidak valid',
            'tanggal.after_or_equal' => 'Tanggal antrian tidak boleh lebih awal dari hari ini',
            'chief_complaint.max' => 'Keluhan maksimal 1000 karakter',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $tanggalAntrian = Carbon::parse($request->tanggal)->format('Y-m-d');

            // Cek existing queue berdasarkan tanggal_antrian
            $existingQueue = Queue::where('user_id', $user->id)
                                 ->whereIn('status', ['waiting', 'serving'])
                                 ->whereDate('tanggal_antrian', $tanggalAntrian)
                                 ->first();

            if ($existingQueue) {
                DB::rollBack();
                return back()->withErrors([
                    'error' => 'Anda sudah memiliki antrian aktif pada tanggal ' . Carbon::parse($tanggalAntrian)->format('d F Y')
                ])->withInput();
            }

            // ✅ NEW: Gunakan addQueue dengan doctor_id untuk session support
            $queue = $this->queueService->addQueue(
                $request->service_id, 
                $user->id, 
                null, // ktpData
                $tanggalAntrian,
                $request->doctor_id // ✅ IMPORTANT: Pass doctor_id untuk session
            );

            // Set data tambahan
            $queue->update([
                'chief_complaint' => $request->chief_complaint,
            ]);

            DB::commit();

            // ✅ SUCCESS MESSAGE dengan info session
            $message = 'Antrian berhasil dibuat untuk tanggal ' . Carbon::parse($tanggalAntrian)->format('d F Y') . '! Nomor antrian Anda: ' . $queue->number;
            
            if (Carbon::parse($tanggalAntrian)->isToday()) {
                $message .= ' (hari ini)';
            }
            
            // ✅ INFO DOKTER dan SESSION
            if ($request->doctor_id && $queue->doctorSchedule) {
                $doctor = $queue->doctorSchedule;
                $message .= ' | Dokter: ' . $doctor->doctor_name;
                $message .= ' | Sesi: ' . $doctor->start_time->format('H:i') . ' - ' . $doctor->end_time->format('H:i');
                
                // ✅ ESTIMASI BERDASARKAN SESSION
                if ($queue->estimated_call_time) {
                    $message .= ' | Estimasi dipanggil: ' . $queue->estimated_call_time->format('H:i') . ' WIB';
                }
            }
            
            if (!empty($request->chief_complaint)) {
                $shortComplaint = strlen($request->chief_complaint) > 50 
                    ? substr($request->chief_complaint, 0, 50) . '...'
                    : $request->chief_complaint;
                $message .= ' | Keluhan: ' . $shortComplaint;
            }

            return redirect()->route('antrian.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat membuat antrian: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function show($id)
    {
        $queue = Queue::with(['service', 'counter', 'user', 'doctorSchedule'])->findOrFail($id);
        
        if ($queue->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('antrian.show', compact('queue'));
    }

    public function edit($id)
    {
        $queue = Queue::with(['service', 'counter', 'user', 'doctorSchedule'])->findOrFail($id);
        
        if ($queue->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($queue->status, ['waiting'])) {
            return redirect()->route('antrian.index')
                           ->withErrors(['error' => 'Antrian tidak dapat diedit karena sudah dipanggil atau selesai.']);
        }

        $services = Service::where('is_active', true)->get();
        
        // ✅ NEW: Get available sessions untuk tanggal antrian
        $availableSessions = $this->queueService->getAvailableDoctorSessions($queue->tanggal_antrian);
        
        return view('antrian.edit', compact('queue', 'services', 'availableSessions'));
    }

    /**
     * ✅ UPDATED: Update dengan session support
     */
    public function update(Request $request, $id)
    {
        $queue = Queue::findOrFail($id);
        
        if ($queue->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($queue->status, ['waiting'])) {
            return redirect()->route('antrian.index')
                           ->withErrors(['error' => 'Antrian tidak dapat diubah karena sudah dipanggil atau selesai.']);
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'doctor_id' => 'nullable|exists:doctor_schedules,id',
            'chief_complaint' => 'nullable|string|max:1000',
        ], [
            'chief_complaint.max' => 'Keluhan maksimal 1000 karakter',
        ]);

        try {
            DB::beginTransaction();

            // ✅ NEW: Validasi dokter jika berubah
            if ($request->doctor_id && $request->doctor_id != $queue->doctor_id) {
                $this->queueService->validateDoctorSession($request->doctor_id, $queue->tanggal_antrian);
            }

            $updateData = [
                'service_id' => $request->service_id,
                'doctor_id' => $request->doctor_id,
                'chief_complaint' => $request->chief_complaint,
            ];
            
            // ✅ UPDATED: Generate nomor baru berdasarkan session jika perlu
            $needNewNumber = false;
            
            if ($queue->service_id != $request->service_id) {
                $needNewNumber = true;
            }
            
            if ($queue->doctor_id != $request->doctor_id) {
                $needNewNumber = true;
            }
            
            if ($needNewNumber) {
                $updateData['number'] = $this->queueService->generateNumberForSession(
                    $request->service_id, 
                    $queue->tanggal_antrian,
                    $request->doctor_id
                );
            }

            $queue->update($updateData);
            DB::commit();

            $message = 'Antrian berhasil diubah! Nomor: ' . $queue->number;
            
            // ✅ INFO SESSION jika ada dokter
            if ($request->doctor_id) {
                $doctor = DoctorSchedule::find($request->doctor_id);
                if ($doctor) {
                    $message .= ' | Dokter: ' . $doctor->doctor_name;
                    $message .= ' | Sesi: ' . $doctor->start_time->format('H:i') . ' - ' . $doctor->end_time->format('H:i');
                }
            }
            
            if ($request->filled('chief_complaint')) {
                $shortComplaint = strlen($request->chief_complaint) > 50 
                    ? substr($request->chief_complaint, 0, 50) . '...'
                    : $request->chief_complaint;
                $message .= ' | Keluhan: ' . $shortComplaint;
            }

            return redirect()->route('antrian.index')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengubah antrian: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        $queue = Queue::findOrFail($id);
        
        if ($queue->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($queue->status, ['waiting'])) {
            return redirect()->route('antrian.index')
                           ->withErrors(['error' => 'Antrian tidak dapat dibatalkan karena sudah dipanggil atau selesai.']);
        }

        try {
            // ✅ NEW: Gunakan QueueService untuk cancel (update estimasi otomatis)
            $this->queueService->cancelQueue($queue);
            
            return redirect()->route('antrian.index')->with('success', 'Antrian berhasil dibatalkan!');
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat membatalkan antrian: ' . $e->getMessage()
            ]);
        }
    }

    public function print($id)
    {
        $queue = Queue::with(['service', 'counter', 'user', 'doctorSchedule'])->findOrFail($id);
        
        if ($queue->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('antrian.print', ['antrian' => $queue]);
    }

    /**
     * ✅ NEW: API untuk get available sessions berdasarkan tanggal
     */
    public function getAvailableSessions(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|after_or_equal:today',
        ]);

        try {
            $tanggalAntrian = Carbon::parse($request->tanggal)->format('Y-m-d');
            $availableSessions = $this->queueService->getAvailableDoctorSessions($tanggalAntrian);
            
            return response()->json([
                'success' => true,
                'sessions' => $availableSessions,
                'date' => Carbon::parse($tanggalAntrian)->format('d F Y'),
                'count' => $availableSessions->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: API untuk validasi session dokter real-time
     */
    public function validateDoctorSession(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctor_schedules,id',
            'tanggal' => 'required|date|after_or_equal:today',
        ]);

        try {
            $doctorId = $request->doctor_id;
            $tanggalAntrian = Carbon::parse($request->tanggal)->format('Y-m-d');
            
            // Validasi session
            $this->queueService->validateDoctorSession($doctorId, $tanggalAntrian);
            
            $doctor = DoctorSchedule::find($doctorId);
            $queueCount = Queue::where('doctor_id', $doctorId)
                ->whereDate('tanggal_antrian', $tanggalAntrian)
                ->where('status', 'waiting')
                ->count();
            
            $estimatedWait = ($queueCount + 1) * 15;
            $nextNumber = $this->queueService->generateNumberForSession($doctor->service_id, $tanggalAntrian, $doctorId);
            
            return response()->json([
                'success' => true,
                'valid' => true,
                'doctor_info' => [
                    'name' => $doctor->doctor_name,
                    'service' => $doctor->service->name ?? 'Unknown',
                    'session_time' => $doctor->start_time->format('H:i') . ' - ' . $doctor->end_time->format('H:i'),
                    'current_queue_count' => $queueCount,
                    'estimated_wait_minutes' => $estimatedWait,
                    'next_queue_number' => $nextNumber,
                    'session_status' => Carbon::parse($tanggalAntrian)->isToday() && now()->format('H:i') >= $doctor->end_time->format('H:i') ? 'ended' : 'active'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'valid' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ UPDATED: API untuk preview nomor antrian dengan session support
     */
    public function previewQueueNumber(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tanggal' => 'required|date|after_or_equal:today',
            'doctor_id' => 'nullable|exists:doctor_schedules,id',
        ]);

        try {
            $serviceId = $request->service_id;
            $tanggalAntrian = Carbon::parse($request->tanggal)->format('Y-m-d');
            $doctorId = $request->doctor_id;
            
            // ✅ NEW: Validasi session jika ada dokter
            if ($doctorId) {
                $this->queueService->validateDoctorSession($doctorId, $tanggalAntrian);
            }
            
            // Preview nomor antrian yang akan didapat
            $previewNumber = $this->queueService->generateNumberForSession($serviceId, $tanggalAntrian, $doctorId);
            
            // Hitung posisi dalam antrian (session atau non-session)
            if ($doctorId) {
                $currentQueues = Queue::where('service_id', $serviceId)
                    ->where('doctor_id', $doctorId)
                    ->where('status', 'waiting')
                    ->whereDate('tanggal_antrian', $tanggalAntrian)
                    ->count();
            } else {
                $currentQueues = Queue::where('service_id', $serviceId)
                    ->where('status', 'waiting')
                    ->whereDate('tanggal_antrian', $tanggalAntrian)
                    ->whereNull('doctor_id')
                    ->count();
            }
            
            $position = $currentQueues + 1;
            $service = Service::find($serviceId);
            
            $result = [
                'success' => true,
                'preview_number' => $previewNumber,
                'position' => $position,
                'service_name' => $service->name,
                'date' => Carbon::parse($tanggalAntrian)->format('d F Y'),
                'estimated_time' => $position * 15, // menit
            ];
            
            // ✅ NEW: Tambah info session jika ada dokter
            if ($doctorId) {
                $doctor = DoctorSchedule::find($doctorId);
                if ($doctor) {
                    $result['doctor_info'] = [
                        'name' => $doctor->doctor_name,
                        'session_time' => $doctor->start_time->format('H:i') . ' - ' . $doctor->end_time->format('H:i'),
                        'estimated_call_time' => Carbon::parse($tanggalAntrian)->isToday() 
                            ? now()->max(Carbon::parse($tanggalAntrian)->setTimeFromTimeString($doctor->start_time->format('H:i')))->addMinutes($position * 15)->format('H:i')
                            : Carbon::parse($tanggalAntrian)->setTimeFromTimeString($doctor->start_time->format('H:i'))->addMinutes($position * 15)->format('H:i')
                    ];
                }
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ UPDATED: API untuk cek available slots dengan session support
     */
    public function checkAvailableSlots(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tanggal' => 'required|date|after_or_equal:today',
            'doctor_id' => 'nullable|exists:doctor_schedules,id',
        ]);

        try {
            $serviceId = $request->service_id;
            $tanggalAntrian = Carbon::parse($request->tanggal)->format('Y-m-d');
            $doctorId = $request->doctor_id;
            
            // Hitung antrian yang sudah ada (session atau non-session)
            if ($doctorId) {
                $existingQueues = Queue::where('service_id', $serviceId)
                    ->where('doctor_id', $doctorId)
                    ->whereDate('tanggal_antrian', $tanggalAntrian)
                    ->count();
            } else {
                $existingQueues = Queue::where('service_id', $serviceId)
                    ->whereDate('tanggal_antrian', $tanggalAntrian)
                    ->whereNull('doctor_id')
                    ->count();
            }
            
            $service = Service::find($serviceId);
            $maxSlots = pow(10, $service->padding) - 1; // Maksimal slot berdasarkan padding
            $availableSlots = max(0, $maxSlots - $existingQueues);
            
            $result = [
                'success' => true,
                'existing_queues' => $existingQueues,
                'available_slots' => $availableSlots,
                'max_slots' => $maxSlots,
                'service_name' => $service->name,
                'date' => Carbon::parse($tanggalAntrian)->format('d F Y'),
                'is_full' => $availableSlots <= 0,
            ];
            
            // ✅ NEW: Tambah info session jika ada dokter
            if ($doctorId) {
                $doctor = DoctorSchedule::find($doctorId);
                if ($doctor) {
                    $result['doctor_info'] = [
                        'name' => $doctor->doctor_name,
                        'session_time' => $doctor->start_time->format('H:i') . ' - ' . $doctor->end_time->format('H:i'),
                    ];
                }
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}