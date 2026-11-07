<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingController extends Controller
{
    // Menampilkan halaman pengaturan beserta data setting database
    public function index(): View
    {
        $setting = Setting::first() ?? new Setting([
            'jam_masuk' => '07:00',
            'jam_toleransi' => '07:15',
            'jam_pulang_senin' => '14:30',
            'jam_pulang_selasa' => '13:00',
            'jam_pulang_rabu' => '14:30',
            'jam_pulang_kamis' => '13:00',
            'jam_pulang_jumat' => '12:30',
        ]);

        return view('pages.admin.profil', compact('setting'));
    }

    // Simpan Konfigurasi Jam Presensi
    public function updatePresensi(Request $request)
    {
        $request->validate([
            'jam_masuk' => 'required',
            'jam_toleransi' => 'required',
            'jam_pulang_senin' => 'required',
            'jam_pulang_selasa' => 'required',
            'jam_pulang_rabu' => 'required',
            'jam_pulang_kamis' => 'required',
            'jam_pulang_jumat' => 'required',
        ]);

        $setting = Setting::first() ?? new Setting();
        $setting->fill($request->only([
            'jam_masuk', 'jam_toleransi',
            'jam_pulang_senin', 'jam_pulang_selasa', 
            'jam_pulang_rabu', 'jam_pulang_kamis', 'jam_pulang_jumat'
        ]));
        $setting->save();

        return response()->json(['success' => true, 'message' => 'Jadwal presensi berhasil diperbarui!']);
    }

    // Simpan Akun Admin (Username & Password)
    public function updateAkun(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'username' => 'required|unique:users,username,' . $user->id,
            'current_password' => 'required',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Kata sandi saat ini salah!'], 422);
        }

        $user->username = $request->username;
        
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }
        
        $user->save();

        return response()->json(['success' => true, 'message' => 'Kredensial akun berhasil diperbarui!']);
    }
}