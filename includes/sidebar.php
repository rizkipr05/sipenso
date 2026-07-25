<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="overflow-hidden">
                <h6 class="mb-0 text-white font-semibold text-truncate" style="max-width: 170px;"><?= sanitize($_SESSION['nama_lengkap'] ?? 'User'); ?></h6>
                <small class="text-xs text-primary text-uppercase fw-bold"><?= strtoupper($role); ?></small>
            </div>
        </div>
    </div>

    <ul class="list-unstyled components">
        <?php if ($role === 'admin'): ?>
            <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/dashboard.php'); ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard Admin
                </a>
            </li>
            <li class="<?= $current_page == 'pengaduan.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/pengaduan.php'); ?>">
                    <i class="fa-solid fa-folder-open"></i> Kelola Pengaduan
                </a>
            </li>
            <li class="<?= $current_page == 'users.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/users.php'); ?>">
                    <i class="fa-solid fa-users-gear"></i> Kelola Akun User
                </a>
            </li>
            <li class="<?= $current_page == 'kategori.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/kategori.php'); ?>">
                    <i class="fa-solid fa-tags"></i> Kategori Pengaduan
                </a>
            </li>
            <li class="<?= $current_page == 'kriteria.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/kriteria.php'); ?>">
                    <i class="fa-solid fa-sliders"></i> Kriteria Prioritas
                </a>
            </li>
            <li class="<?= $current_page == 'laporan.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/laporan.php'); ?>">
                    <i class="fa-solid fa-file-invoice"></i> Rekap &amp; Cetak Laporan
                </a>
            </li>
        <?php elseif ($role === 'petugas'): ?>
            <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('petugas/dashboard.php'); ?>">
                    <i class="fa-solid fa-list-check"></i> Antrean Pengaduan
                </a>
            </li>
            <li class="<?= $current_page == 'laporan.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('admin/laporan.php'); ?>">
                    <i class="fa-solid fa-file-invoice"></i> Laporan Rekapitulasi
                </a>
            </li>
        <?php elseif ($role === 'pelapor'): ?>
            <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('pelapor/dashboard.php'); ?>">
                    <i class="fa-solid fa-gauge"></i> Dashboard Saya
                </a>
            </li>
            <li class="<?= $current_page == 'buat_pengaduan.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('pelapor/buat_pengaduan.php'); ?>">
                    <i class="fa-solid fa-paper-plane"></i> Buat Pengaduan
                </a>
            </li>
            <li class="<?= $current_page == 'riwayat.php' ? 'active' : ''; ?>">
                <a href="<?= base_url('pelapor/riwayat.php'); ?>">
                    <i class="fa-solid fa-clock-history"></i> Riwayat Pengaduan
                </a>
            </li>
        <?php endif; ?>

        <li class="mt-4 border-top border-secondary pt-2">
            <a href="<?= base_url('logout.php'); ?>" class="text-danger">
                <i class="fa-solid fa-right-from-bracket text-danger"></i> Keluar (Logout)
            </a>
        </li>
    </ul>
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
