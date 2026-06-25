# Panduan Pengembangan Aplikasi Mobile LMS 11 Maret (Sisi Siswa/Murid)

Dokumen ini berisi panduan teknis, arsitektur, spesifikasi UI/UX, dan penyajian data untuk pengembangan aplikasi mobile khusus **Siswa/Murid** pada sistem **LMS 11 Maret**.

---

## 1. Arsitektur & Teknologi Rekomendasi
* **Framework**: Flutter atau React Native (Android & iOS).
* **State Management**: Provider, Riverpod, atau Zustand.
* **Fitur Hardware**: Akses Kamera (wajib untuk pemindaian QR Code) dan integrasi GPS/Geolocator (opsional untuk koordinat validasi presensi).

---

## 2. Sistem Autentikasi & Keamanan
* **Metode**: Autentikasi NIS (Nomor Induk Siswa) / NISN sebagai Username, didukung kata sandi/PIN 6 digit.
* **Perangkat Terdaftar (Device Binding)**: Opsional, daftarkan 1 akun siswa hanya untuk 1 ID perangkat (Device UUID) untuk mencegah siswa saling meminjamkan akun atau menitipkan absensi scan QR jarak jauh.

---

## 3. Struktur Navigasi & Halaman Utama (Student Mode)
Aplikasi menggunakan menu navigasi bawah (*Bottom Navigation Bar*):
1. **Beranda (Home)**: Informasi KBM aktif saat ini, statistik absensi kehadiran siswa, dan lencana performa belajar.
2. **Pindai QR**: Kamera langsung untuk men-scan QR Code kehadiran di layar Guru.
3. **Nilai Rapor**: Daftar nilai tugas, kuis, nilai harian sumatif, dan nilai akhir rapor.
4. **Kuesioner**: Menu penilaian kinerja Guru Mapel dan Wali Kelas.

---

## 4. Panduan Penyajian Data & Integrasi API

### A. Dashboard Utama (Siswa)
* **Status Pelajaran Berjalan (Live Card)**:
  * Menampilkan informasi mapel saat ini, nama guru, jam ke-, ruang kelas, dan sisa waktu durasi pembelajaran.
  * Tampilkan status pelajaran dengan warna indikator:
    * **Hijau**: Guru telah hadir dan KBM sedang berlangsung.
    * **Abu-abu**: Jadwal pelajaran selanjutnya (belum dimulai).
    * **Kuning**: Guru sedang dispensasi (tugas luar/rapat) dengan instruksi tugas mandiri.
* **Rekapitulasi Kehadiran Personal**:
  * Widget grafis yang menampilkan jumlah Kehadiran (%), Sakit (hari), Izin (hari), dan Tanpa Keterangan/Alfa (hari) di semester berjalan.
  * Tampilkan warna merah jika akumulasi Alfa mulai kritis (misal: ≥ 3 hari).
* **Integrasi API**: `GET /api/student/dashboard`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": {
        "attendance_pct": 96.5,
        "details": { "sakit": 2, "izin": 1, "alfa": 1 },
        "current_kbm": {
          "id_jadwal": 104,
          "mapel": "Pemrograman Web & Mobile",
          "guru": "Sutrisno, S.Kom",
          "jam_ke": "3-4",
          "status": "HADIR",
          "sisa_menit": 45
        }
      }
    }
    ```

---

### B. Fitur Pemindai Kehadiran (Scan QR Code)
Fitur inti yang mendisiplinkan guru secara fisik di kelas (Reverse QR System).
* **Alur Penggunaan**:
  * Siswa membuka menu scan QR, mengaktifkan kamera ponsel, lalu menyorotkan kamera ke **QR Code dinamis** yang ditayangkan oleh Guru di depan kelas.
  * Aplikasi membaca payload terenkripsi yang berisi (ID Jadwal KBM, Kode Validasi Unik, Timestamp, dan Token Keamanan).
  * Aplikasi mengirim data tersebut ke server.
* **Keamanan Tambahan**:
  * Batasi pemindaian hanya bisa dilakukan dalam waktu singkat (karena QR Code Guru berubah setiap 10 detik).
  * Tampilkan umpan balik instan:
    * **Berhasil (Suara/Getar & Layar Hijau)**: "Kehadiran Guru terverifikasi! Kelas berhasil dimulai."
    * **Gagal (Layar Merah)**: "Token kedaluwarsa!" atau "Anda memindai kode di luar jam/kelas Anda."
* **Integrasi API**: `POST /api/student/scan-attendance`
  * **Skema JSON Request**:
    ```json
    {
      "qr_payload": "U2FsdGVkX19283749823749872398472938472938472398"
    }
    ```
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "message": "Presensi kelas berhasil diverifikasi.",
      "timestamp": "2026-06-25 15:42:00"
    }
    ```

---

### C. Transparansi Nilai & Hasil Belajar
* **Penyajian Data**:
  * Tampilkan daftar mata pelajaran semester aktif dalam bentuk *Card Grid* atau list.
  * Setiap kartu mata pelajaran menampilkan nilai rata-rata tugas/sumatif sejauh ini.
  * Saat diklik, masuk ke halaman detail dafnil (Daftar Nilai) murid tersebut: menampilkan rincian Nilai Sumatif Harian, Nilai Sumatif Akhir Semester (SAS), dan nilai akhir rapor.
  * Gunakan warna peringatan **Merah** untuk nilai di bawah KKM (< 75) dan **Hijau/Putih** untuk nilai aman.
* **Integrasi API**: `GET /api/student/grades`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id_mapel": 101,
          "nama_mapel": "Matematika",
          "nilai_akhir": 78,
          "kkm": 75,
          "status": "LULUS",
          "details": [
            { "tipe": "Sumatif 1", "nilai": 80 },
            { "tipe": "Sumatif 2", "nilai": 75 },
            { "tipe": "SAS", "nilai": 80 }
          ]
        }
      ]
    }
    ```

---

### D. Ujian Live (Interactive CBT)
Fitur untuk pengerjaan evaluasi materi langsung dari gawai murid saat kelas berlangsung.
* **Penyajian Data**:
  * Begitu guru mengaktifkan ujian di kelas, notifikasi push masuk.
  * Antarmuka ujian menampilkan nomor soal, sisa waktu hitung mundur, soal teks/gambar, dan pilihan opsi A hingga E dengan tombol bulat (*Radio Button*).
  * **Anti-Cheating**: Blokir navigasi keluar (aplikasi mendeteksi jika siswa menekan tombol home, back, atau berpindah aplikasi dan secara otomatis mengunci lembar ujian dengan sanksi nilai nol jika dilakukan 3 kali).
* **Integrasi API**: `GET /api/student/exam/{id}`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "exam": {
        "id": 12,
        "judul": "Ujian Tengah Semester Ganjil",
        "durasi_menit": 60,
        "questions": [
          {
            "id_soal": 450,
            "no": 1,
            "pertanyaan": "Manakah yang merupakan classful IP Address?",
            "options": {
              "A": "192.168.1.1",
              "B": "10.0.0.0/8",
              "C": "172.16.0.0",
              "D": "Semua benar"
            }
          }
        ]
      }
    }
    ```
* **Integrasi API**: `POST /api/student/exam/{id}/submit`
  * **Skema JSON Request**:
    ```json
    {
      "answers": [
        { "id_soal": 450, "jawaban": "B" }
      ]
    }
    ```

---

### E. Pengisian Kuesioner Evaluasi
Siswa berperan sebagai auditor kualitas pengajaran.
* **Penyajian Data**:
  * **Kuesioner Wali Kelas (Tiap 3 Bulan)**: Menampilkan sejumlah pertanyaan skala penilaian bintang 1-5 mengenai kepedulian, komunikasi, dan pengayoman Wali Kelas.
  * **Kuesioner Guru Mapel (Akhir Semester)**: Menampilkan evaluasi kinerja metode mengajar Guru Mata Pelajaran.
  * Tampilkan indikator lencana "Belum Diisi" (Merah) dan "Selesai" (Hijau).
* **Integrasi API**: `GET /api/student/questionnaires`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id_kuesioner": 5,
          "tipe": "WALI_KELAS",
          "target_nama": "Sutrisno, S.Kom",
          "status": "BELUM_DIISI",
          "questions": [
            { "id_pertanyaan": 1, "teks": "Wali kelas memberikan solusi dan bimbingan atas masalah akademik murid." }
          ]
        }
      ]
    }
    ```
* **Integrasi API**: `POST /api/student/questionnaires/submit`
  * **Skema JSON Request**:
    ```json
    {
      "kuesioner_id": 5,
      "answers": [
        { "id_pertanyaan": 1, "rating": 5 }
      ]
    }
    ```

---

## 5. Fitur Native Mobile Tambahan
1. **Push Notifications**:
   * Pengingat instan ketika diterbitkan surat pembinaan (SP 1, SP 2, SP 3) ke akun siswa.
   * Notifikasi jika nilai sumatif baru di-input oleh guru mapel.
2. **Optimasi Konsumsi Kuota**:
   * Kompresi payload data se-minimal mungkin agar siswa tidak boros kuota internet saat menggunakan aplikasi di sekolah.
