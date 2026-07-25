<?php
$page_title = "Riwayat Pengaduan - SIPENSO";
$active_nav = "riwayat";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../config/classifier.php';

check_role(['pelapor']);

$user_id = $_SESSION['user_id'];

// Filter parameters
$search   = sanitize($_GET['q'] ?? '');
$status   = sanitize($_GET['status'] ?? '');
$kategori = (int)($_GET['kategori'] ?? 0);

$query = "SELECT p.*, k.nama_kategori 
          FROM pengaduan p 
          JOIN kategori k ON p.kategori_id = k.id 
          WHERE p.user_id = :uid";
$params = ['uid' => $user_id];

if (!empty($search)) {
    $query .= " AND (p.nomor_tiket LIKE :search OR p.judul LIKE :search OR p.isi_laporan LIKE :search)";
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

$query .= " ORDER BY p.created_at DESC";

$pengaduan_list = [];
$kategori_list = [];

if ($pdo) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pengaduan_list = $stmt->fetchAll();

    $stmtKat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmtKat->fetchAll();
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-clock-history text-primary me-2"></i> Riwayat Pengaduan Saya</h3>
            <p class="text-muted small mb-0">Daftar seluruh pengaduan sosial yang pernah Anda ajukan ke Dinas Sosial</p>
        </div>
        <a href="<?= base_url('pelapor/buat_pengaduan.php'); ?>" class="btn btn-primary rounded-pill px-4 fw-bold">
            <i class="fa-solid fa-plus-circle me-1"></i> Buat Pengaduan Baru
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control bg-light" placeholder="Cari tiket, judul, atau isi..." value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= base_url('pelapor/riwayat.php'); ?>" class="btn btn-light border rounded-3"><i class="fa-solid fa-rotate me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Tiket</th>
                            <th>Judul &amp; Lokasi Pengaduan</th>
                            <th>Kategori</th>
                            <th><i class="fa-solid fa-brain text-primary me-1"></i> Saran Model AI</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Prioritas</th>
                            <th>Status Penanganan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pengaduan_list)): ?>
                            <?php foreach ($pengaduan_list as $p): ?>
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-primary"><?= sanitize($p['nomor_tiket']); ?></td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= sanitize($p['judul']); ?></span>
                                        <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= sanitize($p['lokasi_kejadian']); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= sanitize($p['nama_kategori']); ?></span></td>
                                    <td>
                                        <?php
                                        $ai = classify_complaint($p['judul'] . ' ' . $p['isi_laporan']);
                                        $match = ($ai['kategori_id'] == $p['kategori_id']);
                                        ?>
                                        <?php if ($ai['kode']): ?>
                                            <span class="badge rounded-pill" style="font-size:0.7rem;background:<?= $match ? '#19875430' : '#ffc10730'; ?>;color:<?= $match ? '#198754' : '#856404'; ?>;border:1px solid <?= $match ? '#19875488' : '#ffc10788'; ?>;">
                                                <i class="fa-solid fa-brain me-1"></i>
                                                <?= sanitize(explode('(', $ai['nama'])[0]); ?>
                                                <?= $ai['confidence']; ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size:0.7rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= format_tanggal($p['created_at']); ?></td>
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
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                    Tidak ada data pengaduan yang sesuai dengan filter pencarian.
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
