# Panduan Pengembangan Aplikasi Mobile LMS 11 Maret (Sisi Wali Kelas)

Dokumen ini berisi panduan teknis, arsitektur, spesifikasi UI/UX, dan penyajian data untuk pengembangan aplikasi mobile khusus peran **Wali Kelas** pada sistem **LMS 11 Maret**.

---

## 1. Arsitektur & Teknologi Rekomendasi
* **Framework**: Flutter atau React Native (Android & iOS).
* **State Management**: BLoC, Provider, atau Zustand.
* **Integrasi Native**: File picker & PDF generation (untuk cetak dokumen pembinaan) serta Speech-to-Text API (untuk kemudahan pengisian deskripsi P5 secara lisan).

---

## 2. Sistem Autentikasi & Hak Akses
* **Metode**: Menggunakan autentikasi satu pintu dengan akun Guru. 
* **Deteksi Peran**: Setelah login berhasil, sistem memverifikasi apakah akun guru tersebut merupakan wali kelas aktif pada rombel tertentu. Jika ya, menu eksklusif "Wali Kelas" otomatis diaktifkan pada aplikasi.

---

## 3. Struktur Navigasi & Halaman Utama (Wali Kelas Mode)
Wali Kelas memiliki tab dashboard terpisah di menu navigasi bawah atau laci menu samping (*Drawer*):
1. **Beranda Kelas**: Ringkasan performa rombel perwalian secara keseluruhan.
2. **Monitoring Nilai**: Daftar kelengkapan nilai, rata-rata nilai siswa, dan detail remedial.
3. **Pembinaan & SP**: Pencatatan bimbingan siswa bermasalah dan penerbitan berkas peringatan.
4. **Input P5**: Pengisian rubrik penilaian Projek Penguatan Profil Pelajar Pancasila.
5. **Jurnal Kelas**: Memantau aktivitas mengajar harian guru mapel lain di kelas perwaliannya.

---

## 4. Panduan Penyajian Data & Integrasi API

### A. Dashboard Utama Kelas Perwalian
* **Penyajian Data (Glanceable Summary Cards)**:
  * Menampilkan 4 kartu parameter penting kelas:
    1. **Kelas Perwalian**: Nama kelas (contoh: X TKJ 2), jumlah siswa aktif, dan tahun ajaran berjalan.
    2. **Rata-rata Kehadiran**: Persentase kehadiran akumulatif seluruh siswa di kelas (berkelir hijau jika ≥ 90%, merah jika < 90%).
    3. **Rata-rata Rapor Kelas**: Nilai rata-rata dari seluruh nilai mata pelajaran siswa.
    4. **Poin Pelanggaran**: Akumulasi poin pelanggaran aktif kelas perwalian.
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
        "poin_pelanggaran_kelas": 12
      }
    }
    ```

---

### B. Monitoring Nilai Siswa (Deteksi Remedi & Detail Nilai)
* **Penyajian Data**:
  * **List Daftar Siswa**: Menampilkan nama siswa, NIS, persentase kehadiran, poin pelanggaran, bar kemajuan pengisian rapor (0-100%), dan nilai rata-rata.
  * **Highlight Remedi**: Siswa dengan nilai rata-rata < KKM (75) ditandai dengan latar belakang baris berwarna merah transparan (`bg-red-500/5`) dan ikon lencana peringatan jumlah mapel remedi (contoh: `⚠️ 4 Remedi`).
  * **Modal Detail Nilai Akademik (Bottom Sheet)**: 
    * Ketika Wali Kelas menekan tombol **"Lihat Detail"**, muncul jendela modal dari bawah layar yang menampilkan tabel komparasi nilai per mata pelajaran (Nama Mapel, Nilai SAS, Nilai Rapor, Status Kelulusan "Lulus/Remedi" dengan badge hijau/merah).
* **Integrasi API**: `GET /api/walikelas/students-performance`
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
      "student": { "id": 1, "nama": "Dartono Utama", "nis": "20260022" },
      "grades": [
        {
          "id_mapel": 101,
          "nama_mapel": "Matematika",
          "nilai_sas": 80,
          "nilai_akhir": 78,
          "status": "Lulus"
        }
      ]
    }
    ```

---

### C. Menu Pembinaan & Penerbitan Surat SP (Tindakan Disiplin)
* **Penyajian Data**:
  * Mengurutkan daftar siswa berdasarkan prioritas intervensi (siswa dengan akumulasi ketidakhadiran Alfa tertinggi diposisikan di paling atas).
  * **Form Pencatatan Tindakan**:
    1. **Pembinaan Pribadi / Personal**: Form catatan konseling wali kelas dengan siswa.
    2. **Pemanggilan Orang Tua**: Input jadwal & notifikasi undangan kehadiran orang tua ke sekolah.
    3. **Surat Peringatan (SP 1, SP 2, SP 3)**: Tombol aksi penerbitan surat peringatan berjenjang secara formal.
    4. **Surat Perjanjian Siap Tidak Naik Kelas**: Surat kesepakatan tertulis sebagai langkah tegas terakhir.
  * **Fitur Ekspor PDF & Berbagi Cepat**: Dokumen SP yang terbit langsung dikonversi menjadi file PDF resmi sekolah dan terintegrasi dengan tombol share ke WhatsApp Wali Murid.
* **Integrasi API**: `POST /api/walikelas/pembinaan/store`
  * **Skema JSON Request**:
    ```json
    {
      "siswa_id": 3,
      "jenis_tindakan": "PEMBINAAN_PRIBADI",
      "catatan": "Konseling pribadi mengenai motivasi belajar.",
      "tanggal": "2026-06-25"
    }
    ```
* **Integrasi API**: `POST /api/walikelas/sp/generate`
  * **Skema JSON Request**:
    ```json
    {
      "siswa_id": 3,
      "tingkat_sp": 1,
      "alasan": "Melanggar batas kehadiran alpa sebanyak 5 kali."
    }
    ```
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "pdf_url": "https://lms.smk11maret.sch.id/storage/sp/sp1_siswa_3.pdf"
    }
    ```

---

### D. Pengisian Asesmen P5 (Profil Pelajar Pancasila)
* **Penyajian Data & Alur Pengisian**:
  * **Daftar Projek**: Menampilkan projek P5 aktif (Tema, Nama Projek, Deskripsi).
  * **Pengisian Rubrik Multi-Step**:
    * **Step 1 (Rubrik Dimensi)**: Wali kelas memberikan nilai pencapaian dimensi (BB, MB, BSH, SB) menggunakan pilihan tombol opsi visual yang mudah diklik tanpa mengetik.
    * **Step 2 (Catatan Proses)**: Input deskripsi narasi perkembangan murid. Wali kelas dapat menggunakan fitur **Speech-to-Text** (ikon mikrofon) di HP untuk mendiktekan kalimat secara lisan, menghemat waktu penulisan deskripsi panjang murid kelas bimbingan.
* **Integrasi API**: `GET /api/walikelas/p5/projects`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id_projek": 12,
          "nama_projek": "Kearifan Lokal - Batik Tradisional",
          "dimensi": [
            { "id_dimensi": 1, "nama_dimensi": "Kebinekaan Global" }
          ]
        }
      ]
    }
    ```
* **Integrasi API**: `POST /api/walikelas/p5/evaluate`
  * **Skema JSON Request**:
    ```json
    {
      "siswa_id": 1,
      "projek_id": 12,
      "evaluasi": [
        { "id_dimensi": 1, "nilai": "SB" }
      ],
      "catatan": "Sangat aktif dalam melestarikan kerajinan batik."
    }
    ```

---

### E. Monitoring Jurnal KBM Kelas Perwalian
* **Penyajian Data**:
  * Menampilkan riwayat lini masa (*timeline list*) aktivitas harian kelas perwaliannya.
  * Setiap riwayat berisi info: jam ke-, guru pengampu, mata pelajaran, jurnal materi yang diajarkan, serta daftar siswa yang tidak hadir pada sesi tersebut beserta statusnya (Sakit/Izin/Alfa).
* **Integrasi API**: `GET /api/walikelas/class-journal`
  * **Skema JSON Response**:
    ```json
    {
      "success": true,
      "data": [
        {
          "hari": "Kamis",
          "tanggal": "2026-06-25",
          "kegiatan": [
            {
              "jam_ke": "1-2",
              "guru": "Ahmad Dani, S.Pd",
              "mapel": "Bahasa Indonesia",
              "materi": "Menulis Teks Prosedur Kompleks",
              "tidak_hadir": [
                { "nama": "Gatot Pradana", "status": "Sakit" }
              ]
            }
          ]
        }
      ]
    }
    ```

---

## 5. Fitur Pendukung & Push Notification Mobile
1. **Peringatan Ketidakhadiran Kritis**: Notifikasi otomatis ke ponsel Wali Kelas jika ada muridnya yang tidak masuk (Alfa) selama 3 hari berturut-turut atau akumulasi Alfa melebihi batas toleransi.
2. **Peringatan Poin Pelanggaran**: Notifikasi instan apabila guru piket atau bagian kesiswaan menginput poin pelanggaran baru pada murid kelas perwaliannya.
3. **Status Kelengkapan Nilai**: Pengingat berkala progres pengisian nilai rapor dari guru mata pelajaran lain menjelang penutupan batas waktu (lock-time) di kalender akademik.
