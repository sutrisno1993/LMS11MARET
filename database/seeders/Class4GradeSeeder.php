<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReportCard;
use App\Models\ClassSubject;
use App\Models\Student;

class Class4GradeSeeder extends Seeder
{
    public function run()
    {
        // All students in the system
        $students = Student::all();
        // All subjects mapped to classes
        $classSubjects = ClassSubject::all();

        foreach ($students as $siswa) {
            $subjectsForClass = $classSubjects->where('id_kelas', $siswa->id_kelas);

            foreach ($subjectsForClass as $cs) {
                // 20% chance of failing grade to simulate red alerts for the homeroom teacher
                $isLow = (rand(1, 10) <= 2);
                $sas = $isLow ? rand(45, 72) : rand(75, 95);
                $rataTP = $isLow ? rand(50, 73) : rand(76, 98);
                $nilaiAkhir = round(($rataTP * 0.7) + ($sas * 0.3));

                ReportCard::updateOrCreate(
                    [
                        'id_siswa' => $siswa->id_siswa,
                        'id_class_subject' => $cs->id_class_subject,
                    ],
                    [
                        'nilai_sas' => $sas,
                        'nilai_akhir' => $nilaiAkhir,
                    ]
                );
            }
        }
    }
}
