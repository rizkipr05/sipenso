-- ===================================================
-- SISTEM PENGADUAN MASYSARAKAT DINAS SOSIAL (SIPENSO)
-- Database Schema for MySQL / phpMyAdmin
-- ===================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS riwayat_status;
DROP TABLE IF EXISTS log_aktivitas;
DROP TABLE IF EXISTS tanggapan;
DROP TABLE IF EXISTS lampiran;
DROP TABLE IF EXISTS pengaduan;
DROP TABLE IF EXISTS kriteria_prioritas;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Table Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) UNIQUE NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_hp VARCHAR(20) NOT NULL,
    alamat TEXT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'pelapor') NOT NULL DEFAULT 'pelapor',
    status_akun ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table Kategori Pengaduan
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_kategori VARCHAR(30) NOT NULL UNIQUE,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table Kriteria Prioritas (Rule-based evaluation matrix)
CREATE TABLE kriteria_prioritas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kriteria VARCHAR(100) NOT NULL,
    skor_bobot INT NOT NULL DEFAULT 1,
    deskripsi TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table Pengaduan
CREATE TABLE pengaduan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_tiket VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    kategori_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    isi_laporan TEXT NOT NULL,
    lokasi_kejadian TEXT NOT NULL,
    tingkat_dampak ENUM('rendah', 'sedang', 'tinggi', 'mendesak') NOT NULL DEFAULT 'sedang',
    jumlah_terdampak INT DEFAULT 1,
    skor_prioritas INT DEFAULT 0,
    prioritas ENUM('Rendah', 'Sedang', 'Tinggi', 'Mendesak') NOT NULL DEFAULT 'Sedang',
    status ENUM('Pengaduan Masuk', 'Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses', 'Selesai', 'Ditolak') NOT NULL DEFAULT 'Pengaduan Masuk',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table Lampiran (Foto / Dokumen Pendukung)
CREATE TABLE lampiran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengaduan_id INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    tipe_file VARCHAR(100) NOT NULL,
    ukuran_file INT NOT NULL,
    jenis_lampiran ENUM('bukti_pelapor', 'bukti_penyelesaian') NOT NULL DEFAULT 'bukti_pelapor',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengaduan_id) REFERENCES pengaduan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table Tanggapan / Tindak Lanjut Petugas
CREATE TABLE tanggapan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengaduan_id INT NOT NULL,
    petugas_id INT NOT NULL,
    isi_tanggapan TEXT NOT NULL,
    status_tanggapan ENUM('Diverifikasi', 'Diproses', 'Selesai', 'Ditolak') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengaduan_id) REFERENCES pengaduan(id) ON DELETE CASCADE,
    FOREIGN KEY (petugas_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table Riwayat Status (Audit Trail)
CREATE TABLE riwayat_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengaduan_id INT NOT NULL,
    user_id INT NOT NULL,
    status_lama VARCHAR(50) NULL,
    status_baru VARCHAR(50) NOT NULL,
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengaduan_id) REFERENCES pengaduan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- SEED DATA DEFAULT
-- Passwords below are hashed using password_hash('password123', PASSWORD_BCRYPT)
-- ===================================================

INSERT INTO users (nik, nama_lengkap, email, no_hp, alamat, username, password, role, status_akun) VALUES
('3171010000000001', 'Administrator Dinsos', 'admin@dinsos.go.id', '081234567890', 'Jl. Raden Patah No. 1, Jakarta', 'admin', '$2y$10$wE40gW2FepF1K058M3.k/eW0rWp4z9lO/w7sN1c.KkQn.K.7n2aK2', 'admin', 'aktif'),
('3171010000000002', 'Budi Santoso (Petugas)', 'budi.petugas@dinsos.go.id', '081298765432', 'Jl. Merdeka No. 45, Jakarta', 'petugas1', '$2y$10$wE40gW2FepF1K058M3.k/eW0rWp4z9lO/w7sN1c.KkQn.K.7n2aK2', 'petugas', 'aktif'),
('3171010000000003', 'Siti Rahmawati (Petugas)', 'siti.petugas@dinsos.go.id', '081311223344', 'Jl. Melati No. 12, Jakarta', 'petugas2', '$2y$10$wE40gW2FepF1K058M3.k/eW0rWp4z9lO/w7sN1c.KkQn.K.7n2aK2', 'petugas', 'aktif'),
('3171010101900005', 'Ahmad Rizky (Masyarakat)', 'ahmad.rizky@gmail.com', '085711223355', 'Jl. Mawar RT 03/RW 05 No. 10', 'pelapor1', '$2y$10$wE40gW2FepF1K058M3.k/eW0rWp4z9lO/w7sN1c.KkQn.K.7n2aK2', 'pelapor', 'aktif');

-- Seed Kategori
INSERT INTO kategori (kode_kategori, nama_kategori, deskripsi) VALUES
('KAT-BANSOS', 'Bantuan Sosial (PKH, BPNT, DTKS)', 'Pengaduan terkait penyaluran bansos, ketidaktepatan sasaran, atau kendala pendaftaran DTKS'),
('KAT-DISLANSIA', 'Disabilitas & Lansia Terlantarkan', 'Laporan penanganan warga disabilitas atau lansia yang membutuhkan pendampingan & tempat tinggal'),
('KAT-ANAK', 'Perlindungan Anak & ABH', 'Laporan kasus eksploitasi anak, pengatapan anak terlantarkan, dan penanganan anak berhadapan hukum'),
('KAT-BENCANA', 'Korban Bencana Sosial & Alam', 'Bantuan tanggap darurat logistik bagi warga terdampak kebakaran, banjir, dan bencana sosial'),
('KAT-GEPENG', 'Penanganan PMKS (Gepeng/Tunawisma)', 'Laporan penertiban dan pembinaan Pemerlu Pelayanan Kesejahteraan Sosial di tempat umum');

-- Seed Kriteria Prioritas
INSERT INTO kriteria_prioritas (nama_kriteria, skor_bobot, deskripsi) VALUES
('Tingkat Urgensi Keselamatan Jiwa', 4, 'Ancaman terhadap jiwa/kesehatan mendesak warga'),
('Jumlah Warga Terdampak', 3, 'Skala dampak kejadian bagi masyarakat sekitar'),
('Keterlanjuran / Kerentanan Korban', 3, 'Golongan sangat rentan seperti anak balita, lansia lumpuh, atau disabilitas berat'),
('Waktu Kejadian & Potensi Konflik', 2, 'Risiko timbulnya eskalasi atau ketertiban umum');

-- Seed Pengaduan Sample
INSERT INTO pengaduan (nomor_tiket, user_id, kategori_id, judul, isi_laporan, lokasi_kejadian, tingkat_dampak, jumlah_terdampak, skor_prioritas, prioritas, status) VALUES
('TKT-20260725-A101', 4, 1, 'Bantuan BPNT Belum Cair 3 Bulan', 'Saya penerima manfaat BPNT atas nama Ahmad Rizky NIK 3171010101900005, bantuan BPNT kartu KKS tidak terisi dana sejak Mei hingga Juli 2026. Mohon pengecekan status DTKS.', 'Kelurahan Cipinang RT 04 RW 02, Kec. Jatinegara', 'sedang', 1, 5, 'Sedang', 'Diproses'),
('TKT-20260725-B202', 4, 2, 'Lansia Sebatang Kara Butuh Pendampingan Medis', 'Ditemukan seorang lansia usia ~75 tahun kondisi lemas terlantarkan di pos ronda dekat pasar. Membutuhkan tindakan medis dan penampungan panti sosial.', 'Jl. Pasar Baru No. 88, RT 01 RW 01', 'mendesak', 1, 10, 'Mendesak', 'Prioritas Ditentukan');

-- Seed Lampiran Sample
INSERT INTO lampiran (pengaduan_id, nama_file, tipe_file, ukuran_file, jenis_lampiran) VALUES
(1, 'kks_bukti.jpg', 'image/jpeg', 102400, 'bukti_pelapor'),
(2, 'lansia_terlantarkan.jpg', 'image/jpeg', 204800, 'bukti_pelapor');

-- Seed Tanggapan Sample
INSERT INTO tanggapan (pengaduan_id, petugas_id, isi_tanggapan, status_tanggapan) VALUES
(1, 2, 'Laporan telah diverifikasi. Petugas telah melakukan pengecekan data di SIKS-NG. Data KKS Anda sedang dalam proses pemutakhiran rekening oleh pihak bank penyalur.', 'Diproses');

-- Seed Riwayat Status Sample
INSERT INTO riwayat_status (pengaduan_id, user_id, status_lama, status_baru, catatan) VALUES
(1, 4, NULL, 'Pengaduan Masuk', 'Pengaduan berhasil dikirim oleh pelapor'),
(1, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Berkas dan lokasi laporan telah diverifikasi oleh Petugas Budi Santoso'),
(1, 2, 'Diverifikasi', 'Diklasifikasikan', 'Pengaduan diklasifikasikan ke Kategori Bantuan Sosial'),
(1, 2, 'Diklasifikasikan', 'Diproses', 'Diproses tindak lanjut pengecekan SIKS-NG');

-- 8. Table Log Aktivitas (Complex Audit Trail)
CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aktivitas VARCHAR(255) NOT NULL,
    keterangan TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Log Aktivitas
INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address, user_agent) VALUES
(4, 'Registrasi Akun', 'Registrasi mandiri dari IP Publik', '127.0.0.1', 'Mozilla/5.0'),
(4, 'Login', 'Login berhasil', '127.0.0.1', 'Mozilla/5.0'),
(1, 'Login', 'Login admin berhasil', '127.0.0.1', 'Mozilla/5.0');
