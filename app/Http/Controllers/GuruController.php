<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Models\Timetable;

use App\Models\LearningObjective;
use App\Models\TeacherSubject;

class GuruController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $today = now()->toDateString();
        
        // Ambil sesi KBM hari ini untuk guru yang login (terjadwal atau aktual)
        $kbmSessionsRaw = \App\Models\KbmSession::with(['clas', 'subject'])
            ->where('tanggal', $today)
            ->where(function ($query) use ($user) {
                $query->where('id_guru_terjadwal', $user->id_guru)
                      ->orWhere('id_guru_aktual', $user->id_guru);
            })
            ->orderBy('jam_ke')
            ->get();

        // Format data sesuai dengan yang dibutuhkan frontend
        $formattedJadwal = $kbmSessionsRaw->map(function ($session) {
            return [
                'id' => $session->id,
                'kelas' => $session->clas->nama_kelas ?? 'Unknown',
                'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                'jam' => 'Jam ke-' . $session->jam_ke,
                'status_sesi' => $session->status_sesi,
                'status_guru' => $session->status_guru,
                'is_active' => $session->status_sesi === 'PENDING' || $session->status_sesi === 'AKTIF',
                'payload' => $session->status_sesi === 'PENDING' ? json_encode([
                    'id_kbm_session' => $session->id,
                    // Timestamp ditambahkan oleh frontend tiap 10 detik
                ]) : null,
            ];
        });

        return Inertia::render('Guru/Dashboard', [
            'jadwal' => $formattedJadwal,
        ]);
    }

    public function kbmStatus($id)
    {
        $session = \App\Models\KbmSession::find($id);
        if (!$session) {
            return response()->json(['status' => 'ERROR'], 404);
        }
        return response()->json(['status_sesi' => $session->status_sesi]);
    }

    public function sesiKbm($id)
    {
        $user = Auth::user();
        
        $session = \App\Models\KbmSession::with(['clas', 'subject'])->findOrFail($id);
        
        // Ambil bank soal yang relevan untuk mapel ini
        $questionBanks = \App\Models\QuestionBank::where('id_guru', $user->id_guru)
            ->where('id_mapel', $session->id_mapel)
            ->get();

        // Ambil data siswa di kelas ini beserta status absensinya
        $students = \App\Models\Student::where('id_kelas', $session->id_kelas)
            ->orderBy('nama_siswa')
            ->get()
            ->map(function ($s) use ($id) {
                $attendance = \App\Models\StudentAttendance::where('id_kbm_session', $id)
                    ->where('id_siswa', $s->id_siswa)
                    ->first();
                
                return [
                    'id' => $s->id_siswa,
                    'nama' => $s->nama_siswa,
                    'nis' => $s->nis,
                    'status' => $attendance ? $attendance->status : 'HADIR',
                    'metode' => $attendance ? $attendance->metode : null,
                    'waktu' => $attendance && $attendance->waktu_presensi ? $attendance->waktu_presensi->format('H:i') : null,
                ];
            });

        return Inertia::render('Guru/SesiKBM', [
            'sessionId' => $id,
            'session' => $session,
            'questionBanks' => $questionBanks,
            'students' => $students
        ]);
    }

    public function pemetaanMateri()
    {
        $user = Auth::user();
        
        // Ambil daftar mapel yang diajar guru ini
        $mapelList = TeacherSubject::with('subject')
            ->where('id_guru', $user->id_guru)
            ->get()
            ->pluck('subject')
            ->unique('id_mapel')
            ->values();

        // Ambil data Tujuan Pembelajaran (TP) yang sudah dibuat oleh guru ini
        $tpList = LearningObjective::with('subject')
            ->where('id_guru', $user->id_guru)
            ->orderBy('id_mapel')
            ->orderBy('kode_tp')
            ->get();

        return Inertia::render('Guru/PemetaanMateri', [
            'mapelList' => $mapelList,
            'tpList' => $tpList,
        ]);
    }

    public function simpanPemetaanMateri(Request $request)
    {
        $request->validate([
            'id_mapel' => 'required|exists:subjects,id_mapel',
            'kode_tp' => 'required|string|max:50',
            'deskripsi_tp' => 'required|string',
            'semester' => 'required|in:GANJIL,GENAP',
        ]);

        $user = Auth::user();

        LearningObjective::create([
            'id_guru' => $user->id_guru,
            'id_mapel' => $request->id_mapel,
            'kode_tp' => $request->kode_tp,
            'deskripsi_tp' => $request->deskripsi_tp,
            'semester' => $request->semester,
        ]);

        return redirect()->back()->with('message', 'Tujuan Pembelajaran berhasil ditambahkan.');
    }

    public function nilaiSumatif(Request $request)
    {
        $user = Auth::user();

        // 1. Ambil daftar kelas yang diajar guru
        $kelasMapelList = TeacherSubject::with(['class', 'subject'])
            ->where('id_guru', $user->id_guru)
            ->get();

        // Unique classes and subjects for dropdowns
        $kelasList = $kelasMapelList->pluck('class')->unique('id_kelas')->values();
        $mapelList = $kelasMapelList->pluck('subject')->unique('id_mapel')->values();

        // Default selection
        $selectedKelas = $request->input('id_kelas', $kelasList->first()->id_kelas ?? null);
        $selectedMapel = $request->input('id_mapel', $mapelList->first()->id_mapel ?? null);

        // 2. Ambil Tujuan Pembelajaran (TP) untuk Mapel dan Guru terkait
        $tpList = [];
        $students = [];

        if ($selectedMapel) {
            $tpList = LearningObjective::where('id_guru', $user->id_guru)
                ->where('id_mapel', $selectedMapel)
                ->orderBy('kode_tp')
                ->get();
        }

        // 3. Ambil daftar Siswa berdasarkan kelas yang dipilih beserta Nilai-nya
        if ($selectedKelas && count($tpList) > 0) {
            $tpIds = $tpList->pluck('id_tp');

            $studentsRaw = \App\Models\Student::where('id_kelas', $selectedKelas)
                ->orderBy('nama_siswa')
                ->get();

            // Ambil semua nilai siswa di kelas ini untuk TP yang relevan
            $grades = \App\Models\StudentGrade::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
                ->whereIn('id_tp', $tpIds)
                ->get()
                ->groupBy('id_siswa');

            foreach ($studentsRaw as $siswa) {
                $nilaiMapping = [];
                foreach ($tpList as $tp) {
                    // Cari nilai untuk TP ini
                    $grade = $grades->get($siswa->id_siswa)?->firstWhere('id_tp', $tp->id_tp);
                    $nilaiMapping[$tp->id_tp] = $grade ? $grade->nilai : null;
                }

                $students[] = [
                    'id' => $siswa->id_siswa,
                    'nama' => $siswa->nama_siswa,
                    'nis' => $siswa->nis,
                    'nilai' => $nilaiMapping,
                    'catatan' => '', // Nanti diambil dari tabel jika ada
                ];
            }
        }

        return Inertia::render('Guru/NilaiSumatif', [
            'kelasList' => $kelasList,
            'mapelList' => $mapelList,
            'selectedKelas' => $selectedKelas,
            'selectedMapel' => $selectedMapel,
            'tpList' => $tpList,
            'students' => $students,
        ]);
    }

    public function simpanNilaiSumatif(Request $request)
    {
        $request->validate([
            'students' => 'required|array',
        ]);

        $studentsData = $request->input('students');

        foreach ($studentsData as $siswa) {
            $idSiswa = $siswa['id'];
            $nilaiData = $siswa['nilai'] ?? []; // Array dengan key id_tp dan value nilainya

            foreach ($nilaiData as $idTp => $nilai) {
                // Jangan update/insert jika nilai kosong/null
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                \App\Models\StudentGrade::updateOrCreate(
                    [
                        'id_siswa' => $idSiswa,
                        'id_tp' => $idTp,
                    ],
                    [
                        'nilai' => $nilai,
                    ]
                );
            }
        }

        return redirect()->back()->with('message', 'Semua nilai berhasil disimpan!');
    }

    public function nilaiAkhir()
    {
        return Inertia::render('Guru/NilaiAkhir');
    }

    public function raporPreview()
    {
        return Inertia::render('Guru/RaporPreview');
    }

    public function mulaiKbm($id_sesi)
    {
        $session = \App\Models\KbmSession::findOrFail($id_sesi);
        
        if ($session->status_sesi === 'PENDING') {
            $session->status_sesi = 'AKTIF';
            $session->status_guru = 'HADIR';
            $session->waktu_scan_masuk = now();
            $session->save();

            // Inisialisasi absensi semua siswa di kelas ini sebagai default HADIR
            $studentsInClass = \App\Models\Student::where('id_kelas', $session->id_kelas)->get();
            foreach ($studentsInClass as $s) {
                \App\Models\StudentAttendance::updateOrCreate(
                    [
                        'id_kbm_session' => $session->id,
                        'id_siswa' => $s->id_siswa,
                    ],
                    [
                        'status' => 'HADIR',
                        'metode' => 'SYSTEM',
                        'waktu_presensi' => null,
                    ]
                );
            }
        }

        return redirect()->route('guru.sesi-kbm', $id_sesi)->with('message', 'Sesi KBM berhasil dimulai.');
    }

    public function selesaiKbm($id_sesi)
    {
        $session = \App\Models\KbmSession::findOrFail($id_sesi);
        $session->status_sesi = 'SELESAI';
        $session->waktu_scan_keluar = now();
        $session->save();

        return redirect()->route('guru.dashboard')->with('message', 'Sesi KBM selesai.');
    }

    public function simpanPresensi(Request $request, $id_sesi)
    {
        $request->validate([
            'students' => 'required|array',
            'materi_log' => 'nullable|string|max:255',
        ]);

        $session = \App\Models\KbmSession::findOrFail($id_sesi);
        
        // Simpan materi log ke session
        if ($request->has('materi_log')) {
            $session->materi_log = $request->materi_log;
            $session->save();
        }

        // Simpan/update presensi siswa
        foreach ($request->students as $studentData) {
            \App\Models\StudentAttendance::updateOrCreate(
                [
                    'id_kbm_session' => $id_sesi,
                    'id_siswa' => $studentData['id'],
                ],
                [
                    'status' => $studentData['status'],
                    'metode' => 'MANUAL_GURU',
                    'waktu_presensi' => $studentData['status'] === 'HADIR' ? now() : null,
                ]
            );
        }

        return redirect()->back()->with('message', 'Presensi dan Jurnal berhasil disimpan.');
    }

    public function riwayatJurnal()
    {
        $user = Auth::user();
        
        // Ambil semua sesi yang diampu guru ini dan sudah SELESAI
        $sessions = \App\Models\KbmSession::with(['clas', 'subject', 'attendances'])
            ->where(function($q) use ($user) {
                $q->where('id_guru_terjadwal', $user->id_guru)
                  ->orWhere('id_guru_aktual', $user->id_guru);
            })
            ->where('status_sesi', 'SELESAI')
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_ke', 'desc')
            ->get()
            ->map(function ($session) {
                $totalSiswa = \App\Models\Student::where('id_kelas', $session->id_kelas)->count();
                $hadir = $session->attendances->where('status', 'HADIR')->count();
                $sakit = $session->attendances->where('status', 'SAKIT')->count();
                $izin = $session->attendances->where('status', 'IZIN')->count();
                $alpa = $session->attendances->where('status', 'ALPA')->count();

                return [
                    'id' => $session->id,
                    'tanggal' => $session->tanggal->format('Y-m-d'),
                    'kelas' => $session->clas->nama_kelas ?? 'Unknown',
                    'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                    'jam_ke' => $session->jam_ke,
                    'materi_log' => $session->materi_log ?? '-',
                    'stats' => [
                        'total' => $totalSiswa,
                        'hadir' => $hadir,
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alpa' => $alpa,
                    ]
                ];
            });

        return Inertia::render('Guru/RiwayatJurnal', [
            'sessions' => $sessions,
        ]);
    }
}
