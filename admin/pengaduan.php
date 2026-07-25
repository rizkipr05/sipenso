<?php
$page_title = "Kelola Pengaduan - SIPENSO";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/classifier.php';

check_role(['admin']);

// Handle Delete Complaint
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $del_id = (int)$_GET['delete'];
    if ($pdo) {
        $stmt = $pdo->prepare("DELETE FROM pengaduan WHERE id = :id");
        $stmt->execute(['id' => $del_id]);
        set_flash('success', 'Data pengaduan berhasil dihapus.');
        header('Location: ' . base_url('admin/pengaduan.php'));
        exit;
    }
}

// Filters
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
    $query .= " AND (p.nomor_tiket LIKE :search OR p.judul LIKE :search OR u.nama_lengkap LIKE :search)";
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
            <?= get_flash(); ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-folder-tree text-primary me-2"></i> Kelola Seluruh Data Pengaduan</h3>
                    <p class="text-muted small mb-0">Master database seluruh laporan masyarakat terdaftar dalam sistem</p>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card card-custom border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form action="" method="GET" class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="q" class="form-control bg-light" placeholder="Cari nomor tiket / nama..." value="<?= htmlspecialchars($search); ?>">
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
                        <div class="col-md-3 d-flex gap-1">
                            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="fa-solid fa-filter me-1"></i> Filter Data</button>
                            <a href="<?= base_url('admin/pengaduan.php'); ?>" class="btn btn-light border rounded-3"><i class="fa-solid fa-rotate"></i></a>
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
                                    <th>Pelapor</th>
                                    <th>Judul &amp; Kategori</th>
                                    <th><i class="fa-solid fa-brain text-primary me-1"></i> Saran Model AI</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Prioritas</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pengaduan_list)): ?>
                                    <?php foreach ($pengaduan_list as $p): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace fw-bold text-primary"><?= sanitize($p['nomor_tiket']); ?></td>
                                            <td>
                                                <span class="fw-semibold d-block"><?= sanitize($p['nama_pelapor']); ?></span>
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
                                                ?>
                                                <?php if ($ai['kode']): ?>
                                                    <span class="badge rounded-pill" style="font-size:0.7rem;background:<?= $match ? '#19875430' : '#ffc10730'; ?>;color:<?= $match ? '#198754' : '#856404'; ?>;border:1px solid <?= $match ? '#19875488' : '#ffc10788'; ?>;">
                                                        <i class="fa-solid fa-brain me-1"></i>
                                                        <?= sanitize(explode('(', $ai['nama'])[0]); ?>
                                                        <?= $ai['confidence']; ?>%
                                                        <?= $match ? '✓' : '⚠'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border" style="font-size:0.7rem;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= format_tanggal($p['created_at']); ?></td>
                                            <td><?= get_priority_badge($p['prioritas']); ?></td>
                                            <td><?= get_status_badge($p['status']); ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url('petugas/detail_pengaduan.php?id=' . $p['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                                    <i class="fa-solid fa-eye me-1"></i> Detail
                                                </a>
                                                <a href="<?= base_url('admin/pengaduan.php?delete=' . $p['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus permanen pengaduan ini beserta seluruh lampiran dan tanggapannya?');">
                                                    <i class="fa-solid fa-trash me-1"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                            Tidak ada data pengaduan yang sesuai dengan kriteria filter.
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
