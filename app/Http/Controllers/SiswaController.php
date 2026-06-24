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

        $formattedJadwal = collect($jadwalRaw)->map(function ($session) {
            return [
                'id' => $session->id,
                'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                'guru' => $session->guruAktual->nama_guru ?? 'Unknown',
                'jam' => 'Jam ke-' . $session->jam_ke,
                'status_sesi' => $session->status_sesi,
                'status_guru' => $session->status_guru,
            ];
        });

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
            if ($session->status_sesi !== 'PENDING') {
                return response()->json(['message' => 'Sesi KBM ini sudah aktif atau selesai.'], 400);
            }

            // Sukses! Update Sesi
            // Logika Telat bisa ditambahkan berdasarkan jam_ke dan jam aktual.
            // Untuk simplifikasi, anggap HADIR.
            $session->status_sesi = 'AKTIF';
            $session->status_guru = 'HADIR';
            $session->waktu_scan_masuk = now();
            $session->save();

            // Inisialisasi absensi semua siswa di kelas ini sebagai default HADIR (SYSTEM)
            $studentsInClass = \App\Models\Student::where('id_kelas', $session->id_kelas)->get();
            foreach ($studentsInClass as $s) {
                \App\Models\StudentAttendance::updateOrCreate(
                    [
                        'id_kbm_session' => $session->id,
                        'id_siswa' => $s->id_siswa,
                    ],
                    [
                        'status' => 'HADIR',
                        'metode' => $s->id_siswa === $siswa->id_siswa ? 'SCAN_QR' : 'SYSTEM',
                        'waktu_presensi' => $s->id_siswa === $siswa->id_siswa ? now() : null,
                    ]
                );
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
        return Inertia::render('Siswa/Nilai');
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
}
