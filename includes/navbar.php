<?php
$role = $_SESSION['role'] ?? null;
$nama_user = $_SESSION['nama_lengkap'] ?? null;
$foto_profil = $_SESSION['foto_profil'] ?? null;

// Fallback check foto_profil from DB if not set in session
if (is_logged_in() && empty($foto_profil) && isset($pdo)) {
    $stmtAvatar = $pdo->prepare("SELECT foto_profil FROM users WHERE id = :uid LIMIT 1");
    $stmtAvatar->execute(['uid' => $_SESSION['user_id']]);
    $foto_profil = $stmtAvatar->fetchColumn();
    $_SESSION['foto_profil'] = $foto_profil;
}
$avatar_url = (!empty($foto_profil) && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $foto_profil))
    ? base_url('assets/uploads/avatars/' . $foto_profil)
    : null;
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
    <div class="container-fluid px-lg-4">
        <div class="d-flex align-items-center gap-2">
            <?php if (is_logged_in()): ?>
                <button id="sidebarToggle" class="btn btn-outline-light btn-sm me-2 rounded-2" type="button" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars fa-lg"></i>
                </button>
            <?php endif; ?>
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url(); ?>">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-hand-holding-heart fa-lg"></i>
                </div>
                <div>
                    <span class="brand-title fs-5 d-block leading-tight">SIPENSO</span>
                    <span class="text-xs text-slate-400 d-block" style="font-size: 0.68rem; margin-top: -4px;">Dinas Sosial Layanan Pengaduan</span>
                </div>
            </a>
        </div>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSIPENSO">
            <span class="navbar-toggler-icon"></span>
        </button>


            <div class="d-flex align-items-center gap-2">
                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <button class="btn btn-navbar-user dropdown-toggle-no-caret d-flex align-items-center gap-2 rounded-pill px-2 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($avatar_url): ?>
                                <img src="<?= $avatar_url; ?>" alt="Avatar" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid rgba(255,255,255,0.6);">
                            <?php else: ?>
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem; background: linear-gradient(135deg, #3b82f6, #6366f1); border: 2px solid rgba(255,255,255,0.6);">
                                    <?= strtoupper(substr($nama_user, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="badge bg-primary text-uppercase d-none d-sm-inline-block" style="font-size: 0.62rem; letter-spacing: 0.04em;"><?= $role; ?></span>
                            <i class="fa-solid fa-chevron-down text-white-50" style="font-size: 0.7rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 mt-2 p-0 overflow-hidden border-0" style="min-width: 220px;">
                            <li class="px-3 py-3" style="background: linear-gradient(135deg, #090d16, #1e1b4b);">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($avatar_url): ?>
                                        <img src="<?= $avatar_url; ?>" class="rounded-circle flex-shrink-0" style="width: 42px; height: 42px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);" alt="">
                                    <?php else: ?>
                                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.1rem; background: linear-gradient(135deg, #3b82f6, #6366f1);">
                                            <?= strtoupper(substr($nama_user, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-white text-truncate small"><?= sanitize($nama_user); ?></div>
                                        <span class="badge bg-primary text-uppercase" style="font-size: 0.6rem;"><?= $role; ?></span>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 px-3" href="<?= base_url('profile.php'); ?>">
                                    <i class="fa-solid fa-user-gear me-2 text-primary"></i> Profil Saya
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 px-3 text-danger" href="<?= base_url('logout.php'); ?>">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
