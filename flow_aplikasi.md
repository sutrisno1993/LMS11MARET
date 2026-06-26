# MASTER BLUEPRINT & ALUR APLIKASI LMS SMK 11 MARET
*(Dokumen Single Source of Truth)*

Sistem LMS ini digerakkan oleh 4 Peran (Role) utama yang saling terhubung untuk menciptakan ekosistem sekolah digital yang otomatis dan transparan. Berikut adalah penjabaran detail Menu, Tugas, dan Flow untuk setiap peran.

---

## 1. KURIKULUM (Superadmin / Admin)
Pusat komando LMS. Berfokus pada konfigurasi sistem, manajemen master data, dan pemantauan (monitoring) kedisiplinan makro.

### A. Daftar Menu
- **Monitoring**
  - `Dashboard` (`/admin/dashboard`): Layar pantauan *Monitoring KBM Real-Time*.
  - `Performa Guru`: Rekapitulasi persentase kehadiran dan ketepatan waktu guru mengajar.
- **Konfigurasi**
  - `Jadwal JP`: Mengatur jam dinding (durasi fleksibel) untuk shift pagi/siang.
  - `Wali Kelas`: Memploting (menugaskan) guru menjadi Wali dari sebuah Rombongan Belajar.
  - `Manajemen Piket`: Mengelola penugasan Guru Piket dengan antarmuka *Day-Centric* (Berbasis Hari). Satu guru dapat dicentang untuk bertugas di lebih dari 1 hari (misal: Senin & Kamis) untuk Shift Pagi/Siang.
  - `Import Siswa`: Upload CSV massal pendaftaran siswa.
  - `Manajemen Event & Libur`: Mengatur dispensasi KBM dengan fleksibilitas tinggi (Bisa spesifik pilih Tanggal, Target Jam Ke-, Target Shift, dan Target Kelas).
  - `Bobot Nilai`: Konfigurasi bobot penilaian (Sumatif vs Formatif).
  - `Timeline`: Mengatur kalender akademik, batas *lock* input nilai, serta penjadwalan pembukaan **Kuesioner Wali Kelas** (diatur rutin setiap 3 bulan sekali).
- **Laporan**
  - `Ketidakhadiran Guru` (`/admin/laporan-absen-guru`): Memonitor dan mencetak rekapitulasi riwayat guru yang tidak hadir (Alpa, Izin, Sakit, atau Tugas Luar) dalam rentang waktu tertentu.
  - `Evaluasi Wali Kelas` (`/admin/evaluasi-walikelas`): Memantau rekap hasil penilaian (kuesioner/rating) yang diberikan oleh murid terhadap kinerja wali kelas mereka masing-masing.
  - `Laporan Kasus & Pembinaan Siswa` (`/admin/laporan-kasus-siswa`): Memantau rekapitulasi kasus siswa bermasalah (absensi, akademik, maupun perilaku seperti merokok, melawan guru, dll.) lengkap dengan riwayat kasus, jenis penanganannya (pembinaan pribadi, pemanggilan orang tua, home visit, SP 1/2/3), serta lampiran foto/gambar sebagai bukti fisik penanganan.
  - `Ekspor Rapor`: Mengunduh rekapitulasi nilai akhir seluruh sekolah.

### B. Tugas Utama
- Memastikan KBM berjalan tertib setiap harinya lewat Dashboard Real-time.
- Melakukan sinkronisasi master data dan jadwal dari SITAB.
- Menyelamatkan absensi guru dari status "Alpa" saat ada acara/event sekolah lewat fitur Manajemen Event.
- Mencetak Rapor.

### C. Alur Kerja (Flow)
1. **Penerimaan Jadwal (Sinkronisasi)**: Mesin AI luar (SITAB) melempar jadwal mingguan. Kurikulum tidak perlu input manual, cukup memantau data yang ditarik ke endpoint `/api/v1/sync-all`.
2. **Setup Event (Pre-KBM)**: Jika esok hari ada acara khusus (Misal: Apel Pagi hanya memakan Jam ke-1 dan 2), Kurikulum masuk ke `Manajemen Event`. Efeknya: Jadwal guru di jam 1-2 otomatis dilabeli `DISPENSASI`, terhindar dari pemotongan absensi.
3. **Monitoring KBM (Real-Time)**: Setiap hari KBM, Kurikulum memantau `/admin/dashboard`. Baris-baris jadwal KBM akan berubah warna otomatis secara magis dari Abu-abu (`PENDING`) menjadi Hijau (`HADIR`) atau Merah (`ALPA`) secara *live* berdasarkan hasil *scan QR* yang dilakukan murid di kelas. Jika jadwal lewat 15 menit masih abu-abu, Kurikulum bisa mengirim tim inpeksi ke kelas.
4. **Cetak Rapor (Pasca-KBM)**: Di akhir semester, setelah semua divalidasi Wali Kelas, Kurikulum mengekspor semua rapor.

---

## 2. GURU (Mata Pelajaran)
Garda terdepan yang menggerakkan KBM.

### A. Daftar Menu
- **KBM (Kegiatan Belajar Mengajar)**
  - `Dashboard` (`/guru/dashboard`): Menampilkan jadwal hari ini dan layar **Pembuat QR Code**.
  - `Sesi KBM`: Form input Jurnal Materi yang diajarkan.
- **Evaluasi & Penilaian**
  - `Pemetaan Materi`: Menginput Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP) secara Mapel-Centric (berdasarkan mata pelajaran terpilih dengan filter kelas dinamis yang diajar). Dilengkapi dengan panel *Status Pemetaan Mengajar* interaktif (Ready/Belum) per kelas untuk mempermudah monitoring kelengkapan semester aktif.
  - `Bank Soal & Ujian Live`: Modul upload soal Pilihan Ganda (A-E) untuk Asesmen KBM secara langsung (*Live*). Nilainya otomatis masuk ke Daftar Nilai (Dafnil).
  - `Nilai Sumatif`: Form input nilai tugas dan ujian (bisa manual atau otomatis dari Ujian Live).
  - `Nilai Akhir`: Kalkulasi dan pembobotan nilai akhir.
  - `Rapor Preview`: Pratinjau nilai sebelum terkunci.

### B. Tugas Utama
- Hadir secara fisik di ruang kelas pada waktu yang tepat.
- Menampilkan QR Code KBM kepada murid untuk di-scan.
- Mengisi Jurnal Materi KBM yang diajarkan hari tersebut.
- Menginput nilai sumatif murid secara rutin.

### C. Alur Kerja (Flow)
1. **Memulai Kelas (Flow QR)**: Saat masuk kelas dan waktu menunjukkan mulainya jadwal, Guru membuka laptop/HP (`/guru/dashboard`). Sistem mendeteksi jadwal aktif dan otomatis memunculkan **QR Code Dinamis** (mengandung enkripsi *payload* yang di-refresh setiap 10 detik agar anti-palsu).
2. **Verifikasi Kehadiran**: Guru menayangkan QR tersebut lewat proyektor (atau menunjukkannya langsung) dan meminta siswa untuk menscannya. *(Catatan: Untuk jadwal blok jam berurutan dengan mapel yang sama, cukup 1x scan di awal jam. Scan ulang hanya diperlukan jika pindah mata pelajaran).*
3. **Kelas Terbuka**: Setelah siswa menscan, layar QR di laptop/HP Guru akan tertutup sendiri (via *WebSockets* / *Polling*) dan berganti menjadi antarmuka "Kelas Dimulai". Status absensi Guru di server resmi tercatat `HADIR` (atau `TERLAMBAT` jika melewati batas waktu).
4. **Absensi Murid**: Setelah layar QR tertutup, langkah pertama yang Guru lakukan adalah memanggil/mengabsen murid-murid di kelas tersebut. *(Aturan Tegas: Murid yang datang telat setelah jam absen ini langsung dianggap ALPA, sistem tidak melayani revisi kehadiran).*
5. **Pencatatan Jurnal**: Sebelum KBM berakhir, Guru wajib mengisi form `Sesi KBM` untuk mencatat log aktivitas materi hari itu **(berdasarkan mapping CP/TP yang sudah di-upload guru)**, serta menginput **penugasan** jika ada.
6. **Asesmen Live (Opsional)**: Jika hari ini ada ujian/kuis, Guru bisa membuka bank soal dan meluncurkan *Asesmen Live* (Pilihan Ganda A-E) ke dashboard siswa.
7. **Penilaian Otomatis**: Guru memonitor pengerjaan soal siswa. Begitu KBM selesai, nilai Pilihan Ganda siswa langsung ter-*generate* dan otomatis masuk ke Daftar Nilai (Dafnil) tanpa perlu koreksi manual.

---

## 3. MURID (Siswa)
Aktor "Verifikator" yang menjaga kedisiplinan sekolah dan penikmat transparansi akademik.

### A. Daftar Menu
- `Dashboard` (`/siswa/dashboard`): Jadwal KBM pribadi hari ini dan status KBM *live*.
- `Scan QR` (`/siswa/scan-qr`): Kamera internal aplikasi (Pemindai layar Guru).
- `Ujian Live & Penugasan` (`/siswa/ujian`): Layar interaktif untuk mengerjakan soal Pilihan Ganda secara langsung saat KBM.
- `Nilai`: Riwayat nilai tugas/ujian secara transparan.
- `Kuesioner`: Angket evaluasi pengajaran untuk Guru Mapel (tiap akhir semester) dan evaluasi kinerja Wali Kelas (rutin setiap 3 bulan sekali sesuai jadwal Kurikulum).

### B. Tugas Utama
- Menjadi **Saksi Hidup / Verifikator** bahwa guru memang berada di dalam kelas.
- Memantau status pelajaran (apakah ada guru, kosong, atau tugas mandiri).
- Mengerjakan Ujian Live / Penugasan yang diluncurkan guru.

### C. Alur Kerja (Flow)
1. **Memantau Jadwal (Pagi Hari)**: Murid login dengan NIS/NISN. Mereka melihat jadwal KBM hari ini. Jika jadwal di-*dispensasi* Kurikulum (karena ada guru rapat/event), murid bisa melihat keterangan tersebut sehingga tidak perlu mencari-cari guru.
2. **Aksi Scan QR (Pergantian Jam)**: Saat bel pergantian jam dan Guru baru masuk, perwakilan Murid (seperti Ketua Kelas) bergegas membuka `/siswa/scan-qr` di HP-nya. Murid tersebut menyorotkan kameranya ke QR Code yang ditayangkan Guru di depan.
3. **Validasi Server Anti-Curang**: Saat hasil *scan* dikirim, Server menolak mentah-mentah jika QR kedaluwarsa atau murid men-scan jadwal dari kelas lain. Jika valid, HP murid menampilkan *"Kelas Berhasil Dimulai!"*, dan mengesahkan guru.
4. **Mengerjakan Asesmen Live**: Jika guru meluncurkan bank soal Pilihan Ganda, notifikasi akan muncul di perangkat murid. Murid membuka menu `Ujian Live` dan menjawab soal A-E. Selesai mengerjakan, jawaban otomatis terkirim dan terkoreksi.
5. **Cek Nilai Real-Time**: Sewaktu-waktu murid bisa mengecek perolehan nilainya di menu `Nilai`.
6. **Kuesioner Berkala**: 
   - **Evaluasi Wali Kelas**: Setiap 3 bulan sekali.
   - **Evaluasi Guru Mapel**: Di akhir semester.

---

## 4. WALI KELAS
Peran "Turunan" dari Guru yang memiliki wewenang eksekutif atas satu Rombongan Belajar (Kelas) spesifik.

### A. Daftar Menu (Ekstra di atas menu Guru biasa)
- `Dashboard Kelas` (`/walikelas/dashboard`): Layar pemantauan statistik kelas perwalian (Jumlah Siswa, Rata-rata Kehadiran, Rata-rata Rapor Kelas, dan Poin Pelanggaran). Menyajikan tabel daftar siswa lengkap dengan persentase kehadiran semester, poin pelanggaran, progres kelengkapan pengisian rapor, nilai rata-rata rapor, lencana status remedi, serta modal pop-up "Lihat Detail" untuk menganalisis nilai SAS & nilai akhir per mata pelajaran.
- `Jurnal KBM Kelas` (`/walikelas/jurnal`): Memonitor jurnal kegiatan belajar mengajar harian yang diisi oleh guru mata pelajaran di kelas perwaliannya.
- `Pembinaan & SP Siswa` (`/walikelas/pembinaan`): Antarmuka pencatatan dan tindakan kedisiplinan khusus siswa kelas perwalian.
- `Asesmen P5` (`/walikelas/p5-assessment`): Modul pengisian deskripsi projek dan penilaian Capaian Profil Pelajar Pancasila.

### B. Tugas Utama
- Memantau rekap absensi harian, poin pelanggaran, serta perkembangan nilai akademis seluruh siswa di kelas perwaliannya secara real-time.
- Melakukan intervensi dini terhadap siswa berkinerja rendah atau bermasalah kehadiran.
- Menjadi filter dan verifikator terakhir atas nilai guru mapel (memantau persentase kelengkapan rapor) sebelum rapor diterbitkan.
- Mengelola penilaian moral, sikap (P5), serta tindakan pembinaan siswa.

### C. Alur Kerja (Flow)
1. **Pemantauan Akademik & Deteksi Remedi (Real-Time)**: Wali Kelas memantau `Dashboard Kelas`. Baris siswa yang memiliki rata-rata rapor di bawah KKM (< 75) otomatis ditandai dengan warna merah transparan (`bg-red-500/5`). Sistem juga menampilkan lencana peringatan `⚠️ X Remedi` jika ada mata pelajaran yang tidak tuntas.
2. **Investigasi Nilai Detail (Modal View)**: Wali Kelas mengklik tombol "Lihat Detail" pada siswa terkait untuk membuka modal berisi tabel komparasi nilai SAS dan Nilai Akhir Rapor untuk seluruh mata pelajaran di kelas tersebut guna melihat mata pelajaran spesifik yang membutuhkan remedial.
3. **Intervensi Kedisiplinan & Tindakan (Pembinaan & SP)**: Jika siswa kedapatan memiliki akumulasi Alfa yang tinggi atau pelanggaran disiplin lainnya, Wali Kelas masuk ke menu `Pembinaan & SP Siswa` untuk melakukan langkah-langkah pembinaan terstruktur:
   - Mencatat log pembinaan pribadi/personal.
   - Melakukan pemanggilan orang tua murid.
   - Menerbitkan Surat Peringatan tingkat bertahap (SP 1, SP 2, dan SP 3).
   - Menandatangani Surat Perjanjian Siap Tidak Naik Kelas sebagai langkah pembinaan tegas terakhir.
4. **Penilaian P5**: Di pertengahan atau akhir semester, Wali Kelas mengisi asesmen dimensi Profil Pelajar Pancasila untuk setiap siswa di kelas perwalian sesuai projek aktif.
5. **Finalisasi Kelas**: Wali Kelas memastikan semua komponen nilai dari guru mapel lain sudah masuk 100% (kelengkapan rapor 100%). Setelah valid, Kurikulum diizinkan mencetak rapor untuk Rombongan Belajar tersebut.

---

## 5. TUJUAN UTAMA PEMBUATAN APLIKASI (LMS SMK 11 MARET)

Pengembangan sistem LMS ini tidak sekadar untuk digitalisasi bahan ajar, melainkan sebuah instrumen *social-engineering* dan kontrol manajemen sekolah yang komprehensif. Berikut adalah tujuan luhur di balik arsitektur aplikasi ini:

1. **Memberantas Fenomena "Kelas Kosong" (Kendali Kedisiplinan Guru)**
   Sistem **QR Terbalik (Reverse QR)** di mana Guru yang menayangkan QR dan Murid yang memindai, dirancang khusus agar Guru **wajib hadir secara fisik** di dalam kelas. Jika Guru tidak datang atau terlambat, sistem secara *real-time* akan memotong absensinya (Otomatis Alpa/Terlambat), sehingga Waka Kurikulum tidak perlu lagi melakukan sidak (inspeksi) manual ke lorong-lorong kelas.

2. **Pemberdayaan Siswa sebagai "Auditor Eksternal"**
   Murid tidak lagi menjadi objek pasif. Dengan memberikan mereka tombol *Scan QR* dan *Kuesioner Evaluasi*, sistem ini menjadikan murid sebagai verifikator (saksi) atas kehadiran dan kualitas mengajar guru. Ini menciptakan lingkungan sekolah yang transparan dan *fair*.

3. **Sentralisasi & Otomatisasi Pekerjaan Kurikulum**
   Terintegrasinya LMS dengan mesin SITAB (Penjadwalan) membuat Waka Kurikulum terbebas dari tugas *data-entry* jadwal yang memakan waktu berminggu-minggu. *Dashboard* Real-Time memungkinkan Kurikulum memantau 40+ kelas yang berjalan secara bersamaan hanya dari layar monitor di ruangannya (hijau berarti aman, merah berarti kosong).

4. **Transparansi Akademik *Real-Time***
   Dengan adanya fitur `Ujian Live` dan rekap `Nilai Sumatif` yang langsung tampil di *dashboard* siswa, orang tua dan siswa tidak perlu menunggu pembagian rapor akhir semester untuk mengetahui bahwa nilainya anjlok. Mereka bisa melakukan perbaikan secepat mungkin.

5. **Kepatuhan Total pada "Kurikulum Merdeka"**
   Aplikasi ini memaksa guru untuk mengajar secara terkommendasi dan terstruktur. Guru dituntut untuk melakukan **Pemetaan Materi (CP & TP)** di awal. Kelengkapan pemetaan ini dipantau secara real-time melalui panel *Status Pemetaan Mengajar* interaktif di sisi kiri (Ready vs Belum). Setiap jurnal KBM serta asesmen *Live* diikat langsung pada peta materi tersebut demi menjamin ketercapaian kompetensi nasional.

6. **Pengalaman Pengguna (UI/UX) Tanpa Interupsi**
   Semua notifikasi keberhasilan, peringatan, maupun pesan kesalahan di seluruh sistem (baik panel Admin, Guru, Wali Kelas, maupun Siswa) dikelola secara terpusat di level layout utama (`AppLayout.vue`) dan disajikan menggunakan **Custom Toast / Snackbar kustom** yang melayang dan hilang secara otomatis dalam 3 detik. Dilarang keras menggunakan pop-up `alert()` bawaan browser atau OS pada skenario apa pun (seperti kegagalan pengisian form, kegagalan simpan ujian live, atau peringatan ekspor data kosong) demi kelancaran alur produktivitas pengguna.
