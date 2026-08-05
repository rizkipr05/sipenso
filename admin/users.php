<?php
$page_title = "Kelola Akun User - SIPENSO";
require_once __DIR__ . '/../includes/header.php';

check_role(['admin']);

$error = '';
$success = '';

// Handle Add Petugas Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_petugas'])) {
    $nik          = sanitize($_POST['nik'] ?? '');
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email        = sanitize($_POST['email'] ?? '');
    $no_hp        = sanitize($_POST['no_hp'] ?? '');
    $username     = sanitize($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? 'password123';

    if (empty($nama_lengkap) || empty($email) || empty($username)) {
        $error = 'Harap isi semua kolom wajib.';
    } else {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $stmt->execute(['u' => $username, 'e' => $email]);
            if ($stmt->fetch()) {
                $error = 'Username atau email sudah digunakan.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtIns = $pdo->prepare("INSERT INTO users (nik, nama_lengkap, email, no_hp, username, password, role, status_akun) VALUES (:nik, :nama, :email, :hp, :u, :p, 'petugas', 'aktif')");
                $stmtIns->execute([
                    'nik'  => $nik,
                    'nama' => $nama_lengkap,
                    'email'=> $email,
                    'hp'   => $no_hp,
                    'u'    => $username,
                    'p'    => $hash
                ]);
                $new_u_id = $pdo->lastInsertId();
                log_activity($_SESSION['user_id'], 'Create User', 'Menambahkan akun petugas baru: ' . $nama_lengkap . ' (@' . $username . ')', $pdo);
                
                set_flash('success', 'Akun Petugas baru berhasil ditambahkan!');
                header('Location: ' . base_url('admin/users.php'));
                exit;
            }
        }
    }
}

// Handle Status Toggle / Reset Password
if (isset($_GET['toggle_status']) && (int)$_GET['toggle_status'] > 0) {
    $uid = (int)$_GET['toggle_status'];
    if ($pdo) {
        $stmt = $pdo->prepare("UPDATE users SET status_akun = IF(status_akun='aktif','nonaktif','aktif') WHERE id = :uid AND role != 'admin'");
        $stmt->execute(['uid' => $uid]);
        log_activity($_SESSION['user_id'], 'Update Status User', 'Mengubah status keaktifan user ID ' . $uid, $pdo);
        set_flash('success', 'Status akun berhasil diperbarui.');
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }
}

// Handle Account Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_user'])) {
    $uid = (int)($_POST['user_id'] ?? 0);

    if ($uid <= 0) {
        set_flash('danger', 'ID akun tidak valid.');
    } elseif ($uid === (int)$_SESSION['user_id']) {
        set_flash('danger', 'Akun administrator yang sedang digunakan tidak dapat dihapus.');
    } elseif ($pdo) {
        // Administrator accounts are protected from deletion.
        // Related complaints, responses, and activity logs follow the
        // database foreign-key rules when a user is removed.
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :uid AND role != 'admin'");
        $stmt->execute(['uid' => $uid]);

        if ($stmt->rowCount() > 0) {
            log_activity($_SESSION['user_id'], 'Delete User', 'Menghapus akun user ID ' . $uid, $pdo);
            set_flash('success', 'Akun pengguna berhasil dihapus.');
        } else {
            set_flash('danger', 'Akun tidak ditemukan atau merupakan akun administrator.');
        }
    }

    header('Location: ' . base_url('admin/users.php'));
    exit;
}

if (isset($_GET['reset_pwd']) && (int)$_GET['reset_pwd'] > 0) {
    $uid = (int)$_GET['reset_pwd'];
    if ($pdo) {
        $hash = password_hash('password123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :uid");
        $stmt->execute(['p' => $hash, 'uid' => $uid]);
        log_activity($_SESSION['user_id'], 'Reset Password User', 'Mereset sandi user ID ' . $uid . ' ke default', $pdo);
        set_flash('success', 'Kata sandi user telah di-reset menjadi `password123`.');
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }
}

// List users
$role_filter = sanitize($_GET['role'] ?? '');
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
if (!empty($role_filter)) {
    $query .= " AND role = :r";
    $params['r'] = $role_filter;
}
$query .= " ORDER BY role ASC, created_at DESC";

$users_list = [];
if ($pdo) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users_list = $stmt->fetchAll();
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">
            <?= get_flash(); ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-users-gear text-primary me-2"></i> Manajemen Akun Pengguna</h3>
                    <p class="text-muted small mb-0">Kelola hak akses dan status akun Pelapor &amp; Petugas Dinas Sosial</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahPetugas">
                    <i class="fa-solid fa-user-plus me-2"></i> Tambah Akun Petugas Baru
                </button>
            </div>

            <!-- Role Filter Bar -->
            <div class="mb-3 d-flex gap-2">
                <a href="<?= base_url('admin/users.php'); ?>" class="btn btn-sm <?= empty($role_filter) ? 'btn-primary' : 'btn-light border'; ?> rounded-pill px-3">Semua Akun</a>
                <a href="<?= base_url('admin/users.php?role=petugas'); ?>" class="btn btn-sm <?= $role_filter == 'petugas' ? 'btn-primary' : 'btn-light border'; ?> rounded-pill px-3">Petugas</a>
                <a href="<?= base_url('admin/users.php?role=pelapor'); ?>" class="btn btn-sm <?= $role_filter == 'pelapor' ? 'btn-primary' : 'btn-light border'; ?> rounded-pill px-3">Masyarakat / Pelapor</a>
                <a href="<?= base_url('admin/users.php?role=admin'); ?>" class="btn btn-sm <?= $role_filter == 'admin' ? 'btn-primary' : 'btn-light border'; ?> rounded-pill px-3">Administrator</a>
            </div>

            <!-- Data Table -->
            <div class="card card-custom border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Lengkap &amp; NIK</th>
                                    <th>Email / No. HP</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status Akun</th>
                                    <th class="text-center">Aksi Administrator</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users_list)): ?>
                                    <?php foreach ($users_list as $idx => $u): ?>
                                        <tr>
                                            <td class="ps-4 text-muted"><?= $idx + 1; ?></td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?= sanitize($u['nama_lengkap']); ?></span>
                                                <small class="text-muted">NIK: <?= $u['nik'] ? sanitize($u['nik']) : '-'; ?></small>
                                            </td>
                                            <td>
                                                <span class="d-block small"><?= sanitize($u['email']); ?></span>
                                                <small class="text-muted"><?= sanitize($u['no_hp']); ?></small>
                                            </td>
                                            <td><code><?= sanitize($u['username']); ?></code></td>
                                            <td>
                                                <?php if ($u['role'] == 'admin'): ?>
                                                    <span class="badge bg-danger">ADMIN</span>
                                                <?php elseif ($u['role'] == 'petugas'): ?>
                                                    <span class="badge bg-primary">PETUGAS</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">PELAPOR</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($u['status_akun'] == 'aktif'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 rounded-pill">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($u['role'] != 'admin'): ?>
                                                    <a href="<?= base_url('admin/users.php?toggle_status=' . $u['id']); ?>" class="btn btn-sm btn-outline-warning rounded-pill me-1" onclick="return confirm('Ubah status akun ini?');">
                                                        <i class="fa-solid fa-power-off me-1"></i> Toggle Status
                                                    </a>
                                                    <a href="<?= base_url('admin/users.php?reset_pwd=' . $u['id']); ?>" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="return confirm('Reset password akun ini menjadi password123?');">
                                                        <i class="fa-solid fa-key me-1"></i> Reset Password
                                                    </a>
                                                    <form action="<?= base_url('admin/users.php'); ?>" method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                                        <button type="submit" name="action_delete_user" class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="return confirm('Hapus akun <?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?>? Data pengaduan dan riwayat terkait dapat ikut terhapus secara permanen.');">
                                                            <i class="fa-solid fa-trash me-1"></i> Hapus
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">Utama</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Tambah Petugas Baru -->
<div class="modal fade" id="modalTambahPetugas" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Tambah Akun Petugas Dinas Sosial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Lengkap Petugas <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control bg-light" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">NIK (Opsional)</label>
                        <input type="text" name="nik" maxlength="16" class="form-control bg-light">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-semibold">No HP / WA <span class="text-danger">*</span></label>
                            <input type="text" name="no_hp" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-semibold">Username Login <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control bg-light" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-semibold">Kata Sandi Initial</label>
                            <input type="password" name="password" class="form-control bg-light" value="password123">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="action_add_petugas" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Akun Petugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
