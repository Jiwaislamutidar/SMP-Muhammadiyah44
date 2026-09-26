# Product Requirement Document (PRD)
**Sistem Presensi & Manajemen Akademik KBM**  
**Sekolah:** SMP Muhammadiyah 44 Tangerang Selatan  
**Versi:** 1.0  
**Tanggal:** September 2026  
**Status:** In Development  

---

## 1. Overview & Objectives

### 1.1 Latar Belakang
Proses pencatatan kehadiran siswa secara manual di SMP Muhammadiyah 44 membutuhkan waktu KBM yang berharga dan rawan kekeliruan rekapitulasi. Diperlukan sistem presensi berbasis **QR Code Interaktif** untuk mempercepat proses absensi, memvalidasi kehadiran secara *real-time*, serta memfasilitasi pencatatan kehadiran guru.

### 1.2 Tujuan Utama
* **Otomatisasi Presensi:** Mempercepat proses absensi siswa di kelas menggunakan scan QR Code dinamis.
* **Masa Aktif Terkontrol:** Membatasi waktu aktif QR Code hanya pada jam pelajaran (KBM) yang sedang berlangsung.
* **Auto-Alfa Sistem:** Secara otomatis menetapkan status **Alfa** bagi siswa yang belum melakukan scan hingga jam pelajaran usai.
* **Kontrol Guru & Admin:** Memberikan akses manual bagi Guru dan Admin untuk memperbarui status presensi (Sakit, Izin, Alfa) apabila siswa terkendala.
* **Dual-Attendance Guru:** Mencatat kehadiran harian guru di sekolah (Masuk/Pulang) sekaligus presensi sesi mengajar KBM.

---

## 2. Hak Akses Pengguna (User Roles)

| Role | Deskripsi & Hak Akses Utama |
| :--- | :--- |
| **Siswa** | • Login ke portal siswa.<br>• Melakukan scan QR Code KBM di kelas.<br>• Melihat riwayat kehadiran mandiri & jadwal pelajaran. |
| **Guru** | • Login ke portal guru.<br>• Melakukan Absen Masuk & Absen Pulang sekolah.<br>• Menampilkan QR Code KBM aktif sesuai jadwal.<br>• Mengedit status presensi murid secara manual (Hadir/Izin/Sakit/Alfa).<br>• Melihat jadwal mengajar & riwayat KBM. |
| **Admin** | • Memiliki hak akses tertinggi (*Super Admin*).<br>• Mengelola Master Data (Siswa, Guru, Kelas, Mapel, Jadwal).<br>• Mengubah/override status absensi jika ada kesalahan data.<br>• Mengunduh dan mencetak rekapitulasi laporan presensi. |

---

## 3. Spesifikasi Fitur (Functional Requirements)

### F1: Autentikasi & Multi-Role System
* **F1.1:** Login terpusat untuk 3 role (`admin`, `guru`, `siswa`).
* **F1.2:** Pengalihan halaman (*redirect*) otomatis sesuai role pengguna setelah autentikasi berhasil.
* **F1.3:** Proteksi *middleware* untuk mencegah akses antar-role tanpa hak izin.

### F2: Presensi KBM & QR Code (Guru & Siswa)
* **F2.1 Generate QR:** Guru dapat membuka sesi KBM aktif untuk menampilkan QR Code unik berbasis `token_qr` jadwal.
* **F2.2 Dynamic Validation:** QR Code hanya berlaku selama rentang `jam_mulai` hingga `jam_selesai` sesi KBM.
* **F2.3 Scan QR:** Siswa melakukan scan QR melalui kamera HP pada Portal Siswa.
* **F2.4 Validasi Kehadiran:** Jika token valid dan jam KBM aktif, status siswa tercatat sebagai **Hadir** beserta `waktu_scan`.

### F3: Absen Manual & Otomatisasi Sistem
* **F3.1 Manual Override:** Guru/Admin dapat mengubah status siswa menjadi **Hadir**, **Izin**, **Sakit**, atau **Alfa** secara manual via dashboard.
* **F3.2 Auto-Alfa Penutupan:** Setelah `jam_selesai` KBM terlampaui, sistem otomatis memberikan status **Alfa** bagi siswa yang belum memiliki catatan presensi pada sesi tersebut.

### F4: Presensi Harian Guru
* **F4.1 Absen Masuk:** Guru mencatat jam kedatangan di gerbang sekolah.
* **F4.2 Absen Pulang:** Guru mencatat jam kepulangan setelah seluruh jam KBM harian selesai.

### F5: Riwayat & Laporan Akademik
* **F5.1 Riwayat Siswa:** Siswa dapat memantau akumulasi kehadiran (Hadir, Izin, Sakit, Alfa).
* **F5.2 Monitoring Guru:** Guru dapat melihat log sesi mengajar yang sudah selesai maupun yang belum lengkap.
* **F5.3 Rekap Admin:** Admin dapat memfilter dan mengunduh laporan presensi bulanan/semesteran.

---

## 4. Kebutuhan Non-Fungsional (Non-Functional Requirements)

* **Performa:** Waktu pemrosesan scan QR hingga validasi database maksimal **2 detik**.
* **Keamanan Data:** Token QR Code di-generate secara terenkripsi/acak untuk mencegah penyalahgunaan *screenshot* QR di luar kelas.
* **Responsivitas UI:** Tampilan berbasis Blade/CSS dioptimalkan untuk perangkat *mobile* (Siswa) dan *desktop/laptop* (Guru & Admin).
* **Ketersediaan Sistem:** Dapat berjalan di lingkungan server lokal (Laragon/Apache) maupun server *cloud*.

---

## 5. Arsitektur Database (Database Schema)

Sistem menggunakan 8 tabel utama yang saling terelasi:

1. **`users`**: Store credentials login (`id`, `username`, `password`, `role`).
2. **`gurus`**: Data profil guru (`id`, `user_id`, `nip_nuptk`, `nama_lengkap`, `email`, `no_telp`, `mata_pelajaran_utama`).
3. **`siswas`**: Data profil murid (`id`, `user_id`, `nisn`, `nama_lengkap`, `kelas_id`, `status`).
4. **`kelas`**: Data rombel (`id`, `nama_kelas`).
5. **`mapels`**: Master mata pelajaran (`id`, `kode_mapel`, `nama_mapel`).
6. **`jadwals`**: Jadwal KBM (`id`, `guru_id`, `kelas_id`, `mapel_id`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`, `token_qr`, `status_sesi`).
7. **`presensi_guru_sekolahs`**: Log absensi harian guru (`id`, `guru_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`).
8. **`presensi_siswas`**: Log absensi KBM murid (`id`, `jadwal_id`, `siswa_id`, `tanggal`, `waktu_scan`, `status`).

---

## 6. Alur Kerja Utama Sistem (KBM Lifecycle)

```text
[Guru Buka Kelas] 
       │
       ▼
[Sistem Generate QR Code Aktif] 
       │
       ├───► Siswa Scan QR ───► Valid? ───► Status: HADIR
       │
       ├───► Siswa Izin/Sakit (WA) ───► Guru Input Manual (IZIN / SAKIT)
       │
       ▼
[Jam KBM Selesai]
       │
       ▼
[Sistem Auto-Run: Siswa Tanpa Status ───► Status: ALFA]