# Panduan Pengembangan Aplikasi Mobile LMS 11 Maret (Sisi Guru)

Dokumen ini berisi panduan teknis, arsitektur, spesifikasi UI/UX, dan penyajian data untuk pengembangan aplikasi mobile khusus Guru Mata Pelajaran pada sistem **LMS 11 Maret**.

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
* **Single Login Gateway**: Guru masuk melalui portal otentikasi tersentralisasi menggunakan identitas pegawai.
* **Auto-refresh**: Implementasikan *Refresh Token* sebelum token akses kedaluwarsa demi menjaga kenyamanan pengguna.

---

## 3. Struktur Navigasi & Halaman Utama (Teacher Mode)
Aplikasi mobile menggunakan navigasi bawah (*Bottom Navigation Bar*) untuk akses cepat ke fitur utama, dan *Drawer* samping untuk fitur tambahan.

### Menu Bottom Navigation:
1. **Beranda (Dashboard)**: Ringkasan aktivitas hari ini dan jadwal mengajar aktif.
2. **Jurnal KBM**: Input jurnal pembelajaran harian secara cepat.
3. **Materi & Ujian**: Mengelola modul bahan ajar dan absensi kelas.

### Menu Drawer (Fitur Tambahan):
* **Pemetaan Materi**: Kelola Elemen Pembelajaran, Capaian Pembelajaran (CP), Tujuan Pembelajaran (TP), dan Sub-Materi (Topik) berbasis Mapel (Mata Pelajaran). Dilengkapi dengan status pemetaan interaktif per kelas (Ready/Belum) untuk semester aktif.
* **Tugas Piket**: Mengelola presensi Guru Piket. Menggunakan sistem *Multi-Hari* di mana seorang guru bisa ditugaskan piket pada lebih dari 1 hari. Menu ini akan dinamis menampilkan shift pagi/siang sesuai jadwal hari berjalan guru tersebut.

---

## 4. Panduan Penyajian Data & Integrasi API

### A. Dashboard Utama
Halaman utama harus menyajikan informasi krusial secara langsung (glanceable information):
* **Penyajian Data**:
  * Menampilkan ringkasan jadwal mengajar aktif hari ini (lokasi kelas, jam, mapel).
* **Integrasi API**: `GET /api/teacher/dashboard`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": {
        "nama_guru": "Ahmad Dani, S.Pd",
        "jadwal_hari_ini": [
          {
            "jam": "07:15 - 08:45",
            "kelas": "X TKJ 1",
            "mapel": "Pemrograman Dasar"
          }
        ]
      }
    }
    ```

---

### B. Jurnal KBM (Kegiatan Belajar Mengajar) & Presensi Kelas
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


### C. Pemetaan Materi (CP & TP)
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
   * Pemberitahuan jika ada perubahan mendadak pada jadwal KBM Anda.
   * Pengingat waktu mulai kelas (10 menit sebelum jam mengajar).
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

