<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        Auth::logout();
        return view('auth.siswa.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $user = Auth::user();

            // KUNCI KEAMANAN: Cek apakah yang login benar-benar Siswa
            if ($user->role === 'siswa') { // Sesuaikan nama kolom/role di databasemu (misal: 'siswa')
                $request->session()->regenerate();
                return redirect()->route('siswa.dashboard');
            }

            // Jika BUKAN siswa (misal Guru coba login di sini), paksa logout
            Auth::logout();
            return back()->with('error', 'Akses ditolak! Halaman ini khusus untuk Siswa.');
        }

        return back()->with('error', 'Username atau password salah!')->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}