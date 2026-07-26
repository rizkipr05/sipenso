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
?>

<style>
    .auth-bg {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
    }
    .auth-card {
        background: #ffffff;
        border-radius: 1.5rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255,255,255,0.8);
        overflow: hidden;
        width: 100%;
        max-width: 750px;
        position: relative;
    }
    .auth-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 6px;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    }
    .auth-header {
        padding: 2.5rem 3rem 1.5rem;
        text-align: center;
    }
    .auth-icon-wrapper {
        width: 72px; height: 72px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #3b82f6;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 1.25rem;
        box-shadow: inset 0 2px 4px rgba(255,255,255,0.8), 0 4px 12px rgba(59,130,246,0.15);
    }
    .auth-body {
        padding: 0 3rem 3rem;
    }
    .modern-form-group {
        position: relative;
        margin-bottom: 1.25rem;
    }
    .modern-form-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.5rem;
        display: block;
        letter-spacing: 0.02em;
    }
    .modern-form-group i {
        position: absolute;
        left: 1.25rem;
        bottom: 1.15rem;
        color: #94a3b8;
        font-size: 1.1rem;
        transition: color 0.3s ease;
        z-index: 2;
    }
    .modern-form-control {
        width: 100%;
        padding: 1.1rem 1rem 1.1rem 3.25rem;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        font-size: 0.95rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    textarea.modern-form-control {
        padding-top: 1.1rem;
        padding-bottom: 1.1rem;
    }
    .modern-form-group.textarea-group i {
        top: 2.75rem;
        bottom: auto;
    }
    .modern-form-control:focus {
        border-color: #3b82f6;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        outline: none;
    }
    .modern-form-control:focus + i, .modern-form-control:not(:placeholder-shown) + i {
        color: #3b82f6;
    }
    .btn-auth {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #fff;
        border: none;
        border-radius: 1rem;
        padding: 1.15rem;
        font-weight: 700;
        font-size: 1.05rem;
        width: 100%;
        box-shadow: 0 10px 25px -5px rgba(59,130,246,0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        margin-top: 1rem;
    }
    .btn-auth:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -5px rgba(59,130,246,0.5);
        color: #fff;
    }
    .auth-footer-text {
        text-align: center;
        margin-top: 2rem;
        color: #64748b;
        font-size: 0.95rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
        font-weight: 500;
    }
    .auth-footer-text a {
        color: #3b82f6;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s ease;
        margin-left: 0.25rem;
    }
    .auth-footer-text a:hover { color: #1d4ed8; }
</style>

<div class="auth-bg">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon-wrapper">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h3 class="fw-bold mb-1" style="color: #0f172a; letter-spacing: -0.5px;">Registrasi Akun</h3>
            <p class="text-muted small mb-0">Daftar untuk mengajukan &amp; memantau pengaduan ke Dinas Sosial</p>
        </div>
        
        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" style="background:#fef2f2; color:#991b1b; padding-left: 3.5rem; position:relative;" role="alert">
                    <i class="fa-solid fa-circle-exclamation position-absolute" style="left:1.25rem; top:1rem; font-size:1.25rem;"></i>
                    <span class="small fw-semibold"><?= $error; ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="modern-form-group">
                            <label class="modern-form-label">NIK (16 Digit Angka) <span class="text-danger">*</span></label>
                            <input type="text" name="nik" maxlength="16" class="modern-form-control font-monospace" placeholder="Contoh: 3171010101900005" value="<?= sanitize($_POST['nik'] ?? ''); ?>" required>
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="modern-form-group">
                            <label class="modern-form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="modern-form-control" placeholder="Nama sesuai KTP" value="<?= sanitize($_POST['nama_lengkap'] ?? ''); ?>" required>
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="modern-form-group">
                            <label class="modern-form-label">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="modern-form-control" placeholder="nama@email.com" value="<?= sanitize($_POST['email'] ?? ''); ?>" required>
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="modern-form-group">
                            <label class="modern-form-label">No HP / Whatsapp <span class="text-danger">*</span></label>
                            <input type="text" name="no_hp" class="modern-form-control" placeholder="Contoh: 08123456789" value="<?= sanitize($_POST['no_hp'] ?? ''); ?>" required>
                            <i class="fa-solid fa-phone"></i>
                        </div>
                    </div>
                </div>

                <div class="modern-form-group textarea-group">
                    <label class="modern-form-label">Alamat Lengkap</label>
                    <textarea name="alamat" rows="2" class="modern-form-control" placeholder="Jl. Mawar RT 01 / RW 02 Kel. Melati..."><?= sanitize($_POST['alamat'] ?? ''); ?></textarea>
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="modern-form-group">
                            <label class="modern-form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="modern-form-control" placeholder="Username login" value="<?= sanitize($_POST['username'] ?? ''); ?>" required>
                            <i class="fa-solid fa-at"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modern-form-group">
                            <label class="modern-form-label">Kata Sandi <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="modern-form-control" placeholder="Min. 6 karakter" required>
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modern-form-group">
                            <label class="modern-form-label">Konfirmasi Sandi <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="modern-form-control" placeholder="Ulangi kata sandi" required>
                            <i class="fa-solid fa-check-double" style="color: #10b981;"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-auth d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Daftar Akun Sekarang
                </button>
            </form>

            <div class="auth-footer-text">
                Sudah memiliki akun? <a href="<?= base_url('login.php'); ?>">Masuk ke Akun Anda</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
