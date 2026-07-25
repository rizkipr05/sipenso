<?php
$role = $_SESSION['role'] ?? null;
$nama_user = $_SESSION['nama_lengkap'] ?? null;
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
                        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle fs-5"></i>
                            <span><?= sanitize($nama_user); ?></span>
                            <span class="badge bg-primary text-uppercase" style="font-size: 0.65rem;"><?= $role; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 mt-2">
                            <li>
                                <span class="dropdown-header">Akses: <?= strtoupper($role); ?></span>
                            </li>
                            <li><a class="dropdown-item" href="<?= base_url('profile.php'); ?>"><i class="fa-solid fa-user-gear me-2 text-secondary"></i> Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= base_url('logout.php'); ?>"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('login.php'); ?>" class="btn btn-outline-light rounded-pill px-4 me-2">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Masuk
                    </a>
                    <a href="<?= base_url('register.php'); ?>" class="btn btn-primary rounded-pill px-4">
                        <i class="fa-solid fa-user-plus me-1"></i> Daftar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
