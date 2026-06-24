<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class WaliKelasController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('WaliKelas/Dashboard');
    }

    public function p5Assessment()
    {
        return Inertia::render('WaliKelas/P5Assessment');
    }

    public function jurnalIndex(Request $request)
    {
        $user = Auth::user();
        
        // Cari kelas yang dibimbing oleh guru ini
        $kelas = \App\Models\Clas::where('id_guru_wali', $user->id_guru)->first();
        
        if (!$kelas) {
            return Inertia::render('WaliKelas/Jurnal', [
                'sessions' => [],
                'kelas' => null,
                'teacherStats' => [],
                'filters' => []
            ]);
        }

        $semester = $request->input('semester', 'GANJIL'); // GANJIL = Jul-Des, GENAP = Jan-Jun
        $year = $request->input('year', date('Y'));
        $id_mapel = $request->input('id_mapel');

        // Tentukan rentang tanggal berdasarkan semester
        if ($semester === 'GANJIL') {
            $startDate = \Carbon\Carbon::createFromDate($year, 7, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
        } else {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 6, 30)->endOfMonth()->toDateString();
        }

        // Ambil semua sesi KBM untuk kelas ini dalam rentang semester/tahun
        $sessionsAll = \App\Models\KbmSession::with(['guruTerjadwal', 'guruAktual'])
            ->where('id_kelas', $kelas->id_kelas)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        // Kumpulkan ID guru pengajar di kelas ini
        $teacherIds = $sessionsAll->pluck('id_guru_terjadwal')
            ->merge($sessionsAll->pluck('id_guru_aktual'))
            ->filter()
            ->unique();

        $teachers = \App\Models\Teacher::whereIn('id_guru', $teacherIds)->orderBy('nama_guru')->get();
        $subjects = \App\Models\Subject::orderBy('nama_mapel')->get();

        $teacherStats = $teachers->map(function ($teacher) use ($sessionsAll) {
            $sessions = $sessionsAll->filter(function($s) use ($teacher) {
                return $s->id_guru_terjadwal == $teacher->id_guru || $s->id_guru_aktual == $teacher->id_guru;
            });
            $total = $sessions->count();
            $hadir = $sessions->whereIn('status_guru', ['HADIR', 'TERLAMBAT'])->count();
            $alpa = $sessions->where('status_guru', 'ALPA')->count();

            return [
                'id_guru' => $teacher->id_guru,
                'nama_guru' => $teacher->nama_guru,
                'total_sesi' => $total,
                'hadir' => $hadir,
                'alpa' => $alpa,
                'persentase_kehadiran' => $total > 0 ? round(($hadir / $total) * 100, 1) : 100,
            ];
        });

        $query = \App\Models\KbmSession::with(['subject', 'guruAktual', 'guruTerjadwal', 'attendances.student'])
            ->where('id_kelas', $kelas->id_kelas)
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($id_mapel) {
            $query->where('id_mapel', $id_mapel);
        }

        $sessions = $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_ke', 'desc')
            ->get()
            ->map(function($session) {
                $totalSiswa = \App\Models\Student::where('id_kelas', $session->id_kelas)->count();
                $hadir = $session->attendances->where('status', 'HADIR')->count();
                $sakit = $session->attendances->where('status', 'SAKIT')->count();
                $izin = $session->attendances->where('status', 'IZIN')->count();
                $alpa = $session->attendances->where('status', 'ALPA')->count();

                $details = $session->attendances->map(function($att) {
                    return [
                        'nama' => $att->student->nama_siswa ?? 'Unknown',
                        'nis' => $att->student->nis ?? '-',
                        'status' => $att->status,
                        'waktu' => $att->waktu_presensi ? $att->waktu_presensi->format('H:i') : '-',
                        'metode' => $att->metode,
                    ];
                })->sortBy('nama')->values();

                return [
                    'id' => $session->id,
                    'tanggal' => $session->tanggal->format('Y-m-d'),
                    'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                    'guru' => $session->guruAktual->nama_guru ?? $session->guruTerjadwal->nama_guru ?? 'Unknown',
                    'jam_ke' => $session->jam_ke,
                    'status_sesi' => $session->status_sesi,
                    'status_guru' => $session->status_guru,
                    'materi_log' => $session->materi_log ?? '-',
                    'scan_masuk' => $session->waktu_scan_masuk ? $session->waktu_scan_masuk->format('H:i') : '-',
                    'stats' => [
                        'total' => $totalSiswa,
                        'hadir' => $hadir,
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alpa' => $alpa,
                    ],
                    'details' => $details,
                ];
            });

        return Inertia::render('WaliKelas/Jurnal', [
            'sessions' => $sessions,
            'kelas' => $kelas,
            'subjects' => $subjects,
            'teacherStats' => $teacherStats,
            'filters' => [
                'year' => (int)$year,
                'semester' => $semester,
                'id_mapel' => $id_mapel ? (int)$id_mapel : null,
            ]
        ]);
    }

}
