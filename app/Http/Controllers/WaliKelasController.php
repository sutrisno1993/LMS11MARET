<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class WaliKelasController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Cari kelas perwalian guru
        $kelas = \App\Models\Clas::where('id_guru_wali', $user->id_guru)->first();

        if (!$kelas) {
            return Inertia::render('WaliKelas/Dashboard', [
                'kelas' => null,
                'students' => [],
                'subjects' => [],
            ]);
        }

        // Ambil mata pelajaran di kelas ini beserta gurunya
        $classSubjects = \App\Models\ClassSubject::with(['subject'])
            ->where('id_kelas', $kelas->id_kelas)
            ->get();

        $subjects = $classSubjects->map(fn($cs) => [
            'id_mapel' => $cs->id_mapel,
            'nama_mapel' => $cs->subject->nama_mapel ?? 'Unknown',
            'id_class_subject' => $cs->id_class_subject,
        ]);

        $studentsRaw = \App\Models\Student::where('id_kelas', $kelas->id_kelas)
            ->orderBy('nama_siswa')
            ->get();

        // Ambil data nilai akhir (ReportCard)
        $reportCards = \App\Models\ReportCard::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
            ->whereIn('id_class_subject', $classSubjects->pluck('id_class_subject'))
            ->get()
            ->groupBy('id_siswa');

        // Ambil data absensi semester untuk rekap persentase kehadiran
        $attendances = \App\Models\StudentAttendance::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
            ->get()
            ->groupBy('id_siswa');

        $studentsData = $studentsRaw->map(function($siswa) use ($reportCards, $attendances, $classSubjects) {
            $studentAtts = $attendances->get($siswa->id_siswa) ?? collect();
            $hadir = $studentAtts->where('status', 'HADIR')->count();
            $totalAtt = $studentAtts->count();
            $kehadiran = $totalAtt > 0 ? round(($hadir / $totalAtt) * 100) : 100;

            // Map nilai per mapel
            $grades = [];
            $totalRapor = 0;
            $countedMapels = 0;

            $studentRCs = $reportCards->get($siswa->id_siswa) ?? collect();

            foreach ($classSubjects as $cs) {
                $rc = $studentRCs->firstWhere('id_class_subject', $cs->id_class_subject);
                $nilai_akhir = $rc ? $rc->nilai_akhir : null;
                $nilai_sas = $rc ? $rc->nilai_sas : null;

                $grades[$cs->id_mapel] = [
                    'nilai_sas' => $nilai_sas,
                    'nilai_akhir' => $nilai_akhir,
                ];

                if ($nilai_akhir !== null) {
                    $totalRapor += $nilai_akhir;
                    $countedMapels++;
                }
            }

            $raporPct = $classSubjects->count() > 0 ? round(($countedMapels / $classSubjects->count()) * 100) : 0;
            $rataRapor = $countedMapels > 0 ? round($totalRapor / $countedMapels, 1) : null;

            return [
                'id' => $siswa->id_siswa,
                'nama' => $siswa->nama_siswa,
                'nis' => $siswa->nis,
                'kehadiran' => $kehadiran,
                'poin' => 0, // Mocked / Default
                'raporPct' => $raporPct,
                'grades' => $grades,
                'rataRapor' => $rataRapor,
            ];
        });

        return Inertia::render('WaliKelas/Dashboard', [
            'kelas' => $kelas,
            'students' => $studentsData,
            'subjects' => $subjects,
        ]);
    }

    public function p5Assessment()
    {
        $user = Auth::user();
        $kelas = \App\Models\Clas::where('id_guru_wali', $user->id_guru)->first();

        $students = [];
        if ($kelas) {
            $students = \App\Models\Student::where('id_kelas', $kelas->id_kelas)
                ->orderBy('nama_siswa')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id_siswa,
                    'nama' => $s->nama_siswa,
                    'nilai' => 'BSH',
                    'catatan' => '',
                ]);
        }

        return Inertia::render('WaliKelas/P5Assessment', [
            'kelas' => $kelas,
            'students' => $students,
        ]);
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

    public function pembinaanIndex(Request $request)
    {
        $user = Auth::user();
        
        // Cari kelas yang dibimbing oleh guru ini
        $kelas = \App\Models\Clas::where('id_guru_wali', $user->id_guru)->first();
        
        if (!$kelas) {
            return Inertia::render('WaliKelas/Pembinaan', [
                'students' => [],
                'kelas' => null,
                'disciplineHistory' => [],
                'filters' => []
            ]);
        }

        $semester = $request->input('semester', 'GANJIL');
        $year = $request->input('year', date('Y'));

        // Tentukan rentang tanggal
        if ($semester === 'GANJIL') {
            $startDate = \Carbon\Carbon::createFromDate($year, 7, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
        } else {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 6, 30)->endOfMonth()->toDateString();
        }

        // Ambil data siswa
        $studentsRaw = \App\Models\Student::where('id_kelas', $kelas->id_kelas)
            ->orderBy('nama_siswa')
            ->get();

        // Ambil semua attendances siswa di kelas ini pada rentang semester
        $attendances = \App\Models\StudentAttendance::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
            ->whereHas('kbmSession', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('id_siswa');

        $studentsData = $studentsRaw->map(function($siswa) use ($attendances) {
            $studentAtts = $attendances->get($siswa->id_siswa) ?? collect();
            $hadir = $studentAtts->where('status', 'HADIR')->count();
            $sakit = $studentAtts->where('status', 'SAKIT')->count();
            $izin = $studentAtts->where('status', 'IZIN')->count();
            $alpa = $studentAtts->where('status', 'ALPA')->count();
            $total = $studentAtts->count();
            $persentase = $total > 0 ? round(($hadir / $total) * 100) : 100;

            return [
                'id_siswa' => $siswa->id_siswa,
                'nama' => $siswa->nama_siswa,
                'nis' => $siswa->nis,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'persentase' => $persentase,
            ];
        });

        // Ambil riwayat tindakan pembinaan
        $disciplineHistory = \App\Models\StudentDiscipline::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
            ->with('student')
            ->orderBy('tanggal_tindakan', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('WaliKelas/Pembinaan', [
            'students' => $studentsData,
            'kelas' => $kelas,
            'disciplineHistory' => $disciplineHistory,
            'filters' => [
                'semester' => $semester,
                'year' => (int)$year,
            ]
        ]);
    }

    public function simpanPembinaan(Request $request)
    {
        $request->validate([
            'id_siswa' => 'required|exists:students,id_siswa',
            'tipe_tindakan' => 'required|string',
            'tanggal_tindakan' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        \App\Models\StudentDiscipline::create([
            'id_siswa' => $request->id_siswa,
            'tipe_tindakan' => $request->tipe_tindakan,
            'tanggal_tindakan' => $request->tanggal_tindakan,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->back()->with('message', 'Tindakan pembinaan berhasil dicatat.');
    }

    public function hapusPembinaan($id)
    {
        $discipline = \App\Models\StudentDiscipline::findOrFail($id);
        $discipline->delete();

        return redirect()->back()->with('message', 'Catatan pembinaan berhasil dihapus.');
    }
}
