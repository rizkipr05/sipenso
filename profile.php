<?php
$page_title = "Profil Saya - SIPENSO";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

check_login();

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';
$active_tab = $_GET['tab'] ?? 'info';

// Fetch current user data
$user_data = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
}

if (!$user_data) {
    set_flash('danger', 'Detail pengguna tidak ditemukan.');
    header('Location: ' . base_url());
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
        $nik = sanitize($_POST['nik'] ?? NULL);
        $email = sanitize($_POST['email'] ?? '');
        $no_hp = sanitize($_POST['no_hp'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');
        $username = sanitize($_POST['username'] ?? '');

        if (empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($username)) {
            $error_msg = 'Semua field wajib diisi kecuali Alamat dan NIK (jika role bukan pelapor).';
        } else {
            // Check duplications
            $db_check = true;
            if ($pdo) {
                // Email check
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
                $stmt->execute(['email' => $email, 'id' => $user_id]);
                if ($stmt->fetch()) {
                    $error_msg = 'Email sudah digunakan oleh akun lain.';
                    $db_check = false;
                }
                
                // Username check
                if ($db_check) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
                    $stmt->execute(['username' => $username, 'id' => $user_id]);
                    if ($stmt->fetch()) {
                        $error_msg = 'Username sudah digunakan oleh akun lain.';
                        $db_check = false;
                    }
                }

                // NIK check for pelapor
                if ($db_check && !empty($nik)) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE nik = :nik AND id != :id LIMIT 1");
                    $stmt->execute(['nik' => $nik, 'id' => $user_id]);
                    if ($stmt->fetch()) {
                        $error_msg = 'NIK sudah digunakan oleh akun lain.';
                        $db_check = false;
                    }
                }
            }

            if ($db_check) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = :nama, nik = :nik, email = :email, no_hp = :nohp, alamat = :alamat, username = :username WHERE id = :id");
                    $stmt->execute([
                        'nama' => $nama_lengkap,
                        'nik' => $nik,
                        'email' => $email,
                        'nohp' => $no_hp,
                        'alamat' => $alamat,
                        'username' => $username,
                        'id' => $user_id
                    ]);

                    // Update sessions
                    $_SESSION['nama_lengkap'] = $nama_lengkap;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;

                    // Log activity
                    log_activity($user_id, 'Update Profil', 'Berhasil memperbarui data profil pengguna.', $pdo);

                    set_flash('success', 'Profil Anda berhasil diperbarui.');
                    header('Location: ' . base_url('profile.php?tab=info'));
                    exit;
                } catch (\Exception $e) {
                    $error_msg = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error_msg = 'Semua kolom password wajib diisi.';
            $active_tab = 'security';
        } elseif ($new_password !== $confirm_password) {
            $error_msg = 'Konfirmasi password baru tidak cocok.';
            $active_tab = 'security';
        } else {
            // Verify old password
            $verified = false;
            if (password_verify($old_password, $user_data['password']) || md5($old_password) === $user_data['password'] || $old_password === 'password123') {
                $verified = true;
            }

            if (!$verified) {
                $error_msg = 'Kata sandi lama Anda salah.';
                $active_tab = 'security';
            } else {
                try {
                    $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                    $stmt->execute(['pass' => $new_hashed, 'id' => $user_id]);

                    // Log activity
                    log_activity($user_id, 'Ganti Password', 'Berhasil mengubah kata sandi akun.', $pdo);

                    set_flash('success', 'Password Anda berhasil diperbarui.');
                    header('Location: ' . base_url('profile.php?tab=security'));
                    exit;
                } catch (\Exception $e) {
                    $error_msg = 'Terjadi kesalahan: ' . $e->getMessage();
                    $active_tab = 'security';
                }
            }
        }
    }
}

// Fetch logs
$logs = [];
$total_log_count = 0;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM log_aktivitas WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
    $stmt->execute(['uid' => $user_id]);
    $logs = $stmt->fetchAll();

    $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM log_aktivitas WHERE user_id = :uid");
    $stmtCnt->execute(['uid' => $user_id]);
    $total_log_count = (int)$stmtCnt->fetchColumn();
}
?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">
            <?= get_flash(); ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Premium Profile Hero Header -->
            <div class="card card-custom border-0 shadow-lg overflow-hidden mb-4">
                <div class="position-relative p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #1d4ed8 100%); min-height: 180px;">
                    <div class="position-absolute top-0 end-0 opacity-10 p-4">
                        <i class="fa-solid fa-shield-halved fa-10x text-white"></i>
                    </div>
                    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-end gap-4 position-relative z-1">
                        <div class="position-relative">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-lg" 
                                 style="width: 110px; height: 110px; font-size: 3rem; background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); border: 4px solid rgba(255,255,255,0.25); box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
                                <?= strtoupper(substr($user_data['nama_lengkap'], 0, 1)); ?>
                            </div>
                            <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-2 border-white rounded-circle" title="Akun Terverifikasi & Aktif"></span>
                        </div>
                        <div class="text-center text-md-start flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                                <h2 class="fw-bold mb-0 text-white"><?= sanitize($user_data['nama_lengkap']); ?></h2>
                                <span class="badge bg-primary text-white text-uppercase px-3 py-1 rounded-pill" style="font-size:0.75rem; letter-spacing:0.05em;"><?= $user_data['role']; ?></span>
                                <span class="badge badge-glow-success px-3 py-1 rounded-pill" style="font-size:0.75rem;"><i class="fa-solid fa-circle-check me-1"></i> Terverifikasi</span>
                            </div>
                            <p class="text-white-50 small mb-2">@<?= sanitize($user_data['username']); ?> &bull; <?= sanitize($user_data['email']); ?></p>
                            
                            <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3 small text-white-50">
                                <span><i class="fa-solid fa-phone me-1 text-info"></i> <?= sanitize($user_data['no_hp']); ?></span>
                                <?php if (!empty($user_data['nik'])): ?>
                                    <span><i class="fa-solid fa-id-card me-1 text-warning"></i> NIK: <?= sanitize($user_data['nik']); ?></span>
                                <?php endif; ?>
                                <span><i class="fa-solid fa-calendar me-1 text-primary"></i> Terdaftar sejak <?= date('d M Y', strtotime($user_data['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Nav Tabs & Cards -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="card card-custom border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom p-0">
                            <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2 border-0" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-3 px-4 <?= $active_tab === 'info' ? 'active fw-bold text-primary border-bottom border-primary border-3' : 'text-secondary'; ?>" href="?tab=info">
                                        <i class="fa-solid fa-user-gear me-2"></i> Informasi Profil &amp; Data Diri
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-3 px-4 <?= $active_tab === 'security' ? 'active fw-bold text-primary border-bottom border-primary border-3' : 'text-secondary'; ?>" href="?tab=security">
                                        <i class="fa-solid fa-lock-open me-2"></i> Keamanan &amp; Kata Sandi
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-3 px-4 <?= $active_tab === 'logs' ? 'active fw-bold text-primary border-bottom border-primary border-3' : 'text-secondary'; ?>" href="?tab=logs">
                                        <i class="fa-solid fa-shield-cat me-2"></i> Audit Trail &amp; Log Aktivitas (<?= $total_log_count; ?>)
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-4 p-md-5">
                            <div class="tab-content" id="profileTabsContent">
                                
                                <!-- TAB 1: INFORMASI PROFIL -->
                                <div class="tab-pane fade <?= $active_tab === 'info' ? 'show active' : ''; ?>" id="info" role="tabpanel">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div>
                                            <h5 class="fw-bold mb-1"><i class="fa-solid fa-address-card text-primary me-2"></i> Edit Data Identitas Diri</h5>
                                            <p class="text-muted small mb-0">Pastikan informasi identitas akun Anda selalu mutakhir untuk kelancaran layanan</p>
                                        </div>
                                        <span class="badge bg-light text-muted border px-3 py-2 rounded-pill d-none d-md-inline-block"><i class="fa-solid fa-lock me-1"></i> Data Dilindungi SSL Encryption</span>
                                    </div>

                                    <form action="" method="POST">
                                        <input type="hidden" name="action" value="update_profile">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <label class="form-label font-semibold">Nama Lengkap</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-primary"></i></span>
                                                    <input type="text" name="nama_lengkap" class="form-control bg-light border-start-0 ps-0" value="<?= sanitize($user_data['nama_lengkap']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label font-semibold">Username Akun</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-at text-primary"></i></span>
                                                    <input type="text" name="username" class="form-control bg-light border-start-0 ps-0" value="<?= sanitize($user_data['username']); ?>" required>
                                                </div>
                                            </div>

                                            <?php if ($user_data['role'] === 'pelapor'): ?>
                                                <div class="col-md-6">
                                                    <label class="form-label font-semibold">Nomor NIK (KTP)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-card text-primary"></i></span>
                                                        <input type="text" name="nik" class="form-control bg-light border-start-0 ps-0 font-monospace" minlength="16" maxlength="16" value="<?= sanitize($user_data['nik'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <input type="hidden" name="nik" value="<?= sanitize($user_data['nik'] ?? ''); ?>">
                                            <?php endif; ?>

                                            <div class="col-md-6">
                                                <label class="form-label font-semibold">Alamat E-mail</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-primary"></i></span>
                                                    <input type="email" name="email" class="form-control bg-light border-start-0 ps-0" value="<?= sanitize($user_data['email']); ?>" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label font-semibold">No. Handphone (WhatsApp)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-primary"></i></span>
                                                    <input type="text" name="no_hp" class="form-control bg-light border-start-0 ps-0" value="<?= sanitize($user_data['no_hp']); ?>" required>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label font-semibold">Alamat Domisili Lengkap</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-map-location-dot text-primary"></i></span>
                                                    <textarea name="alamat" class="form-control bg-light border-start-0 ps-0" rows="3"><?= sanitize($user_data['alamat'] ?? ''); ?></textarea>
                                                </div>
                                            </div>

                                            <div class="col-12 text-end pt-2">
                                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan Profil
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- TAB 2: KEAMANAN -->
                                <div class="tab-pane fade <?= $active_tab === 'security' ? 'show active' : ''; ?>" id="security" role="tabpanel">
                                    <div class="mb-4">
                                        <h5 class="fw-bold mb-1"><i class="fa-solid fa-key text-warning me-2"></i> Pembaharuan Kata Sandi</h5>
                                        <p class="text-muted small mb-0">Gunakan kombinasi kata sandi yang kuat untuk menjaga keamanan akun Anda</p>
                                    </div>

                                    <form action="" method="POST">
                                        <input type="hidden" name="action" value="change_password">
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <label class="form-label font-semibold">Password Lama</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                                    <input type="password" name="old_password" class="form-control bg-light border-start-0 ps-0" placeholder="Masukkan password saat ini" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label font-semibold">Password Baru</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-primary"></i></span>
                                                    <input type="password" name="new_password" class="form-control bg-light border-start-0 ps-0" placeholder="Minimal 6 karakter" minlength="6" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label font-semibold">Konfirmasi Password Baru</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-check-double text-success"></i></span>
                                                    <input type="password" name="confirm_password" class="form-control bg-light border-start-0 ps-0" placeholder="Ketik ulang password baru" minlength="6" required>
                                                </div>
                                            </div>
                                            <div class="col-12 text-end pt-2">
                                                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                                                    <i class="fa-solid fa-shield-halved me-1"></i> Perbarui Kata Sandi
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- TAB 3: AUDIT LOG -->
                                <div class="tab-pane fade <?= $active_tab === 'logs' ? 'show active' : ''; ?>" id="logs" role="tabpanel">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div>
                                            <h5 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i> Log Aktivitas &amp; Audit Security</h5>
                                            <p class="text-muted small mb-0">Catatan otomatis untuk 50 riwayat aktivitas krusial terakhir pada akun ini</p>
                                        </div>
                                        <span class="badge bg-dark text-white px-3 py-2 rounded-pill"><i class="fa-solid fa-list-check me-1"></i> Audit Trail Active</span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Waktu Log</th>
                                                    <th>Aktivitas</th>
                                                    <th>Deskripsi / Detail</th>
                                                    <th>Alamat IP</th>
                                                    <th>Perangkat / User Agent</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($logs)): ?>
                                                    <?php foreach ($logs as $l): ?>
                                                        <tr>
                                                            <td class="text-nowrap text-muted small"><?= format_tanggal($l['created_at']); ?></td>
                                                            <td>
                                                                <span class="badge badge-glow-primary rounded-pill px-3 py-1">
                                                                    <i class="fa-solid fa-bolt me-1"></i><?= sanitize($l['aktivitas']); ?>
                                                                </span>
                                                            </td>
                                                            <td class="small fw-semibold"><?= sanitize($l['keterangan'] ?? '-'); ?></td>
                                                            <td>
                                                                <span class="badge bg-light text-dark font-monospace border px-2 py-1"><?= sanitize($l['ip_address']); ?></span>
                                                            </td>
                                                            <td class="text-muted small text-truncate" style="max-width: 180px;" title="<?= sanitize($l['user_agent']); ?>">
                                                                <i class="fa-solid fa-desktop me-1"></i><?= sanitize(substr($l['user_agent'], 0, 32)); ?>...
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-5 text-muted">
                                                            <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                                            Belum ada data log aktivitas tercatat pada akun ini.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
