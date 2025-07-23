<?php
// File: app/Http/Controllers/RiwayatController.php
// PERBAIKAN untuk menampilkan dokter yang dipilih saat antrian

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        try {
            // ✅ PERBAIKAN UTAMA: Eager load semua relationship yang diperlukan
            $query = Queue::with([
                'service',              // Service/Poli
                'user',                 // Data user/pasien
                'counter',              // Loket
                'doctorSchedule',       // ✅ PERBAIKAN: Doctor yang dipilih saat antrian
                'medicalRecord.doctor'  // Doctor dari rekam medis (jika ada)
            ])->where('user_id', Auth::id());

            // ✅ Filter berdasarkan poli/service jika ada
            if ($request->filled('poli')) {
                $query->whereHas('service', function($q) use ($request) {
                    $q->where('name', $request->poli);
                });
            }

            // ✅ Urutkan berdasarkan created_at (riwayat terbaru dulu)
            $riwayatAntrian = $query->orderBy('created_at', 'desc')
                                   ->paginate(10)
                                   ->appends($request->query());

            // ✅ Data untuk filter dropdown (hanya service yang pernah digunakan user)
            $availableServices = Queue::where('user_id', Auth::id())
                                    ->with('service')
                                    ->get()
                                    ->pluck('service')
                                    ->filter()
                                    ->unique('id')
                                    ->pluck('name')
                                    ->sort()
                                    ->values();

            return view('riwayat.index', compact('riwayatAntrian', 'availableServices'));
            
        } catch (\Exception $e) {
            // ✅ Error handling yang proper dengan Log facade yang sudah diimport
            Log::error('Error in RiwayatController@index: ' . $e->getMessage());
            
            return view('riwayat.index', [
                'riwayatAntrian' => collect()->paginate(10),
                'availableServices' => collect()
            ])->with('error', 'Terjadi kesalahan saat memuat riwayat kunjungan.');
        }
    }

    /**
     * ✅ Method tambahan untuk export riwayat (opsional)
     */
    public function export(Request $request)
    {
        try {
            // ✅ PERBAIKAN: Eager load dengan doctorSchedule
            $query = Queue::with([
                'service', 
                'user', 
                'counter', 
                'doctorSchedule',       // ✅ PERBAIKAN: Doctor yang dipilih saat antrian
                'medicalRecord.doctor'  // Doctor dari rekam medis
            ])->where('user_id', Auth::id());

            if ($request->filled('poli')) {
                $query->whereHas('service', function($q) use ($request) {
                    $q->where('name', $request->poli);
                });
            }

            $riwayatAntrian = $query->orderBy('created_at', 'desc')->get();

            // Generate CSV atau PDF export
            $filename = 'riwayat_kunjungan_' . Auth::user()->name . '_' . date('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($riwayatAntrian) {
                $file = fopen('php://output', 'w');
                
                // Header CSV
                fputcsv($file, [
                    'Nomor Antrian',
                    'Nama Pasien', 
                    'Poli/Layanan',
                    'Tanggal Kunjungan',
                    'Status',
                    'Dokter',
                    'Keluhan',
                    'Diagnosis'
                ]);

                // Data
                foreach ($riwayatAntrian as $antrian) {
                    fputcsv($file, [
                        $antrian->number,
                        $antrian->name ?? '-',
                        $antrian->poli ?? '-',
                        $antrian->formatted_tanggal,
                        $antrian->status_label,
                        $antrian->doctor_name ?? '-',  // ✅ INI SEKARANG AKAN DAPAT DATA DARI DOCTOR_ID
                        $antrian->medicalRecord->chief_complaint ?? '-',
                        $antrian->medicalRecord->diagnosis ?? '-'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            Log::error('Error in RiwayatController@export: ' . $e->getMessage());
            
            return redirect()->route('riwayat.index')
                           ->with('error', 'Gagal mengexport riwayat kunjungan.');
        }
    }

    /**
     * ✅ Method untuk mendapatkan detail antrian tertentu
     */
    public function show($id)
    {
        try {
            // ✅ PERBAIKAN: Eager load dengan doctorSchedule
            $antrian = Queue::with([
                'service', 
                'user', 
                'counter', 
                'doctorSchedule',       // ✅ PERBAIKAN: Doctor yang dipilih saat antrian
                'medicalRecord.doctor'  // Doctor dari rekam medis
            ])->where('user_id', Auth::id())
              ->findOrFail($id);

            return view('riwayat.detail', compact('antrian'));
            
        } catch (\Exception $e) {
            Log::error('Error in RiwayatController@show: ' . $e->getMessage());
            
            return redirect()->route('riwayat.index')
                           ->with('error', 'Riwayat kunjungan tidak ditemukan.');
        }
    }

    /**
     * ✅ Method untuk statistik riwayat kunjungan user - DIPERBAIKI
     */
    public function statistics()
    {
        try {
            $userId = Auth::id();
            
            // ✅ Query untuk poli terbanyak yang diperbaiki
            $poliTerbanyak = Queue::where('user_id', $userId)
                                ->with('service')
                                ->get()
                                ->groupBy('service.name')
                                ->map(function($group) {
                                    return $group->count();
                                })
                                ->sortDesc()
                                ->keys()
                                ->first();
            
            $stats = [
                'total_kunjungan' => Queue::where('user_id', $userId)->count(),
                'kunjungan_selesai' => Queue::where('user_id', $userId)->where('status', 'finished')->count(),
                'kunjungan_dibatalkan' => Queue::where('user_id', $userId)->where('status', 'canceled')->count(),
                'poli_terbanyak' => $poliTerbanyak,
                'kunjungan_bulan_ini' => Queue::where('user_id', $userId)
                                            ->whereMonth('created_at', now()->month)
                                            ->whereYear('created_at', now()->year)
                                            ->count(),
                'kunjungan_tahun_ini' => Queue::where('user_id', $userId)
                                            ->whereYear('created_at', now()->year)
                                            ->count(),
            ];

            return response()->json($stats);
            
        } catch (\Exception $e) {
            Log::error('Error in RiwayatController@statistics: ' . $e->getMessage());
            
            return response()->json(['error' => 'Gagal memuat statistik'], 500);
        }
    }

    /**
     * ✅ Method alternatif untuk statistik dengan query yang lebih efisien
     */
    public function statisticsOptimized()
    {
        try {
            $userId = Auth::id();
            
            // Query yang lebih efisien untuk poli terbanyak
            $poliTerbanyak = Queue::join('services', 'queues.service_id', '=', 'services.id')
                                ->where('queues.user_id', $userId)
                                ->select('services.name')
                                ->groupBy('services.name')
                                ->orderByRaw('COUNT(*) DESC')
                                ->limit(1)
                                ->value('name');
            
            $stats = [
                'total_kunjungan' => Queue::where('user_id', $userId)->count(),
                'kunjungan_selesai' => Queue::where('user_id', $userId)->where('status', 'finished')->count(),
                'kunjungan_dibatalkan' => Queue::where('user_id', $userId)->where('status', 'canceled')->count(),
                'poli_terbanyak' => $poliTerbanyak,
                'kunjungan_bulan_ini' => Queue::where('user_id', $userId)
                                            ->whereMonth('created_at', now()->month)
                                            ->whereYear('created_at', now()->year)
                                            ->count(),
                'kunjungan_tahun_ini' => Queue::where('user_id', $userId)
                                            ->whereYear('created_at', now()->year)
                                            ->count(),
            ];

            return response()->json($stats);
            
        } catch (\Exception $e) {
            Log::error('Error in RiwayatController@statisticsOptimized: ' . $e->getMessage());
            
            return response()->json(['error' => 'Gagal memuat statistik'], 500);
        }
    }
}