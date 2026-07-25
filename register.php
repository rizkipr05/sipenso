<?php
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

if (is_logged_in()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
    } elseif ($role === 'petugas') {
        header('Location: ' . base_url('petugas/dashboard.php'));
    } else {
        header('Location: ' . base_url('pelapor/dashboard.php'));
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik          = sanitize($_POST['nik'] ?? '');
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email        = sanitize($_POST['email'] ?? '');
    $no_hp        = sanitize($_POST['no_hp'] ?? '');
    $alamat       = sanitize($_POST['alamat'] ?? '');
    $username     = sanitize($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($nik) || empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($username) || empty($password)) {
        $error = 'Harap isi semua kolom wajib.';
    } elseif (strlen($nik) !== 16 || !is_numeric($nik)) {
        $error = 'NIK harus berupa 16 digit angka.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } else {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nik = :nik OR email = :email OR username = :username LIMIT 1");
            $stmt->execute(['nik' => $nik, 'email' => $email, 'username' => $username]);
            if ($stmt->fetch()) {
                $error = 'NIK, Email, atau Username sudah terdaftar di sistem.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (nik, nama_lengkap, email, no_hp, alamat, username, password, role, status_akun) VALUES (:nik, :nama, :email, :hp, :alamat, :uname, :pass, 'pelapor', 'aktif')");
                
                $result = $stmt->execute([
                    'nik' => $nik,
                    'nama' => $nama_lengkap,
                    'email' => $email,
                    'hp' => $no_hp,
                    'alamat' => $alamat,
                    'uname' => $username,
                    'pass' => $hashed_password
                ]);

                if ($result) {
                    $new_user_id = $pdo->lastInsertId();
                    log_activity($new_user_id, 'Registrasi Akun', 'Registrasi mandiri dari IP Publik.', $pdo);

                    set_flash('success', 'Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.');
                    header('Location: ' . base_url('login.php'));
                    exit;
                } else {
                    $error = 'Gagal mendaftar. Silakan coba lagi.';
                }
            }
        } else {
            $error = 'Koneksi ke database gagal. Pastikan database MySQL telah di-import.';
        }
    }
}

$page_title = "Pendaftaran Pelapor - SIPENSO";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card card-custom border-0 shadow-lg overflow-hidden">
                <div class="card-header text-white p-4 p-md-5 text-center border-0 position-relative" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #1d4ed8 100%);">
                    <div class="position-absolute top-0 end-0 opacity-10 p-3">
                        <i class="fa-solid fa-user-shield fa-6x text-white"></i>
                    </div>
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 65px; height: 65px; background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); border: 3px solid rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-user-plus fa-xl text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-white">Registrasi Akun Pelapor</h4>
                    <p class="text-white-50 small mb-0">Daftar untuk mengajukan &amp; memantau pengaduan ke Dinas Sosial</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">NIK (16 Digit Angka) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-card text-primary"></i></span>
                                    <input type="text" name="nik" maxlength="16" class="form-control bg-light border-start-0 ps-0 font-monospace" placeholder="Contoh: 3171010101900005" value="<?= sanitize($_POST['nik'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-primary"></i></span>
                                    <input type="text" name="nama_lengkap" class="form-control bg-light border-start-0 ps-0" placeholder="Nama sesuai KTP" value="<?= sanitize($_POST['nama_lengkap'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-primary"></i></span>
                                    <input type="email" name="email" class="form-control bg-light border-start-0 ps-0" placeholder="nama@email.com" value="<?= sanitize($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">No HP / Whatsapp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-primary"></i></span>
                                    <input type="text" name="no_hp" class="form-control bg-light border-start-0 ps-0" placeholder="Contoh: 08123456789" value="<?= sanitize($_POST['no_hp'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-semibold">Alamat Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-map-location-dot text-primary"></i></span>
                                <textarea name="alamat" rows="2" class="form-control bg-light border-start-0 ps-0" placeholder="Jl. Mawar RT 01 / RW 02 Kel. Melati..."><?= sanitize($_POST['alamat'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-at text-primary"></i></span>
                                    <input type="text" name="username" class="form-control bg-light border-start-0 ps-0" placeholder="Username login" value="<?= sanitize($_POST['username'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Kata Sandi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-primary"></i></span>
                                    <input type="password" name="password" class="form-control bg-light border-start-0 ps-0" placeholder="Min. 6 karakter" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Konfirmasi Sandi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-check-double text-success"></i></span>
                                    <input type="password" name="confirm_password" class="form-control bg-light border-start-0 ps-0" placeholder="Ulangi kata sandi" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm mt-3">
                            <i class="fa-solid fa-paper-plane me-2"></i> Daftar Akun Pelapor
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <p class="mb-0 text-muted small">Sudah memiliki akun? 
                            <a href="<?= base_url('login.php'); ?>" class="fw-bold text-primary text-decoration-none">Masuk ke Akun Anda</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
