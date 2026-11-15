<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi Input Form
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        // Cek Keberadaan Username & Password di Database
        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();

            // Pengalihan Halaman Sesuai Role Akun
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            } elseif ($user->role === 'guru') {
                return redirect()->intended('/guru/dashboard');
            } else {
                return redirect()->intended('/siswa/dashboard');
            }
        }

        // Jika Username/Password Salah
        return back()->withErrors([
            'username' => 'Username atau password yang dimasukkan salah.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}