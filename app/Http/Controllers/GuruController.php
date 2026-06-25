<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Models\Timetable;

use App\Models\LearningObjective;
use App\Models\TeacherSubject;
use App\Models\LearningElement;
use App\Models\LearningTopic;

class GuruController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $today = now()->toDateString();
        
        // Get day of week name
        $dayOfWeek = now()->dayOfWeek;
        $days = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        $hariEnum = $days[$dayOfWeek];

        // Ambil sesi KBM harian untuk guru yang login (terjadwal atau aktual)
        $kbmSessionsRaw = \App\Models\KbmSession::with(['clas', 'subject'])
            ->where('tanggal', $today)
            ->where(function ($query) use ($user) {
                $query->where('id_guru_terjadwal', $user->id_guru)
                      ->orWhere('id_guru_aktual', $user->id_guru);
            })
            ->orderBy('jam_ke')
            ->get();

        // Format data sesuai dengan yang dibutuhkan frontend
        $formattedJadwal = $kbmSessionsRaw->map(function ($session) use ($hariEnum) {
            $shift = $session->clas->shift_operasional ?? 'PAGI';
            
            // Cari JpSchedule untuk menentukan apakah jam pelajaran ini aktif sekarang
            $jp = \App\Models\JpSchedule::where('hari', $hariEnum)
                ->where('shift', $shift)
                ->where('jam_ke', $session->jam_ke)
                ->first();

            $nowTime = now()->toTimeString();
            $isTimeActive = false;
            if ($jp) {
                $isTimeActive = ($nowTime >= $jp->waktu_mulai && $nowTime <= $jp->waktu_selesai);
            }

            // Tambahkan waktu mulai & selesai ke response agar UI bisa menampilkannya
            $jamMulai = $jp ? substr($jp->waktu_mulai, 0, 5) : '-';
            $jamSelesai = $jp ? substr($jp->waktu_selesai, 0, 5) : '-';

            return [
                'id' => $session->id,
                'kelas' => $session->clas->nama_kelas ?? 'Unknown',
                'mapel' => $session->subject->nama_mapel ?? 'Unknown',
                'jam' => 'Jam ke-' . $session->jam_ke,
                'jam_ke' => $session->jam_ke,
                'jamMulai' => $jamMulai,
                'jamSelesai' => $jamSelesai,
                'status_sesi' => $session->status_sesi,
                'status_guru' => $session->status_guru,
                'is_active' => $isTimeActive, // is_active HANYA jika waktu sekarang di dalam jam KBM tersebut!
                'shift' => $shift,
                'payload' => $session->status_sesi === 'SCANNING' ? json_encode([
                    'id_kbm_session' => $session->id,
                ]) : null,
            ];
        });

        // Ambil mata pelajaran yang diampu guru secara dinamis
        $mapelDiampu = \App\Models\ClassSubject::with(['clas', 'subject'])
            ->where(function($query) use ($user) {
                $query->where('id_guru_mutlak', $user->id_guru)
                      ->orWhereIn('id_class_subject', \App\Models\Timetable::where('id_guru', $user->id_guru)->pluck('id_class_subject'));
            })
            ->get()
            ->groupBy('id_mapel')
            ->map(function($group) {
                $first = $group->first();
                $kelasNames = $group->map(fn($cs) => $cs->clas->nama_kelas ?? 'Unknown')->unique()->implode(' & ');
                $totalJp = $group->sum('durasi_jp');
                return [
                    'nama' => $first->subject->nama_mapel ?? 'Unknown',
                    'kelas' => $kelasNames,
                    'jp' => $totalJp,
                ];
            })
            ->values();

        return Inertia::render('Guru/Dashboard', [
            'jadwal' => $formattedJadwal,
            'mapelDiampu' => $mapelDiampu,
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
        
        // Ambil daftar kelas & mapel yang diajar guru ini (mutlak & terjadwal di timetable)
        $kelasMapelList = \App\Models\ClassSubject::with(['clas', 'subject'])
            ->where(function($query) use ($user) {
                $query->where('id_guru_mutlak', $user->id_guru)
                      ->orWhereIn('id_class_subject', \App\Models\Timetable::where('id_guru', $user->id_guru)->pluck('id_class_subject'));
            })
            ->get()
            ->map(function($cs) {
                return [
                    'id_kelas' => $cs->id_kelas,
                    'nama_kelas' => $cs->clas->nama_kelas ?? 'Unknown',
                    'id_mapel' => $cs->id_mapel,
                    'nama_mapel' => $cs->subject->nama_mapel ?? 'Unknown',
                ];
            })
            ->unique(function($item) {
                return $item['id_kelas'] . '-' . $item['id_mapel'];
            })
            ->values();

        // Ambil daftar Elemen beserta TPs, Classes, dan Topics
        $elementsList = LearningElement::with(['subject', 'tps.topics', 'tps.classes'])
            ->where('id_guru', $user->id_guru)
            ->get();

        // Ambil data TP lama yang belum di-link ke Elemen (Legacy)
        $legacyTps = LearningObjective::with(['subject', 'classes', 'topics'])
            ->where('id_guru', $user->id_guru)
            ->whereNull('id_element')
            ->get();

        return Inertia::render('Guru/PemetaanMateri', [
            'kelasMapelList' => $kelasMapelList,
            'elementsList' => $elementsList,
            'legacyTps' => $legacyTps,
        ]);
    }

    public function simpanPemetaanMateri(Request $request)
    {
        $request->validate([
            'id_element' => 'nullable|exists:learning_elements,id_element',
            'id_mapel' => 'required|exists:subjects,id_mapel',
            'nama_elemen' => 'required|string|max:255',
            'deskripsi_cp' => 'required|string',
            'tps' => 'required|array',
            'tps.*.id_tp' => 'nullable|exists:learning_objectives,id_tp',
            'tps.*.kode_tp' => 'required|string|max:50',
            'tps.*.deskripsi_tp' => 'required|string',
            'tps.*.semester' => 'required|in:GANJIL,GENAP',
            'tps.*.target_kelas' => 'required|array',
            'tps.*.target_kelas.*' => 'exists:classes,id_kelas',
            'tps.*.topics' => 'nullable|array',
        ]);

        $user = Auth::user();

        // 1. Simpan atau Update Elemen & CP
        $element = LearningElement::updateOrCreate(
            ['id_element' => $request->id_element, 'id_guru' => $user->id_guru],
            [
                'id_guru' => $user->id_guru,
                'id_mapel' => $request->id_mapel,
                'nama_elemen' => $request->nama_elemen,
                'deskripsi_cp' => $request->deskripsi_cp,
            ]
        );

        $savedTpIds = [];

        // 2. Loop simpan TPs
        foreach ($request->tps as $tpData) {
            $tp = LearningObjective::updateOrCreate(
                ['id_tp' => $tpData['id_tp'] ?? null, 'id_guru' => $user->id_guru],
                [
                    'id_element' => $element->id_element,
                    'id_guru' => $user->id_guru,
                    'id_mapel' => $request->id_mapel,
                    'kode_tp' => $tpData['kode_tp'],
                    'deskripsi_tp' => $tpData['deskripsi_tp'],
                    'semester' => $tpData['semester'],
                ]
            );
            $savedTpIds[] = $tp->id_tp;

            // Sync kelas ke TP
            $tp->classes()->sync($tpData['target_kelas']);

            // Sync sub-materi (topics) ke TP
            $tp->topics()->delete();
            if (!empty($tpData['topics'])) {
                foreach ($tpData['topics'] as $topicName) {
                    if ($topicName && trim($topicName) !== '') {
                        $tp->topics()->create([
                            'nama_topik' => trim($topicName)
                        ]);
                    }
                }
            }
        }

        // Hapus TP di bawah elemen ini yang tidak dikirimkan lagi (berarti dihapus dari draf)
        if ($request->id_element) {
            LearningObjective::where('id_element', $element->id_element)
                ->whereNotIn('id_tp', $savedTpIds)
                ->delete();
        }

        return redirect()->back()->with('message', 'Pemetaan Materi berhasil disimpan.');
    }

    public function hapusElement($id_element)
    {
        $user = Auth::user();
        $element = LearningElement::where('id_guru', $user->id_guru)->findOrFail($id_element);
        $element->delete();

        return redirect()->back()->with('message', 'Elemen Pembelajaran berhasil dihapus.');
    }

    public function hapusPemetaanMateri($id_tp)
    {
        $user = Auth::user();
        $tp = LearningObjective::where('id_guru', $user->id_guru)->findOrFail($id_tp);
        $tp->delete();

        return redirect()->back()->with('message', 'Tujuan Pembelajaran berhasil dihapus.');
    }

    public function nilaiSumatif(Request $request)
    {
        $user = Auth::user();

        // 1. Ambil daftar kelas yang diajar guru (mutlak & terjadwal di timetable)
        $kelasMapelList = \App\Models\ClassSubject::with(['clas', 'subject'])
            ->where(function($query) use ($user) {
                $query->where('id_guru_mutlak', $user->id_guru)
                      ->orWhereIn('id_class_subject', \App\Models\Timetable::where('id_guru', $user->id_guru)->pluck('id_class_subject'));
            })
            ->get();

        // Unique classes and subjects for dropdowns
        $kelasList = $kelasMapelList->pluck('clas')->filter()->unique('id_kelas')->values();
        $mapelList = $kelasMapelList->pluck('subject')->filter()->unique('id_mapel')->values();

        // Default selection
        $selectedKelas = $request->input('id_kelas', $kelasList->first()->id_kelas ?? null);
        $selectedMapel = $request->input('id_mapel', $mapelList->first()->id_mapel ?? null);

        // 2. Ambil Tujuan Pembelajaran (TP) beserta Topics/Materi di dalamnya
        $tpList = [];
        $students = [];

        if ($selectedMapel && $selectedKelas) {
            $tpList = LearningObjective::with('topics')
                ->where('id_guru', $user->id_guru)
                ->where('id_mapel', $selectedMapel)
                ->whereHas('classes', function ($q) use ($selectedKelas) {
                    $q->where('classes.id_kelas', $selectedKelas);
                })
                ->orderBy('kode_tp')
                ->get();
        }

        // 3. Ambil daftar Siswa berdasarkan kelas yang dipilih beserta Nilainya
        if ($selectedKelas && count($tpList) > 0) {
            $tpIds = $tpList->pluck('id_tp');
            $topicIds = $tpList->flatMap(fn($tp) => $tp->topics->pluck('id_topic'));

            $studentsRaw = \App\Models\Student::where('id_kelas', $selectedKelas)
                ->orderBy('nama_siswa')
                ->get();

            // Ambil semua nilai siswa di kelas ini (baik yang terhubung ke id_topic maupun id_tp legacy)
            $grades = \App\Models\StudentGrade::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
                ->where(function($query) use ($topicIds, $tpIds) {
                    $query->whereIn('id_topic', $topicIds)
                          ->orWhereIn('id_tp', $tpIds);
                })
                ->get()
                ->groupBy('id_siswa');

            foreach ($studentsRaw as $siswa) {
                $nilaiMapping = [];
                foreach ($tpList as $tp) {
                    foreach ($tp->topics as $topic) {
                        // Cari nilai untuk topic ini
                        $grade = $grades->get($siswa->id_siswa)?->firstWhere('id_topic', $topic->id_topic);
                        
                        // Fallback ke legacy TP grade jika nilai topik kosong
                        if (!$grade) {
                            $grade = $grades->get($siswa->id_siswa)?->firstWhere('id_tp', $tp->id_tp);
                        }

                        $nilaiMapping[$topic->id_topic] = $grade ? $grade->nilai : null;
                    }
                }

                $students[] = [
                    'id' => $siswa->id_siswa,
                    'nama' => $siswa->nama_siswa,
                    'nis' => $siswa->nis,
                    'nilai' => $nilaiMapping,
                    'catatan' => '',
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
            $nilaiData = $siswa['nilai'] ?? []; // Array dengan key id_topic dan value nilainya

            foreach ($nilaiData as $idTopic => $nilai) {
                // Jika nilai dihapus/kosong, hapus record dari database (CRUD - Delete)
                if ($nilai === null || $nilai === '') {
                    \App\Models\StudentGrade::where('id_siswa', $idSiswa)
                        ->where('id_topic', $idTopic)
                        ->delete();
                    continue;
                }

                \App\Models\StudentGrade::updateOrCreate(
                    [
                        'id_siswa' => $idSiswa,
                        'id_topic' => $idTopic,
                    ],
                    [
                        'nilai' => $nilai,
                    ]
                );
            }
        }

        return redirect()->back()->with('message', 'Semua nilai berhasil disimpan!');
    }

    public function nilaiAkhir(Request $request)
    {
        $user = Auth::user();

        // 1. Ambil daftar kelas & mapel yang diajar
        $kelasMapelList = \App\Models\ClassSubject::with(['clas', 'subject'])
            ->where(function($query) use ($user) {
                $query->where('id_guru_mutlak', $user->id_guru)
                      ->orWhereIn('id_class_subject', \App\Models\Timetable::where('id_guru', $user->id_guru)->pluck('id_class_subject'));
            })
            ->get();

        $kelasList = $kelasMapelList->pluck('clas')->filter()->unique('id_kelas')->values();
        $mapelList = $kelasMapelList->pluck('subject')->filter()->unique('id_mapel')->values();

        $selectedKelas = $request->input('id_kelas', $kelasList->first()->id_kelas ?? null);
        $selectedMapel = $request->input('id_mapel', $mapelList->first()->id_mapel ?? null);

        $students = [];

        if ($selectedKelas && $selectedMapel) {
            $classSubject = \App\Models\ClassSubject::where('id_kelas', $selectedKelas)
                ->where('id_mapel', $selectedMapel)
                ->first();

            $idClassSubject = $classSubject ? $classSubject->id_class_subject : null;

            // Ambil semua TP dan topiknya untuk menghitung rata-rata sumatif
            $tps = LearningObjective::with('topics')
                ->where('id_guru', $user->id_guru)
                ->where('id_mapel', $selectedMapel)
                ->whereHas('classes', function ($q) use ($selectedKelas) {
                    $q->where('classes.id_kelas', $selectedKelas);
                })
                ->get();

            $topicIds = $tps->flatMap(fn($tp) => $tp->topics->pluck('id_topic'));

            $studentsRaw = \App\Models\Student::where('id_kelas', $selectedKelas)
                ->orderBy('nama_siswa')
                ->get();

            // Ambil semua grades siswa
            $grades = \App\Models\StudentGrade::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
                ->whereIn('id_topic', $topicIds)
                ->get()
                ->groupBy('id_siswa');

            // Ambil report cards (SAS & Nilai Akhir)
            $reportCards = [];
            if ($idClassSubject) {
                $reportCards = \App\Models\ReportCard::whereIn('id_siswa', $studentsRaw->pluck('id_siswa'))
                    ->where('id_class_subject', $idClassSubject)
                    ->get()
                    ->groupBy('id_siswa');
            }

            foreach ($studentsRaw as $siswa) {
                $studentGrades = $grades->get($siswa->id_siswa) ?? collect();
                $validScores = $studentGrades->pluck('nilai')->filter(fn($val) => $val !== null && $val !== '');
                
                $rataTP = $validScores->count() > 0 ? round($validScores->average()) : null;

                $rc = $idClassSubject ? ($reportCards->get($siswa->id_siswa)?->first()) : null;

                $students[] = [
                    'id' => $siswa->id_siswa,
                    'nama' => $siswa->nama_siswa,
                    'nis' => $siswa->nis,
                    'rataTP' => $rataTP,
                    'sas' => $rc ? $rc->nilai_sas : null,
                    'nilai_akhir' => $rc ? $rc->nilai_akhir : null,
                    'id_rapor' => $rc ? $rc->id_rapor : null,
                ];
            }
        }

        return Inertia::render('Guru/NilaiAkhir', [
            'kelasList' => $kelasList,
            'mapelList' => $mapelList,
            'selectedKelas' => $selectedKelas,
            'selectedMapel' => $selectedMapel,
            'students' => $students,
        ]);
    }

    public function simpanNilaiAkhir(Request $request)
    {
        $request->validate([
            'id_kelas' => 'required',
            'id_mapel' => 'required',
            'students' => 'required|array',
        ]);

        $idKelas = $request->input('id_kelas');
        $idMapel = $request->input('id_mapel');

        $classSubject = \App\Models\ClassSubject::where('id_kelas', $idKelas)
            ->where('id_mapel', $idMapel)
            ->firstOrFail();

        foreach ($request->input('students') as $siswa) {
            $idSiswa = $siswa['id'];
            $sas = $siswa['sas'];
            $rataTP = $siswa['rataTP'];

            if ($sas === null || $sas === '') {
                \App\Models\ReportCard::where('id_siswa', $idSiswa)
                    ->where('id_class_subject', $classSubject->id_class_subject)
                    ->delete();
                continue;
            }

            // Hitung nilai akhir dengan bobot 70% rata-rata TP + 30% SAS
            $nilaiAkhir = round(($rataTP * 0.7) + ($sas * 0.3));

            \App\Models\ReportCard::updateOrCreate(
                [
                    'id_siswa' => $idSiswa,
                    'id_class_subject' => $classSubject->id_class_subject,
                ],
                [
                    'nilai_sas' => $sas,
                    'nilai_akhir' => $nilaiAkhir,
                ]
            );
        }

        return redirect()->back()->with('message', 'Nilai Rapor berhasil disinkronisasi.');
    }

    public function raporPreview()
    {
        return Inertia::render('Guru/RaporPreview');
    }

    public function mulaiKbm($id_sesi)
    {
        $session = \App\Models\KbmSession::findOrFail($id_sesi);
        
        $blockSessions = \App\Models\KbmSession::where('tanggal', $session->tanggal)
            ->where('id_kelas', $session->id_kelas)
            ->where('id_mapel', $session->id_mapel)
            ->where('id_guru_terjadwal', $session->id_guru_terjadwal)
            ->get();

        foreach ($blockSessions as $bSession) {
            if ($bSession->status_sesi === 'PENDING') {
                $bSession->status_sesi = 'SCANNING';
                $bSession->status_guru = 'HADIR';
                $bSession->waktu_scan_masuk = now();
                $bSession->save();

                // Inisialisasi absensi semua siswa di kelas ini sebagai default HADIR
                $studentsInClass = \App\Models\Student::where('id_kelas', $bSession->id_kelas)->get();
                foreach ($studentsInClass as $s) {
                    \App\Models\StudentAttendance::updateOrCreate(
                        [
                            'id_kbm_session' => $bSession->id,
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
        }

        return redirect()->route('guru.sesi-kbm', $id_sesi)->with('message', 'Sesi KBM berhasil dimulai.');
    }

    public function selesaiKbm($id_sesi)
    {
        $session = \App\Models\KbmSession::findOrFail($id_sesi);
        
        $blockSessions = \App\Models\KbmSession::where('tanggal', $session->tanggal)
            ->where('id_kelas', $session->id_kelas)
            ->where('id_mapel', $session->id_mapel)
            ->where('id_guru_terjadwal', $session->id_guru_terjadwal)
            ->get();

        foreach ($blockSessions as $bSession) {
            $bSession->status_sesi = 'SELESAI';
            $bSession->waktu_scan_keluar = now();
            $bSession->save();
        }

        return redirect()->route('guru.dashboard')->with('message', 'Sesi KBM selesai.');
    }

    public function simpanPresensi(Request $request, $id_sesi)
    {
        $request->validate([
            'students' => 'required|array',
            'materi_log' => 'nullable|string|max:255',
        ]);

        $session = \App\Models\KbmSession::findOrFail($id_sesi);
        
        $blockSessions = \App\Models\KbmSession::where('tanggal', $session->tanggal)
            ->where('id_kelas', $session->id_kelas)
            ->where('id_mapel', $session->id_mapel)
            ->where('id_guru_terjadwal', $session->id_guru_terjadwal)
            ->get();

        foreach ($blockSessions as $bSession) {
            // Simpan materi log ke session
            if ($request->has('materi_log')) {
                $bSession->materi_log = $request->materi_log;
                $bSession->save();
            }

            // Simpan/update presensi siswa untuk masing-masing sesi di blok tersebut
            foreach ($request->students as $studentData) {
                \App\Models\StudentAttendance::updateOrCreate(
                    [
                        'id_kbm_session' => $bSession->id,
                        'id_siswa' => $studentData['id'],
                    ],
                    [
                        'status' => $studentData['status'],
                        'metode' => 'MANUAL_GURU',
                        'waktu_presensi' => $studentData['status'] === 'HADIR' ? now() : null,
                    ]
                );
            }
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

    public function jadwal()
    {
        $user = Auth::user();
        
        $timetable = Timetable::with(['classSubject.clas', 'classSubject.subject'])
            ->where('id_guru', $user->id_guru)
            ->get();

        $jpSchedules = \App\Models\JpSchedule::orderBy('shift')
            ->orderByRaw("FIELD(hari, 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU')")
            ->orderBy('waktu_mulai')
            ->get()
            ->groupBy(['shift', 'hari']);

        return Inertia::render('Guru/Jadwal', [
            'timetable' => $timetable,
            'jpSchedules' => $jpSchedules
        ]);
    }
}
