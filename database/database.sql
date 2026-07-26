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
    foto_profil VARCHAR(255) NULL,
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
('TKT-20260725-B202', 4, 2, 'Lansia Sebatang Kara Butuh Pendampingan Medis', 'Ditemukan seorang lansia usia ~75 tahun kondisi lemas terlantarkan di pos ronda dekat pasar. Membutuhkan tindakan medis dan penampungan panti sosial.', 'Jl. Pasar Baru No. 88, RT 01 RW 01', 'mendesak', 1, 10, 'Mendesak', 'Prioritas Ditentukan'),
('TKT-20260710-C303', 4, 3, 'Anak Usia 8 Tahun Terlantar di Pasar', 'Seorang anak laki-laki sekitar 8 tahun ditemukan mengemis dan terlihat tidak terawat di area pasar tradisional. Anak tersebut mengaku tidak tahu alamat rumah dan tidak bersekolah.', 'Pasar Tradisional Pasar Minggu, Jl. Raya Pasar Minggu', 'mendesak', 1, 12, 'Mendesak', 'Selesai'),
('TKT-20260712-D404', 4, 4, 'Korban Banjir Butuh Bantuan Logistik Darurat', 'Warga RT 07 RW 03 terdampak banjir kiriman sejak 3 hari lalu. Sekitar 25 kepala keluarga mengungsi ke Masjid Al-Ikhlas. Stok makanan dan obat-obatan menipis dan belum ada bantuan.', 'RT 07 RW 03, Kel. Bukit Duri, Kec. Tebet', 'tinggi', 25, 9, 'Tinggi', 'Diproses'),
('TKT-20260715-E505', 4, 5, 'Pengemis dan Tunawisma di Terminal Bus', 'Terdapat sekitar 6-8 orang PMKS (gepeng/tunawisma) yang tinggal dan beristirahat di sudut Terminal Kampung Melayu. Kondisi memprihatinkan dan mengganggu ketertiban terminal.', 'Terminal Kampung Melayu, Jakarta Timur', 'sedang', 7, 4, 'Sedang', 'Diverifikasi'),
('TKT-20260718-F606', 4, 1, 'Data Penerima PKH Tidak Sesuai, Warga Mampu Dapat Bansos', 'Tetangga saya yang memiliki kendaraan pribadi dan usaha warung makan masih terdaftar sebagai penerima PKH. Sementara keluarga saya yang benar-benar miskin tidak masuk DTKS.', 'Jl. Kebon Nanas Selatan RT 02 RW 08, Jakarta Timur', 'sedang', 1, 5, 'Sedang', 'Pengaduan Masuk'),
('TKT-20260720-G707', 4, 2, 'Lansia 82 Tahun Lumpuh Tidak Ada yang Merawat', 'Nenek Sumiati, 82 tahun, kondisi lumpuh dan hidup sebatang kara. Beliau tinggal di rumah tidak layak huni dan membutuhkan penempatan di panti jompo segera.', 'Jl. Cempaka Putih Timur No. 17, Jakarta Pusat', 'mendesak', 1, 11, 'Mendesak', 'Selesai'),
('TKT-20260722-H808', 4, 3, 'Remaja ABH 15 Tahun Butuh Pendampingan Hukum', 'Seorang remaja usia 15 tahun ditangkap polisi karena terlibat kasus pencurian. Remaja tersebut merupakan anak yatim piatu dan membutuhkan pendampingan hukum serta rehabilitasi sosial.', 'Polsek Matraman, Jakarta Timur', 'tinggi', 1, 8, 'Tinggi', 'Diklasifikasikan'),
('TKT-20260723-I909', 4, 4, 'Korban Kebakaran 47 Jiwa Kehilangan Tempat Tinggal', 'Kebakaran hebat menghanguskan 12 rumah warga di pemukiman padat. Sebanyak 47 jiwa kehilangan tempat tinggal dan membutuhkan bantuan sandang, pangan, dan hunian sementara.', 'Jl. Kampung Bali RT 05 RW 03, Tanah Abang', 'mendesak', 47, 14, 'Mendesak', 'Diproses'),
('TKT-20260723-J010', 4, 5, 'Pengamen Anak di Bawah Umur di Lampu Merah', 'Ada anak-anak di bawah umur (sekitar 10-14 tahun) yang mengamen di persimpangan lampu merah setiap hari dari pagi hingga malam hari tanpa pengawasan orang dewasa.', 'Perempatan Jl. Gatot Subroto - Jl. MT. Haryono', 'rendah', 4, 3, 'Rendah', 'Ditolak'),
('TKT-20260724-K111', 4, 1, 'Penyaluran Beras Bansos Tidak Merata di RW 04', 'Penyaluran beras bantuan sosial di RW kami tidak merata. Beberapa warga mampu mendapatkan jatah, sementara warga kurang mampu justru tidak mendapat. Mohon verifikasi ulang.', 'RW 04, Kel. Pisangan Baru, Kec. Matraman', 'sedang', 12, 6, 'Sedang', 'Prioritas Ditentukan'),
('TKT-20260725-L212', 4, 2, 'Penyandang Disabilitas Berat Butuh Kursi Roda', 'Warga disabilitas fisik (lumpuh kedua kaki) bernama Pak Suroso, 45 tahun, tidak memiliki kursi roda dan alat bantu jalan. Kondisi ekonomi sangat terbatas dan membutuhkan bantuan assistive device.', 'Jl. Otista Raya No. 33, Jatinegara, Jakarta Timur', 'sedang', 1, 5, 'Sedang', 'Diproses');

-- Seed Lampiran Sample
INSERT INTO lampiran (pengaduan_id, nama_file, tipe_file, ukuran_file, jenis_lampiran) VALUES
(1, 'kks_bukti.jpg', 'image/jpeg', 102400, 'bukti_pelapor'),
(2, 'lansia_terlantarkan.jpg', 'image/jpeg', 204800, 'bukti_pelapor'),
(3, 'foto_anak_terlantar.jpg', 'image/jpeg', 153600, 'bukti_pelapor'),
(4, 'kondisi_banjir.jpg', 'image/jpeg', 307200, 'bukti_pelapor'),
(4, 'pengungsi_masjid.jpg', 'image/jpeg', 256000, 'bukti_pelapor'),
(7, 'foto_nenek_sumiati.jpg', 'image/jpeg', 184320, 'bukti_pelapor'),
(9, 'foto_korban_kebakaran.jpg', 'image/jpeg', 204800, 'bukti_pelapor'),
(9, 'bukti_penyelesaian_kebakaran.pdf', 'application/pdf', 512000, 'bukti_penyelesaian'),
(11, 'daftar_penerima_bansos.pdf', 'application/pdf', 512000, 'bukti_pelapor'),
(12, 'foto_pak_suroso.jpg', 'image/jpeg', 128000, 'bukti_pelapor');

-- Seed Tanggapan Sample
INSERT INTO tanggapan (pengaduan_id, petugas_id, isi_tanggapan, status_tanggapan) VALUES
(1, 2, 'Laporan telah diverifikasi. Petugas telah melakukan pengecekan data di SIKS-NG. Data KKS Anda sedang dalam proses pemutakhiran rekening oleh pihak bank penyalur.', 'Diproses'),
(3, 2, 'Anak telah ditemukan dan dibawa ke Rumah Singgah Dinas Sosial. Tim TKSK sedang melakukan penelusuran identitas dan orang tua. Anak dalam kondisi sehat dan aman.', 'Selesai'),
(3, 3, 'Proses penanganan telah selesai. Anak berhasil dipertemukan dengan pihak keluarga. Pendampingan lanjutan oleh pekerja sosial akan dilakukan selama 3 bulan.', 'Selesai'),
(4, 3, 'Tim Tagana telah diterjunkan ke lokasi pengungsian. Bantuan logistik berupa beras 500kg, mie instan, dan air mineral telah didistribusikan. Proses evakuasi masih berlangsung.', 'Diproses'),
(5, 2, 'Laporan telah diterima dan diverifikasi. Koordinasi dengan Satpol PP dan Tim Penjangkauan PMKS sedang dilakukan untuk penertiban dan pembinaan sosial.', 'Diverifikasi'),
(7, 3, 'Nenek Sumiati telah berhasil ditempatkan di Panti Wredha Budi Mulia 1. Proses administrasi dan pemeriksaan kesehatan awal telah selesai dilakukan. Kasus dinyatakan selesai ditangani.', 'Selesai'),
(9, 2, 'Tim Tagana dan relawan sosial telah mendirikan tenda pengungsian darurat. Bantuan sandang dan pangan untuk 47 jiwa telah disalurkan dan proses pengurusan hunian sementara sedang berjalan.', 'Diproses'),
(11, 3, 'Data penerima bansos RW 04 sedang dilakukan verifikasi ulang bersama tim DTKS kelurahan. Proses pemutakhiran data diperkirakan selesai dalam 14 hari kerja.', 'Diproses'),
(12, 2, 'Permohonan bantuan kursi roda untuk Pak Suroso telah dicatat. Tim penilai sosial dijadwalkan melakukan kunjungan rumah pada minggu depan untuk asesmen kebutuhan.', 'Diproses');

-- Seed Riwayat Status Sample
INSERT INTO riwayat_status (pengaduan_id, user_id, status_lama, status_baru, catatan) VALUES
(1, 4, NULL, 'Pengaduan Masuk', 'Pengaduan berhasil dikirim oleh pelapor'),
(1, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Berkas dan lokasi laporan telah diverifikasi oleh Petugas Budi Santoso'),
(1, 2, 'Diverifikasi', 'Diklasifikasikan', 'Pengaduan diklasifikasikan ke Kategori Bantuan Sosial'),
(1, 2, 'Diklasifikasikan', 'Diproses', 'Diproses tindak lanjut pengecekan SIKS-NG'),
(2, 4, NULL, 'Pengaduan Masuk', 'Pengaduan masuk dari pelapor'),
(2, 3, 'Pengaduan Masuk', 'Diverifikasi', 'Laporan diverifikasi oleh Petugas Siti Rahmawati'),
(2, 3, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan ke kategori Disabilitas & Lansia'),
(2, 3, 'Diklasifikasikan', 'Prioritas Ditentukan', 'Prioritas Mendesak ditetapkan karena ancaman jiwa'),
(3, 4, NULL, 'Pengaduan Masuk', 'Pengaduan masuk dari pelapor'),
(3, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Diverifikasi dan tim lapangan diturunkan'),
(3, 2, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan Perlindungan Anak'),
(3, 2, 'Diklasifikasikan', 'Prioritas Ditentukan', 'Prioritas Mendesak'),
(3, 2, 'Prioritas Ditentukan', 'Diproses', 'Anak dibawa ke Rumah Singgah'),
(3, 3, 'Diproses', 'Selesai', 'Anak berhasil dipertemukan dengan keluarga'),
(4, 4, NULL, 'Pengaduan Masuk', 'Pengaduan darurat banjir masuk'),
(4, 3, 'Pengaduan Masuk', 'Diverifikasi', 'Tim Tagana konfirmasi kondisi lapangan'),
(4, 3, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan Korban Bencana'),
(4, 3, 'Diklasifikasikan', 'Prioritas Ditentukan', 'Prioritas Tinggi - 25 KK terdampak'),
(4, 3, 'Prioritas Ditentukan', 'Diproses', 'Bantuan logistik disalurkan'),
(5, 4, NULL, 'Pengaduan Masuk', 'Pengaduan PMKS masuk'),
(5, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Diverifikasi koordinasi Satpol PP'),
(6, 4, NULL, 'Pengaduan Masuk', 'Pengaduan data bansos tidak tepat sasaran masuk'),
(7, 4, NULL, 'Pengaduan Masuk', 'Pengaduan lansia terlantar masuk'),
(7, 3, 'Pengaduan Masuk', 'Diverifikasi', 'Kunjungan rumah dilakukan petugas'),
(7, 3, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan Disabilitas & Lansia'),
(7, 3, 'Diklasifikasikan', 'Prioritas Ditentukan', 'Prioritas Mendesak - lansia lumpuh sebatang kara'),
(7, 3, 'Prioritas Ditentukan', 'Diproses', 'Proses penempatan panti jompo'),
(7, 3, 'Diproses', 'Selesai', 'Nenek Sumiati berhasil masuk Panti Wredha'),
(8, 4, NULL, 'Pengaduan Masuk', 'Pengaduan ABH remaja masuk'),
(8, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Koordinasi dengan Polsek'),
(8, 2, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan Perlindungan Anak & ABH'),
(9, 4, NULL, 'Pengaduan Masuk', 'Pengaduan darurat kebakaran masuk'),
(9, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Tim verifikasi turun ke lokasi'),
(9, 2, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan Korban Bencana'),
(9, 2, 'Diklasifikasikan', 'Prioritas Ditentukan', 'Prioritas Mendesak - 47 jiwa terdampak'),
(9, 2, 'Prioritas Ditentukan', 'Diproses', 'Tenda dan bantuan darurat didirikan'),
(10, 4, NULL, 'Pengaduan Masuk', 'Pengaduan PMKS anak masuk'),
(10, 3, 'Pengaduan Masuk', 'Ditolak', 'Ditolak - bukan kewenangan Dinsos, dilimpahkan ke Satpol PP'),
(11, 4, NULL, 'Pengaduan Masuk', 'Pengaduan ketidakmerataan bansos masuk'),
(11, 3, 'Pengaduan Masuk', 'Diverifikasi', 'Diverifikasi data penerima RW 04'),
(11, 3, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan ketidaktepatan sasaran bansos'),
(11, 3, 'Diklasifikasikan', 'Prioritas Ditentukan', 'Prioritas Sedang - 12 orang terdampak'),
(12, 4, NULL, 'Pengaduan Masuk', 'Pengaduan kebutuhan kursi roda masuk'),
(12, 2, 'Pengaduan Masuk', 'Diverifikasi', 'Verifikasi kondisi Pak Suroso'),
(12, 2, 'Diverifikasi', 'Diklasifikasikan', 'Diklasifikasikan Disabilitas & Lansia'),
(12, 2, 'Diklasifikasikan', 'Diproses', 'Asesmen kebutuhan dijadwalkan');

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
