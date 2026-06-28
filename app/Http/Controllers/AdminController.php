<?php

namespace App\Http\Controllers;

use App\Models\JpSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Tampilkan Dashboard Admin
     */
    public function dashboard()
    {
        $today = \Carbon\Carbon::today()->toDateString();

        // -------------------------------------------------------------
        // 1. DATA HARI INI (Untuk Panel Atas & Live KBM)
        // -------------------------------------------------------------
        $kbmHariIni = \App\Models\KbmSession::with(['clas', 'subject', 'guruAktual'])
            ->where('tanggal', $today)
            ->get();

        $stats = [
            'total_sesi' => $kbmHariIni->count(),
            'hadir' => $kbmHariIni->where('status_guru', 'HADIR')->count(),
            'terlambat' => $kbmHariIni->where('status_guru', 'TERLAMBAT')->count(),
            'alpa' => $kbmHariIni->where('status_guru', 'ALPA')->count(),
            'pending' => $kbmHariIni->where('status_guru', 'PENDING')->count()
        ];

        // Dapatkan jadwal JP untuk hari ini
        $dayIndex = \Carbon\Carbon::parse($today)->dayOfWeek;
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariEnum = $days[$dayIndex];
        $jpSchedules = \App\Models\JpSchedule::where('hari', $hariEnum)->get();

        $liveKbm = $kbmHariIni->map(function($session) use ($jpSchedules) {
            $waktu = '-';
            if ($session->clas) {
                $shift = $session->clas->shift_operasional;
                $jp = $jpSchedules->where('shift', $shift)->where('jam_ke', $session->jam_ke)->first();
                if ($jp && $jp->waktu_mulai && $jp->waktu_selesai) {
                    $start = \Carbon\Carbon::parse($jp->waktu_mulai)->format('H:i');
                    $end = \Carbon\Carbon::parse($jp->waktu_selesai)->format('H:i');
                    $waktu = $start . ' - ' . $end;
                }
            }

            return [
                'id' => $session->id,
                'kelas' => $session->clas ? $session->clas->nama_kelas : '-',
                'id_kelas' => $session->id_kelas,
                'mapel' => $session->subject ? $session->subject->nama_mapel : '-',
                'guru' => $session->guruAktual ? $session->guruAktual->nama_guru : '-',
                'jam_ke' => $session->jam_ke ?? '-',
                'waktu' => $waktu,
                'status' => $session->status_guru,
                'scan_masuk' => $session->waktu_scan_masuk ? $session->waktu_scan_masuk->format('H:i') : '-',
                'materi_log' => $session->materi_log
            ];
        })->sortBy('jam_ke')->values();

        // -------------------------------------------------------------
        // 2. GRAFIK 7 HARI TERAKHIR (chartDays)
        // -------------------------------------------------------------
        $chartDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $sessions = \App\Models\KbmSession::where('tanggal', $date->toDateString())->get();
            $chartDays[] = [
                'label' => $i === 0 ? 'Hari Ini' : $date->translatedFormat('D'),
                'hadir' => $sessions->where('status_guru', 'HADIR')->count(),
                'terlambat' => $sessions->where('status_guru', 'TERLAMBAT')->count(),
                'alpa' => $sessions->where('status_guru', 'ALPA')->count(),
            ];
        }

        // -------------------------------------------------------------
        // 3. REKAPITULASI BULAN BERJALAN (Progress & Performa Guru)
        // -------------------------------------------------------------
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = \Carbon\Carbon::now()->endOfMonth()->toDateString();

        $kbmBulanIni = \App\Models\KbmSession::with('guruAktual')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->where('status_guru', '!=', 'PENDING') // Hanya yang sudah dieksekusi (atau seharusnya dieksekusi)
            ->get();

        $totalBulanIni = $kbmBulanIni->count();
        $progressBars = [];
        if ($totalBulanIni > 0) {
            $progressBars = [
                ['label' => 'Hadir', 'pct' => round(($kbmBulanIni->where('status_guru', 'HADIR')->count() / $totalBulanIni) * 100, 1), 'color' => '#22C55E'],
                ['label' => 'Terlambat', 'pct' => round(($kbmBulanIni->where('status_guru', 'TERLAMBAT')->count() / $totalBulanIni) * 100, 1), 'color' => '#F59E0B'],
                ['label' => 'Alpa', 'pct' => round(($kbmBulanIni->where('status_guru', 'ALPA')->count() / $totalBulanIni) * 100, 1), 'color' => '#EF4444'],
            ];
        }

        // Kelompokkan per Guru untuk tabel performa (Diurutkan dari Persentase Hadir Terendah)
        $guruPerforma = $kbmBulanIni->groupBy('id_guru_aktual')->map(function($sessions, $id_guru) {
            $total = $sessions->count();
            $hadir = $sessions->where('status_guru', 'HADIR')->count();
            $terlambat = $sessions->where('status_guru', 'TERLAMBAT')->count();
            $alpa = $sessions->where('status_guru', 'ALPA')->count();
            $pct = $total > 0 ? round(($hadir / $total) * 100) : 0;
            
            $guruName = $sessions->first()->guruAktual ? $sessions->first()->guruAktual->nama_guru : 'Guru Tidak Ditemukan';

            return [
                'nama' => $guruName,
                'total' => $total,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alpa' => $alpa,
                'pct' => $pct
            ];
        })->values()->sortBy('pct')->take(20)->values(); // Ambil 20 guru performa terendah

        $classes = \App\Models\Clas::orderBy('nama_kelas')->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'live_kbm' => $liveKbm,
            'chartDays' => $chartDays,
            'progressBars' => $progressBars,
            'guruPerforma' => $guruPerforma,
            'tanggal_hari_ini' => \Carbon\Carbon::today()->translatedFormat('l, d F Y'),
            'classes' => $classes
        ]);
    }

    public function downloadTemplateSiswa()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=template_import_siswa.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['NIS', 'NISN', 'Nama Siswa', 'Nama Kelas'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['20231001', '0012345678', 'Ahmad Budi Santoso', '10 TKJ 1']);
            fputcsv($file, ['20231002', '0012345679', 'Siti Aminah', '10 TKJ 1']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Tampilkan Manajemen Jadwal JP
     */
    public function jadwalJp()
    {
        $schedules = JpSchedule::orderBy('shift')
            ->orderByRaw("FIELD(hari, 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU')")
            ->orderBy('waktu_mulai')
            ->get()
            ->groupBy(['shift', 'hari']);

        return Inertia::render('Admin/JadwalJp', [
            'schedules' => $schedules
        ]);
    }

    /**
     * Update Jadwal JP
     */
    public function updateJadwalJp(Request $request)
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:jp_schedules,id',
            'schedules.*.waktu_mulai' => 'required|date_format:H:i:s',
            'schedules.*.waktu_selesai' => 'required|date_format:H:i:s',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->schedules as $item) {
                JpSchedule::where('id', $item['id'])->update([
                    'waktu_mulai' => $item['waktu_mulai'],
                    'waktu_selesai' => $item['waktu_selesai'],
                ]);
            }
            DB::commit();

            return redirect()->back()->with('success', 'Jadwal JP berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui jadwal: ' . $e->getMessage());
        }
    }
    /**
     * Tampilkan Manajemen Wali Kelas
     */
    public function waliKelas()
    {
        $classes = \App\Models\Clas::with('waliKelas')->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $teachers = \App\Models\Teacher::orderBy('nama_guru')->get();

        return Inertia::render('Admin/WaliKelas', [
            'classes' => $classes,
            'teachers' => $teachers
        ]);
    }

    /**
     * Update penugasan wali kelas
     */
    public function updateWaliKelas(Request $request)
    {
        $request->validate([
            'id_kelas' => 'required|exists:classes,id_kelas',
            'id_guru_wali' => [
                'nullable',
                'exists:teachers,id_guru',
                Rule::unique('classes', 'id_guru_wali')->ignore($request->id_kelas, 'id_kelas')
            ],
        ], [
            'id_guru_wali.unique' => 'Guru ini sudah ditugaskan sebagai wali di kelas lain!'
        ]);

        \App\Models\Clas::where('id_kelas', $request->id_kelas)->update([
            'id_guru_wali' => $request->id_guru_wali
        ]);

        return redirect()->back()->with('success', 'Wali kelas berhasil ditugaskan!');
    }

    /**
     * Reset semua penugasan wali kelas
     */
    public function resetAllWaliKelas()
    {
        \App\Models\Clas::query()->update([
            'id_guru_wali' => null
        ]);

        return redirect()->back()->with('success', 'Seluruh penugasan wali kelas berhasil dikosongkan!');
    }

    /**
     * Tampilkan Data Siswa
     */
    public function siswaIndex(Request $request)
    {
        $query = \App\Models\Student::with('clas');
        
        if ($request->filled('id_kelas')) {
            $query->where('id_kelas', $request->id_kelas);
        }
        
        $students = $query->orderBy('nama_siswa')->paginate(50);
        $classes = \App\Models\Clas::orderBy('nama_kelas')->get();

        return Inertia::render('Admin/Siswa', [
            'students' => $students,
            'classes' => $classes,
            'filters' => $request->only(['id_kelas'])
        ]);
    }

    /**
     * Export Data Siswa ke CSV
     */
    public function exportSiswa()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=data_siswa_lms.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['ID Internal (JANGAN DIUBAH)', 'NIS', 'NISN', 'Nama Siswa', 'Nama Kelas'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            $students = \App\Models\Student::with('clas')->orderBy('id_kelas')->orderBy('nama_siswa')->get();
            foreach ($students as $siswa) {
                fputcsv($file, [
                    $siswa->id_siswa,
                    $siswa->nis,
                    $siswa->nisn,
                    $siswa->nama_siswa,
                    $siswa->clas ? $siswa->clas->nama_kelas : ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview Import Data Siswa dari CSV
     */
    public function importSiswa(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), "r");
        
        // Skip header
        fgetcsv($handle);
        
        $previewData = [];
        $existingNisn = \App\Models\Student::pluck('id_siswa', 'nisn')->toArray();
        $existingNis = \App\Models\Student::pluck('id_siswa', 'nis')->toArray();

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $id_internal = trim($data[0] ?? '');
            $nis = trim($data[1] ?? '');
            $nisn = trim($data[2] ?? '');
            $nama = trim($data[3] ?? '');
            $nama_kelas = trim($data[4] ?? '');

            if (empty($nis) && empty($nama) && empty($nama_kelas)) {
                continue; // Skip empty rows
            }

            $status = 'VALID';
            $errorMsg = [];

            if (empty($nama_kelas)) {
                $status = 'ERROR';
                $errorMsg[] = 'Kelas kosong';
            } else {
                $kelas = \App\Models\Clas::where('nama_kelas', $nama_kelas)->first();
                if (!$kelas) {
                    $status = 'ERROR';
                    $errorMsg[] = "Kelas '$nama_kelas' belum terdaftar di sistem";
                }
            }

            if (empty($id_internal)) {
                // Anak Baru (Insert)
                if (empty($nisn)) {
                    $status = 'ERROR';
                    $errorMsg[] = 'NISN kosong untuk anak baru';
                } else if (isset($existingNisn[$nisn])) {
                    $status = 'ERROR';
                    $errorMsg[] = 'NISN sudah ada di database (duplikat)';
                }
                
                if (empty($nis)) {
                    $status = 'ERROR';
                    $errorMsg[] = 'NIS kosong untuk anak baru';
                } else if (isset($existingNis[$nis])) {
                    $status = 'ERROR';
                    $errorMsg[] = 'NIS sudah ada di database (duplikat)';
                }
            } else {
                // Anak Lama (Update), ID prioritas
                if (!\App\Models\Student::find($id_internal)) {
                    $status = 'ERROR';
                    $errorMsg[] = 'ID Internal tidak ditemukan di sistem';
                }
            }

            $previewData[] = [
                'id_internal' => $id_internal,
                'nis' => $nis,
                'nisn' => $nisn,
                'nama' => $nama,
                'kelas' => $nama_kelas,
                'status' => $status,
                'error_msg' => implode(', ', $errorMsg)
            ];
        }
        fclose($handle);

        return response()->json([
            'success' => true,
            'preview_data' => $previewData
        ]);
    }

    /**
     * Konfirmasi dan Simpan Import Data Siswa
     */
    public function importConfirmSiswa(Request $request)
    {
        $request->validate([
            'students' => 'required|array'
        ]);

        $successCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            foreach ($request->students as $row) {
                if ($row['status'] === 'ERROR') {
                    $errorCount++;
                    continue; // Skip the ones still erroring
                }

                $kelas = \App\Models\Clas::where('nama_kelas', $row['kelas'])->first();
                if (!$kelas) {
                    $errorCount++;
                    continue;
                }

                if (!empty($row['id_internal'])) {
                    // Update Siswa Lama
                    \App\Models\Student::where('id_siswa', $row['id_internal'])->update([
                        'nis' => $row['nis'],
                        'nisn' => $row['nisn'],
                        'nama_siswa' => $row['nama'],
                        'id_kelas' => $kelas->id_kelas
                    ]);
                    $successCount++;
                } else {
                    // Insert Siswa Baru
                    \App\Models\Student::create([
                        'nis' => $row['nis'],
                        'nisn' => $row['nisn'],
                        'nama_siswa' => $row['nama'],
                        'id_kelas' => $kelas->id_kelas
                    ]);
                    $successCount++;
                }
            }
            
            DB::commit();
            return redirect()->back()->with('success', "$successCount siswa berhasil disimpan, $errorCount gagal/dilewati.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan Manajemen Event & Libur
     */
    public function eventIndex()
    {
        $events = \App\Models\Event::orderBy('tanggal', 'desc')->get();
        $classes = \App\Models\Clas::orderBy('tingkat')->orderBy('nama_kelas')->get();

        return Inertia::render('Admin/ManajemenEvent', [
            'events' => $events,
            'classes' => $classes,
        ]);
    }

    /**
     * Simpan Event Baru
     */
    public function storeEvent(Request $request)
    {
        $request->validate([
            'nama_event' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'target_shift' => 'nullable|in:PAGI,SIANG,SEMUA',
            'jam_ke' => 'nullable|array',
            'jam_ke.*' => 'integer',
            'target_kelas' => 'nullable|array',
            'target_kelas.*' => 'exists:classes,id_kelas',
        ]);

        \App\Models\Event::create([
            'nama_event' => $request->nama_event,
            'tanggal' => $request->tanggal,
            'target_shift' => $request->target_shift === 'SEMUA' ? null : $request->target_shift,
            'jam_ke' => empty($request->jam_ke) ? null : $request->jam_ke,
            'target_kelas' => empty($request->target_kelas) ? null : $request->target_kelas,
        ]);

        return redirect()->back()->with('success', 'Event berhasil ditambahkan!');
    }

    /**
     * Hapus Event
     */
    public function deleteEvent($id)
    {
        $event = \App\Models\Event::findOrFail($id);
        $event->delete();

        return redirect()->back()->with('success', 'Event berhasil dihapus!');
    }
    /**
     * Tampilkan Monitoring Nilai (Untuk Kurikulum)
     */
    public function monitoringNilai()
    {
        // Tarik semua kelas beserta wali kelasnya dan siswanya
        $classes = \App\Models\Clas::with(['waliKelas'])->orderBy('tingkat')->orderBy('nama_kelas')->get();
        
        $classesData = [];

        foreach ($classes as $kelas) {
            $students = \App\Models\Student::where('id_kelas', $kelas->id_kelas)->get();
            $studentsData = [];

            foreach ($students as $siswa) {
                // Cek Nilai Akhir (ReportCard)
                $reportCards = \App\Models\ReportCard::where('id_siswa', $siswa->id_siswa)->get();
                $avgScore = 0;
                $hasReportCard = false;

                if ($reportCards->count() > 0) {
                    $avgScore = round($reportCards->avg('nilai_akhir'), 1);
                    $hasReportCard = true;
                } else {
                    // Fallback: Cek nilai formatif/sumatif berjalan
                    $studentGrades = \App\Models\StudentGrade::where('id_siswa', $siswa->id_siswa)->get();
                    if ($studentGrades->count() > 0) {
                        $avgScore = round($studentGrades->avg('nilai'), 1);
                    }
                }

                $status = ($avgScore > 0 && $avgScore < 75) ? 'KURANG' : 'AMAN';

                $studentsData[] = [
                    'id_siswa' => $siswa->id_siswa,
                    'nis' => $siswa->nis,
                    'nama_siswa' => $siswa->nama_siswa,
                    'avg_score' => $avgScore,
                    'has_report_card' => $hasReportCard,
                    'status' => $status
                ];
            }

            // Hitung statistik kelas ini
            $totalSiswa = count($studentsData);
            $totalKurang = count(array_filter($studentsData, function($s) { return $s['status'] === 'KURANG'; }));

            // Nomor HP Wali Kelas (jika ada) untuk WhatsApp
            $waliHp = $kelas->waliKelas ? $kelas->waliKelas->no_hp : null;
            // Jika wali hp null, beri default atau null
            // Pastikan format hp diawali dengan 62
            if ($waliHp && strpos($waliHp, '0') === 0) {
                $waliHp = '62' . substr($waliHp, 1);
            }

            $classesData[] = [
                'id_kelas' => $kelas->id_kelas,
                'nama_kelas' => $kelas->nama_kelas,
                'nama_wali' => $kelas->waliKelas ? $kelas->waliKelas->nama_guru : 'Belum Atur Wali',
                'no_hp_wali' => $waliHp,
                'total_siswa' => $totalSiswa,
                'total_kurang' => $totalKurang,
                'students' => $studentsData
            ];
        }

        return Inertia::render('Admin/MonitoringNilai', [
            'classesData' => $classesData
        ]);
    }

    /**
     * Tampilkan Kalender Pendidikan (Timeline)
     */
    public function timeline()
    {
        $calendars = \App\Models\AcademicCalendar::orderBy('start_date', 'asc')->get();

        return Inertia::render('Admin/Timeline', [
            'calendars' => $calendars
        ]);
    }

    /**
     * Simpan Event Kalender Akademik Baru
     */
    public function storeTimeline(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:LIBUR,UJIAN,KEGIATAN,LAINNYA',
            'color' => 'required|string|max:20',
        ]);

        \App\Models\AcademicCalendar::create($request->all());

        return redirect()->back()->with('success', 'Kalender akademik berhasil ditambahkan!');
    }

    /**
     * Hapus Event Kalender Akademik
     */
    public function deleteTimeline($id)
    {
        \App\Models\AcademicCalendar::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Kalender akademik berhasil dihapus!');
    }

    /**
     * Laporan Performa Guru dan Wali Kelas
     */
    public function laporanPerforma(Request $request)
    {
        $semester = $request->input('semester', 'GANJIL'); // GANJIL = Jul-Des, GENAP = Jan-Jun
        $year = $request->input('year', date('Y'));

        // Tentukan rentang tanggal berdasarkan semester
        if ($semester === 'GANJIL') {
            $startDate = \Carbon\Carbon::createFromDate($year, 7, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
        } else {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 6, 30)->endOfMonth()->toDateString();
        }

        // Ambil semua sesi KBM dalam bulan ini
        $kbmBulanIni = \App\Models\KbmSession::with('guruAktual')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('status_guru', '!=', 'PENDING')
            ->get();

        // Ambil daftar kelas untuk mengetahui siapa wali kelasnya
        $classes = \App\Models\Clas::with('waliKelas')->get();
        $waliKelasMap = []; // id_guru -> nama_kelas
        foreach ($classes as $cls) {
            if ($cls->id_guru_wali) {
                $waliKelasMap[$cls->id_guru_wali] = $cls->nama_kelas;
            }
        }

        // Kelompokkan sesi berdasarkan guru
        $grouped = $kbmBulanIni->groupBy('id_guru_aktual');

        // Jika ada guru yang tidak ada sesinya di bulan ini tapi kita tetap ingin tampilkan,
        // kita bisa ambil semua guru. Tapi untuk performa, kita tampilkan yang ada jadwalnya saja.
        $allTeachers = \App\Models\Teacher::orderBy('nama_guru')->get();

        $laporanGuru = $allTeachers->map(function ($guru) use ($grouped, $waliKelasMap) {
            $sessions = $grouped->get($guru->id_guru, collect());
            $total = $sessions->count();
            $hadir = $sessions->where('status_guru', 'HADIR')->count();
            $terlambat = $sessions->where('status_guru', 'TERLAMBAT')->count();
            $alpa = $sessions->where('status_guru', 'ALPA')->count();
            $pctHadir = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

            return [
                'id_guru' => $guru->id_guru,
                'nama_guru' => $guru->nama_guru,
                'total_sesi' => $total,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alpa' => $alpa,
                'persentase' => $pctHadir,
            ];
        })->values();

        // -------------------------------------------------------------
        // PERFORMA WALI KELAS (Dari Penilaian Kuesioner)
        // -------------------------------------------------------------
        $kuesionerWaliKelasIds = \App\Models\Questionnaire::where('tipe', 'WALI_KELAS')->pluck('id_questionnaire');
        
        $responsesWaliKelas = \App\Models\QuestionnaireResponse::whereIn('id_questionnaire', $kuesionerWaliKelasIds)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $rekapWaliKelas = $responsesWaliKelas->groupBy('id_guru_target')->map(function ($responses) {
            return [
                'avg_rating' => round($responses->avg('rating_score'), 2),
                'total_respon' => $responses->unique('id_siswa')->count()
            ];
        });

        $laporanWaliKelas = $classes->filter(function($cls) { return $cls->id_guru_wali; })->map(function ($cls) use ($rekapWaliKelas) {
            $stats = $rekapWaliKelas->get($cls->id_guru_wali, ['avg_rating' => 0, 'total_respon' => 0]);
            return [
                'id_guru' => $cls->id_guru_wali,
                'nama_guru' => $cls->waliKelas ? $cls->waliKelas->nama_guru : 'Tidak Diketahui',
                'kelas_wali' => $cls->nama_kelas,
                'skor_kuesioner' => $stats['avg_rating'], // Skala 1-5
                'persentase' => $stats['avg_rating'] > 0 ? round(($stats['avg_rating'] / 5) * 100, 1) : 0, 
                'total_respon' => $stats['total_respon']
            ];
        })->values();

        // -------------------------------------------------------------
        // KEAKTIVAN SISWA (Berdasarkan akumulasi Sakit, Izin, Alpa terbanyak)
        // -------------------------------------------------------------
        $studentAttendances = \App\Models\StudentAttendance::with(['student.clas'])
            ->whereHas('kbmSession', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->whereIn('status', ['SAKIT', 'IZIN', 'ALPA'])
            ->get();

        $laporanSiswa = $studentAttendances->groupBy('id_siswa')->map(function ($attendances) {
            $first = $attendances->first()->student;
            $sakit = $attendances->where('status', 'SAKIT')->count();
            $izin = $attendances->where('status', 'IZIN')->count();
            $alpa = $attendances->where('status', 'ALPA')->count();
            $totalTidakHadir = $sakit + $izin + $alpa;

            return [
                'id_siswa' => $attendances->first()->id_siswa,
                'nama_siswa' => $first->nama_siswa ?? 'Siswa Tidak Ditemukan',
                'nis' => $first->nis ?? '-',
                'kelas' => $first->clas->nama_kelas ?? 'Unknown',
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total_tidak_hadir' => $totalTidakHadir,
            ];
        })
        ->sortByDesc('total_tidak_hadir')
        ->values()
        ->take(50);

        return Inertia::render('Admin/LaporanPerforma', [
            'laporanGuru' => $laporanGuru,
            'laporanWaliKelas' => $laporanWaliKelas,
            'laporanSiswa' => $laporanSiswa,
            'selectedSemester' => $semester,
            'selectedYear' => $year,
        ]);
    }

    /**
     * Jurnal Tatap Muka Harian & Riwayat Timeline KBM
     */
    public function jurnalIndex(Request $request)
    {
        $semester = $request->input('semester', 'GANJIL'); // GANJIL = Jul-Des, GENAP = Jan-Jun
        $year = $request->input('year', date('Y'));
        $id_kelas = $request->input('id_kelas');
        $id_guru = $request->input('id_guru');
        $id_mapel = $request->input('id_mapel');

        // Tentukan rentang tanggal berdasarkan semester
        if ($semester === 'GANJIL') {
            $startDate = \Carbon\Carbon::createFromDate($year, 7, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfMonth()->toDateString();
        } else {
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfMonth()->toDateString();
            $endDate = \Carbon\Carbon::createFromDate($year, 6, 30)->endOfMonth()->toDateString();
        }

        // Ambil semua guru untuk filter dan statistik
        $teachers = \App\Models\Teacher::orderBy('nama_guru')->get();
        $classes = \App\Models\Clas::orderBy('nama_kelas')->get();
        $subjects = \App\Models\Subject::orderBy('nama_mapel')->get();

        // Query semua sesi KBM dalam rentang semester/tahun ini untuk hitung statistik kehadiran guru
        $sessionsAll = \App\Models\KbmSession::whereBetween('tanggal', [$startDate, $endDate])->get();

        $teacherStats = $teachers->map(function ($teacher) use ($sessionsAll) {
            $sessions = $sessionsAll->filter(function($s) use ($teacher) {
                return $s->id_guru_terjadwal == $teacher->id_guru || $s->id_guru_aktual == $teacher->id_guru;
            });
            $total = $sessions->count();
            $hadir = $sessions->whereIn('status_guru', ['HADIR', 'TERLAMBAT'])->count();
            $alpa = $sessions->where('status_guru', 'ALPA')->count();
            $pending = $sessions->where('status_guru', 'PENDING')->count();

            return [
                'id_guru' => $teacher->id_guru,
                'nama_guru' => $teacher->nama_guru,
                'total_sesi' => $total,
                'hadir' => $hadir,
                'alpa' => $alpa,
                'pending' => $pending,
                'persentase_kehadiran' => $total > 0 ? round(($hadir / $total) * 100, 1) : 100,
            ];
        });

        // Query timeline KBM
        $query = \App\Models\KbmSession::with(['clas', 'subject', 'guruAktual', 'guruTerjadwal', 'attendances.student'])
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($id_kelas) {
            $query->where('id_kelas', $id_kelas);
        }

        if ($id_guru) {
            $query->where(function($q) use ($id_guru) {
                $q->where('id_guru_terjadwal', $id_guru)
                  ->orWhere('id_guru_aktual', $id_guru);
            });
        }

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
                    'kelas' => $session->clas->nama_kelas ?? 'Unknown',
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

        return Inertia::render('Admin/Jurnal/Index', [
            'sessions' => $sessions,
            'classes' => $classes,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'teacherStats' => $teacherStats,
            'filters' => [
                'year' => (int)$year,
                'semester' => $semester,
                'id_kelas' => $id_kelas ? (int)$id_kelas : null,
                'id_guru' => $id_guru ? (int)$id_guru : null,
                'id_mapel' => $id_mapel ? (int)$id_mapel : null,
            ]
        ]);
    }

    /**
     * Tampilkan Laporan Kasus & Pembinaan Siswa Terpusat
     */
    public function laporanKasusSiswa(Request $request)
    {
        $id_kelas = $request->input('id_kelas');
        $kategori_kasus = $request->input('kategori_kasus');
        $tipe_tindakan = $request->input('tipe_tindakan');

        $query = \App\Models\StudentDiscipline::with(['student.clas.waliKelas'])
            ->orderBy('tanggal_tindakan', 'desc')
            ->orderBy('created_at', 'desc');

        if ($id_kelas) {
            $query->whereHas('student', function ($q) use ($id_kelas) {
                $q->where('id_kelas', $id_kelas);
            });
        }

        if ($kategori_kasus) {
            $query->where('kategori_kasus', $kategori_kasus);
        }

        if ($tipe_tindakan) {
            $query->where('tipe_tindakan', $tipe_tindakan);
        }

        $cases = $query->get()->map(function($item) {
            return [
                'id' => $item->id,
                'nama_siswa' => $item->student->nama_siswa ?? 'Siswa Tidak Ditemukan',
                'nis' => $item->student->nis ?? '-',
                'nama_kelas' => $item->student->clas->nama_kelas ?? 'Tidak Diketahui',
                'nama_wali' => $item->student->clas->waliKelas->nama_guru ?? 'Belum Diatur',
                'kategori_kasus' => $item->kategori_kasus,
                'kasus_detail' => $item->kasus_detail ?? '-',
                'tipe_tindakan' => $item->tipe_tindakan,
                'tanggal_tindakan' => $item->tanggal_tindakan,
                'keterangan' => $item->keterangan ?? '-',
                'tindakan_lanjut' => $item->tindakan_lanjut ?? '-',
                'foto_bukti' => $item->foto_bukti,
            ];
        });

        $classes = \App\Models\Clas::orderBy('nama_kelas')->get();

        return Inertia::render('Admin/LaporanKasusSiswa', [
            'cases' => $cases,
            'classes' => $classes,
            'filters' => [
                'id_kelas' => $id_kelas ? (int)$id_kelas : null,
                'kategori_kasus' => $kategori_kasus,
                'tipe_tindakan' => $tipe_tindakan,
            ]
        ]);
    }

    /**
     * Manajemen Penugasan Guru Piket
     */
    public function piketIndex()
    {
        $teachers = \App\Models\Teacher::orderBy('nama_guru')->get(['id_guru', 'nama_guru']);
        $allTeachers = \App\Models\Teacher::get(['id_guru', 'piket_pagi', 'piket_siang']);
        
        $assignments = [
            'PAGI' => ['SENIN' => null, 'SELASA' => null, 'RABU' => null, 'KAMIS' => null, 'JUMAT' => null, 'SABTU' => null],
            'SIANG' => ['SENIN' => null, 'SELASA' => null, 'RABU' => null, 'KAMIS' => null, 'JUMAT' => null, 'SABTU' => null],
        ];

        foreach ($allTeachers as $t) {
            $pagi = is_array($t->piket_pagi) ? $t->piket_pagi : [];
            $siang = is_array($t->piket_siang) ? $t->piket_siang : [];
            foreach ($pagi as $hari) {
                if (array_key_exists($hari, $assignments['PAGI'])) {
                    $assignments['PAGI'][$hari] = $t->id_guru;
                }
            }
            foreach ($siang as $hari) {
                if (array_key_exists($hari, $assignments['SIANG'])) {
                    $assignments['SIANG'][$hari] = $t->id_guru;
                }
            }
        }

        return Inertia::render('Admin/ManajemenPiket', [
            'teachers' => $teachers,
            'initialAssignments' => $assignments,
        ]);
    }

    public function updatePiket(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.PAGI' => 'required|array',
            'assignments.SIANG' => 'required|array',
        ]);

        $assignments = $request->assignments;
        
        // Rebuild mapping: id_guru => ['piket_pagi' => [], 'piket_siang' => []]
        $teacherMap = [];
        foreach (['PAGI', 'SIANG'] as $shift) {
            foreach ($assignments[$shift] as $hari => $id_guru) {
                if ($id_guru) {
                    if (!isset($teacherMap[$id_guru])) {
                        $teacherMap[$id_guru] = ['piket_pagi' => [], 'piket_siang' => []];
                    }
                    if ($shift === 'PAGI') {
                        $teacherMap[$id_guru]['piket_pagi'][] = $hari;
                    } else {
                        $teacherMap[$id_guru]['piket_siang'][] = $hari;
                    }
                }
            }
        }

        $allTeachers = \App\Models\Teacher::all();
        foreach ($allTeachers as $t) {
            $needsSave = false;
            if (isset($teacherMap[$t->id_guru])) {
                $t->piket_pagi = $teacherMap[$t->id_guru]['piket_pagi'];
                $t->piket_siang = $teacherMap[$t->id_guru]['piket_siang'];
                $needsSave = true;
            } else {
                if (!empty($t->piket_pagi) || !empty($t->piket_siang)) {
                    $t->piket_pagi = [];
                    $t->piket_siang = [];
                    $needsSave = true;
                }
            }
            if ($needsSave) {
                $t->save();
            }
        }

        return redirect()->back()->with('success', 'Jadwal piket berhasil diperbarui!');
    }

    public function kehadiranGuruIndex(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());
        
        $attendances = \App\Models\TeacherAttendance::with('teacher')
            ->where('tanggal', $tanggal)
            ->get();
            
        return Inertia::render('Admin/KehadiranGuru', [
            'tanggal' => $tanggal,
            'attendances' => $attendances
        ]);
    }
}
