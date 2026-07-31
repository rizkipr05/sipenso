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

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" style="background-color: var(--bg-body) !important;">
        <div class="container-fluid px-3 py-2">
            <?= get_flash(); ?>

            <!-- Header Banner -->
            <div class="card card-custom border-0 bg-dark text-white p-4 mb-4 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary text-uppercase mb-2" style="background: rgba(59,130,246,0.3) !important; color: #93c5fd !important;"><i class="fa-solid fa-user me-1"></i> Area Masyarakat Pelapor</span>
                        <h4 class="fw-bolder mb-1 text-white" style="letter-spacing:-0.5px;">Selamat Datang, <?= sanitize($_SESSION['nama_lengkap']); ?>!</h4>
                        <p class="text-white-50 small mb-0">Pantau progres penanganan pengaduan Anda atau sampaikan laporan kesejahteraan sosial baru.</p>
                    </div>
                    <div>
                        <a href="<?= base_url('pelapor/buat_pengaduan.php'); ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow">
                            <i class="fa-solid fa-plus-circle me-2"></i> Buat Pengaduan Baru
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stat Cards aligned with the new Admin Dashboard aesthetic -->
            <div class="row g-3 mb-4">
                <?php 
                $cards = [
                    ['title' => 'Total Laporan Saya', 'count' => $total_pengaduan, 'desc' => 'Seluruh pengaduan masuk', 'color' => '#3b82f6', 'icon' => 'fa-file-lines', 'bg' => 'rgba(59,130,246,0.1)'],
                    ['title' => 'Sedang Diproses', 'count' => $proses_pengaduan, 'desc' => 'Menunggu tindak lanjut', 'color' => '#f59e0b', 'icon' => 'fa-clock', 'bg' => 'rgba(245,158,11,0.1)'],
                    ['title' => 'Selesai Ditangani', 'count' => $selesai_pengaduan, 'desc' => 'Pengaduan tuntas', 'color' => '#10b981', 'icon' => 'fa-check-circle', 'bg' => 'rgba(16,185,129,0.1)']
                ];
                foreach($cards as $c): ?>
                <div class="col-md-4">
                    <div class="card card-custom border-0 shadow-sm h-100 p-3" style="border-radius: 12px;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; background: <?= $c['color'] ?>; color: white;">
                                <i class="fa-solid <?= $c['icon'] ?> fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-dark fw-bold mb-0 text-uppercase" style="font-size: 0.75rem;"><?= $c['title'] ?></h6>
                                <h3 class="fw-bolder mb-0 text-dark"><?= $c['count'] ?></h3>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= $c['desc'] ?></small>
                            </div>
                        </div>
                        <div style="height: 35px; width: 100%; border-bottom: 2px solid <?= $c['color'] ?>; opacity: 0.3;">
                            <svg viewBox="0 0 100 20" preserveAspectRatio="none" style="width:100%; height:100%;"><path d="M0,10 Q25,20 50,10 T100,10 L100,20 L0,20 Z" fill="<?= $c['color'] ?>"/></svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Complaints Table aligned with light theme -->
            <div class="card card-custom border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;">PENGADUAN TERBARU SAYA</h6>
                    <a href="<?= base_url('pelapor/riwayat.php'); ?>" class="text-primary text-decoration-none fw-bold" style="font-size:0.8rem;">Lihat Riwayat <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead style="background:var(--bg-body-light); color:var(--text-muted); font-size:0.75rem;">
                                <tr>
                                    <th class="ps-4">No. Tiket</th>
                                    <th>Judul Pengaduan</th>
                                    <th class="text-center">Kategori</th>
                                    <th>Tgl Kirim</th>
                                    <th class="text-center">Prioritas</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_pengaduan)): ?>
                                    <?php foreach ($recent_pengaduan as $p): ?>
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <td class="ps-4 font-monospace fw-bold text-muted"><?= sanitize($p['nomor_tiket']); ?></td>
                                            <td>
                                                <div class="fw-bold text-dark d-block"><?= sanitize($p['judul']); ?></div>
                                                <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= sanitize(substr($p['lokasi_kejadian'], 0, 35)); ?>...</small>
                                            </td>
                                            <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary border-primary border" style="font-weight:500; font-size:0.75rem; border-radius:15px;"><?= sanitize($p['nama_kategori']); ?></span></td>
                                            <td class="text-muted" style="font-size:0.75rem;">
                                                <div class="fw-bold text-dark"><?= date('d M Y', strtotime($p['created_at'])) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                    $c = ['Rendah'=>'success','Sedang'=>'warning','Tinggi'=>'danger','Mendesak'=>'danger']; 
                                                    $col = $c[$p['prioritas']] ?? 'secondary';
                                                ?>
                                                <span class="text-<?= $col ?> fw-bold" style="font-size:0.75rem;">&bull; <?= $p['prioritas']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= ($p['status']=='Selesai')?'success':(($p['status']=='Pengaduan Masuk')?'danger':'warning'); ?>-subtle text-<?= ($p['status']=='Selesai')?'success':(($p['status']=='Pengaduan Masuk')?'danger':'warning'); ?> fw-bold d-inline-block px-3 py-1" style="border-radius:15px;">
                                                    <?= $p['status']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('pelapor/detail.php?id=' . $p['id']); ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                                                    <i class="fa-solid fa-eye me-1"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                            Anda belum pernah membuat pengaduan.<br>Klik tombol "Buat Pengaduan Baru" di atas untuk mengirimkan laporan.
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
