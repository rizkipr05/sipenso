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
<nav class="navbar navbar-expand-lg navbar-light navbar-custom sticky-top">
    <div class="container-fluid px-lg-4 d-flex align-items-center">
        
        <?php if (is_logged_in()): ?>
            <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm d-lg-none me-3 rounded-2" type="button" title="Toggle Sidebar">
                <i class="fa-solid fa-bars fa-lg"></i>
            </button>
        <?php endif; ?>

        <!-- System identity, positioned immediately to the right of the sidebar -->
        <div class="d-flex flex-column justify-content-center flex-grow-1 me-3 d-none d-md-flex">
            <h5 class="fw-bolder text-dark mb-0 lh-sm" style="font-size: 1.05rem; letter-spacing: -0.2px;">SISTEM INFORMASI KLASIFIKASI DAN PRIORITAS<br>PENANGANAN PENGADUAN MASYARAKAT</h5>
            <small class="fw-bold text-primary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">DINAS SOSIAL KABUPATEN LABUHANBATU</small>
        </div>
        
        <!-- Right Section: Search, Bell, Profile -->
        <div class="d-flex align-items-center ms-auto gap-3">
            
            <!-- Search Bar -->
            <div class="input-group d-none d-lg-flex" style="width: 250px;">
                <input type="text" class="form-control" placeholder="Cari pengaduan, NIK, nama..." aria-label="Search" style="border-radius: 20px 0 0 20px; border-right: none; font-size: 0.85rem; padding: 10px 15px;">
                <span class="input-group-text bg-white" style="border-radius: 0 20px 20px 0; border-left: none; cursor: pointer;">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
            </div>
            
            <!-- Notification Bell -->
            <a href="#" class="position-relative text-muted ms-1 me-2 d-none d-sm-block">
                <i class="fa-solid fa-bell fs-5"></i>
                <span class="position-absolute pt-1 start-100 translate-middle p-1 bg-danger border border-white rounded-circle" style="top: 2px;">
                    <span class="visually-hidden">New alerts</span>
                </span>
            </a>

            <!-- User Dropdown -->
            <?php if (is_logged_in()): ?>
                <div class="dropdown">
                    <button class="btn btn-navbar-user dropdown-toggle-no-caret d-flex align-items-center gap-2 rounded-pill px-2 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:none !important; background:transparent !important;">
                        
                        <!-- Avatar -->
                        <?php if ($avatar_url): ?>
                            <img src="<?= $avatar_url; ?>" alt="Avatar" class="rounded-circle shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-sm" style="width: 38px; height: 38px; font-size: 0.95rem; background: linear-gradient(135deg, #3b82f6, #6366f1);">
                                <?= strtoupper(substr($nama_user, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Text Name & Role -->
                        <div class="text-start d-none d-sm-block lh-1 ms-1 me-2">
                            <span class="d-block fw-bold text-dark" style="font-size: 0.85rem;"><?= sanitize($nama_user); ?></span>
                            <span class="text-muted d-block mt-1" style="font-size: 0.7rem;"><?= ($role == 'admin') ? 'Administrator' : ucfirst((string)$role); ?> <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.6rem;"></i></span>
                        </div>
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 mt-2 p-2 border-0" style="min-width: 200px;">
                        <li>
                            <a class="dropdown-item py-2 px-3 rounded-2" href="<?= base_url('profile.php'); ?>">
                                <i class="fa-solid fa-user-gear me-2 text-primary"></i> Profil Saya
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item py-2 px-3 text-danger rounded-2" href="<?= base_url('logout.php'); ?>">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= base_url('login.php'); ?>" class="btn btn-primary rounded-pill px-4 text-sm fw-bold">Login</a>
            <?php endif; ?>
            
        </div>
    </div>
</nav>
