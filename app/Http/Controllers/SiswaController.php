<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Models\Student;
use App\Models\Timetable;

class SiswaController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $today = now()->toDateString();
        
        // Ambil data siswa
        $siswa = Student::with('clas')->find($user->id_siswa);

        $jadwalRaw = [];
        if ($siswa && $siswa->id_kelas) {
            $jadwalRaw = \App\Models\KbmSession::with(['clas', 'subject', 'guruAktual'])
                ->where('id_kelas', $siswa->id_kelas)
                ->where('tanggal', $today)
                ->orderBy('jam_ke')
                ->get();
        }

        // Get day of week name
        $dayOfWeek = now()->dayOfWeek;
        $days = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        $hariEnum = $days[$dayOfWeek];
        
        $shift = $siswa->clas->shift_operasional ?? 'PAGI';

        // Retrieve JpSchedules for today and this shift
        $schedules = \App\Models\JpSchedule::where('hari', $hariEnum)
            ->where('shift', $shift)
            ->orderBy('waktu_mulai')
            ->get();

        $formattedJadwal = $schedules->map(function ($jp) use ($jadwalRaw, $user) {
            $session = collect($jadwalRaw)->firstWhere('jam_ke', $jp->jam_ke);
            
            $jamMulai = substr($jp->waktu_mulai, 0, 5);
            $jamSelesai = substr($jp->waktu_selesai, 0, 5);

            // Cek apakah JP ini adalah jam pelajaran aktif saat ini
            $nowTime = now()->toTimeString();
            $isTimeActive = ($nowTime >= $jp->waktu_mulai && $nowTime <= $jp->waktu_selesai);
            $isTimePassed = ($nowTime > $jp->waktu_selesai);

            if (!$jp->is_kbm) {
                // Jam Istirahat
                return [
                    'id' => 'break-' . $jp->id,
                    'mapel' => '☕ ISTIRAHAT',
                    'guru' => 'Istirahat Sejenak',
                    'jamKe' => 0,
                    'jamMulai' => $jamMulai,
                    'jamSelesai' => $jamSelesai,
                    'status' => 'BREAK',
                    'isTimeActive' => $isTimeActive,
                    'isTimePassed' => $isTimePassed,
                    'presensiSiswa' => null,
                    'ruang' => '-',
                ];
            }

            if ($session) {
                $attendance = \App\Models\StudentAttendance::where('id_kbm_session', $session->id)
                    ->where('id_siswa', $user->id_siswa)
                    ->first();

                return [
                    'id' => $session->id,
                    'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                    'guru' => $session->guruAktual->nama_guru ?? 'Unknown',
                    'jamKe' => $session->jam_ke,
                    'jamMulai' => $jamMulai,
                    'jamSelesai' => $jamSelesai,
                    'status' => $session->status_sesi,
                    'isTimeActive' => $isTimeActive,
                    'isTimePassed' => $isTimePassed,
                    'presensiSiswa' => $attendance ? $attendance->status : null,
                    'ruang' => 'Ruang Kelas',
                ];
            }

            // Jika kosong/tidak ada kelas di jam ke tersebut
            return [
                'id' => 'empty-' . $jp->id,
                'mapel' => 'Tidak Ada Kelas',
                'guru' => '-',
                'jamKe' => $jp->jam_ke,
                'jamMulai' => $jamMulai,
                'jamSelesai' => $jamSelesai,
                'status' => 'KOSONG',
                'isTimeActive' => $isTimeActive,
                'isTimePassed' => $isTimePassed,
                'presensiSiswa' => null,
                'ruang' => '-',
            ];
        })->values();

        return Inertia::render('Siswa/Dashboard', [
            'siswa' => $siswa,
            'kelas' => $siswa->clas->nama_kelas ?? 'Unknown',
            'jadwal' => $formattedJadwal,
        ]);
    }

    public function scanQr()
    {
        return Inertia::render('Siswa/ScanQR');
    }

    public function processScan(Request $request)
    {
        $request->validate([
            'payload' => 'required|string'
        ]);

        try {
            $data = json_decode($request->payload, true);
            if (!isset($data['id_kbm_session']) || !isset($data['timestamp'])) {
                return response()->json(['message' => 'QR Code tidak valid!'], 400);
            }

            // Validasi Kedaluwarsa (15 detik)
            $diffSeconds = (time() * 1000 - $data['timestamp']) / 1000;
            if ($diffSeconds > 15) {
                return response()->json(['message' => 'QR Code sudah KEDALUWARSA! Silakan scan QR terbaru di layar proyektor.'], 400);
            }

            $session = \App\Models\KbmSession::find($data['id_kbm_session']);
            if (!$session) {
                return response()->json(['message' => 'Sesi KBM tidak ditemukan!'], 404);
            }

            // Validasi Kelas Siswa
            $user = Auth::user();
            $siswa = Student::find($user->id_siswa);
            if ($siswa->id_kelas !== $session->id_kelas) {
                return response()->json(['message' => 'DITOLAK! Anda men-scan jadwal dari kelas lain.'], 403);
            }
            // Validasi Status Sesi
            if ($session->status_sesi !== 'SCANNING') {
                return response()->json(['message' => 'Sesi KBM ini belum dimulai oleh Guru atau sudah selesai.'], 400);
            }

            // Cari semua sesi dalam blok yang sama
            $blockSessions = \App\Models\KbmSession::where('tanggal', $session->tanggal)
                ->where('id_kelas', $session->id_kelas)
                ->where('id_mapel', $session->id_mapel)
                ->where('id_guru_terjadwal', $session->id_guru_terjadwal)
                ->get();

            foreach ($blockSessions as $bSession) {
                if ($bSession->status_sesi === 'SCANNING' || $bSession->status_sesi === 'PENDING') {
                    $bSession->status_sesi = 'AKTIF';
                    $bSession->status_guru = 'HADIR';
                    $bSession->waktu_scan_masuk = now();
                    $bSession->save();
                }

                // Inisialisasi absensi semua siswa di kelas ini sebagai default HADIR (SYSTEM)
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
            return response()->json([
                'message' => 'Berhasil! Kelas ' . ($session->subject->nama_mapel ?? '') . ' resmi dimulai.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function ujianLive()
    {
        return Inertia::render('Siswa/UjianLive');
    }

    public function getLiveExam()
    {
        $user = Auth::user();
        $siswa = Student::find($user->id_siswa);
        $today = now()->toDateString();

        // Cari sesi KBM siswa hari ini yang sedang AKTIF
        $activeSession = \App\Models\KbmSession::where('id_kelas', $siswa->id_kelas)
            ->where('tanggal', $today)
            ->where('status_sesi', 'AKTIF')
            ->first();

        if (!$activeSession) {
            return response()->json(['exam' => null, 'message' => 'Tidak ada sesi kelas yang aktif.']);
        }

        // Cari Ujian yang dilaunching ke sesi ini
        $exam = \App\Models\LiveExam::with(['questionBank.questions' => function($q) {
            // Kita sembunyikan jawaban_benar saat dikirim ke frontend!
            $q->select('id_question', 'id_bank', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e');
        }])
        ->where('id_kbm_session', $activeSession->id)
        ->where('status', 'ACTIVE')
        ->first();

        if (!$exam) {
            return response()->json(['exam' => null, 'message' => 'Tidak ada asesmen aktif saat ini.']);
        }

        // Ambil jawaban siswa yang sudah tersimpan (jika mereka me-refresh halaman)
        $answered = \App\Models\StudentAnswer::where('id_exam', $exam->id_exam)
            ->where('id_siswa', $siswa->id_siswa)
            ->get();

        return response()->json([
            'exam' => $exam,
            'answered' => $answered->pluck('jawaban_siswa', 'id_question')
        ]);
    }

    public function submitAnswer(Request $request)
    {
        $request->validate([
            'id_exam' => 'required|exists:live_exams,id_exam',
            'id_question' => 'required|exists:questions,id_question',
            'jawaban_siswa' => 'required|in:A,B,C,D,E'
        ]);

        $user = Auth::user();
        $exam = \App\Models\LiveExam::find($request->id_exam);

        if ($exam->status !== 'ACTIVE') {
            return response()->json(['message' => 'Ujian sudah ditutup oleh Guru!'], 400);
        }

        $question = \App\Models\Question::find($request->id_question);
        $isCorrect = ($question->jawaban_benar === $request->jawaban_siswa);

        \App\Models\StudentAnswer::updateOrCreate(
            [
                'id_exam' => $request->id_exam,
                'id_siswa' => $user->id_siswa,
                'id_question' => $request->id_question,
            ],
            [
                'jawaban_siswa' => $request->jawaban_siswa,
                'is_correct' => $isCorrect
            ]
        );

        return response()->json(['message' => 'Jawaban tersimpan']);
    }

    public function nilai()
    {
        $user = Auth::user();
        $siswa = Student::with('clas')->find($user->id_siswa);

        $nilaiList = [];
        $bobotFormatif = 40;
        $bobotSumatif = 40;
        $bobotAbsensi = 20;

        if ($siswa && $siswa->id_kelas) {
            // Ambil semua ClassSubject untuk kelas siswa
            $classSubjects = \App\Models\ClassSubject::with(['subject', 'guruMutlak'])
                ->where('id_kelas', $siswa->id_kelas)
                ->get();

            // Ambil konfigurasi bobot nilai
            $config = \App\Models\GradeConfig::find(1);
            if ($config) {
                $bobotFormatif = $config->bobot_formatif;
                $bobotSumatif = $config->bobot_sumatif;
                $bobotAbsensi = $config->bobot_absensi;
            }

            foreach ($classSubjects as $cs) {
                // 1. Ambil Tujuan Pembelajaran (TP) untuk mapel ini
                $tpList = \App\Models\LearningObjective::where('id_mapel', $cs->id_mapel)
                    ->where('id_guru', $cs->id_guru_mutlak)
                    ->orderBy('kode_tp')
                    ->get();

                // 2. Ambil nilai siswa untuk masing-masing TP
                $tpGrades = \App\Models\StudentGrade::where('id_siswa', $siswa->id_siswa)
                    ->whereIn('id_tp', $tpList->pluck('id_tp'))
                    ->get();

                // Hitung rata-rata Formatif (TP)
                $details = [];
                $tpTotalScore = 0;
                $tpGradedCount = 0;

                foreach ($tpList as $tp) {
                    $gradeObj = $tpGrades->firstWhere('id_tp', $tp->id_tp);
                    $nilaiTp = $gradeObj ? $gradeObj->nilai : null;

                    if ($nilaiTp !== null) {
                        $tpTotalScore += $nilaiTp;
                        $tpGradedCount++;
                    }

                    $details[] = [
                        'id_tp' => $tp->id_tp,
                        'kode' => $tp->kode_tp,
                        'deskripsi' => $tp->deskripsi_tp,
                        'nilai' => $nilaiTp,
                        'is_remedial' => ($nilaiTp !== null && $nilaiTp < 75), // KKM 75
                        'is_empty' => ($nilaiTp === null),
                    ];
                }

                $rataTP = $tpGradedCount > 0 ? round($tpTotalScore / $tpGradedCount, 1) : 0;

                // 3. Hitung persentase kehadiran (Absensi)
                // Cari total sesi KBM SELESAI untuk kelas ini dan mapel ini
                $totalSessions = \App\Models\KbmSession::where('id_kelas', $siswa->id_kelas)
                    ->where('id_mapel', $cs->id_mapel)
                    ->where('status_sesi', 'SELESAI')
                    ->count();

                // Cari total kehadiran HADIR untuk siswa ini
                $presentSessions = \App\Models\StudentAttendance::where('id_siswa', $siswa->id_siswa)
                    ->where('status', 'HADIR')
                    ->whereHas('kbmSession', function ($q) use ($cs) {
                        $q->where('id_kelas', $cs->id_kelas)
                          ->where('id_mapel', $cs->id_mapel)
                          ->where('status_sesi', 'SELESAI');
                    })
                    ->count();

                $nilaiAbsensi = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 1) : 100.0;

                // 4. Ambil Nilai SAS & Rapor Resmi dari ReportCard
                $raporObj = \App\Models\ReportCard::where('id_siswa', $siswa->id_siswa)
                    ->where('id_class_subject', $cs->id_class_subject)
                    ->first();
                $nilaiSAS = $raporObj ? $raporObj->nilai_sas : 0;

                // 5. Kalkulasi Estimasi Nilai Akhir (Preview)
                $nilaiAkhir = ($rataTP * $bobotFormatif + $nilaiSAS * $bobotSumatif + $nilaiAbsensi * $bobotAbsensi) / 100;
                $nilaiAkhir = round($nilaiAkhir);
                
                // Jika Guru sudah pernah mensubmit nilai akhir secara resmi, GUNAKAN NILAI GURU (Read-only)
                if ($raporObj && $raporObj->nilai_akhir !== null) {
                    $nilaiAkhir = $raporObj->nilai_akhir;
                }

                // Generate deskripsi capaian dinamis HANYA UNTUK DITAMPILKAN (Bukan disimpan ke database)
                $desc = $raporObj->deskripsi_capaian ?? '';
                if (empty($desc)) {
                    $sortedDetails = collect($details)->whereNotNull('nilai')->sortByDesc('nilai');
                    if ($sortedDetails->count() > 0) {
                        $highest = $sortedDetails->first();
                        $lowest = $sortedDetails->last();
                        $desc = "Menunjukkan penguasaan yang sangat baik dalam " . $highest['deskripsi'] . ". Namun, perlu pendampingan lebih lanjut pada " . $lowest['deskripsi'] . ".";
                    }
                }

                $nilaiList[] = [
                    'id' => $cs->id_class_subject,
                    'nama' => $cs->subject->nama_mapel ?? 'Unknown',
                    'guru' => $cs->guruMutlak->nama_guru ?? 'Unknown',
                    'nilai' => $nilaiAkhir,
                    'rataTP' => $rataTP,
                    'sas' => $nilaiSAS,
                    'absensi' => $nilaiAbsensi,
                    'deskripsi' => $desc ?: 'Belum ada deskripsi capaian.',
                    'details' => $details,
                    'expanded' => false
                ];
            }
        }

        return Inertia::render('Siswa/Nilai', [
            'nilaiList' => $nilaiList,
            'bobotConfig' => [
                'formatif' => $bobotFormatif,
                'sumatif' => $bobotSumatif,
                'absensi' => $bobotAbsensi
            ]
        ]);
    }

    public function kuesioner()
    {
        $user = Auth::user();
        $siswa = Student::with('clas')->find($user->id_siswa);

        // Cari kuesioner yang sedang AKTIF
        $activeQuestionnaire = \App\Models\Questionnaire::with('questions')
            ->where('is_active', true)
            ->first();

        $targets = [];

        if ($activeQuestionnaire) {
            if ($activeQuestionnaire->tipe === 'WALI_KELAS') {
                if ($siswa->clas && $siswa->clas->id_guru_wali) {
                    $wali = \App\Models\Teacher::find($siswa->clas->id_guru_wali);
                    if ($wali) {
                        $targets[] = $wali;
                    }
                }
            } else if ($activeQuestionnaire->tipe === 'GURU_MAPEL') {
                // Ambil daftar guru yang mengajar di kelas siswa ini dari jp_schedules
                $guruIds = \App\Models\JpSchedule::where('id_kelas', $siswa->id_kelas)
                    ->pluck('id_guru')
                    ->unique()
                    ->filter();

                $targets = \App\Models\Teacher::whereIn('id_guru', $guruIds)->get();
            }
            
            // Ambil id_guru yang sudah dinilai oleh siswa ini untuk kuesioner yang sedang aktif
            $answeredGuruIds = \App\Models\QuestionnaireResponse::where('id_questionnaire', $activeQuestionnaire->id_questionnaire)
                ->where('id_siswa', $siswa->id_siswa)
                ->pluck('id_guru_target')
                ->unique()
                ->toArray();
                
            // Saring target, tandai mana yang sudah dikerjakan
            $targets = collect($targets)->map(function($guru) use ($answeredGuruIds) {
                $guru->sudah_dinilai = in_array($guru->id_guru, $answeredGuruIds);
                return $guru;
            });
        }

        return Inertia::render('Siswa/Kuesioner', [
            'questionnaire' => $activeQuestionnaire,
            'targets' => $targets
        ]);
    }

    public function submitKuesioner(Request $request)
    {
        $request->validate([
            'id_questionnaire' => 'required|exists:questionnaires,id_questionnaire',
            'id_guru_target' => 'required|exists:teachers,id_guru',
            'responses' => 'required|array', // key: id_q_question, value: rating_score
            'komentar' => 'nullable|string'
        ]);

        $user = Auth::user();

        // Cek apakah kuesioner masih aktif
        $q = \App\Models\Questionnaire::find($request->id_questionnaire);
        if (!$q->is_active) {
            return redirect()->back()->with('error', 'Kuesioner ini sudah ditutup.');
        }

        foreach ($request->responses as $id_q_question => $rating) {
            \App\Models\QuestionnaireResponse::updateOrCreate(
                [
                    'id_questionnaire' => $request->id_questionnaire,
                    'id_siswa' => $user->id_siswa,
                    'id_guru_target' => $request->id_guru_target,
                    'id_q_question' => $id_q_question,
                ],
                [
                    'rating_score' => $rating,
                    'komentar' => $request->komentar // simpan komentar di semua response untuk guru ini (atau salah satu saja juga boleh)
                ]
            );
        }

        return redirect()->back()->with('success', 'Terima kasih atas penilaian Anda!');
    }

    public function jadwal()
    {
        $user = Auth::user();
        $siswa = Student::with('clas')->find($user->id_siswa);

        $timetable = [];
        $jpSchedules = [];

        if ($siswa && $siswa->id_kelas) {
            $timetable = Timetable::with(['classSubject.subject', 'teacher'])
                ->whereHas('classSubject', function ($query) use ($siswa) {
                    $query->where('id_kelas', $siswa->id_kelas);
                })
                ->get();

            $shift = $siswa->clas->shift_operasional ?? 'PAGI';

            $jpSchedules = \App\Models\JpSchedule::where('shift', $shift)
                ->orderByRaw("FIELD(hari, 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU')")
                ->orderBy('waktu_mulai')
                ->get()
                ->groupBy('hari');
        }

        return Inertia::render('Siswa/Jadwal', [
            'timetable' => $timetable,
            'jpSchedules' => $jpSchedules,
            'kelas' => $siswa->clas->nama_kelas ?? 'Unknown',
            'shift' => $siswa->clas->shift_operasional ?? 'PAGI',
        ]);
    }
}
