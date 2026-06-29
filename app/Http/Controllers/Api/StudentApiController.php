<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\KbmSession;
use App\Models\JpSchedule;
use App\Models\ClassSubject;
use App\Models\GradeConfig;
use App\Models\LearningObjective;
use App\Models\StudentGrade;
use App\Models\ReportCard;
use App\Models\Questionnaire;
use App\Models\Teacher;
use App\Models\QuestionnaireResponse;
use App\Models\LiveExam;
use App\Models\StudentAnswer;
use App\Models\Question;

class StudentApiController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->id_siswa) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or not a student.'], 401);
        }

        $student = Student::with('clas')->find($user->id_siswa);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student record not found.'], 404);
        }

        // 1. Calculate overall attendance statistics
        $attendances = StudentAttendance::where('id_siswa', $student->id_siswa)
            ->whereHas('kbmSession', function ($q) {
                $q->where('status_sesi', 'SELESAI');
            })
            ->get();

        $totalSelesai = $attendances->count();
        $hadirCount = $attendances->where('status', 'HADIR')->count();
        $sakitCount = $attendances->where('status', 'SAKIT')->count();
        $izinCount = $attendances->where('status', 'IZIN')->count();
        $alfaCount = $attendances->where('status', 'ALPA')->count();

        $attendancePct = $totalSelesai > 0 ? round(($hadirCount / $totalSelesai) * 100, 1) : 100.0;

        // 2. Determine currently active KBM session
        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeek;
        $days = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        $hariEnum = $days[$dayOfWeek];
        $shift = $student->clas->shift_operasional ?? 'PAGI';

        $schedules = JpSchedule::where('hari', $hariEnum)
            ->where('shift', $shift)
            ->orderBy('waktu_mulai')
            ->get();

        $sessionsToday = KbmSession::with(['subject', 'guruAktual'])
            ->where('id_kelas', $student->id_kelas)
            ->where('tanggal', $today)
            ->get();

        $nowTime = now()->toTimeString();
        $currentKbm = null;

        foreach ($schedules as $jp) {
            if ($nowTime >= $jp->waktu_mulai && $nowTime <= $jp->waktu_selesai) {
                if ($jp->is_kbm) {
                    $session = $sessionsToday->firstWhere('jam_ke', $jp->jam_ke);
                    if ($session) {
                        $waktuSelesai = \Carbon\Carbon::parse($today . ' ' . $jp->waktu_selesai);
                        $sisaMenit = max(0, now()->diffInMinutes($waktuSelesai, false));

                        $currentKbm = [
                            'id_jadwal' => $session->id,
                            'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                            'guru' => $session->guruAktual->nama_guru ?? 'Unknown',
                            'jam_ke' => (string) $session->jam_ke,
                            'status' => $session->status_guru, // PENDING, HADIR, TERLAMBAT, ALPA, DISPENSASI
                            'sisa_menit' => $sisaMenit,
                        ];
                    }
                }
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'student_name' => $student->nama_siswa,
                'class_name' => $student->clas->nama_kelas ?? 'Tanpa Kelas',
                'attendance_pct' => $attendancePct,
                'details' => [
                    'sakit' => $sakitCount,
                    'izin' => $izinCount,
                    'alfa' => $alfaCount,
                ],
                'current_kbm' => $currentKbm,
            ],
        ]);
    }

    public function scanAttendance(Request $request)
    {
        $request->validate([
            'qr_payload' => 'required|string'
        ]);

        try {
            $data = json_decode($request->qr_payload, true);
            if (!$data || !isset($data['id_kbm_session']) || !isset($data['timestamp'])) {
                return response()->json(['success' => false, 'message' => 'QR Code tidak valid!'], 400);
            }

            // Validasi Kedaluwarsa (15 detik)
            $diffSeconds = (time() * 1000 - $data['timestamp']) / 1000;
            if ($diffSeconds > 15) {
                return response()->json(['success' => false, 'message' => 'QR Code sudah KEDALUWARSA! Silakan scan QR terbaru di layar proyektor.'], 400);
            }

            $session = KbmSession::find($data['id_kbm_session']);
            if (!$session) {
                return response()->json(['success' => false, 'message' => 'Sesi KBM tidak ditemukan!'], 404);
            }

            // Validasi Kelas Siswa
            $user = $request->user();
            $student = Student::find($user->id_siswa);
            if ($student->id_kelas !== $session->id_kelas) {
                return response()->json(['success' => false, 'message' => 'DITOLAK! Anda men-scan jadwal dari kelas lain.'], 403);
            }

            // Validasi Status Sesi
            if ($session->status_sesi !== 'SCANNING') {
                return response()->json(['success' => false, 'message' => 'Sesi KBM ini belum dimulai oleh Guru atau sudah selesai.'], 400);
            }

            // Cari semua sesi dalam blok yang sama
            $blockSessions = KbmSession::where('tanggal', $session->tanggal)
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
                $studentsInClass = Student::where('id_kelas', $bSession->id_kelas)->get();
                foreach ($studentsInClass as $s) {
                    StudentAttendance::updateOrCreate(
                        [
                            'id_kbm_session' => $bSession->id,
                            'id_siswa' => $s->id_siswa,
                        ],
                        [
                            'status' => 'HADIR',
                            'metode' => $s->id_siswa === $student->id_siswa ? 'SCAN_QR' : 'SYSTEM',
                            'waktu_presensi' => $s->id_siswa === $student->id_siswa ? now() : null,
                        ]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Presensi kelas berhasil diverifikasi.',
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem.'], 500);
        }
    }

    public function grades(Request $request)
    {
        $user = $request->user();
        $student = Student::with('clas')->find($user->id_siswa);

        $nilaiList = [];
        $bobotFormatif = 40;
        $bobotSumatif = 40;
        $bobotAbsensi = 20;

        if ($student && $student->id_kelas) {
            $classSubjects = ClassSubject::with(['subject', 'guruMutlak'])
                ->where('id_kelas', $student->id_kelas)
                ->get();

            $config = GradeConfig::find(1);
            if ($config) {
                $bobotFormatif = $config->bobot_formatif;
                $bobotSumatif = $config->bobot_sumatif;
                $bobotAbsensi = $config->bobot_absensi;
            }

            foreach ($classSubjects as $cs) {
                $tpList = LearningObjective::where('id_mapel', $cs->id_mapel)
                    ->where('id_guru', $cs->id_guru_mutlak)
                    ->orderBy('kode_tp')
                    ->get();

                $tpGrades = StudentGrade::where('id_siswa', $student->id_siswa)
                    ->whereIn('id_tp', $tpList->pluck('id_tp'))
                    ->get();

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
                        'tipe' => $tp->kode_tp,
                        'nilai' => $nilaiTp ?? 0,
                    ];
                }

                $rataTP = $tpGradedCount > 0 ? round($tpTotalScore / $tpGradedCount, 1) : 0;

                $totalSessions = KbmSession::where('id_kelas', $student->id_kelas)
                    ->where('id_mapel', $cs->id_mapel)
                    ->where('status_sesi', 'SELESAI')
                    ->count();

                $presentSessions = StudentAttendance::where('id_siswa', $student->id_siswa)
                    ->where('status', 'HADIR')
                    ->whereHas('kbmSession', function ($q) use ($cs) {
                        $q->where('id_kelas', $cs->id_kelas)
                          ->where('id_mapel', $cs->id_mapel)
                          ->where('status_sesi', 'SELESAI');
                    })
                    ->count();

                $nilaiAbsensi = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 1) : 100.0;

                $raporObj = ReportCard::where('id_siswa', $student->id_siswa)
                    ->where('id_class_subject', $cs->id_class_subject)
                    ->first();
                $nilaiSAS = $raporObj ? $raporObj->nilai_sas : 0;

                $nilaiAkhir = ($rataTP * $bobotFormatif + $nilaiSAS * $bobotSumatif + $nilaiAbsensi * $bobotAbsensi) / 100;
                $nilaiAkhir = round($nilaiAkhir);
                
                if ($raporObj && $raporObj->nilai_akhir !== null) {
                    $nilaiAkhir = $raporObj->nilai_akhir;
                }

                $details[] = [
                    'tipe' => 'SAS',
                    'nilai' => (int) $nilaiSAS
                ];

                $nilaiList[] = [
                    'id_mapel' => $cs->id_mapel,
                    'nama_mapel' => $cs->subject->nama_mapel ?? 'Unknown',
                    'nilai_akhir' => (int) $nilaiAkhir,
                    'kkm' => 75,
                    'status' => $nilaiAkhir >= 75 ? 'LULUS' : 'REMEDIAL',
                    'details' => $details,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $nilaiList,
        ]);
    }

    public function questionnaires(Request $request)
    {
        $user = $request->user();
        $student = Student::with('clas')->find($user->id_siswa);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student record not found.'], 404);
        }

        $activeQuestionnaire = Questionnaire::with('questions')
            ->where('is_active', true)
            ->first();

        $data = [];

        if ($activeQuestionnaire) {
            $targets = [];
            if ($activeQuestionnaire->tipe === 'WALI_KELAS') {
                if ($student->clas && $student->clas->id_guru_wali) {
                    $wali = Teacher::find($student->clas->id_guru_wali);
                    if ($wali) {
                        $targets[] = $wali;
                    }
                }
            } else if ($activeQuestionnaire->tipe === 'GURU_MAPEL') {
                $guruIds = JpSchedule::where('id_kelas', $student->id_kelas)
                    ->pluck('id_guru')
                    ->unique()
                    ->filter();

                $targets = Teacher::whereIn('id_guru', $guruIds)->get();
            }

            $answeredGuruIds = QuestionnaireResponse::where('id_questionnaire', $activeQuestionnaire->id_questionnaire)
                ->where('id_siswa', $student->id_siswa)
                ->pluck('id_guru_target')
                ->unique()
                ->toArray();

            foreach ($targets as $guru) {
                $isAnswered = in_array($guru->id_guru, $answeredGuruIds);

                $questions = $activeQuestionnaire->questions->map(function ($q) {
                    return [
                        'id_pertanyaan' => $q->id_q_question,
                        'teks' => $q->teks_pertanyaan,
                    ];
                });

                $data[] = [
                    'id_kuesioner' => $activeQuestionnaire->id_questionnaire,
                    'tipe' => $activeQuestionnaire->tipe,
                    'id_guru_target' => $guru->id_guru,
                    'target_nama' => $guru->nama_guru,
                    'status' => $isAnswered ? 'SELESAI' : 'BELUM_DIISI',
                    'questions' => $questions,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function submitQuestionnaire(Request $request)
    {
        $request->validate([
            'id_questionnaire' => 'required|exists:questionnaires,id_questionnaire',
            'id_guru_target' => 'required|exists:teachers,id_guru',
            'responses' => 'required|array', // key: id_q_question, value: rating_score
            'komentar' => 'nullable|string'
        ]);

        $user = $request->user();
        $q = Questionnaire::find($request->id_questionnaire);

        if (!$q || !$q->is_active) {
            return response()->json(['success' => false, 'message' => 'Kuesioner ini sudah ditutup.'], 400);
        }

        foreach ($request->responses as $id_q_question => $rating) {
            QuestionnaireResponse::updateOrCreate(
                [
                    'id_questionnaire' => $request->id_questionnaire,
                    'id_siswa' => $user->id_siswa,
                    'id_guru_target' => $request->id_guru_target,
                    'id_q_question' => $id_q_question,
                ],
                [
                    'rating_score' => $rating,
                    'komentar' => $request->komentar
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas penilaian Anda!'
        ]);
    }

    public function getExam(Request $request, $id)
    {
        $user = $request->user();
        $student = Student::find($user->id_siswa);

        $exam = LiveExam::with(['questionBank.questions' => function($q) {
            $q->select('id_question', 'id_bank', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e');
        }])
        ->where('id_exam', $id)
        ->where('status', 'ACTIVE')
        ->first();

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Tidak ada asesmen aktif saat ini.'], 404);
        }

        $answered = StudentAnswer::where('id_exam', $exam->id_exam)
            ->where('id_siswa', $student->id_siswa)
            ->get();

        return response()->json([
            'success' => true,
            'exam' => $exam,
            'answered' => $answered->pluck('jawaban_siswa', 'id_question')
        ]);
    }

    public function submitExamAnswer(Request $request, $id)
    {
        $request->validate([
            'id_question' => 'required|exists:questions,id_question',
            'jawaban_siswa' => 'required|in:A,B,C,D,E'
        ]);

        $user = $request->user();
        $exam = LiveExam::find($id);

        if (!$exam || $exam->status !== 'ACTIVE') {
            return response()->json(['success' => false, 'message' => 'Ujian sudah ditutup oleh Guru!'], 400);
        }

        $question = Question::find($request->id_question);
        $isCorrect = ($question->jawaban_benar === $request->jawaban_siswa);

        StudentAnswer::updateOrCreate(
            [
                'id_exam' => $exam->id_exam,
                'id_siswa' => $user->id_siswa,
                'id_question' => $request->id_question,
            ],
            [
                'jawaban_siswa' => $request->jawaban_siswa,
                'is_correct' => $isCorrect
            ]
        );

        return response()->json(['success' => true, 'message' => 'Jawaban tersimpan']);
    }
}
