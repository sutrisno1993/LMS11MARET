<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LearningElement;
use App\Models\LearningObjective;
use App\Models\LearningTopic;
use App\Models\StudentGrade;
use App\Models\Student;

class TestGradeSeeder extends Seeder
{
    public function run()
    {
        // Cari Guru Catur Wulandari (id_guru = 5)
        // Buat Elemen
        $elem = LearningElement::updateOrCreate(
            ['id_guru' => 5, 'id_mapel' => 18, 'nama_elemen' => 'Makhluk Hidup dan Lingkungannya'],
            ['deskripsi_cp' => 'Peserta didik menjelaskan interaksi antar makhluk hidup dan lingkungannya secara kritis.']
        );

        // Buat TP 1
        $tp1 = LearningObjective::updateOrCreate(
            ['id_element' => $elem->id_element, 'id_guru' => 5, 'id_mapel' => 18, 'kode_tp' => 'TP 1.1'],
            ['deskripsi_tp' => 'Mengidentifikasi ciri-ciri makhluk hidup dan aspek biotik/abiotik', 'semester' => 'GANJIL']
        );
        $tp1->classes()->sync([1]);

        // Topics untuk TP 1
        $tp1->topics()->delete();
        $top1_1 = $tp1->topics()->create(['nama_topik' => 'Ciri Makhluk Hidup']);
        $top1_2 = $tp1->topics()->create(['nama_topik' => 'Biotik & Abiotik']);

        // Buat TP 2
        $tp2 = LearningObjective::updateOrCreate(
            ['id_element' => $elem->id_element, 'id_guru' => 5, 'id_mapel' => 18, 'kode_tp' => 'TP 1.2'],
            ['deskripsi_tp' => 'Menjelaskan rantai makanan dan jaring-jaring makanan', 'semester' => 'GANJIL']
        );
        $tp2->classes()->sync([1]);

        // Topics untuk TP 2
        $tp2->topics()->delete();
        $top2_1 = $tp2->topics()->create(['nama_topik' => 'Rantai Makanan']);
        $top2_2 = $tp2->topics()->create(['nama_topik' => 'Jaring Makanan']);

        // Tambah Nilai Siswa
        $students = Student::where('id_kelas', 1)->get();
        foreach ($students as $siswa) {
            StudentGrade::updateOrCreate(
                ['id_siswa' => $siswa->id_siswa, 'id_topic' => $top1_1->id_topic],
                ['nilai' => rand(75, 95)]
            );
            StudentGrade::updateOrCreate(
                ['id_siswa' => $siswa->id_siswa, 'id_topic' => $top1_2->id_topic],
                ['nilai' => rand(70, 92)]
            );
            StudentGrade::updateOrCreate(
                ['id_siswa' => $siswa->id_siswa, 'id_topic' => $top2_1->id_topic],
                ['nilai' => rand(80, 96)]
            );
            // Biarkan top2_2 kosong untuk demo / test input nilai
        }
    }
}
