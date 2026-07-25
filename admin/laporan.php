<?php
$page_title = "Laporan Rekapitulasi - SIPENSO";
require_once __DIR__ . '/../includes/header.php';

check_role(['admin', 'petugas']);

// Filter parameters
$tgl_mulai = sanitize($_GET['tgl_mulai'] ?? date('Y-m-01'));
$tgl_selesai = sanitize($_GET['tgl_selesai'] ?? date('Y-m-d'));
$kategori   = (int)($_GET['kategori'] ?? 0);
$prioritas  = sanitize($_GET['prioritas'] ?? '');
$status     = sanitize($_GET['status'] ?? '');

$query = "SELECT p.*, k.nama_kategori, k.kode_kategori, u.nama_lengkap AS nama_pelapor, u.nik 
          FROM pengaduan p 
          JOIN kategori k ON p.kategori_id = k.id 
          JOIN users u ON p.user_id = u.id 
          WHERE DATE(p.created_at) BETWEEN :tgl1 AND :tgl2";
$params = ['tgl1' => $tgl_mulai, 'tgl2' => $tgl_selesai];

if ($kategori > 0) {
    $query .= " AND p.kategori_id = :kategori";
    $params['kategori'] = $kategori;
}
if (!empty($prioritas)) {
    $query .= " AND p.prioritas = :prioritas";
    $params['prioritas'] = $prioritas;
}
if (!empty($status)) {
    $query .= " AND p.status = :status";
    $params['status'] = $status;
}

$query .= " ORDER BY p.created_at ASC";

$laporan_list = [];
$kategori_list = [];

if ($pdo) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $laporan_list = $stmt->fetchAll();

    $stmtKat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmtKat->fetchAll();
}

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Rekapitulasi_Pengaduan_Dinsos_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No. Tiket', 'Tanggal', 'NIK Pelapor', 'Nama Pelapor', 'Kategori', 'Judul Laporan', 'Lokasi', 'Tingkat Dampak', 'Jumlah Terdampak', 'Skor Prioritas', 'Prioritas', 'Status']);
    
    foreach ($laporan_list as $row) {
        fputcsv($output, [
            $row['nomor_tiket'],
            $row['created_at'],
            $row['nik'],
            $row['nama_pelapor'],
            $row['nama_kategori'],
            $row['judul'],
            $row['lokasi_kejadian'],
            $row['tingkat_dampak'],
            $row['jumlah_terdampak'],
            $row['skor_prioritas'],
            $row['prioritas'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">
            <?= get_flash(); ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Rekapitulasi &amp; Cetak Laporan</h3>
                    <p class="text-muted small mb-0">Cetak rekap berkala dengan Kop Surat Resmi Dinas Sosial atau export data ke Excel/CSV</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('admin/laporan.php?' . http_build_query(array_merge($_GET, ['export' => 'csv']))); ?>" class="btn btn-outline-success rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-file-excel me-1"></i> Export CSV/Excel
                    </a>
                    <button onclick="window.print();" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-print me-1"></i> Cetak Laporan Kop Resmi
                    </button>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card card-custom border-0 shadow-sm mb-4 no-print">
                <div class="card-body p-3">
                    <form action="" method="GET" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label text-xs font-semibold mb-1">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control bg-light" value="<?= htmlspecialchars($tgl_mulai); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xs font-semibold mb-1">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control bg-light" value="<?= htmlspecialchars($tgl_selesai); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xs font-semibold mb-1">Kategori</label>
                            <select name="kategori" class="form-select bg-light">
                                <option value="">-- Semua --</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id']; ?>" <?= $kategori == $kat['id'] ? 'selected' : ''; ?>><?= sanitize($kat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xs font-semibold mb-1">Prioritas</label>
                            <select name="prioritas" class="form-select bg-light">
                                <option value="">-- Semua --</option>
                                <option value="Mendesak" <?= $prioritas == 'Mendesak' ? 'selected' : ''; ?>>Mendesak</option>
                                <option value="Tinggi" <?= $prioritas == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                                <option value="Sedang" <?= $prioritas == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                <option value="Rendah" <?= $prioritas == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                            <a href="<?= base_url('admin/laporan.php'); ?>" class="btn btn-light border rounded-3"><i class="fa-solid fa-rotate"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Print Container Layout with Kop Surat -->
            <div class="card card-custom border-0 shadow-lg p-4 p-md-5 print-container bg-white">
                
                <!-- Kop Surat Official Header -->
                <div class="kop-surat mb-4 pb-3 border-bottom border-3 border-dark text-center position-relative">
                    <div class="row align-items-center">
                        <div class="col-2 text-center">
                            <i class="fa-solid fa-building-columns fa-4x text-primary"></i>
                        </div>
                        <div class="col-8 text-center">
                            <h5 class="fw-bold mb-0 text-uppercase tracking-wider text-dark">PEMERINTAH KOTA / KABUPATEN</h5>
                            <h3 class="fw-extrabold mb-1 text-uppercase text-dark">DINAS SOSIAL DAN PEMBERDAYAAN MASYARAKAT</h3>
                            <p class="mb-0 small text-muted">Jl. Jend. Sudirman No. 123, Kompleks Perkantoran Pemerintah Kota | Telp: (021) 555-0199</p>
                            <p class="mb-0 small text-muted">Email: pengaduan@dinsos.go.id | Website: https://dinsos.go.id</p>
                        </div>
                        <div class="col-2 text-center">
                            <i class="fa-solid fa-hand-holding-heart fa-4x text-danger"></i>
                        </div>
                    </div>
                    <div class="border-bottom border-1 border-dark mt-2"></div>
                </div>

                <!-- Report Title & Period -->
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-dark text-uppercase mb-1">LAPORAN REKAPITULASI PENGADUAN MASYARAKAT</h4>
                    <p class="text-muted small">Periode Laporan: <strong><?= format_tanggal($tgl_mulai); ?></strong> s/d <strong><?= format_tanggal($tgl_selesai); ?></strong></p>
                </div>

                <!-- Table Data -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle text-xs">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 4%;">No</th>
                                <th style="width: 14%;">No. Tiket / Tanggal</th>
                                <th style="width: 16%;">Pelapor (NIK)</th>
                                <th style="width: 15%;">Kategori Laporan</th>
                                <th style="width: 25%;">Judul &amp; Lokasi</th>
                                <th style="width: 13%;">Prioritas</th>
                                <th style="width: 13%;">Status Penanganan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($laporan_list)): ?>
                                <?php foreach ($laporan_list as $idx => $r): ?>
                                    <tr>
                                        <td class="text-center fw-semibold"><?= $idx + 1; ?></td>
                                        <td>
                                            <span class="font-monospace fw-bold text-primary d-block"><?= sanitize($r['nomor_tiket']); ?></span>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold d-block"><?= sanitize($r['nama_pelapor']); ?></span>
                                            <small class="text-muted">NIK: <?= sanitize($r['nik']); ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= sanitize($r['nama_kategori']); ?></span></td>
                                        <td>
                                            <strong class="d-block text-dark"><?= sanitize($r['judul']); ?></strong>
                                            <small class="text-muted"><i class="fa-solid fa-location-dot text-danger"></i> <?= sanitize($r['lokasi_kejadian']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?= get_priority_badge($r['prioritas']); ?>
                                            <small class="d-block text-muted">Skor: <?= (int)$r['skor_prioritas']; ?></small>
                                        </td>
                                        <td class="text-center"><?= get_status_badge($r['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada data rekapitulasi pada periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Signature Section for Official Print -->
                <div class="row mt-5 pt-3">
                    <div class="col-6 text-center">
                        <p class="mb-1 text-xs">Mengetahui,</p>
                        <p class="fw-bold mb-5 text-uppercase">Kepala Dinas Sosial</p>
                        <br><br>
                        <p class="fw-bold mb-0 text-decoration-underline">Drs. H. Ahmad Subakti, M.Si</p>
                        <p class="text-xs text-muted mb-0">NIP. 19750812 199903 1 004</p>
                    </div>
                    <div class="col-6 text-center">
                        <p class="mb-1 text-xs">Dibuat Pada: <?= date('d F Y'); ?></p>
                        <p class="fw-bold mb-5 text-uppercase">Petugas Administrator SIPENSO</p>
                        <br><br>
                        <p class="fw-bold mb-0 text-decoration-underline"><?= sanitize($_SESSION['nama_lengkap']); ?></p>
                        <p class="text-xs text-muted mb-0">SIPENSO System Administrator</p>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
