<?php
$page_title = "Dashboard Admin - SIPENSO";
require_once __DIR__ . '/../includes/header.php';

check_role(['admin']);

// Fetch stats for Admin Dashboard
$total_pengaduan = 0;
$total_masuk = 0;
$total_proses = 0;
$total_selesai = 0;
$total_ditolak = 0;
$total_users = 0;

$kategori_chart_labels = [];
$kategori_chart_counts = [];

$prioritas_chart_labels = ['Mendesak', 'Tinggi', 'Sedang', 'Rendah'];
$prioritas_chart_counts = [0, 0, 0, 0];

$recent_pengaduan = [];

if ($pdo) {
    // Total users
    $total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Total status counts
    $stmtSt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM pengaduan GROUP BY status");
    while ($r = $stmtSt->fetch()) {
        $total_pengaduan += $r['cnt'];
        if ($r['status'] == 'Pengaduan Masuk') $total_masuk += $r['cnt'];
        elseif (in_array($r['status'], ['Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses'])) $total_proses += $r['cnt'];
        elseif ($r['status'] == 'Selesai') $total_selesai += $r['cnt'];
        elseif ($r['status'] == 'Ditolak') $total_ditolak += $r['cnt'];
    }

    // Category chart data
    $stmtKatData = $pdo->query("SELECT k.nama_kategori, COUNT(p.id) AS cnt 
                                FROM kategori k 
                                LEFT JOIN pengaduan p ON k.id = p.kategori_id 
                                GROUP BY k.id 
                                ORDER BY k.nama_kategori ASC");
    while ($r = $stmtKatData->fetch()) {
        $kategori_chart_labels[] = $r['nama_kategori'];
        $kategori_chart_counts[] = (int)$r['cnt'];
    }

    // Priority chart data
    $stmtPrioData = $pdo->query("SELECT prioritas, COUNT(*) AS cnt FROM pengaduan GROUP BY prioritas");
    $prio_map = ['Mendesak' => 0, 'Tinggi' => 0, 'Sedang' => 0, 'Rendah' => 0];
    while ($r = $stmtPrioData->fetch()) {
        $prio_map[$r['prioritas']] = (int)$r['cnt'];
    }
    $prioritas_chart_counts = array_values($prio_map);

    // Recent list
    $stmtRec = $pdo->query("SELECT p.*, k.nama_kategori, u.nama_lengkap AS nama_pelapor 
                            FROM pengaduan p 
                            JOIN kategori k ON p.kategori_id = k.id 
                            JOIN users u ON p.user_id = u.id 
                            ORDER BY p.created_at DESC LIMIT 5");
    $recent_pengaduan = $stmtRec->fetchAll();
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
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Dashboard Statistik Administrator</h3>
                    <p class="text-muted small mb-0">Overview sistem pengaduan masyarakat Dinas Sosial secara komprehensif</p>
                </div>
                <a href="<?= base_url('admin/laporan.php'); ?>" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-print me-1"></i> Rekap &amp; Cetak Laporan
                </a>
            </div>

            <!-- Stats Counters Row -->
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stat-card bg-dark shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Total Laporan</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_pengaduan; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stat-card stat-gradient-primary shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Pengaduan Masuk</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_masuk; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stat-card stat-gradient-warning text-white shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Sedang Diproses</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_proses; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-spinner"></i></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stat-card stat-gradient-success shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Selesai</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_selesai; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stat-card bg-danger shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Ditolak</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_ditolak; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stat-card stat-gradient-info shadow-sm">
                        <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Total Users</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_users; ?></h2>
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    </div>
                </div>
            </div>

            <!-- Chart Visualizations -->
            <div class="row g-4 mb-4">
                <!-- Chart 1: Category Distribution -->
                <div class="col-lg-6">
                    <div class="card card-custom border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Distribusi Pengaduan per Kategori</h6>
                        </div>
                        <div class="card-body p-4 d-flex align-items-center justify-content-center">
                            <div style="width: 100%; max-width: 380px; height: 280px;">
                                <canvas id="chartKategori"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart 2: Priority Distribution -->
                <div class="col-lg-6">
                    <div class="card card-custom border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-bar text-warning me-2"></i> Grafik Prioritas Penanganan</h6>
                        </div>
                        <div class="card-body p-4">
                            <div style="width: 100%; height: 280px;">
                                <canvas id="chartPrioritas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Complaints Table -->
            <div class="card card-custom border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-history text-primary me-2"></i> Laporan Masuk Terbaru</h5>
                    <a href="<?= base_url('admin/pengaduan.php'); ?>" class="btn btn-sm btn-outline-primary rounded-pill">Kelola Seluruh Pengaduan <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No. Tiket</th>
                                    <th>Pelapor</th>
                                    <th>Judul Pengaduan</th>
                                    <th>Kategori</th>
                                    <th>Prioritas</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_pengaduan)): ?>
                                    <?php foreach ($recent_pengaduan as $p): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace fw-bold text-primary"><?= sanitize($p['nomor_tiket']); ?></td>
                                            <td><span class="fw-semibold"><?= sanitize($p['nama_pelapor']); ?></span></td>
                                            <td><?= sanitize($p['judul']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= sanitize($p['nama_kategori']); ?></span></td>
                                            <td><?= get_priority_badge($p['prioritas']); ?></td>
                                            <td><?= get_status_badge($p['status']); ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url('petugas/detail_pengaduan.php?id=' . $p['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="fa-solid fa-eye me-1"></i> Detail
                                                </a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart 1: Kategori Pie Chart
    const ctxKat = document.getElementById('chartKategori').getContext('2d');
    new Chart(ctxKat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($kategori_chart_labels); ?>,
            datasets: [{
                data: <?= json_encode($kategori_chart_counts); ?>,
                backgroundColor: ['#0d6efd', '#06b6d4', '#4f46e5', '#f59e0b', '#ef4444', '#10b981'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Chart 2: Prioritas Bar Chart
    const ctxPrio = document.getElementById('chartPrioritas').getContext('2d');
    new Chart(ctxPrio, {
        type: 'bar',
        data: {
            labels: <?= json_encode($prioritas_chart_labels); ?>,
            datasets: [{
                label: 'Jumlah Pengaduan',
                data: <?= json_encode($prioritas_chart_counts); ?>,
                backgroundColor: ['#dc3545', '#ffc107', '#0dcaf0', '#6c757d'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
