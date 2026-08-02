<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<nav id="sidebar" class="d-flex flex-column">
    <div class="sidebar-header d-flex flex-column align-items-center pt-4 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="d-flex align-items-center gap-3 w-100 px-4 mb-2">
            <img src="<?= base_url('assets/1.png'); ?>" alt="Logo Dinas Sosial" class="sidebar-brand-logo">
            <div class="text-white fw-bold lh-sm flex-grow-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                DINAS SOSIAL<br>
                KAB. LABUHANBATU
            </div>
        </div>
    </div>

    <!-- Scrollable Menu -->
    <div class="overflow-auto flex-grow-1 sidebar-scroll">
        <ul class="list-unstyled components mt-3 mb-0">
            <?php if ($role === 'admin'): ?>
                <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('admin/dashboard.php'); ?>"><i class="fa-solid fa-house"></i> Dashboard</a>
                </li>
                <li class="<?= $current_page == 'pengaduan.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('admin/pengaduan.php'); ?>"><i class="fa-solid fa-folder-open"></i> Data Pengaduan</a>
                </li>
                <li class="<?= $current_page == 'kategori.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('admin/kategori.php'); ?>"><i class="fa-solid fa-layer-group"></i> Klasifikasi Pengaduan</a>
                </li>
                <li class="<?= $current_page == 'kriteria.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('admin/kriteria.php'); ?>"><i class="fa-solid fa-triangle-exclamation"></i> Prioritas Penanganan</a>
                </li>
                <li class="<?= $current_page == 'laporan.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('admin/laporan.php'); ?>"><i class="fa-solid fa-file-invoice"></i> Laporan</a>
                </li>
                <li class="<?= $current_page == 'users.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('admin/users.php'); ?>"><i class="fa-solid fa-user-shield"></i> User Management</a>
                </li>

            <?php elseif ($role === 'pelapor'): ?>
                <!-- Pelapor Specific Menu matching the stylistic approach -->
                <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('pelapor/dashboard.php'); ?>"><i class="fa-solid fa-house"></i> Dashboard</a>
                </li>
                <li class="<?= $current_page == 'buat_pengaduan.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('pelapor/buat_pengaduan.php'); ?>"><i class="fa-solid fa-plus-circle"></i> Buat Pengaduan</a>
                </li>
                <li class="<?= $current_page == 'riwayat.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('pelapor/riwayat.php'); ?>"><i class="fa-solid fa-clock-history"></i> Riwayat Pengaduan</a>
                </li>


            <?php elseif ($role === 'petugas'): ?>
                <!-- Petugas Specific Menu -->
                <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="<?= base_url('petugas/dashboard.php'); ?>"><i class="fa-solid fa-house"></i> Dashboard Petugas</a>
                </li>

            <?php endif; ?>

            <li class="mt-2 mb-3">
                <a href="<?= base_url('logout.php'); ?>" class="text-danger">
                    <i class="fa-solid fa-right-from-bracket text-pink-500"></i> <span style="color: #ec4899;">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Bottom Illustration & Text -->
    <div class="sidebar-footer px-4 pb-4 pt-3 text-center" style="border-top: 1px solid rgba(255,255,255,0.05); background: var(--sidebar-bg);">
        <div class="d-flex justify-content-center mb-2">
            <i class="fa-solid fa-building-columns text-primary opacity-50" style="font-size: 3rem;"></i>
        </div>
        <p class="text-white-50 fst-italic mb-0" style="font-size: 0.75rem;">
            "Melayani dengan Hati<br>Bersama untuk Masyarakat"
        </p>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }
});
</script>
