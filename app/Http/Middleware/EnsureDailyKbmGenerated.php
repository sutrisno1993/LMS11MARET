<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class EnsureDailyKbmGenerated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya cek jika request bukan untuk API atau aset statis, dan jika user sudah login
        if ($request->is('admin/*') || $request->is('guru/*') || $request->is('walikelas/*') || $request->is('siswa/*')) {
            $today = Carbon::today()->toDateString();
            $cacheKey = 'kbm_generated_' . $today;

            if (!Cache::has($cacheKey)) {
                // Generate KBM untuk hari ini
                // Gunakan date parameter sesuai dengan waktu mock yang aktif
                Artisan::call('kbm:generate-today', [
                    '--date' => $today
                ]);

                // Simpan flag di cache sampai akhir hari (berdasarkan waktu mock/asli)
                $endOfDay = Carbon::today()->endOfDay();
                Cache::put($cacheKey, true, $endOfDay);
            }

            // Jalankan pengecekan ALPA Guru secara real-time
            $this->checkTeacherAlpa($today);
        }

        return $next($request);
    }

    /**
     * Periksa sesi KBM PENDING yang sudah lewat waktu selesainya, lalu tandai Guru sebagai ALPA
     */
    private function checkTeacherAlpa($today)
    {
        $now = Carbon::now();
        $nowTime = $now->toTimeString();
        $dayIndex = $now->dayOfWeek;
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariEnum = strtoupper($days[$dayIndex]);

        // 1. Sesi KBM hari-hari sebelumnya yang masih PENDING otomatis menjadi KOSONG & ALPA
        \App\Models\KbmSession::where('tanggal', '<', $today)
            ->where('status_sesi', 'PENDING')
            ->update([
                'status_sesi' => 'KOSONG',
                'status_guru' => 'ALPA'
            ]);

        // 2. Sesi KBM hari ini yang sudah lewat jam selesainya tapi masih PENDING otomatis ALPA
        if ($hariEnum !== 'MINGGU') {
            $pendingToday = \App\Models\KbmSession::with(['clas'])
                ->where('tanggal', $today)
                ->where('status_sesi', 'PENDING')
                ->get();

            foreach ($pendingToday as $session) {
                if ($session->clas) {
                    $shift = $session->clas->shift_operasional;
                    $jp = \App\Models\JpSchedule::where('hari', $hariEnum)
                        ->where('shift', $shift)
                        ->where('jam_ke', $session->jam_ke)
                        ->first();

                    if ($jp && $jp->waktu_selesai) {
                        if ($nowTime > $jp->waktu_selesai) {
                            $session->status_sesi = 'KOSONG';
                            $session->status_guru = 'ALPA';
                            $session->save();
                        }
                    }
                }
            }
        }
    }
}
