<?php
$page_title = "Kriteria & Aturan Prioritas - SIPENSO";
require_once __DIR__ . '/../includes/header.php';

check_role(['admin']);

// Handle Add / Edit Kriteria
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $nama   = sanitize($_POST['nama_kriteria'] ?? '');
    $bobot  = (int)($_POST['skor_bobot'] ?? 1);
    $desk   = sanitize($_POST['deskripsi'] ?? '');

    if (!empty($nama)) {
        if ($pdo) {
            $bobot = max(1, min(10, $bobot));
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO kriteria_prioritas (nama_kriteria, skor_bobot, deskripsi) VALUES (:nama, :bobot, :desk)");
                $stmt->execute(['nama' => $nama, 'bobot' => $bobot, 'desk' => $desk]);
                set_flash('success', 'Kriteria prioritas berhasil ditambahkan.');
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $pdo->prepare("UPDATE kriteria_prioritas SET nama_kriteria = :nama, skor_bobot = :bobot, deskripsi = :desk WHERE id = :id");
                $stmt->execute(['nama' => $nama, 'bobot' => $bobot, 'desk' => $desk, 'id' => $id]);
                set_flash('success', 'Kriteria prioritas berhasil diperbarui.');
            }
            header('Location: ' . base_url('admin/kriteria.php'));
            exit;
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $del_id = (int)$_GET['delete'];
    if ($pdo) {
        $stmt = $pdo->prepare("DELETE FROM kriteria_prioritas WHERE id = :id");
        $stmt->execute(['id' => $del_id]);
        set_flash('success', 'Kriteria prioritas berhasil dihapus.');
        header('Location: ' . base_url('admin/kriteria.php'));
        exit;
    }
}

$kriteria_list = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM kriteria_prioritas ORDER BY skor_bobot DESC");
    $kriteria_list = $stmt->fetchAll();
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
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-sliders text-primary me-2"></i> Kelola Kriteria &amp; Aturan Prioritas</h3>
                    <p class="text-muted small mb-0">Aturan matriks pembobotan prioritas pengaduan Dinas Sosial (Rule-based Non-AI)</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahKriteria">
                    <i class="fa-solid fa-plus-circle me-2"></i> Tambah Kriteria Prioritas
                </button>
            </div>

            <!-- Priority Threshold Explanation Card -->
            <div class="card card-custom border-0 shadow-sm mb-4 bg-dark text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2 text-warning"><i class="fa-solid fa-calculator me-2"></i> Rumus Penentuan Prioritas Otomatis:</h5>
                    <p class="text-slate-300 small mb-2">Penentuan skala prioritas dihitung dari kombinasi <strong>Skor Bobot Dampak Laporan</strong> + <strong>Skala Jumlah Warga Terdampak</strong>:</p>
                    <div class="d-flex flex-wrap gap-2 text-xs">
                        <span class="badge bg-danger px-3 py-2">Skor &ge; 9 Poin: MENDESAK</span>
                        <span class="badge bg-warning text-dark px-3 py-2">Skor 6 - 8 Poin: TINGGI</span>
                        <span class="badge bg-info text-dark px-3 py-2">Skor 4 - 5 Poin: SEDANG</span>
                        <span class="badge bg-secondary px-3 py-2">Skor &lt; 4 Poin: RENDAH</span>
                    </div>
                </div>
            </div>

            <div class="card card-custom border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Kriteria Prioritas</th>
                                    <th>Skor Bobot (Poin)</th>
                                    <th>Deskripsi Kriteria</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($kriteria_list)): ?>
                                    <?php foreach ($kriteria_list as $idx => $kr): ?>
                                        <tr>
                                            <td class="ps-4 text-muted"><?= $idx + 1; ?></td>
                                            <td><span class="fw-bold text-dark"><?= sanitize($kr['nama_kriteria']); ?></span></td>
                                            <td><span class="badge bg-primary px-3 py-1 rounded-pill"><?= (int)$kr['skor_bobot']; ?> Poin</span></td>
                                            <td class="small text-muted"><?= sanitize($kr['deskripsi']); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditKriteria"
                                                    data-id="<?= (int)$kr['id']; ?>" data-nama="<?= sanitize($kr['nama_kriteria']); ?>" data-bobot="<?= (int)$kr['skor_bobot']; ?>" data-deskripsi="<?= sanitize($kr['deskripsi']); ?>">
                                                    <i class="fa-solid fa-edit me-1"></i> Edit
                                                </button>
                                                <a href="<?= base_url('admin/kriteria.php?delete=' . $kr['id']); ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus kriteria ini?');">
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

<!-- Modal Edit Kriteria -->
<div class="modal fade" id="modalEditKriteria" tabindex="-1"><div class="modal-dialog"><div class="modal-content border-0 shadow-lg rounded-4"><div class="modal-header bg-primary text-white p-3"><h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Kriteria Prioritas</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="editKriteriaId"><div class="modal-body p-4"><div class="mb-3"><label class="form-label">Nama Kriteria <span class="text-danger">*</span></label><input type="text" name="nama_kriteria" id="editKriteriaNama" class="form-control bg-light" required></div><div class="mb-3"><label class="form-label">Skor Bobot Poin <span class="text-danger">*</span></label><input type="number" name="skor_bobot" id="editKriteriaBobot" min="1" max="10" class="form-control bg-light" required></div><div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="editKriteriaDeskripsi" rows="3" class="form-control bg-light"></textarea></div></div><div class="modal-footer bg-light p-3"><button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Perubahan</button></div></form></div></div></div>
<script>document.getElementById('modalEditKriteria').addEventListener('show.bs.modal', function (event) { const button = event.relatedTarget; this.querySelector('#editKriteriaId').value = button.dataset.id; this.querySelector('#editKriteriaNama').value = button.dataset.nama; this.querySelector('#editKriteriaBobot').value = button.dataset.bobot; this.querySelector('#editKriteriaDeskripsi').value = button.dataset.deskripsi; });</script>

<!-- Modal Tambah Kriteria -->
<div class="modal fade" id="modalTambahKriteria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Tambah Kriteria Prioritas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kriteria" class="form-control bg-light" placeholder="Contoh: Ancaman Keselamatan Balita & Lansia" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Skor Bobot Poin <span class="text-danger">*</span></label>
                        <input type="number" name="skor_bobot" min="1" max="10" class="form-control bg-light" value="3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Deskripsi</label>
                        <textarea name="deskripsi" rows="3" class="form-control bg-light" placeholder="Penjelasan kriteria pembobotan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Tambah Kriteria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
