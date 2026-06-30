<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DevTimeController extends Controller
{
    public function update(Request $request)
    {
        if (!app()->environment(['local', 'development'])) {
            abort(403, 'Unauthorized action in production.');
        }

        $target = Carbon::parse($request->target_time);
        $realTime = time();
        
        // Calculate offset in seconds (target_time - real_time)
        $offset = $target->timestamp - $realTime;

        if ($offset < 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'target_time' => 'Waktu simulasi hanya boleh diatur ke masa depan (ke depan).',
            ]);
        }

        Cache::forever('time_offset', $offset);
        
        // Clear schedules cache so it regenerates for the new simulated day
        for ($i = 0; $i < 7; $i++) {
            Cache::forget('jp_schedules_today_' . $i);
        }

        // Reset KBM sessions on the target simulated date back to PENDING
        // and delete related student attendances to allow clean KBM simulation testing
        $targetDate = $target->toDateString();
        Cache::forget('kbm_generated_' . $targetDate);
        
        $sessions = \App\Models\KbmSession::where('tanggal', $targetDate)->get();
        foreach ($sessions as $session) {
            $session->status_sesi = 'PENDING';
            $session->status_guru = 'PENDING';
            $session->waktu_scan_masuk = null;
            $session->waktu_scan_keluar = null;
            $session->materi_log = null;
            $session->save();

            \App\Models\StudentAttendance::where('id_kbm_session', $session->id)->delete();
        }

        return back()->with('success', 'Waktu simulasi berhasil diatur ke ' . $request->target_time);
    }

    public function reset()
    {
        if (!app()->environment(['local', 'development'])) {
            abort(403, 'Unauthorized action in production.');
        }

        Cache::forget('time_offset');
        
        // Clear schedules cache
        for ($i = 0; $i < 7; $i++) {
            Cache::forget('jp_schedules_today_' . $i);
        }

        return back()->with('success', 'Simulasi waktu direset ke waktu sistem riil.');
    }

    public function bypassScan($id)
    {
        if (!app()->environment(['local', 'development'])) {
            abort(403, 'Unauthorized action in production.');
        }

        $session = \App\Models\KbmSession::findOrFail($id);

        // Find the first student in this class
        $siswa = \App\Models\Student::where('id_kelas', $session->id_kelas)->first();
        if (!$siswa) {
            return back()->with('error', 'Tidak ada siswa terdaftar di kelas ini untuk simulasi scan.');
        }

        // Cari semua sesi dalam blok yang sama
        $blockSessions = \App\Models\KbmSession::where('tanggal', $session->tanggal)
            ->where('id_kelas', $session->id_kelas)
            ->where('id_mapel', $session->id_mapel)
            ->where('id_guru_terjadwal', $session->id_guru_terjadwal)
            ->get();

        foreach ($blockSessions as $bSession) {
            $bSession->status_sesi = 'AKTIF';
            $bSession->status_guru = 'HADIR';
            $bSession->waktu_scan_masuk = now();
            $bSession->save();

            // Inisialisasi absensi semua siswa di kelas ini sebagai default HADIR
            $studentsInClass = \App\Models\Student::where('id_kelas', $bSession->id_kelas)->get();
            foreach ($studentsInClass as $s) {
                \App\Models\StudentAttendance::updateOrCreate(
                    [
                        'id_kbm_session' => $bSession->id,
                        'id_siswa' => $s->id_siswa,
                    ],
                    [
                        'status' => 'HADIR',
                        'metode' => $s->id_siswa === $siswa->id_siswa ? 'SCAN_QR' : 'SYSTEM',
                        'waktu_presensi' => $s->id_siswa === $siswa->id_siswa ? now() : null,
                    ]
                );
            }
        }

        return back()->with('success', 'Simulasi scan berhasil! Sesi KBM diaktifkan untuk kelas ini.');
    }
}
