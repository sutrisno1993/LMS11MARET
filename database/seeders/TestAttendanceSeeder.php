<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KbmSession;
use App\Models\StudentAttendance;
use App\Models\Student;
use Carbon\Carbon;

class TestAttendanceSeeder extends Seeder
{
    public function run()
    {
        // X TKJ 1 is class 4
        $students = Student::where('id_kelas', 4)->get();
        if ($students->isEmpty()) {
            return;
        }

        // Create some past KBM sessions
        $dates = [
            Carbon::today()->subDays(1),
            Carbon::today()->subDays(2),
            Carbon::today()->subDays(3),
            Carbon::today()->subDays(4),
            Carbon::today()->subDays(5),
            Carbon::today()->subDays(6),
            Carbon::today()->subDays(7),
            Carbon::today()->subDays(8),
            Carbon::today()->subDays(9),
            Carbon::today()->subDays(10),
        ];

        foreach ($dates as $idx => $date) {
            // Delete if exists to avoid unique constraint violations
            KbmSession::where('tanggal', $date->toDateString())
                ->where('id_kelas', 4)
                ->where('jam_ke', 1)
                ->delete();

            $session = KbmSession::create([
                'tanggal' => $date->toDateString(),
                'id_kelas' => 4,
                'id_mapel' => 18, // IPAS
                'jam_ke' => 1,
                'id_guru_terjadwal' => 5, // Catur Wulandari
                'id_guru_aktual' => 5,
                'status_sesi' => 'SELESAI',
                'status_guru' => 'HADIR',
                'materi_log' => 'Materi Bab ' . ($idx + 1),
                'waktu_scan_masuk' => $date->copy()->setTime(7, 0, 0),
                'waktu_scan_keluar' => $date->copy()->setTime(8, 30, 0),
            ]);

            // Add attendance for each student
            foreach ($students as $student) {
                // Let's create some Alpa/Sakit/Izin
                // Student 16 (first student): very active
                // Student 17: some Sakit/Izin
                // Student 18: high Alpa (e.g. 4 Alpas) to trigger discipline SP
                $status = 'HADIR';
                if ($student->id_siswa == 17) {
                    if ($idx == 0) $status = 'SAKIT';
                    if ($idx == 3) $status = 'IZIN';
                } elseif ($student->id_siswa == 18) {
                    if (in_array($idx, [0, 2, 5, 8])) {
                        $status = 'ALPA';
                    }
                } elseif ($student->id_siswa == 19) {
                    if (in_array($idx, [1, 4])) {
                        $status = 'SAKIT';
                    }
                }

                StudentAttendance::updateOrCreate(
                    [
                        'id_kbm_session' => $session->id,
                        'id_siswa' => $student->id_siswa,
                    ],
                    [
                        'status' => $status,
                        'metode' => $status == 'HADIR' ? 'SCAN_QR' : 'MANUAL_GURU',
                        'waktu_presensi' => $status == 'HADIR' ? $date->copy()->setTime(7, rand(5, 15), 0) : null,
                    ]
                );
            }
        }
    }
}
