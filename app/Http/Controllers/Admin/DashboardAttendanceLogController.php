<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardAttendanceLog;
use App\Models\DashboardAttendanceSessionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardAttendanceLogController extends Controller
{
    public function destroy(DashboardAttendanceLog $log): RedirectResponse
    {
        $log->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Log presensi guru berhasil dihapus dari dashboard.');
    }

    public function destroySession(DashboardAttendanceSessionLog $sessionLog): RedirectResponse
    {
        $sessionLog->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Log sesi presensi guru berhasil dihapus dari dashboard.');
    }

    public function destroyPeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode' => ['required', 'in:hari,minggu,bulan,semua'],
        ]);
        $deleted = DB::transaction(function () use ($validated): int {
            $query = DashboardAttendanceLog::query();
            $sessionQuery = DashboardAttendanceSessionLog::query();

            foreach ([$query, $sessionQuery] as $deleteQuery) {
                match ($validated['periode']) {
                    'hari' => $deleteQuery->whereDate('tanggal', today()),
                    'minggu' => $deleteQuery->whereDate('tanggal', '>=', today()->subDays(6))
                        ->whereDate('tanggal', '<=', today()),
                    'bulan' => $deleteQuery->whereDate('tanggal', '>=', today()->subMonth())
                        ->whereDate('tanggal', '<=', today()),
                    'semua' => null,
                };
            }

            return $query->delete() + $sessionQuery->delete();
        });

        return redirect()->route('admin.dashboard')
            ->with('success', 'Log presensi dashboard berhasil dihapus: '.$deleted.' catatan.');
    }
}
