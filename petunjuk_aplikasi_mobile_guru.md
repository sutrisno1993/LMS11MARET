# Panduan Pengembangan Aplikasi Mobile LMS 11 Maret (Sisi Guru)

Dokumen ini berisi panduan teknis, arsitektur, spesifikasi UI/UX, dan penyajian data untuk pengembangan aplikasi mobile khusus Guru (termasuk peran Wali Kelas) pada sistem **LMS 11 Maret**.

---

## 1. Arsitektur & Teknologi Rekomendasi
* **Framework**: Flutter atau React Native (untuk kemudahan *cross-platform* Android & iOS dengan basis kode tunggal).
* **State Management**: BLoC/Provider (Flutter) atau Redux/Zustand (React Native).
* **Local Database**: Hive atau SQLite (digunakan untuk menyimpan data secara lokal agar mendukung *Offline Mode* saat guru menginput jurnal kelas).
* **HTTP Client**: Axios atau Dio dengan interceptor untuk penanganan token JWT secara otomatis.

---

## 2. Sistem Autentikasi & Keamanan
* **Metode**: Token-based Authentication menggunakan JWT (JSON Web Token).
* **Sesi**: Simpan token secara aman di secure storage (Keychain untuk iOS, Shared Preferences dengan enkripsi untuk Android).
* **Biometrik**: Integrasikan sidik jari (Fingerprint) atau wajah (Face ID) untuk kemudahan login setelah login pertama berhasil.
* **Auto-refresh**: Implementasikan *Refresh Token* sebelum token akses kedaluwarsa demi menjaga kenyamanan pengguna.

---

## 3. Struktur Navigasi & Halaman Utama (Teacher Mode)
Aplikasi mobile menggunakan navigasi bawah (*Bottom Navigation Bar*) untuk akses cepat ke fitur utama, dan *Drawer* samping untuk fitur tambahan.

### Menu Bottom Navigation:
1. **Beranda (Dashboard)**: Ringkasan aktivitas hari ini, jadwal mengajar aktif, dan statistik kelas perwalian.
2. **Jurnal KBM**: Input jurnal pembelajaran harian secara cepat.
3. **Asesmen P5**: Evaluasi dan penilaian projek profil pelajar Pancasila.
4. **Pembinaan Siswa**: Monitoring poin pelanggaran dan pembuatan surat peringatan (SP) *(Hanya tampil jika Guru = Wali Kelas)*.

> [!NOTE]
> **Logika Visibilitas Menu (Role-based UI):**
> * **Guru Biasa (Bukan Wali Kelas)**: Menu **Pembinaan Siswa** pada *Bottom Navigation* disembunyikan (atau dinonaktifkan). Pada halaman *Beranda*, kartu ringkasan kelas perwalian (Jumlah Siswa, Rapor Kelas, dll.) juga tidak ditampilkan.
> * **Wali Kelas**: Semua menu dan komponen di atas akan ditampilkan secara penuh.

### Menu Drawer (Fitur Tambahan):
* **Pemetaan Materi**: Kelola Elemen Pembelajaran, Capaian Pembelajaran (CP), Tujuan Pembelajaran (TP), dan Sub-Materi (Topik) berbasis Mapel (Mata Pelajaran). Dilengkapi dengan status pemetaan interaktif per kelas (Ready/Belum) untuk semester aktif.
* **Tugas Piket**: Mengelola presensi Guru Piket. Menggunakan sistem *Multi-Hari* di mana seorang guru bisa ditugaskan piket pada lebih dari 1 hari. Menu ini akan dinamis menampilkan shift pagi/siang sesuai jadwal hari berjalan guru tersebut.

---

## 4. Panduan Penyajian Data & Integrasi API

### A. Dashboard Utama (Wali Kelas / Guru)
Halaman utama harus menyajikan informasi krusial secara langsung (glanceable information):
* **Penyajian Data Kelas**:
  * Menampilkan kartu ringkasan kelas perwalian (Jumlah Siswa, Rata-rata Kehadiran, Rata-rata Rapor, Poin Pelanggaran).
  * Kehadiran disajikan dengan diagram lingkaran kecil (*Donut Chart*) berwarna hijau jika ≥ 90% dan merah jika < 90%.
  * Skor rata-rata rapor kelas disajikan dengan angka tebal berkelir kuning/putih.
* **Integrasi API**: `GET /api/walikelas/dashboard-summary`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": {
        "nama_kelas": "X TKJ 2",
        "total_siswa": 5,
        "rata_kehadiran": 98.4,
        "rata_rapor_kelas": 80.2,
        "poin_pelanggaran_kelas": 12,
        "tahun_ajaran": "2026/2027",
        "semester": "Ganjil"
      }
    }
    ```

---

### B. Daftar Siswa & Detail Nilai (Wali Kelas)
Bagian ini memungkinkan Wali Kelas untuk memantau progres nilai dan kelengkapan rapor siswa bimbingan.
* **Penyajian Data**:
  * **Daftar Siswa**: Gunakan daftar vertikal (*ListView*) dengan fitur pencarian waktu nyata (*Real-time Search Bar*) serta filter kelas.
  * **Indikator Kelengkapan Rapor**: Tampilkan bar kemajuan (*Progress Bar*) persentase kelengkapan nilai (0% - 100%).
  * **Alert Remedi**: Jika siswa memiliki nilai di bawah KKM (< 75), tampilkan lencana (*Badge*) merah menyala seperti: `⚠️ 3 Remedi`.
  * **Detail Nilai Akademik (Bottom Sheet/Modal)**: Ketika baris siswa diketuk, munculkan *Bottom Sheet* dari bawah layar yang menampilkan tabel nilai SAS dan nilai Akhir per mata pelajaran.
* **Warna Status**:
  * Nilai Rapor ≥ 75: Teks Hijau / Badge Hijau (Status: Lulus).
  * Nilai Rapor < 75: Teks Merah / Badge Merah (Status: Remedi).
* **Integrasi API**: `GET /api/walikelas/students`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id": 1,
          "nama": "Dartono Utama",
          "nis": "20260022",
          "kehadiran": 100,
          "poin": 0,
          "rapor_pct": 100,
          "rata_rapor": 78.9,
          "remedi_count": 4
        }
      ]
    }
    ```
* **Integrasi API**: `GET /api/walikelas/students/{id}/grades`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "student": {
        "id": 1,
        "nama": "Dartono Utama",
        "nis": "20260022"
      },
      "grades": [
        {
          "id_mapel": 101,
          "nama_mapel": "Matematika",
          "nilai_sas": 80,
          "nilai_akhir": 78,
          "status": "Lulus"
        },
        {
          "id_mapel": 102,
          "nama_mapel": "Bahasa Indonesia",
          "nilai_sas": 65,
          "nilai_akhir": 72,
          "status": "Remedi"
        }
      ]
    }
    ```

---

### C. Pengisian Asesmen P5 (Profil Pelajar Pancasila)
Karena pengisian deskripsi projek bisa cukup panjang, antarmuka mobile harus dirancang agar meminimalkan kelelahan mengetik.
* **Penyajian Data & Alur Input**:
  * **Daftar Projek**: Tampilkan daftar projek aktif yang sedang berjalan di semester ini.
  * **Alur Multi-Step**: Jangan gunakan satu form panjang. Bagi pengisian nilai menjadi 2 langkah:
    1. **Langkah 1**: Penilaian dimensi (Sangat Berkembang, Berkembang Sesuai Harapan, Mulai Berkembang, Belum Berkembang) menggunakan pilihan opsi berbasis ikon/kartu (*Card Radio Button*).
    2. **Langkah 2**: Input teks deskripsi proyek dengan fitur *Speech-to-Text* (Guru dapat mendiktekan catatan proyek menggunakan mikrofon ponsel untuk diubah otomatis menjadi teks).
* **Integrasi API**: `GET /api/walikelas/p5-assessments`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id_projek": 12,
          "nama_projek": "Kearifan Lokal - Batik Tradisional",
          "dimensi": [
            { "id_dimensi": 1, "nama_dimensi": "Kebinekaan Global" },
            { "id_dimensi": 2, "nama_dimensi": "Kreatif" }
          ]
        }
      ]
    }
    ```
* **Integrasi API**: `POST /api/walikelas/p5-assessments/store`
  * **Skema JSON Request**:
    ```json
    {
      "siswa_id": 1,
      "projek_id": 12,
      "nilai_dimensi": [
        { "id_dimensi": 1, "nilai": "SB" },
        { "id_dimensi": 2, "nilai": "BSH" }
      ],
      "catatan_proses": "Siswa sangat aktif berkolaborasi dan menunjukkan kreativitas tinggi dalam mendesain motif batik baru."
    }
    ```

---

### D. Jurnal KBM (Kegiatan Belajar Mengajar) & Presensi Kelas
Fitur penting bagi guru mata pelajaran untuk mencatat KBM secara real-time langsung dari ruang kelas.
* **Penyajian Data**:
  * **Deteksi Jadwal Otomatis**: Secara otomatis mendeteksi hari dan jam mengajar aktif guru berdasarkan jam server saat aplikasi dibuka.
  * **Pemilihan Topik/Materi Terintegrasi**: Input materi KBM tidak hanya berupa teks manual, melainkan guru dapat memilih secara cepat dari daftar sub-materi/topik yang telah dipetakan pada menu *Pemetaan Materi*.
  * **Form Presensi Cepat**: Menampilkan daftar nama siswa di kelas tersebut dengan tombol status kehadiran langsung tap: `Hadir` (Default - Hijau), `Sakit` (Kuning), `Izin` (Biru), `Alfa` (Merah).
  * **Offline Sync**: Jika kelas tidak memiliki sinyal internet stabil, simpan data presensi dan jurnal di database lokal (SQLite/Hive). Ketika koneksi kembali pulih, tampilkan indikator "Sinkronisasi Data" dan kirim data secara latar belakang (*Background Sync*).
* **Integrasi API**: `POST /api/teacher/jurnal-kbm`
  * **Skema JSON Request**:
    ```json
    {
      "jadwal_id": 45,
      "id_topic": 12,
      "materi": "Pengenalan Routing Dinamis OSPF",
      "catatan": "KBM berjalan kondusif, praktik konfigurasi selesai tepat waktu.",
      "presensi": [
        { "siswa_id": 1, "status": "Hadir" },
        { "siswa_id": 2, "status": "Sakit" },
        { "siswa_id": 3, "status": "Alfa" }
      ]
    }
    ```

---

### E. Menu Pembinaan & SP Siswa (Tindakan Wali Kelas)
Fitur untuk memantau, mencatat, dan menangani kasus siswa kelas perwalian yang bermasalah, baik terkait absensi, akademik, maupun perilaku/kedisiplinan.
* **Penyajian Data & Kategori Kasus**:
  * **Kategori Kasus**: Kasus yang dicatat meliputi:
    * **Absensi**: Ketidakhadiran tanpa keterangan (Alfa) yang melebihi batas.
    * **Akademik**: Tidak mengerjakan tugas, nilai di bawah KKM berulang kali.
    * **Perilaku/Kedisiplinan**: Merokok di sekolah, melawan guru, atribut tidak lengkap, berkelahi, dll.
  * **Riwayat Kasus Siswa**: Halaman detail siswa menampilkan daftar kronologis kasus yang pernah dilakukan beserta status penanganannya.
  * **Detail Penanganan & Bukti Gambar (Upload Foto)**: Setiap tindakan penanganan wajib disertai dengan unggahan foto/gambar sebagai bukti autentik penanganan di lapangan. Pilihan jenis penanganan meliputi:
    * **Pembinaan Pribadi**: Konseling empat mata antara Wali Kelas dan siswa (bukti: foto sesi pembinaan).
    * **Pemanggilan Orang Tua**: Pertemuan resmi wali kelas, siswa, dan orang tua di sekolah (bukti: foto pertemuan / dokumen berita acara).
    * **Home Visit (Kunjungan Rumah)**: Wali kelas mengunjungi kediaman orang tua siswa (bukti: foto bersama orang tua di rumah).
    * **Pemberian Surat Peringatan (SP 1, SP 2, SP 3)**: Penerbitan surat peringatan resmi (bukti: foto surat SP yang sudah ditandatangani basah oleh orang tua dan bermaterai).
* **Integrasi API**: `GET /api/walikelas/pembinaan-logs`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id_log": 201,
          "siswa_id": 3,
          "nama_siswa": "Hendri Caket Sihombing",
          "kategori_kasus": "Perilaku",
          "kasus_detail": "Ketahuan merokok di belakang laboratorium komputer saat jam istirahat.",
          "jenis_tindakan": "Home Visit",
          "tanggal": "2026-06-25",
          "keterangan": "Melakukan kunjungan rumah untuk klarifikasi dan koordinasi dengan orang tua.",
          "foto_bukti": "https://lms11maret.sch.id/storage/bukti-pembinaan/visit_hendri_20260625.jpg",
          "status_dokumen": "Dokumen Terverifikasi"
        }
      ]
    }
    ```
* **Integrasi API**: `POST /api/walikelas/pembinaan/action`
  * **Format Request**: `multipart/form-data`
  * **Skema Parameter Request**:
    ```json
    {
      "siswa_id": 3,
      "kategori_kasus": "Perilaku", 
      "kasus_detail": "Merokok di area sekolah",
      "jenis_tindakan": "Home Visit",
      "keterangan": "Melakukan kunjungan rumah bersama guru BK.",
      "tindakan_lanjut": "Orang tua berjanji akan mengawasi pergaulan anak di rumah.",
      "foto_bukti": "[File Gambar / Upload dari Kamera Ponsel]"
    }
    ```

---

### F. Pemetaan Materi (CP & TP)
Fitur untuk mengelola pemetaan kurikulum merdeka (Elemen, CP, TP, dan Sub-Materi/Topik) yang diampu oleh Guru secara Mapel-Centric.
* **Penyajian Data & Alur Kerja**:
  * **Halaman Utama Pemetaan**: Menampilkan daftar Elemen yang telah dibuat dengan visualisasi berbentuk kartu lipat (*Accordion/Expandable List*). Setiap kartu menampilkan nama elemen, CP, dan daftar TP di dalamnya.
  * **Status Pemetaan Mengajar (Interactive Widget)**: Widget sidebar kiri yang mengelompokkan jadwal mengajar Guru berdasarkan Mata Pelajaran, dengan indikator status glassmorphic untuk semester aktif:
    * **Ready (Green Glow)**: TP sudah terisi minimal 1 di semester tersebut.
    * **Belum (Red Glow)**: Belum ada TP yang didefinisikan sama sekali di semester tersebut.
    * *Interaktivitas*: Mengklik kelas/mapel pada widget ini otomatis menyetel filter header dan melakukan smooth scroll ke area panel editor.
  * **Penyaring Header (Mapel-Centric)**: 
    * **Dropdown Utama**: Memilih Mata Pelajaran (Mapel) yang diampu.
    * **Dropdown Kedua (Filter Kelas)**: Berfungsi menyaring tampilan data. Pilihan kelas yang muncul disaring secara dinamis hanya untuk kelas yang diajar Guru tersebut pada Mapel terpilih.
  * **Form Tambah/Edit Elemen**: Form input bersih menggunakan *Single-page Form* untuk data Elemen (Nama Elemen, Deskripsi CP) dan komponen dinamis *List View* di bawahnya untuk menambahkan multi-TP.
  * **Kelola Tujuan Pembelajaran (TP)**: Di setiap item TP, guru dapat menentukan kode TP, deskripsi TP, semester (Ganjil/Genap), menentukan target penerapan kelas secara inline melalui checkbox dinamis (semua kelas paralel tercentang otomatis secara default untuk kemudahan input), dan menambah daftar sub-materi/topik.
* **Integrasi API**: `GET /api/teacher/pemetaan-materi`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": {
        "kelasMapelList": [
          {
            "id_kelas": 1,
            "nama_kelas": "X TKJ 2",
            "id_mapel": 101,
            "nama_mapel": "Matematika"
          }
        ],
        "elementsList": [
          {
            "id_element": 12,
            "id_mapel": 101,
            "nama_elemen": "Aljabar dan Fungsi",
            "deskripsi_cp": "Siswa mampu memahami, memodelkan, dan menyelesaikan masalah terkait persamaan aljabar.",
            "tps": [
              {
                "id_tp": 45,
                "kode_tp": "TP 1.1",
                "deskripsi_tp": "Menjelaskan konsep dasar persamaan linear dua variabel",
                "semester": "GANJIL",
                "target_kelas": [1, 2],
                "topics": [
                  { "id_topic": 1, "nama_topik": "Persamaan Linear" },
                  { "id_topic": 2, "nama_topik": "Eliminasi & Substitusi" }
                ]
              }
            ]
          }
        ]
      }
    }
    ```
* **Integrasi API**: `POST /api/teacher/pemetaan-materi/store`
  * **Skema JSON Request**:
    ```json
    {
      "id_element": 12,
      "id_mapel": 101,
      "nama_elemen": "Aljabar dan Fungsi",
      "deskripsi_cp": "Siswa mampu memahami, memodelkan, dan menyelesaikan masalah terkait persamaan aljabar.",
      "tps": [
        {
          "id_tp": 45,
          "kode_tp": "TP 1.1",
          "deskripsi_tp": "Menjelaskan konsep dasar persamaan linear dua variabel",
          "semester": "GANJIL",
          "target_kelas": [1, 2],
          "topics": ["Persamaan Linear", "Eliminasi & Substitusi"]
        }
      ]
    }
    ```
* **Integrasi API**: `DELETE /api/teacher/pemetaan-materi/element/{id_element}`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "message": "Elemen Pembelajaran berhasil dihapus."
    }
    ```
* **Integrasi API**: `DELETE /api/teacher/pemetaan-materi/tp/{id_tp}`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "message": "Tujuan Pembelajaran berhasil dihapus."
    }
    ```

---

## 5. Fitur Native Mobile Tambahan
Untuk meningkatkan fungsionalitas, aplikasi mobile guru harus dilengkapi dengan fitur berikut:
1. **Push Notifications**:
   * Pemberitahuan jika ada siswa kelas perwaliannya yang tidak masuk tanpa keterangan selama 3 hari berturut-turut.
   * Pengingat batas akhir pengisian Asesmen P5 dan Nilai Rapor.
2. **Scanner QR Code**:
   * Digunakan untuk mencatat kehadiran siswa secara instan pada event sekolah atau ujian kelas.
3. **Mode Gelap (Dark Mode)**:
   * Menggunakan palet warna gelap premium (Slate/Navy) untuk mengurangi ketegangan mata guru saat mengoreksi nilai di malam hari.

---

## 6. Desain Antarmuka (UI/UX) Premium
* **Font**: Gunakan font modern seperti **Inter** atau **Outfit** untuk keterbacaan data numerik (angka nilai rapor) yang jelas.
* **Custom Toast / Snackbar (Sistem Notifikasi Terpusat)**: Seluruh umpan balik penyelesaian aksi (seperti Sukses Simpan Nilai, Catat Tindakan Pembinaan, Simpan Pemetaan Materi) maupun notifikasi kesalahan/validasi **wajib** menggunakan *Toast Notification* atau *Snackbar* kustom (Hijau untuk sukses, Kuning untuk peringatan/informasi, Merah untuk error) yang diatur terpusat di level layout utama (`AppLayout.vue`). Umpan balik dikirimkan secara reaktif via session flash/event router. **Sangat dilarang** menggunakan pop-up dialog bawaan browser (`alert()`) yang mengganggu alur (*flow*) interaksi pengguna.
* **Feedback Sentuhan (Haptic Feedback)**: Berikan getaran halus saat guru mengubah status kehadiran siswa atau memberikan nilai remedi untuk memperkuat konfirmasi input.
* **Skeleton Loader**: Tampilkan efek bayangan animasi (*shimmer effect*) saat memuat daftar nilai siswa, hindari penggunaan spinner loading tradisional berputar yang membosankan.

