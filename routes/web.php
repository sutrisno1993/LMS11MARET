<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\WaliKelasController;

use Illuminate\Support\Facades\Auth;

// Root → redirect ke dashboard jika sudah login, atau ke Welcome jika belum
Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()->role;
        if ($role === 'ADMIN') return redirect()->route('admin.dashboard');
        if ($role === 'TEACHER') {
            return redirect()->route('guru.dashboard');
        }
        if ($role === 'STUDENT') return redirect()->route('siswa.dashboard');
    }
    return Inertia::render('Welcome');
})->name('home');

// Development Tools
if (app()->environment(['local', 'development'])) {
    Route::post('/dev/mock-time', function (\Illuminate\Http\Request $request) {
        $request->validate(['mock_time' => 'required|date']);
        $targetTime = strtotime($request->mock_time);
        $offset = $targetTime - time();
        \Illuminate\Support\Facades\Cache::put('time_offset', $offset);
        return back()->with('message', 'Waktu aplikasi berhasil diubah!');
    });
    Route::post('/dev/reset-time', function () {
        \Illuminate\Support\Facades\Cache::forget('time_offset');
        return back()->with('message', 'Waktu aplikasi dikembalikan ke normal!');
    });
}

// Auth Routes
Route::middleware('guest')->group(function () {
    // Rute login utama (redirect ke Welcome agar konsisten)
    Route::get('/login', function () {
        return Inertia::render('Welcome');
    })->name('login');

    // Halaman Form Login Spesifik
    Route::get('/login/admin', function () {
        return Inertia::render('Auth/LoginAdmin');
    })->name('login.admin');
    
    Route::get('/login/guru', function () {
        return Inertia::render('Auth/LoginTeacher');
    })->name('login.guru');
    
    Route::get('/login/siswa', function () {
        return Inertia::render('Auth/LoginStudent');
    })->name('login.siswa');

    // Proses Login
    Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
    Route::post('/login/guru', [AuthController::class, 'loginTeacher']);
    Route::post('/login/siswa', [AuthController::class, 'loginStudent']);

    // Guest: Request Lupa Password
    Route::post('/password/reset-request', [\App\Http\Controllers\PasswordResetController::class, 'requestReset'])->name('password.reset-request');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

use App\Http\Controllers\AdminController;

// Admin routes (akan dilindungi middleware auth nanti)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::redirect('', '/admin/dashboard');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/jadwal-jp', [AdminController::class, 'jadwalJp'])->name('jadwal-jp');
    Route::post('/jadwal-jp', [AdminController::class, 'updateJadwalJp']);
    
    // Manajemen Wali Kelas
    Route::get('/wali-kelas', [AdminController::class, 'waliKelas'])->name('wali-kelas');
    Route::post('/wali-kelas', [AdminController::class, 'updateWaliKelas']);
    Route::post('/wali-kelas/reset', [AdminController::class, 'resetAllWaliKelas']);
    
    // Manajemen Siswa
    Route::get('/siswa', [AdminController::class, 'siswaIndex'])->name('siswa');
    Route::post('/siswa/import', [AdminController::class, 'importSiswa'])->name('siswa.import');
    
    // Timeline / Kalender Akademik
    Route::get('/timeline', [AdminController::class, 'timeline'])->name('timeline');
    Route::post('/timeline', [AdminController::class, 'storeTimeline']);
    Route::delete('/timeline/{id}', [AdminController::class, 'deleteTimeline']);

    // Manajemen Event & Libur
    Route::get('/events', [AdminController::class, 'eventIndex'])->name('events.index');
    Route::post('/events', [AdminController::class, 'storeEvent'])->name('events.store');
    Route::delete('/events/{id}', [AdminController::class, 'deleteEvent'])->name('events.delete');

    // Manajemen Kuesioner
    Route::get('/kuesioner', [\App\Http\Controllers\AdminKuesionerController::class, 'index'])->name('kuesioner.index');
    Route::post('/kuesioner', [\App\Http\Controllers\AdminKuesionerController::class, 'store'])->name('kuesioner.store');
    Route::post('/kuesioner/{id}/toggle', [\App\Http\Controllers\AdminKuesionerController::class, 'toggleActive'])->name('kuesioner.toggle');
    Route::delete('/kuesioner/{id}', [\App\Http\Controllers\AdminKuesionerController::class, 'destroy'])->name('kuesioner.destroy');
    Route::get('/kuesioner/{id}/report', [\App\Http\Controllers\AdminKuesionerController::class, 'report'])->name('kuesioner.report');

    // Konfigurasi & Lainnya
    Route::get('/grade-config', [\App\Http\Controllers\AdminConfigController::class, 'gradeConfigIndex'])->name('grade-config.index');
    Route::post('/grade-config', [\App\Http\Controllers\AdminConfigController::class, 'gradeConfigUpdate'])->name('grade-config.update');
    Route::get('/rapor', [\App\Http\Controllers\AdminConfigController::class, 'rapor'])->name('rapor');
    Route::get('/monitoring-nilai', [\App\Http\Controllers\AdminController::class, 'monitoringNilai'])->name('monitoring-nilai');
    Route::get('/laporan-performa', [\App\Http\Controllers\AdminController::class, 'laporanPerforma'])->name('laporan-performa');
    Route::get('/jurnal', [\App\Http\Controllers\AdminController::class, 'jurnalIndex'])->name('jurnal.index');
    Route::get('/laporan-kasus-siswa', [\App\Http\Controllers\AdminController::class, 'laporanKasusSiswa'])->name('laporan-kasus-siswa');
    
    // Manajemen Guru Piket
    Route::get('/piket', [\App\Http\Controllers\AdminController::class, 'piketIndex'])->name('piket.index');
    Route::post('/piket', [\App\Http\Controllers\AdminController::class, 'updatePiket'])->name('piket.update');
    Route::get('/kehadiran-guru', [\App\Http\Controllers\AdminController::class, 'kehadiranGuruIndex'])->name('kehadiran-guru.index');

    // Persetujuan Reset Password Guru
    Route::get('/reset-requests', [\App\Http\Controllers\PasswordResetController::class, 'listTeacherRequests'])->name('reset-requests.index');
    Route::post('/reset-requests/{id}/approve', [\App\Http\Controllers\PasswordResetController::class, 'approveTeacherRequest'])->name('reset-requests.approve');
    Route::post('/reset-requests/{id}/reject', [\App\Http\Controllers\PasswordResetController::class, 'rejectTeacherRequest'])->name('reset-requests.reject');
});

// Guru routes
Route::middleware(['auth', 'role:TEACHER'])->prefix('guru')->name('guru.')->group(function () {
    Route::redirect('/', '/guru/dashboard');
    Route::redirect('', '/guru/dashboard');

    Route::get('/dashboard', [\App\Http\Controllers\GuruController::class, 'dashboard'])->name('dashboard');
    Route::get('/sesi-kbm/{id}', [\App\Http\Controllers\GuruController::class, 'sesiKbm'])->name('sesi-kbm');
    Route::post('/sesi-kbm/{id_sesi}/mulai', [\App\Http\Controllers\GuruController::class, 'mulaiKbm'])->name('kbm.mulai');
    Route::post('/sesi-kbm/{id_sesi}/selesai', [\App\Http\Controllers\GuruController::class, 'selesaiKbm'])->name('kbm.selesai');
    Route::post('/sesi-kbm/{id_sesi}/presensi', [\App\Http\Controllers\GuruController::class, 'simpanPresensi'])->name('kbm.presensi.store');
    Route::get('/riwayat-jurnal', [\App\Http\Controllers\GuruController::class, 'riwayatJurnal'])->name('riwayat-jurnal');
    Route::get('/jadwal', [\App\Http\Controllers\GuruController::class, 'jadwal'])->name('jadwal');
    
    // Fitur KBM Tambahan
    Route::get('/tugas-piket', [\App\Http\Controllers\GuruPiketController::class, 'index'])->name('tugas-piket');
    Route::post('/tugas-piket/absensi', [\App\Http\Controllers\GuruPiketController::class, 'simpanAbsensiGuru'])->name('tugas-piket.absensi');
    
    Route::get('/pemetaan-materi', [\App\Http\Controllers\GuruController::class, 'pemetaanMateri'])->name('pemetaan-materi');
    Route::post('/pemetaan-materi', [\App\Http\Controllers\GuruController::class, 'simpanPemetaanMateri']);
    Route::delete('/pemetaan-materi/{id_tp}', [\App\Http\Controllers\GuruController::class, 'hapusPemetaanMateri'])->name('pemetaan-materi.delete');
    Route::delete('/pemetaan-materi/element/{id_element}', [\App\Http\Controllers\GuruController::class, 'hapusElement'])->name('pemetaan-materi.element.delete');
    Route::get('/nilai-sumatif', [\App\Http\Controllers\GuruController::class, 'nilaiSumatif'])->name('nilai-sumatif');
    Route::post('/nilai-sumatif', [\App\Http\Controllers\GuruController::class, 'simpanNilaiSumatif']);
    Route::get('/nilai-akhir', [\App\Http\Controllers\GuruController::class, 'nilaiAkhir'])->name('nilai-akhir');
    Route::post('/nilai-akhir', [\App\Http\Controllers\GuruController::class, 'simpanNilaiAkhir']);
    Route::get('/rapor-preview', [\App\Http\Controllers\GuruController::class, 'raporPreview'])->name('rapor-preview');
    Route::get('/kbm-status/{id}', [\App\Http\Controllers\GuruController::class, 'kbmStatus'])->name('kbm-status');

    // Bank Soal & Ujian Live
    Route::get('/bank-soal', [\App\Http\Controllers\BankSoalController::class, 'index'])->name('bank-soal.index');
    Route::get('/bank-soal/create', [\App\Http\Controllers\BankSoalController::class, 'create'])->name('bank-soal.create');
    Route::post('/bank-soal', [\App\Http\Controllers\BankSoalController::class, 'store'])->name('bank-soal.store');
    Route::post('/bank-soal/{id}/pertanyaan', [\App\Http\Controllers\BankSoalController::class, 'storePertanyaan'])->name('bank-soal.pertanyaan.store');
    Route::get('/bank-soal/{id}', [\App\Http\Controllers\BankSoalController::class, 'show'])->name('bank-soal.show');
    
    // Live Exam
    Route::post('/live-exam/launch', [\App\Http\Controllers\LiveExamController::class, 'launch'])->name('live-exam.launch');
    Route::get('/live-exam/{id}/monitor', [\App\Http\Controllers\LiveExamController::class, 'monitor'])->name('live-exam.monitor');
    Route::post('/live-exam/{id}/close', [\App\Http\Controllers\LiveExamController::class, 'close'])->name('live-exam.close');
    Route::post('/kbm/{id_sesi}/ujian', [\App\Http\Controllers\BankSoalController::class, 'launchUjian'])->name('kbm.ujian.launch');
    Route::post('/kbm/ujian/{id}/tutup', [\App\Http\Controllers\BankSoalController::class, 'closeUjian'])->name('kbm.ujian.close');

    // Upload Materi (Bahan Ajar)
    Route::get('/materi', [\App\Http\Controllers\MaterialController::class, 'index'])->name('materi.index');
    Route::post('/materi', [\App\Http\Controllers\MaterialController::class, 'store'])->name('materi.store');
    Route::delete('/materi/{id}', [\App\Http\Controllers\MaterialController::class, 'destroy'])->name('materi.destroy');
    Route::get('/materi/{id}/download', [\App\Http\Controllers\MaterialController::class, 'download'])->name('materi.download');
});

// Siswa routes
Route::middleware(['auth', 'role:STUDENT'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::redirect('/', '/siswa/dashboard');
    Route::redirect('', '/siswa/dashboard');

    Route::get('/dashboard', [\App\Http\Controllers\SiswaController::class, 'dashboard'])->name('dashboard');
    Route::get('/scan-qr', [\App\Http\Controllers\SiswaController::class, 'scanQr'])->name('scan-qr');
    Route::post('/scan-qr', [\App\Http\Controllers\SiswaController::class, 'processScan'])->name('process-scan');
    Route::get('/nilai', [\App\Http\Controllers\SiswaController::class, 'nilai'])->name('nilai');
    
    // Ujian Live
    Route::get('/ujian-live', [\App\Http\Controllers\SiswaController::class, 'ujianLive'])->name('ujian-live');
    Route::get('/ujian-live/fetch', [\App\Http\Controllers\SiswaController::class, 'getLiveExam']);
    Route::post('/ujian-live/submit', [\App\Http\Controllers\SiswaController::class, 'submitAnswer']);

    // Kuesioner Evaluasi
    Route::get('/kuesioner', [\App\Http\Controllers\SiswaController::class, 'kuesioner'])->name('kuesioner');
    Route::post('/kuesioner', [\App\Http\Controllers\SiswaController::class, 'submitKuesioner'])->name('kuesioner.submit');

    // Jadwal Kelas
    Route::get('/jadwal', [\App\Http\Controllers\SiswaController::class, 'jadwal'])->name('jadwal');

    // Download Materi (Bahan Ajar)
    Route::get('/materi', [\App\Http\Controllers\MaterialController::class, 'siswaIndex'])->name('materi.index');
    Route::get('/materi/{id}/download', [\App\Http\Controllers\MaterialController::class, 'download'])->name('materi.download');
});

// Wali Kelas routes
Route::middleware(['auth', 'role:TEACHER'])->prefix('walikelas')->name('walikelas.')->group(function () {
    Route::get('/dashboard', [WaliKelasController::class, 'dashboard'])->name('dashboard');
    Route::get('/p5-assessment', [WaliKelasController::class, 'p5Assessment'])->name('p5-assessment');
    Route::get('/jurnal', [WaliKelasController::class, 'jurnalIndex'])->name('jurnal.index');
    Route::get('/pembinaan', [WaliKelasController::class, 'pembinaanIndex'])->name('pembinaan.index');
    Route::post('/pembinaan', [WaliKelasController::class, 'simpanPembinaan'])->name('pembinaan.store');
    Route::delete('/pembinaan/{id}', [WaliKelasController::class, 'hapusPembinaan'])->name('pembinaan.delete');

    // Persetujuan Reset Password Murid
    Route::get('/reset-requests', [\App\Http\Controllers\PasswordResetController::class, 'listStudentRequests'])->name('reset-requests.index');
    Route::post('/reset-requests/{id}/approve', [\App\Http\Controllers\PasswordResetController::class, 'approveStudentRequest'])->name('reset-requests.approve');
    Route::post('/reset-requests/{id}/reject', [\App\Http\Controllers\PasswordResetController::class, 'rejectStudentRequest'])->name('reset-requests.reject');
});
