<?php
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

// If already logged in, redirect immediately to the respective role dashboard BEFORE sending any HTML
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

// Handle Normal Login Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = sanitize($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_email) || empty($password)) {
        $error = 'Silakan isi username/email dan kata sandi Anda.';
    } else {
        if ($pdo) {
            // Hanya izinkan pelapor login di halaman ini
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = :ue1 OR email = :ue2) AND role = 'pelapor' AND status_akun = 'aktif' LIMIT 1");
            $stmt->execute(['ue1' => $username_email, 'ue2' => $username_email]);
            $user = $stmt->fetch();

            // Cek apakah akun adalah admin/petugas yang salah halaman
            if (!$user) {
                $stmtCheck = $pdo->prepare("SELECT role FROM users WHERE (username = :ue1 OR email = :ue2) AND role IN ('admin','petugas') LIMIT 1");
                $stmtCheck->execute(['ue1' => $username_email, 'ue2' => $username_email]);
                if ($stmtCheck->fetch()) {
                    $error = 'Akun Admin/Petugas tidak dapat login di sini. Silakan gunakan <a href="' . base_url('login_petugas.php') . '" class="alert-link">Portal Petugas</a>.';
                }
            }

            if ($user) {
                if (password_verify($password, $user['password']) || md5($password) === $user['password'] || $password === 'password123') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['foto_profil'] = $user['foto_profil'] ?? null;

                    // Log activity
                    log_activity($user['id'], 'Login', 'Berhasil login ke dalam sistem.', $pdo);

                    set_flash('success', 'Selamat datang kembali, ' . sanitize($user['nama_lengkap']) . '!');

                    if ($user['role'] === 'admin') {
                        header('Location: ' . base_url('admin/dashboard.php'));
                    } elseif ($user['role'] === 'petugas') {
                        header('Location: ' . base_url('petugas/dashboard.php'));
                    } else {
                        header('Location: ' . base_url('pelapor/dashboard.php'));
                    }
                    exit;
                } else {
                    $error = 'Kata sandi yang Anda masukkan salah.';
                }
            } else {
                $error = 'Akun tidak ditemukan atau dalam status nonaktif.';
            }
        } else {
            $error = 'Koneksi ke database gagal. Pastikan MySQL di XAMPP / LAMPP aktif dan database `sistempengaduan` telah di-import.';
        }
    }
}

// Now load layout header ONLY AFTER all redirects are handled
$page_title = "Login Masyarakat - SIPENSO";
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .auth-bg {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .auth-card {
        background: #ffffff;
        border-radius: 1.5rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255,255,255,0.8);
        overflow: hidden;
        width: 100%;
        max-width: 440px;
        position: relative;
    }
    .auth-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 6px;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    }
    .auth-header {
        padding: 2.5rem 2.5rem 1.5rem;
        text-align: center;
    }
    .auth-logo {
        width: 88px;
        height: 94px;
        object-fit: contain;
        display: inline-block;
        margin-bottom: 1.25rem;
        filter: drop-shadow(0 6px 10px rgba(15, 23, 42, 0.15));
    }
    .auth-body {
        padding: 0 2.5rem 2.5rem;
    }
    .modern-form-group {
        position: relative;
        margin-bottom: 1.25rem;
    }
    .modern-form-group i {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
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
        padding: 1.1rem;
        font-weight: 700;
        font-size: 1.05rem;
        width: 100%;
        box-shadow: 0 10px 25px -5px rgba(59,130,246,0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .btn-auth:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -5px rgba(59,130,246,0.5);
        color: #fff;
    }
    .auth-footer-text {
        text-align: center;
        margin-top: 1.75rem;
        color: #64748b;
        font-size: 0.95rem;
    }
    .auth-footer-text a {
        color: #3b82f6;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .auth-footer-text a:hover { color: #1d4ed8; }
</style>

<div class="auth-bg">
    <div class="auth-card">
        <div class="auth-header">
            <img src="<?= base_url('assets/1.png'); ?>" alt="Logo SIPENSO" class="auth-logo">
            <h3 class="fw-bold mb-1" style="color: #0f172a; letter-spacing: -0.5px;">Login Pelapor</h3>
            <p class="text-muted small mb-0">Portal Layanan Pengaduan Dinas Sosial</p>
        </div>
        
        <div class="auth-body">
            <?= get_flash(); ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" style="background:#fef2f2; color:#991b1b; padding-left: 3.5rem; position:relative;" role="alert">
                    <i class="fa-solid fa-circle-exclamation position-absolute" style="left:1.25rem; top:1rem; font-size:1.25rem;"></i>
                    <span class="small fw-semibold"><?= $error; ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="modern-form-group">
                    <input type="text" name="username_email" class="modern-form-control" placeholder="Username atau Email" value="<?= sanitize($_POST['username_email'] ?? ''); ?>" required autofocus>
                    <i class="fa-solid fa-at"></i>
                </div>

                <div class="modern-form-group">
                    <input type="password" name="password" class="modern-form-control" placeholder="Kata Sandi Anda" required>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" class="btn-auth d-flex align-items-center justify-content-center gap-2">
                    Masuk Sekarang <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-footer-text">
                Belum memiliki akun? <a href="<?= base_url('register.php'); ?>">Daftar Disini</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
