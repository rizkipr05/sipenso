<?php
$page_title = "Dashboard Admin - SIPENSO";
require_once __DIR__ . '/../includes/header.php';
check_role(['admin']);

// Fetch stats for Admin Dashboard
$total_pengaduan = 0; $total_masuk = 0; $total_proses = 0; $total_selesai = 0;
$kategori_chart_labels = []; $kategori_chart_counts = [];
$prioritas_chart_labels = ['Tinggi', 'Sedang', 'Rendah'];
$prioritas_chart_counts = [0, 0, 0];
$recent_pengaduan = [];

if ($pdo) {
    // Total status counts
    $stmtSt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM pengaduan GROUP BY status");
    while ($r = $stmtSt->fetch()) {
        $total_pengaduan += $r['cnt'];
        if ($r['status'] == 'Pengaduan Masuk') $total_masuk += $r['cnt'];
        elseif (in_array($r['status'], ['Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses'])) $total_proses += $r['cnt'];
        elseif ($r['status'] == 'Selesai') $total_selesai += $r['cnt'];
    }

    // Category chart data
    $stmtKatData = $pdo->query("SELECT k.nama_kategori, COUNT(p.id) AS cnt FROM kategori k LEFT JOIN pengaduan p ON k.id = p.kategori_id GROUP BY k.id ORDER BY cnt DESC LIMIT 5");
    while ($r = $stmtKatData->fetch()) {
        $kategori_chart_labels[] = $r['nama_kategori'];
        $kategori_chart_counts[] = (int)$r['cnt'];
    }

    // Priority chart data
    $stmtPrioData = $pdo->query("SELECT prioritas, COUNT(*) AS cnt FROM pengaduan GROUP BY prioritas");
    $prio_map = ['Tinggi' => 0, 'Sedang' => 0, 'Rendah' => 0];
    while ($r = $stmtPrioData->fetch()) {
        if(isset($prio_map[$r['prioritas']])) $prio_map[$r['prioritas']] = (int)$r['cnt'];
    }
    $prioritas_chart_counts = array_values($prio_map);

    // Recent list
    $stmtRec = $pdo->query("SELECT p.*, k.nama_kategori, u.nama_lengkap AS nama_pelapor FROM pengaduan p JOIN kategori k ON p.kategori_id = k.id JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
    $recent_pengaduan = $stmtRec->fetchAll();
}
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div id="content" style="background-color: var(--bg-body) !important;">
        <div class="container-fluid px-3 py-2">
            <?= get_flash(); ?>
            <!-- 4 Stat Cards -->
            <div class="row g-3 mb-4" id="statistik">
                <?php 
                $cards = [
                    ['title' => 'TOTAL PENGADUAN', 'count' => $total_pengaduan, 'desc' => 'Seluruh pengaduan masuk', 'color' => '#3b82f6', 'icon' => 'fa-file-lines', 'bg' => 'rgba(59,130,246,0.1)'],
                    ['title' => 'SELESAI DITANGANI', 'count' => $total_selesai, 'desc' => ($total_pengaduan?round(($total_selesai/$total_pengaduan)*100,1):0).'% dari total pengaduan', 'color' => '#10b981', 'icon' => 'fa-check-circle', 'bg' => 'rgba(16,185,129,0.1)'],
                    ['title' => 'SEDANG DIPROSES', 'count' => $total_proses, 'desc' => ($total_pengaduan?round(($total_proses/$total_pengaduan)*100,1):0).'% dari total pengaduan', 'color' => '#f59e0b', 'icon' => 'fa-clock', 'bg' => 'rgba(245,158,11,0.1)'],
                    ['title' => 'BELUM DIPROSES', 'count' => $total_masuk, 'desc' => ($total_pengaduan?round(($total_masuk/$total_pengaduan)*100,1):0).'% dari total pengaduan', 'color' => '#ef4444', 'icon' => 'fa-circle-exclamation', 'bg' => 'rgba(239,68,68,0.1)']
                ];
                foreach($cards as $c): ?>
                <div class="col-xl-3 col-md-6">
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
                            <!-- Generic SVG Wave Placeholder for aesthetics -->
                            <svg viewBox="0 0 100 20" preserveAspectRatio="none" style="width:100%; height:100%;"><path d="M0,10 Q25,20 50,10 T100,10 L100,20 L0,20 Z" fill="<?= $c['color'] ?>"/></svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card card-custom border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.8rem;">GRAFIK PENGADUAN 6 BULAN TERAKHIR</h6>
                            <select class="form-select form-select-sm border-0 bg-light w-auto text-muted"><option>6 Bulan</option></select>
                        </div>
                        <div class="card-body p-3">
                            <canvas id="chartBar" style="height:220px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-custom border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0 text-dark text-center" style="font-size: 0.8rem;">PENGADUAN BERDASARKAN KLASIFIKASI</h6>
                        </div>
                        <div class="card-body p-3 d-flex justify-content-center">
                            <canvas id="chartKategori" style="height:220px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-custom border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0 text-dark text-center" style="font-size: 0.8rem;">PENGADUAN BERDASARKAN PRIORITAS</h6>
                        </div>
                        <div class="card-body p-3 d-flex justify-content-center">
                            <canvas id="chartPrioritas" style="height:220px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Table & Quick Actions -->
            <div class="row g-3">
                <!-- Recent Complaints Table -->
                <div class="col-lg-8">
                    <div class="card card-custom border-0 shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;">PENGADUAN TERBARU</h6>
                            <a href="<?= base_url('admin/pengaduan.php'); ?>" class="text-primary text-decoration-none fw-bold" style="font-size:0.8rem;">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead style="background:var(--bg-body-light); color:var(--text-muted); font-size:0.75rem;">
                                        <tr>
                                            <th class="ps-4">No</th>
                                            <th>Tanggal</th>
                                            <th>Nama Pelapor</th>
                                            <th>Judul Pengaduan</th>
                                            <th class="text-center">Klasifikasi</th>
                                            <th class="text-center">Prioritas</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_pengaduan)): $no=1; foreach ($recent_pengaduan as $p): ?>
                                            <tr style="border-bottom:1px solid #f1f5f9;">
                                                <td class="ps-4 fw-bold text-muted"><?= $no++; ?></td>
                                                <td class="text-muted" style="font-size:0.75rem;">
                                                    <div class="fw-bold text-dark"><?= date('d M Y', strtotime($p['created_at'])) ?></div>
                                                    <div><?= date('H:i', strtotime($p['created_at'])) ?> WIB</div>
                                                </td>
                                                <td class="fw-semibold text-dark"><?= sanitize($p['nama_pelapor']); ?></td>
                                                <td><?= sanitize(substr($p['judul'],0,40)).'...'; ?></td>
                                                <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary border-primary border" style="font-weight:500; font-size:0.7rem; border-radius:15px;"><?= sanitize($p['nama_kategori']); ?></span></td>
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
                                                    <a href="<?= base_url('petugas/detail_pengaduan.php?id=' . $p['id']); ?>" class="btn btn-primary text-white p-1 px-2" style="border-radius:6px; font-size:0.7rem;">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada pengaduan</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center px-4 py-3 bg-white" style="border-top:1px solid #f1f5f9; border-radius: 0 0 12px 12px;">
                                <a href="<?= base_url('admin/pengaduan.php'); ?>" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm" style="font-size: 0.85rem; max-width: 300px;">Lihat Semua Pengaduan</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Announcment -->
                <div class="col-lg-4">
                    <!-- Aksi Cepat -->
                    <div class="card card-custom border-0 shadow-sm mb-3" style="border-radius: 12px;">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;">AKSI CEPAT</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="<?= base_url('admin/pengaduan.php'); ?>" class="text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-white shadow-sm border border-light transition-smooth" onmouseover="this.className='text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-light shadow-md border transition-smooth'">
                                        <i class="fa-solid fa-file-signature text-primary fs-3 mb-2"></i>
                                        <div class="fw-bold text-center" style="font-size: 0.75rem;">Buat Pengaduan</div>
                                        <div class="text-muted text-center" style="font-size:0.65rem;">Tambah pengaduan baru</div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= base_url('admin/kategori.php'); ?>" class="text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-white shadow-sm border border-light transition-smooth" onmouseover="this.className='text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-light shadow-md border transition-smooth'">
                                        <i class="fa-solid fa-folder-tree text-warning fs-3 mb-2"></i>
                                        <div class="fw-bold text-center" style="font-size: 0.75rem;">Klasifikasi Pengaduan</div>
                                        <div class="text-muted text-center" style="font-size:0.65rem;">Kelola klasifikasi</div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= base_url('admin/kriteria.php'); ?>" class="text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-white shadow-sm border border-light transition-smooth" onmouseover="this.className='text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-light shadow-md border transition-smooth'">
                                        <i class="fa-solid fa-circle-exclamation text-warning fs-3 mb-2"></i>
                                        <div class="fw-bold text-center" style="font-size: 0.75rem;">Prioritas Penanganan</div>
                                        <div class="text-muted text-center" style="font-size:0.65rem;">Atur prioritas pengaduan</div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= base_url('admin/laporan.php'); ?>" class="text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-white shadow-sm border border-light transition-smooth" onmouseover="this.className='text-decoration-none d-flex flex-column align-items-center justify-content-center p-3 h-100 rounded-3 text-dark bg-light shadow-md border transition-smooth'">
                                        <i class="fa-solid fa-chart-bar text-primary fs-3 mb-2"></i>
                                        <div class="fw-bold text-center" style="font-size: 0.75rem;">Laporan Pengaduan</div>
                                        <div class="text-muted text-center" style="font-size:0.65rem;">Cetak & unduh laporan</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pengumuman Banner -->
                    <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #f0f7ff, #e0e7ff);">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 0.9rem;">PENGUMUMAN</h6>
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.8rem;">Jam layanan pengaduan:</h6>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.75rem;">Senin - Jumat : 08.00 - 16.00 WIB</div>
                                    <p class="text-muted mb-0 lh-sm" style="font-size: 0.65rem;">Pengaduan akan ditindaklanjuti sesuai prioritas dan ketentuan yang berlaku.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shared chart options
    const sharedPieOptions = {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { boxWidth: 10, font: {size: 10, family: "'Inter', sans-serif"} } }
        },
        cutout: '65%' // Donut feel
    };

    // Chart 1: Bar Chart (Mock data for 6 months)
    new Chart(document.getElementById('chartBar').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
            datasets: [{
                label: 'Pengaduan',
                data: [18, 25, 45, 38, 32, 28],
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2,2] } },
                x: { grid: { display: false } }
            }
        }
    });

    // Chart 2: Klasifikasi
    new Chart(document.getElementById('chartKategori').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(!empty($kategori_chart_labels) ? $kategori_chart_labels : ['Bantuan Sosial', 'Pendataan', 'Permasalahan Sosial', 'Pelayanan', 'Lain-lain']); ?>,
            datasets: [{
                data: <?= json_encode(!empty($kategori_chart_counts) ? $kategori_chart_counts : [96,62,48,28,22]); ?>,
                backgroundColor: ['#ef4444', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: sharedPieOptions
    });

    // Chart 3: Prioritas
    new Chart(document.getElementById('chartPrioritas').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($prioritas_chart_labels); ?>,
            datasets: [{
                data: <?= json_encode(!empty(array_filter($prioritas_chart_counts)) ? array_slice($prioritas_chart_counts, 0, 3) : [88, 98, 70]); ?>,
                backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: sharedPieOptions
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
