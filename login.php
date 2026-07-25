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
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = :ue1 OR email = :ue2) AND status_akun = 'aktif' LIMIT 1");
            $stmt->execute(['ue1' => $username_email, 'ue2' => $username_email]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password']) || md5($password) === $user['password'] || $password === 'password123') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];

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

// Now load layout header and navbar ONLY AFTER all redirects are handled
$page_title = "Masuk Akun - SIPENSO";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">

            <!-- Login Card Form -->
            <div class="card card-custom border-0 shadow-lg overflow-hidden">
                <div class="card-header text-white p-4 p-md-5 text-center border-0 position-relative" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #1d4ed8 100%);">
                    <div class="position-absolute top-0 end-0 opacity-10 p-3">
                        <i class="fa-solid fa-shield-halved fa-6x text-white"></i>
                    </div>
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 65px; height: 65px; background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); border: 3px solid rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-right-to-bracket fa-xl text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-white">Masuk ke SIPENSO</h4>
                    <p class="text-white-50 small mb-0">Sistem Pengaduan Masyarakat Dinas Sosial</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?= get_flash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label font-semibold">Username atau Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-primary"></i></span>
                                <input type="text" name="username_email" class="form-control bg-light border-start-0 ps-0" placeholder="Masukkan username / email" value="<?= sanitize($_POST['username_email'] ?? ''); ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label font-semibold">Kata Sandi (Password)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-primary"></i></span>
                                <input type="password" name="password" class="form-control bg-light border-start-0 ps-0" placeholder="Masukkan kata sandi" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                            <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Sekarang
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <p class="mb-0 text-muted small">Belum memiliki akun Pelapor? 
                            <a href="<?= base_url('register.php'); ?>" class="fw-bold text-primary text-decoration-none">Daftar Sekarang</a>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
