<?php
// File: app/Http/Controllers/RegisterController.php - UPDATED

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('register.index');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nomor_ktp' => 'required|string|size:16|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20|unique:users',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:Laki-laki,Perempuan',
            'address' => 'nullable|string',
        ], [
            'nomor_ktp.required' => 'Nomor KTP harus diisi',
            'nomor_ktp.size' => 'Nomor KTP harus 16 digit',
            'nomor_ktp.unique' => 'Nomor KTP sudah terdaftar',
            'email.unique' => 'Email sudah terdaftar',
            'phone.unique' => 'Nomor HP sudah terdaftar',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // ✅ Cek apakah user dengan KTP ini sudah ada
            $existingUser = User::where('nomor_ktp', $request->nomor_ktp)->first();
            
            if ($existingUser) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors(['nomor_ktp' => 'Nomor KTP sudah terdaftar dengan nama: ' . $existingUser->name])
                    ->withInput();
            }

            // ✅ Buat user baru - nomor RM akan auto-generate di boot method
            $user = User::create([
                'name' => $request->name,
                'nomor_ktp' => $request->nomor_ktp,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'address' => $request->address ?: 'Alamat belum diisi',
                'role' => 'user', // Default role untuk registrasi publik
            ]);

            DB::commit();

            // ✅ Success message dengan nomor RM
            $successMessage = 'Registrasi berhasil!';

            return redirect()->route('login')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat registrasi. Silakan coba lagi.'])
                ->withInput();
        }
    }
}