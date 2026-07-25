<?php
$page_title = "Dashboard Petugas - SIPENSO";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../config/classifier.php';

check_role(['petugas', 'admin']);

// Filter parameters
$search    = sanitize($_GET['q'] ?? '');
$status    = sanitize($_GET['status'] ?? '');
$kategori  = (int)($_GET['kategori'] ?? 0);
$prioritas = sanitize($_GET['prioritas'] ?? '');

$query = "SELECT p.*, k.nama_kategori, u.nama_lengkap AS nama_pelapor, u.nik 
          FROM pengaduan p 
          JOIN kategori k ON p.kategori_id = k.id 
          JOIN users u ON p.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.nomor_tiket LIKE :search OR p.judul LIKE :search OR u.nama_lengkap LIKE :search OR p.lokasi_kejadian LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($status)) {
    $query .= " AND p.status = :status";
    $params['status'] = $status;
}

if ($kategori > 0) {
    $query .= " AND p.kategori_id = :kategori";
    $params['kategori'] = $kategori;
}

if (!empty($prioritas)) {
    $query .= " AND p.prioritas = :prioritas";
    $params['prioritas'] = $prioritas;
}

$query .= " ORDER BY CASE 
            WHEN p.prioritas = 'Mendesak' THEN 1 
            WHEN p.prioritas = 'Tinggi' THEN 2 
            WHEN p.prioritas = 'Sedang' THEN 3 
            ELSE 4 END ASC, p.created_at DESC";

$pengaduan_list = [];
$kategori_list = [];
$stats = ['masuk' => 0, 'mendesak' => 0, 'proses' => 0, 'selesai' => 0];

if ($pdo) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pengaduan_list = $stmt->fetchAll();

    $stmtKat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmtKat->fetchAll();

    // Stats count
    $stmtSt = $pdo->query("SELECT status, prioritas, COUNT(*) AS cnt FROM pengaduan GROUP BY status, prioritas");
    while ($r = $stmtSt->fetch()) {
        if ($r['status'] == 'Pengaduan Masuk') $stats['masuk'] += $r['cnt'];
        if (in_array($r['prioritas'], ['Mendesak', 'Tinggi'])) $stats['mendesak'] += $r['cnt'];
        if ($r['status'] == 'Diproses') $stats['proses'] += $r['cnt'];
        if ($r['status'] == 'Selesai') $stats['selesai'] += $r['cnt'];
    }
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
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-list-check text-primary me-2"></i> Panel Petugas Dinas Sosial</h3>
            <p class="text-muted small mb-0">Verifikasi, klasifikasi, tentukan prioritas, dan proses tindak lanjut pengaduan masyarakat</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-dark px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-user-shield me-1"></i> Mode Petugas Penanganan</span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-gradient-primary shadow-sm">
                <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Pengaduan Baru Masuk</h6>
                <h2 class="fw-extrabold mb-0"><?= $stats['masuk']; ?></h2>
                <div class="stat-icon"><i class="fa-solid fa-inbox"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-danger shadow-sm">
                <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Prioritas Mendesak &amp; Tinggi</h6>
                <h2 class="fw-extrabold mb-0"><?= $stats['mendesak']; ?></h2>
                <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-gradient-warning text-white shadow-sm">
                <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Sedang Diproses</h6>
                <h2 class="fw-extrabold mb-0"><?= $stats['proses']; ?></h2>
                <div class="stat-icon"><i class="fa-solid fa-spinner"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-gradient-success shadow-sm">
                <h6 class="text-white-50 text-uppercase text-xs font-bold mb-1">Selesai Ditangani</h6>
                <h2 class="fw-extrabold mb-0"><?= $stats['selesai']; ?></h2>
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>
    </div>

    <!-- Multi-Filter Card -->
    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-2">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control bg-light" placeholder="Cari tiket, nama pelapor, lokasi..." value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select bg-light">
                        <option value="">-- Semua Status --</option>
                        <option value="Pengaduan Masuk" <?= $status == 'Pengaduan Masuk' ? 'selected' : ''; ?>>Pengaduan Masuk</option>
                        <option value="Diverifikasi" <?= $status == 'Diverifikasi' ? 'selected' : ''; ?>>Diverifikasi</option>
                        <option value="Diklasifikasikan" <?= $status == 'Diklasifikasikan' ? 'selected' : ''; ?>>Diklasifikasikan</option>
                        <option value="Prioritas Ditentukan" <?= $status == 'Prioritas Ditentukan' ? 'selected' : ''; ?>>Prioritas Ditentukan</option>
                        <option value="Diproses" <?= $status == 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                        <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="Ditolak" <?= $status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="kategori" class="form-select bg-light">
                        <option value="">-- Semua Kategori --</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= $kat['id']; ?>" <?= $kategori == $kat['id'] ? 'selected' : ''; ?>><?= sanitize($kat['nama_kategori']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="prioritas" class="form-select bg-light">
                        <option value="">-- Semua Prioritas --</option>
                        <option value="Mendesak" <?= $prioritas == 'Mendesak' ? 'selected' : ''; ?>>Mendesak</option>
                        <option value="Tinggi" <?= $prioritas == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                        <option value="Sedang" <?= $prioritas == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                        <option value="Rendah" <?= $prioritas == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= base_url('petugas/dashboard.php'); ?>" class="btn btn-light border rounded-3"><i class="fa-solid fa-rotate"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-inbox text-primary me-2"></i> Daftar Antrean Pengaduan Masuk</h5>
            <span class="badge bg-secondary">Total: <?= count($pengaduan_list); ?> Data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Tiket</th>
                            <th>Pelapor (NIK)</th>
                            <th>Judul &amp; Kategori Laporan</th>
                            <th><i class="fa-solid fa-brain text-primary me-1"></i> Saran Model AI</th>
                            <th>Skor &amp; Prioritas</th>
                            <th>Status Penanganan</th>
                            <th class="text-center">Aksi Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pengaduan_list)): ?>
                            <?php foreach ($pengaduan_list as $p): ?>
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-primary"><?= sanitize($p['nomor_tiket']); ?></td>
                                    <td>
                                        <span class="fw-semibold text-dark d-block"><?= sanitize($p['nama_pelapor']); ?></span>
                                        <small class="text-muted">NIK: <?= sanitize($p['nik']); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= sanitize($p['judul']); ?></span>
                                        <span class="badge bg-light text-dark border"><?= sanitize($p['nama_kategori']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $ai = classify_complaint($p['judul'] . ' ' . $p['isi_laporan']);
                                        $match = ($ai['kategori_id'] == $p['kategori_id']);
                                        $aiConf = $ai['confidence'];
                                        ?>
                                        <?php if ($ai['kode']): ?>
                                            <span class="badge rounded-pill" style="font-size:0.7rem;background:<?= $match ? '#19875430' : '#ffc10730'; ?>;color:<?= $match ? '#198754' : '#856404'; ?>;border:1px solid <?= $match ? '#19875488' : '#ffc10788'; ?>;">
                                                <i class="fa-solid fa-brain me-1"></i>
                                                <?= sanitize(explode('(', $ai['nama'])[0]); ?>
                                                <?= $aiConf; ?>%
                                                <?= $match ? '✓' : '⚠'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size:0.7rem;">Tidak dikenali</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= get_priority_badge($p['prioritas']); ?>
                                        <small class="d-block text-muted mt-1">(Skor: <?= (int)$p['skor_prioritas']; ?> Poin)</small>
                                    </td>
                                    <td><?= get_status_badge($p['status']); ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('petugas/detail_pengaduan.php?id=' . $p['id']); ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                                            <i class="fa-solid fa-user-gear me-1"></i> Verifikasi &amp; Diproses
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                    Tidak ada pengaduan yang sesuai dengan kriteria filter.
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
