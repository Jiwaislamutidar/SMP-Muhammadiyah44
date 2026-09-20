<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        Auth::logout();
        // Pastikan ini mengarah ke file view login khusus untuk guru
        return view('auth.guru.login'); 
    }

    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Proses pengecekan ke database
        if (Auth::attempt($credentials, $request->has('remember'))) {
            $user = Auth::user();

            // CEK ROLE: Pastikan yang berhasil login hanya user dengan role 'guru'
            if ($user->role === 'guru') { // Sesuaikan nama kolom role dengan database-mu
                $request->session()->regenerate();
                // Redirect ke nama route dashboard guru (sesuai dengan web.php)
                return redirect()->route('guru.dashboard'); 
            }

            // Jika role BUKAN guru (misal, akun siswa yang mencoba masuk)
            Auth::logout(); // Segera keluarkan secara paksa
            return back()->with('error', 'Akses ditolak! Halaman ini khusus untuk Guru.')->withInput();
        }

        // Jika username atau password tidak ada di database
        return back()->with('error', 'Username atau password salah!')->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect ke halaman login guru setelah logout
        return redirect()->route('guru.login');
    }
}