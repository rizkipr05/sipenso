<?php
$page_title = "Kelola Kategori Pengaduan - SIPENSO";
require_once __DIR__ . '/../includes/header.php';

check_role(['admin']);

$error = '';

// Handle Add / Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $kode   = sanitize($_POST['kode_kategori'] ?? '');
    $nama   = sanitize($_POST['nama_kategori'] ?? '');
    $desk   = sanitize($_POST['deskripsi'] ?? '');

    if (empty($kode) || empty($nama)) {
        $error = 'Kode Kategori dan Nama Kategori wajib diisi.';
    } else {
        if ($pdo) {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO kategori (kode_kategori, nama_kategori, deskripsi) VALUES (:kode, :nama, :desk)");
                    $stmt->execute(['kode' => $kode, 'nama' => $nama, 'desk' => $desk]);
                    set_flash('success', 'Kategori baru berhasil ditambahkan.');
                } elseif ($action === 'edit' && $id > 0) {
                    $stmt = $pdo->prepare("UPDATE kategori SET kode_kategori = :kode, nama_kategori = :nama, deskripsi = :desk WHERE id = :id");
                    $stmt->execute(['kode' => $kode, 'nama' => $nama, 'desk' => $desk, 'id' => $id]);
                    set_flash('success', 'Kategori berhasil diperbarui.');
                }
            } catch (\PDOException $e) {
                set_flash('danger', 'Kode kategori sudah digunakan atau data kategori tidak dapat disimpan.');
            }
            header('Location: ' . base_url('admin/kategori.php'));
            exit;
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $del_id = (int)$_GET['delete'];
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = :id");
            $stmt->execute(['id' => $del_id]);
            set_flash('success', 'Kategori berhasil dihapus.');
        } catch (\PDOException $e) {
            set_flash('danger', 'Tidak dapat menghapus kategori yang sedang digunakan dalam data pengaduan.');
        }
        header('Location: ' . base_url('admin/kategori.php'));
        exit;
    }
}

$kategori_list = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT k.*, COUNT(p.id) AS total_pengaduan 
                        FROM kategori k 
                        LEFT JOIN pengaduan p ON k.id = p.kategori_id 
                        GROUP BY k.id 
                        ORDER BY k.nama_kategori ASC");
    $kategori_list = $stmt->fetchAll();
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
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-tags text-primary me-2"></i> Kelola Kategori Pengaduan</h3>
                    <p class="text-muted small mb-0">Pengaturan daftar kategori masalah sosial di Dinas Sosial</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                    <i class="fa-solid fa-plus-circle me-2"></i> Tambah Kategori Baru
                </button>
            </div>

            <div class="card card-custom border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Kode Kategori</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi Kategori</th>
                                    <th>Total Pengaduan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($kategori_list)): ?>
                                    <?php foreach ($kategori_list as $idx => $kat): ?>
                                        <tr>
                                            <td class="ps-4 text-muted"><?= $idx + 1; ?></td>
                                            <td><code><?= sanitize($kat['kode_kategori']); ?></code></td>
                                            <td><span class="fw-bold text-dark"><?= sanitize($kat['nama_kategori']); ?></span></td>
                                            <td class="small text-muted"><?= sanitize($kat['deskripsi']); ?></td>
                                            <td><span class="badge bg-info text-dark px-3 py-1 rounded-pill"><?= (int)$kat['total_pengaduan']; ?> Laporan</span></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditKategori"
                                                    data-id="<?= (int)$kat['id']; ?>" data-kode="<?= sanitize($kat['kode_kategori']); ?>" data-nama="<?= sanitize($kat['nama_kategori']); ?>" data-deskripsi="<?= sanitize($kat['deskripsi']); ?>">
                                                    <i class="fa-solid fa-edit me-1"></i> Edit
                                                </button>
                                                <a href="<?= base_url('admin/kategori.php?delete=' . $kat['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus kategori ini?');">
                                                    <i class="fa-solid fa-trash me-1"></i> Hapus
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

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1"><div class="modal-dialog"><div class="modal-content border-0 shadow-lg rounded-4"><div class="modal-header bg-primary text-white p-3"><h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Kategori</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="editKategoriId"><div class="modal-body p-4"><div class="mb-3"><label class="form-label">Kode Kategori <span class="text-danger">*</span></label><input type="text" name="kode_kategori" id="editKategoriKode" class="form-control bg-light" required></div><div class="mb-3"><label class="form-label">Nama Kategori <span class="text-danger">*</span></label><input type="text" name="nama_kategori" id="editKategoriNama" class="form-control bg-light" required></div><div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="editKategoriDeskripsi" rows="3" class="form-control bg-light"></textarea></div></div><div class="modal-footer bg-light p-3"><button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Perubahan</button></div></form></div></div></div>
<script>document.getElementById('modalEditKategori').addEventListener('show.bs.modal', function (event) { const button = event.relatedTarget; this.querySelector('#editKategoriId').value = button.dataset.id; this.querySelector('#editKategoriKode').value = button.dataset.kode; this.querySelector('#editKategoriNama').value = button.dataset.nama; this.querySelector('#editKategoriDeskripsi').value = button.dataset.deskripsi; });</script>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Tambah Kategori Pengaduan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Kode Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kode_kategori" class="form-control bg-light" placeholder="Contoh: KAT-LANSIA" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="form-control bg-light" placeholder="Contoh: Penanganan Lansia Terlantarkan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Deskripsi</label>
                        <textarea name="deskripsi" rows="3" class="form-control bg-light" placeholder="Jelaskan ruang lingkup kategori ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Tambah Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
