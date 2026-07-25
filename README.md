# 🏛️ SIPENSO - Sistem Pengaduan Masyarakat Dinas Sosial

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

**SIPENSO** adalah platform sistem informasi pengaduan masyarakat berbasis web yang dirancang khusus untuk meningkatkan efisiensi penanganan aduan masalah sosial pada **Dinas Sosial**. Platform ini dilengkapi dengan **Engine Klasifikasi Otomatis berbasis *Keyword-Weight Scoring***, kalkulator prioritas penanganan dinamis, serta sistem audit trail aktivitas pengguna demi transparansi dan akuntabilitas.

---

## 🌟 Fitur Utama

### 🤖 Engine Klasifikasi Otomatis (Automatic Classifier)
- **Sistem Pembobotan Kata Kunci (*Keyword-Weight Scoring*)**: Mengkategorikan pengaduan secara otomatis ke dalam 5 kategori utama (`BANSOS`, `DISLANSIA`, `ANAK`, `BENCANA`, `GEPENG`).
- **AJAX Real-Time Suggestion Panel**: Memberikan rekomendasi kategori secara *real-time* dengan skor tingkat kepercayaan (*confidence percentage*) saat masyarakat mengetik judul atau isi pengaduan.

### 📊 Kalkulasi Prioritas Penanganan Dinamis
- Menghitung skor prioritas aduan (`SANGAT TINGGI`, `TINGGI`, `SEDANG`, `RENDAH`) secara otomatis berdasarkan:
  - **Tingkat Dampak Masalah** (*Rendah, Sedang, Tinggi, Sangat Tinggi*).
  - **Jumlah Warga Terdampak**.

### 👥 Manajemen Multi-Role & Dashboard Terisolasi
- **Admin**: Akses kontrol penuh terhadap pengaduan, manajemen pengguna/petugas, kelola kategori, kriteria prioritas, dan rekap laporan.
- **Petugas**: Memproses aduan masuk, melakukan verifikasi lapang, mengklaim penanganan, memperbarui status tiket, dan memberikan tanggapan/solusi.
- **Pelapor (Masyarakat)**: Mengajukan pengaduan baru, memantau status perkembangan secara *real-time*, mencetak bukti tiket pengaduan, dan melihat riwayat tindak lanjut.

### 🛡️ Audit Trail & Manajemen Profil Terpusat
- **Audit Log Otomatis**: Mencatat setiap aksi krusial pengguna (*Login, Logout, Buat Pengaduan, Verifikasi, Update Status, Reset Password*) lengkap dengan *IP Address* dan *User Agent*.
- **Halaman Profil Enterprise**: Kelola data diri, ganti kata sandi dengan enkripsi BCRYPT, dan pantau log aktivitas keamanan akun.

### 🎨 Design System Premium & Modern UI/UX
- **Glassmorphism & Frosted Effects**: Header dan panel dengan efek *backdrop blur* yang elegan.
- **Gradien & Micro-Animations**: Kartu statistik bergradien kustom, *pulse glow badges*, dan efek transisi halus.
- **Sidebar Integration**: Tata letak terintegrasi secara konsisten di seluruh role dan halaman.

---

## 🛠️ Teknologi & Dependensi

- **Backend**: PHP Native 7.4 / 8.x (PDO Object-Oriented Database Driver)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3 Custom Design System, JavaScript (Vanilla AJAX / Fetch API)
- **Framework & Library**:
  - Bootstrap 5.3 (Responsive Grid & Components)
  - FontAwesome 6.4 (Iconography)
  - Google Fonts (Inter & Plus Jakarta Sans)
- **Keamanan**: Prepared Statements (Anti SQL Injection), BCRYPT Password Hashing, HTML Sanitation (`htmlspecialchars`).

---

## 🗂️ Struktur Direktori Proyek

```text
sistempengaduan/
├── admin/                  # Portal & Dashboard Administrator
│   ├── dashboard.php       # Overview statistik & grafik
│   ├── pengaduan.php       # Master kelola pengaduan
│   ├── users.php           # Kelola akun pelapor & petugas
│   ├── kategori.php        # Kelola kategori aduan
│   ├── kriteria.php        # Kelola pembobotan prioritas
│   └── laporan.php         # Cetak & rekap laporan
├── api/                    # Endpoint API AJAX
│   └── classify.php        # Endpoint klasifikasi teks real-time
├── assets/                 # Berkas statis (CSS, JS, Uploads)
│   ├── css/style.css       # Pusat Design System SIPENSO
│   └── uploads/            # Berkas foto bukti lampiran
├── config/                 # Berkas konfigurasi inti
│   ├── database.php        # Koneksi PDO MySQL
│   ├── helpers.php         # Helper functions & audit logger
│   └── classifier.php      # Algoritma klasifikasi keyword
├── database/               # Berkas skema SQL
│   └── database.sql        # Database dump & data awal
├── includes/               # Komponen UI Reusable
│   ├── header.php          # Meta tags & asset loader
│   ├── navbar.php          # Top navigation bar & user profile dropdown
│   ├── sidebar.php         # Navigation sidebar per role
│   └── footer.php          # Footer pelengkap
├── pelapor/                # Portal Pelapor (Masyarakat)
│   ├── dashboard.php       # Ringkasan tiket pelapor
│   ├── buat_pengaduan.php  # Form pengaduan baru + AI Classifier
│   ├── riwayat.php         # Daftar riwayat aduan
│   └── detail.php          # Detail status & bukti cetak
├── petugas/                # Portal Petugas Lapangan/Verifikator
│   ├── dashboard.php       # Antrean tugas penanganan
│   └── detail_pengaduan.php# Form verifikasi & tanggapan
├── index.php               # Landing Page utama
├── login.php               # Halaman masuk akun
├── register.php            # Halaman pendaftaran pelapor
├── profile.php             # Halaman manajemen profil & audit log
└── logout.php              # Destruksi sesi login
```

---

## 🚀 Panduan Instalasi & Penggunaan

### 1. Prasyarat Sistem
- Web Server (Apache / Nginx) - disarankan menggunakan **XAMPP / LAMPP / WAMP**.
- **PHP** versi 7.4 atau lebih baru.
- **MySQL / MariaDB**.

### 2. Langkah-Langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/rizkipr05/sipenso.git
   cd sipenso
   ```
   Atau letakkan folder proyek ini di direktori root server lokal Anda (`htdocs` pada XAMPP/LAMPP).
   Contoh direktori: `/opt/lampp/htdocs/sistempengaduan/` atau `C:/xampp/htdocs/sistempengaduan/`.

2. **Import Database**
   - Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Buat database baru dengan nama `sistempengaduan`.
   - Import file SQL yang berada di direktori `database/database.sql`.

3. **Konfigurasi Database**
   Buka file `config/database.php` dan sesuaikan kredensial MySQL Anda jika diperlukan:
   ```php
   $host = 'localhost';
   $db   = 'sistempengaduan';
   $user = 'root';
   $pass = ''; // Sesuaikan dengan password MySQL Anda
   ```

4. **Jalankan Aplikasi**
   Buka peramban (browser) dan akses:
   ```text
   http://localhost/sistempengaduan/
   ```

---

## 🔑 Kredensial Pengujian (Default Credentials)

Untuk keperluan demonstrasi dan pengujian, Anda dapat menggunakan akun bawaan berikut:

| Role | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| 🛡️ **Administrator** | `admin` | `password123` | Akses penuh ke seluruh kontrol sistem |
| 👷 **Petugas Lapangan** | `petugas` | `password123` | Memproses & memverifikasi aduan |
| 👤 **Pelapor (Warga)** | `pelapor` | `password123` | Membuat aduan & memantau status |

---

## 📋 Matriks Hak Akses Role

| Fitur / Modul | Administrator | Petugas | Pelapor |
| :--- | :---: | :---: | :---: |
| Landing Page & Statistik Publik | ✅ | ✅ | ✅ |
| Buat Pengaduan & Rekomendasi AI | ❌ | ❌ | ✅ |
| Pantau Status Tiket & Cetak Bukti | ❌ | ❌ | ✅ |
| Verifikasi & Update Status Aduan | ✅ | ✅ | ❌ |
| Beri Tanggapan / Tindak Lanjut | ✅ | ✅ | ❌ |
| Kelola Kategori & Kriteria Prioritas | ✅ | ❌ | ❌ |
| Kelola Akun User & Tambah Petugas | ✅ | ❌ | ❌ |
| Cetak Rekap Laporan & Export Data | ✅ | ❌ | ❌ |
| Manajemen Profil & Audit Trail Akun | ✅ | ✅ | ✅ |

---

## 📝 Lisensi

Proyek ini dirilis di bawah lisensi [MIT License](LICENSE). Bebas digunakan, dimodifikasi, dan dikembangkan kembali untuk kepentingan akademis maupun operasional instansi.

---

<p center align="center">
  Developed with ❤️ for <b>Dinas Sosial - SIPENSO Project</b>
</p>
