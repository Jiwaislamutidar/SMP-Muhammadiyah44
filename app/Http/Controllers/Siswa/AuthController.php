<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'siswa') {
            return redirect()->route('siswa.dashboard');
        }

        return view('auth.siswa.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $user = Auth::user();

            if ($user->role === 'siswa') {
                $request->session()->regenerate();
                return redirect()->route('siswa.dashboard');
            }

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