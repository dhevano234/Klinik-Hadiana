<?php
// File: app/Http/Controllers/Api/UserController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str; // ✅ ADD this import

class UserController extends Controller
{
    /**
     * Get user medical record number
     */
    public function getMedicalRecord(int $userId): JsonResponse
    {
        try {
            $user = User::where('id', $userId)
                       ->where('role', 'user')
                       ->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'medical_record_number' => null,
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'medical_record_number' => $user->medical_record_number,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'medical_record_number' => $user->medical_record_number,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving medical record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get user complete details for medical record form
     */
    public function getDetails(int $userId): JsonResponse
    {
        try {
            $user = User::where('id', $userId)
                       ->where('role', 'user')
                       ->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'medical_record_number' => $user->medical_record_number,
                'patient_details' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'medical_record_number' => $user->medical_record_number,
                    'nomor_ktp' => $user->nomor_ktp,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'gender_label' => $user->gender_label,
                    'birth_date' => $user->birth_date?->format('Y-m-d'),
                    'age' => $user->age,
                    'address' => $user->address,
                    'has_medical_record' => !empty($user->medical_record_number),
                    'profile_complete' => $user->isProfileCompleteForQueue(),
                ],
                'formatted_info' => $this->formatPatientInfo($user),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving patient details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Search users for medical record (with medical record number)
     */
    public function searchForMedicalRecord(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            $users = User::where('role', 'user')
                        ->where(function ($q) use ($query) {
                            $q->where('name', 'LIKE', "%{$query}%")
                              ->orWhere('medical_record_number', 'LIKE', "%{$query}%")
                              ->orWhere('nomor_ktp', 'LIKE', "%{$query}%");
                        })
                        ->limit(20)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'medical_record_number' => $user->medical_record_number,
                                'label' => $user->medical_record_number 
                                    ? "【{$user->medical_record_number}】 {$user->name}"
                                    : "【Belum ada No. RM】 {$user->name}",
                                'has_mrn' => !empty($user->medical_record_number),
                            ];
                        });
            
            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Format patient information for display
     */
    private function formatPatientInfo(User $user): string
    {
        $info = [];
        
        if ($user->medical_record_number) {
            $info[] = "📋 **No. RM:** {$user->medical_record_number}";
        } else {
            $info[] = "📋 **No. RM:** ⚠️ Belum ada";
        }
        
        if ($user->nomor_ktp) {
            $info[] = "🆔 **NIK:** {$user->nomor_ktp}";
        }
        
        if ($user->phone) {
            $info[] = "📱 **HP:** {$user->phone}";
        }
        
        if ($user->gender) {
            $info[] = "⚥ **Jenis Kelamin:** {$user->gender_label}";
        }
        
        if ($user->age) {
            $info[] = "🎂 **Umur:** {$user->age} tahun";
        }
        
        if ($user->address && $user->address !== 'Alamat belum diisi') {
            $info[] = "🏠 **Alamat:** " . Str::limit($user->address, 50);
        }
        
        return implode("\n", $info);
    }
}