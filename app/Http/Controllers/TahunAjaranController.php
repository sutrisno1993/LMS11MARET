<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TahunAjaranController extends Controller
{
    /**
     * Tampilkan halaman Ganti Tahun Ajaran dengan statistik data yang akan dihapus.
     */
    public function index()
    {
        $stats = [
            'total_siswa'       => DB::table('students')->count(),
            'total_guru'        => DB::table('teachers')->count(),
            'total_kelas'       => DB::table('classes')->count(),
            'total_mapel'       => DB::table('subjects')->count(),
            'total_kbm'         => DB::table('kbm_sessions')->count(),
            'total_absensi'     => DB::table('student_attendances')->count(),
            'total_nilai'       => DB::table('student_grades')->count(),
            'total_rapor'       => DB::table('report_cards')->count(),
            'total_ujian'       => DB::table('live_exams')->count(),
            'total_jawaban'     => DB::table('student_answers')->count(),
            'total_kuesioner'   => DB::table('questionnaire_responses')->count(),
            'total_disiplin'    => DB::table('student_disciplines')->count(),
            'total_materi'      => DB::table('materials')->count(),
            'total_user_non_admin' => DB::table('users')->where('role', '!=', 'ADMIN')->count(),
        ];

        return Inertia::render('Admin/TahunAjaran', [
            'stats' => $stats,
        ]);
    }

    /**
     * Eksekusi proses Ganti Tahun Ajaran.
     * Menghapus SELURUH data kecuali jp_schedules dan akun admin.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return redirect()->back()->with('error', 'Password admin yang dimasukkan salah! Ganti tahun ajaran dibatalkan.');
        }

        DB::beginTransaction();

        try {
            // Matikan foreign key checks agar truncate bisa berjalan
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // ============================================================
            // 1. Hapus semua data transaksional (child tables first)
            // ============================================================
            DB::table('student_answers')->truncate();
            DB::table('student_attendances')->truncate();
            DB::table('student_grades')->truncate();
            DB::table('student_disciplines')->truncate();
            DB::table('report_cards')->truncate();
            DB::table('questionnaire_responses')->truncate();
            DB::table('questionnaire_questions')->truncate();
            DB::table('teacher_attendances')->truncate();
            DB::table('learning_objective_class')->truncate();
            DB::table('kbm_sessions')->truncate();

            // ============================================================
            // 2. Hapus data ujian & bank soal
            // ============================================================
            DB::table('live_exams')->truncate();
            DB::table('questions')->truncate();
            DB::table('question_banks')->truncate();

            // ============================================================
            // 3. Hapus data kurikulum & materi
            // ============================================================
            DB::table('materials')->truncate();
            DB::table('learning_topics')->truncate();
            DB::table('learning_elements')->truncate();
            DB::table('learning_objectives')->truncate();

            // ============================================================
            // 4. Hapus data kuesioner, event, kalender
            // ============================================================
            DB::table('questionnaires')->truncate();
            DB::table('events')->truncate();
            DB::table('academic_calendars')->truncate();

            // ============================================================
            // 5. Hapus jadwal & mapping (kecuali jp_schedules)
            // ============================================================
            DB::table('timetable')->truncate();
            DB::table('class_subjects')->truncate();
            DB::table('teacher_subjects')->truncate();
            DB::table('grade_configs')->truncate();

            // ============================================================
            // 6. Hapus data siswa & guru
            // ============================================================
            DB::table('students')->truncate();
            DB::table('teachers')->truncate();

            // ============================================================
            // 7. Hapus user accounts non-admin & token akses
            // ============================================================
            DB::table('personal_access_tokens')->truncate();
            DB::table('password_reset_requests')->truncate();
            DB::table('users')->where('role', '!=', 'ADMIN')->delete();

            // ============================================================
            // 8. Reset kelas (hapus data kelas & reset wali kelas)
            // ============================================================
            DB::table('classes')->truncate();

            // ============================================================
            // 9. Hapus data mapel
            // ============================================================
            DB::table('subjects')->truncate();

            // Nyalakan kembali foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            return redirect()->back()->with('success', 'Tahun ajaran berhasil direset! Silakan impor data baru dari SITAB.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Pastikan FK checks dinyalakan kembali meskipun gagal
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return redirect()->back()->with('error', 'Gagal mereset tahun ajaran: ' . $e->getMessage());
        }
    }
}
