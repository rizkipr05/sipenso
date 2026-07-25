<?php
$page_title = "Dashboard Pelapor - SIPENSO";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

check_role(['pelapor']);

$user_id = $_SESSION['user_id'];

// Stats for current user
$total_pengaduan = 0;
$proses_pengaduan = 0;
$selesai_pengaduan = 0;
$recent_pengaduan = [];

if ($pdo) {
    // Counts
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS count FROM pengaduan WHERE user_id = :uid GROUP BY status");
    $stmt->execute(['uid' => $user_id]);
    while ($row = $stmt->fetch()) {
        $total_pengaduan += $row['count'];
        if (in_array($row['status'], ['Pengaduan Masuk', 'Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses'])) {
            $proses_pengaduan += $row['count'];
        } elseif ($row['status'] == 'Selesai') {
            $selesai_pengaduan += $row['count'];
        }
    }

    // Recent list
    $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori 
                           FROM pengaduan p 
                           JOIN kategori k ON p.kategori_id = k.id 
                           WHERE p.user_id = :uid 
                           ORDER BY p.created_at DESC LIMIT 5");
    $stmt->execute(['uid' => $user_id]);
    $recent_pengaduan = $stmt->fetchAll();
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">
            <?= get_flash(); ?>

            <!-- Header Banner -->
            <div class="card card-custom border-0 bg-dark text-white p-4 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary text-uppercase mb-2"><i class="fa-solid fa-user me-1"></i> Area Masyarakat Pelapor</span>
                        <h3 class="fw-bold mb-1">Selamat Datang, <?= sanitize($_SESSION['nama_lengkap']); ?>!</h3>
                        <p class="text-slate-300 small mb-0">Pantau progres penanganan pengaduan Anda atau sampaikan laporan kesejahteraan sosial baru.</p>
                    </div>
                    <div>
                        <a href="<?= base_url('pelapor/buat_pengaduan.php'); ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow">
                            <i class="fa-solid fa-plus-circle me-2"></i> Buat Pengaduan Baru
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card bg-primary shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Total Pengaduan Saya</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_pengaduan; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-warning text-dark shadow-sm">
                        <h6 class="text-dark-50 text-uppercase text-xs font-bold mb-1">Sedang Diproses</h6>
                        <h2 class="fw-extrabold mb-0"><?= $proses_pengaduan; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-spinner"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-success shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Selesai Ditangani</h6>
                        <h2 class="fw-extrabold mb-0"><?= $selesai_pengaduan; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Recent Complaints Table -->
            <div class="card card-custom border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-history text-primary me-2"></i> Pengaduan Terbaru Saya</h5>
                    <a href="<?= base_url('pelapor/riwayat.php'); ?>" class="btn btn-sm btn-outline-primary rounded-pill">Lihat Semua History <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No. Tiket</th>
                                    <th>Judul Pengaduan</th>
                                    <th>Kategori</th>
                                    <th>Tanggal Kirim</th>
                                    <th>Prioritas</th>
                                    <th>Status Penanganan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_pengaduan)): ?>
                                    <?php foreach ($recent_pengaduan as $p): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace fw-bold text-primary"><?= sanitize($p['nomor_tiket']); ?></td>
                                            <td>
                                                <span class="fw-semibold text-dark d-block"><?= sanitize($p['judul']); ?></span>
                                                <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= sanitize(substr($p['lokasi_kejadian'], 0, 35)); ?>...</small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= sanitize($p['nama_kategori']); ?></span></td>
                                            <td class="small text-muted"><?= format_tanggal($p['created_at'], false); ?></td>
                                            <td><?= get_priority_badge($p['prioritas']); ?></td>
                                            <td><?= get_status_badge($p['status']); ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url('pelapor/detail.php?id=' . $p['id']); ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                    <i class="fa-solid fa-eye me-1"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                            Anda belum pernah membuat pengaduan. Klik tombol "Buat Pengaduan Baru" di atas untuk mengirimkan laporan.
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
