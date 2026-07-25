<?php
$page_title = "Verifikasi & Penanganan Pengaduan - SIPENSO";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../config/classifier.php';

check_role(['petugas', 'admin']);

$petugas_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$pengaduan = null;
$kategori_list = [];
$lampiran_list = [];
$tanggapan_list = [];
$riwayat_list = [];
$error = '';
$success = '';

if ($id > 0 && $pdo) {
    // 1. Fetch Complaint Data
    $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, k.kode_kategori, u.nama_lengkap AS nama_pelapor, u.nik, u.no_hp, u.email, u.alamat AS alamat_pelapor 
                           FROM pengaduan p 
                           JOIN kategori k ON p.kategori_id = k.id 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $pengaduan = $stmt->fetch();

    if ($pengaduan) {
        $stmtKat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
        $kategori_list = $stmtKat->fetchAll();

        $stmtLamp = $pdo->prepare("SELECT * FROM lampiran WHERE pengaduan_id = :pid ORDER BY id ASC");
        $stmtLamp->execute(['pid' => $id]);
        $lampiran_list = $stmtLamp->fetchAll();

        $stmtResp = $pdo->prepare("SELECT t.*, u.nama_lengkap AS nama_petugas 
                                   FROM tanggapan t 
                                   JOIN users u ON t.petugas_id = u.id 
                                   WHERE t.pengaduan_id = :pid 
                                   ORDER BY t.created_at DESC");
        $stmtResp->execute(['pid' => $id]);
        $tanggapan_list = $stmtResp->fetchAll();

        $stmtLog = $pdo->prepare("SELECT r.*, u.nama_lengkap AS nama_user 
                                  FROM riwayat_status r 
                                  JOIN users u ON r.user_id = u.id 
                                  WHERE r.pengaduan_id = :pid 
                                  ORDER BY r.created_at ASC");
        $stmtLog->execute(['pid' => $id]);
        $riwayat_list = $stmtLog->fetchAll();
    }
}

if (!$pengaduan) {
    set_flash('danger', 'Data pengaduan tidak ditemukan.');
    header('Location: ' . base_url('petugas/dashboard.php'));
    exit;
}

// Handle Form Submission: Update Classification, Priority & Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update'])) {
    $kategori_id     = (int)($_POST['kategori_id'] ?? $pengaduan['kategori_id']);
    $tingkat_dampak  = sanitize($_POST['tingkat_dampak'] ?? $pengaduan['tingkat_dampak']);
    $jumlah_terdampak= max(1, (int)($_POST['jumlah_terdampak'] ?? $pengaduan['jumlah_terdampak']));
    $status_baru     = sanitize($_POST['status_baru'] ?? $pengaduan['status']);
    $catatan_update  = sanitize($_POST['catatan_update'] ?? '');
    $isi_tanggapan   = sanitize($_POST['isi_tanggapan'] ?? '');

    try {
        $pdo->beginTransaction();

        // 1. Recalculate Rule-Based Priority Matrix (No AI/ML)
        $calc = calculate_priority($tingkat_dampak, $jumlah_terdampak);
        $skor_prioritas = $calc['skor'];
        $prioritas = $calc['prioritas'];

        $status_lama = $pengaduan['status'];

        // 2. Update Pengaduan
        $stmtUpd = $pdo->prepare("UPDATE pengaduan SET 
                                    kategori_id = :kat, 
                                    tingkat_dampak = :dampak, 
                                    jumlah_terdampak = :jumlah, 
                                    skor_prioritas = :skor, 
                                    prioritas = :prio, 
                                    status = :st 
                                  WHERE id = :id");
        $stmtUpd->execute([
            'kat'    => $kategori_id,
            'dampak' => $tingkat_dampak,
            'jumlah' => $jumlah_terdampak,
            'skor'   => $skor_prioritas,
            'prio'   => $prioritas,
            'st'     => $status_baru,
            'id'     => $id
        ]);

        // 3. Insert Tanggapan (if text provided)
        if (!empty($isi_tanggapan)) {
            $stmtTgp = $pdo->prepare("INSERT INTO tanggapan (pengaduan_id, petugas_id, isi_tanggapan, status_tanggapan) VALUES (:pid, :petugas, :isi, :st)");
            $stmtTgp->execute([
                'pid'     => $id,
                'petugas' => $petugas_id,
                'isi'     => $isi_tanggapan,
                'st'      => $status_baru
            ]);
        }

        // 4. File Attachment Upload (Bukti Penyelesaian Petugas)
        if (isset($_FILES['bukti_penyelesaian']) && $_FILES['bukti_penyelesaian']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['bukti_penyelesaian']['tmp_name'];
            $file_name = $_FILES['bukti_penyelesaian']['name'];
            $file_size = $_FILES['bukti_penyelesaian']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'bukti_solusi_' . $id . '_' . time() . '.' . $file_ext;
                $target_dir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

                if (move_uploaded_file($file_tmp, $target_dir . $new_filename)) {
                    $stmtLamp = $pdo->prepare("INSERT INTO lampiran (pengaduan_id, nama_file, tipe_file, ukuran_file, jenis_lampiran) VALUES (:pid, :fname, :ftype, :fsize, 'bukti_penyelesaian')");
                    $stmtLamp->execute([
                        'pid'   => $id,
                        'fname' => $new_filename,
                        'ftype' => $_FILES['bukti_penyelesaian']['type'],
                        'fsize' => $file_size
                    ]);
                }
            }
        }

        // 5. Audit Log (Riwayat Status) if status changed or note provided
        if ($status_lama !== $status_baru || !empty($catatan_update)) {
            $stmtLog = $pdo->prepare("INSERT INTO riwayat_status (pengaduan_id, user_id, status_lama, status_baru, catatan) VALUES (:pid, :uid, :slama, :sbaru, :catatan)");
            $stmtLog->execute([
                'pid'     => $id,
                'uid'     => $petugas_id,
                'slama'   => $status_lama,
                'sbaru'   => $status_baru,
                'catatan' => !empty($catatan_update) ? $catatan_update : 'Perubahan status & prioritas penanganan oleh petugas'
            ]);
        }

        // Log activity
        log_activity($petugas_id, 'Verifikasi Pengaduan', 'Memperbarui status/kategori tiket ' . $pengaduan['nomor_tiket'] . ' ke ' . $status_baru, $pdo);

        $pdo->commit();
        set_flash('success', 'Data pengaduan berhasil diperbarui dan tanggapan telah disimpan.');
        header('Location: ' . base_url('petugas/detail_pengaduan.php?id=' . $id));
        exit;

    } catch (\Exception $e) {
        $pdo->rollBack();
        $error = 'Gagal memperbarui data: ' . $e->getMessage();
    }
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">
            <?= get_flash(); ?>

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="<?= base_url('petugas/dashboard.php'); ?>" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Antrean Petugas
        </a>
        <div>
            <button onclick="window.print();" class="btn btn-outline-dark rounded-pill px-3 no-print">
                <i class="fa-solid fa-print me-1"></i> Cetak Lembar Kerja Petugas
            </button>
        </div>
    </div>

    <div class="row g-4 print-container">
        <!-- Left Column: Complaint Details & Timeline -->
        <div class="col-lg-7">
            <div class="card card-custom border-0 shadow-lg mb-4">
                <div class="card-header bg-dark text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary font-monospace fs-6 px-3 mb-1"><?= sanitize($pengaduan['nomor_tiket']); ?></span>
                        <h4 class="fw-bold mb-0 text-white"><?= sanitize($pengaduan['judul']); ?></h4>
                    </div>
                    <div class="text-end">
                        <?= get_status_badge($pengaduan['status']); ?>
                        <div class="mt-1"><?= get_priority_badge($pengaduan['prioritas']); ?></div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Workflow Progress Stepper -->
                    <div class="mb-4 no-print">
                        <h6 class="fw-bold text-muted text-uppercase text-xs tracking-wider mb-3"><i class="fa-solid fa-route me-1"></i> Alur Progres Penanganan:</h6>
                        <div class="timeline-stepper">
                            <?php 
                            $statuses = ['Pengaduan Masuk', 'Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses', 'Selesai'];
                            $current_status = $pengaduan['status'];
                            $current_index = array_search($current_status, $statuses);
                            if ($current_status == 'Ditolak') $current_index = -1;
                            
                            foreach ($statuses as $idx => $st):
                                $class = '';
                                if ($current_status != 'Ditolak') {
                                    if ($idx < $current_index) $class = 'completed';
                                    elseif ($idx == $current_index) $class = 'active';
                                }
                            ?>
                            <div class="step-item <?= $class; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid <?= $idx < $current_index ? 'fa-check' : ($idx == $current_index ? 'fa-spinner fa-spin' : 'fa-circle'); ?>"></i>
                                </div>
                                <div class="step-title"><?= $st; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-file-text text-primary me-2"></i> Isi Pengaduan Masyarakat:</h6>
                    <div class="p-3 bg-light rounded-3 text-dark mb-3" style="white-space: pre-line; line-height: 1.6;">
                        <?= sanitize($pengaduan['isi_laporan']); ?>
                    </div>

                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-location-dot text-danger me-2"></i> Lokasi Kejadian:</h6>
                    <p class="bg-light p-3 rounded-3 text-muted mb-4"><?= sanitize($pengaduan['lokasi_kejadian']); ?></p>

                    <!-- Attachment Files -->
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-paperclip text-primary me-2"></i> Bukti &amp; Dokumen Lampiran:</h6>
                    <?php if (!empty($lampiran_list)): ?>
                        <div class="list-group mb-4">
                            <?php foreach ($lampiran_list as $f): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid <?= $f['jenis_lampiran'] == 'bukti_penyelesaian' ? 'fa-circle-check text-success' : 'fa-file-image text-primary'; ?> fa-lg"></i>
                                        <div>
                                            <span class="fw-semibold d-block"><?= sanitize($f['nama_file']); ?></span>
                                            <span class="badge <?= $f['jenis_lampiran'] == 'bukti_penyelesaian' ? 'bg-success' : 'bg-secondary'; ?> text-uppercase" style="font-size: 0.65rem;">
                                                <?= $f['jenis_lampiran'] == 'bukti_penyelesaian' ? 'Bukti Penyelesaian Petugas' : 'Bukti Pelapor'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="<?= base_url('assets/uploads/' . $f['nama_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="fa-solid fa-eye me-1"></i> Lihat Berkas
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small italic mb-4">Tidak ada berkas lampiran.</p>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- Existing Response Logs -->
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-primary me-2"></i> Riwayat Tanggapan Petugas:</h6>
                    <?php if (!empty($tanggapan_list)): ?>
                        <div class="d-flex flex-column gap-3 mb-4">
                            <?php foreach ($tanggapan_list as $tgp): ?>
                                <div class="card border-0 bg-light rounded-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark"><i class="fa-solid fa-user-shield text-primary me-1"></i> <?= sanitize($tgp['nama_petugas']); ?></span>
                                            <small class="text-muted"><?= format_tanggal($tgp['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0 small text-slate-800"><?= nl2br(sanitize($tgp['isi_tanggapan'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small italic mb-4">Belum ada tanggapan petugas sebelumnya.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Right Column: Petugas Control Panel Form -->
        <div class="col-lg-5 no-print">

            <?php
            // ----- Server-side auto-classification of this complaint -----
            $classify_text = ($pengaduan['judul'] ?? '') . ' ' . ($pengaduan['isi_laporan'] ?? '');
            $classify_result = classify_complaint($classify_text, $pdo);
            $colorMap = ['success'=>'#198754','info'=>'#0dcaf0','warning'=>'#ffc107','danger'=>'#dc3545','secondary'=>'#6c757d','primary'=>'#0d6efd'];
            ?>

            <!-- AI Classification Result Panel -->
            <div class="card card-custom border-0 shadow-sm mb-3">
                <div class="card-header border-0 p-3" style="background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%);">
                    <h6 class="fw-bold mb-0 text-white"><i class="fa-solid fa-brain me-2 text-primary"></i> Hasil Klasifikasi Model</h6>
                    <p class="text-white-50 mb-0" style="font-size:0.72rem;">Analisis otomatis berdasarkan teks laporan</p>
                </div>
                <div class="card-body p-3">
                    <?php if ($classify_result['kode']): ?>
                        <?php $winColor = $colorMap[$classify_result['warna']] ?? '#6c757d'; ?>
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border mb-3"
                            style="background:<?= $winColor; ?>18; border-color:<?= $winColor; ?>44 !important;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:38px;height:38px;min-width:38px;background:<?= $winColor; ?>;">
                                <i class="fa-solid <?= $classify_result['ikon']; ?>"></i>
                            </div>
                            <div>
                                <div class="text-xs text-muted">Saran Kategori</div>
                                <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= sanitize($classify_result['nama']); ?></div>
                                <div class="text-xs"
                                    style="color:<?= $winColor; ?>">
                                    Confidence: <strong><?= $classify_result['confidence']; ?>%</strong>
                                    <?php if ($pengaduan['kategori_id'] == ($classify_result['kategori_id'] ?? -1)): ?>
                                        <span class="badge bg-success ms-1" style="font-size:0.65rem;">✓ Sesuai</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">⚠ Berbeda</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-muted fw-bold text-uppercase mb-2">Skor Per Kategori:</p>
                        <?php foreach ($classify_result['per_kategori'] as $kode_cat => $cat): ?>
                            <?php $barColor = $colorMap[$cat['warna']] ?? '#6c757d'; ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between" style="font-size:0.72rem;">
                                    <span class="text-muted"><?= sanitize(explode('(', $cat['label'])[0]); ?></span>
                                    <span class="fw-bold" style="color:<?= $barColor; ?>"><?= $cat['confidence']; ?>%</span>
                                </div>
                                <div class="progress" style="height:5px;border-radius:4px;">
                                    <div class="progress-bar" style="width:<?= $cat['confidence']; ?>%;background:<?= $barColor; ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small text-center mb-0"><i class="fa-solid fa-question-circle me-1"></i> Tidak cukup teks untuk diklasifikasikan.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-custom border-primary shadow-lg">
                <div class="card-header bg-primary text-white p-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-sliders me-2"></i> Form Aksi &amp; Penanganan Petugas</h5>
                    <small class="text-white-50">Klasifikasi, Evaluasi Prioritas Rule-Based, &amp; Status</small>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- 1. Klasifikasi Kategori -->
                        <div class="mb-3">
                            <label class="form-label font-semibold"><i class="fa-solid fa-tags text-primary me-1"></i> 1. Klasifikasi Kategori</label>
                            <select name="kategori_id" class="form-select bg-light" required>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id']; ?>" <?= $pengaduan['kategori_id'] == $kat['id'] ? 'selected' : ''; ?>>
                                        <?= sanitize($kat['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 2. Evaluasi Rule-Based Prioritas Matrix (Dinas Sosial Criteria) -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-layer-group text-warning me-1"></i> 2. Penentuan Prioritas Rule-Based</h6>
                            <p class="text-muted text-xs mb-3">Kriteria Dinas Sosial menghitung bobot dampak &amp; jumlah terdampak secara deterministik.</p>
                            
                            <div class="mb-2">
                                <label class="form-label text-xs font-semibold">Tingkat Dampak Masalah</label>
                                <select name="tingkat_dampak" class="form-select form-select-sm" required>
                                    <option value="rendah" <?= $pengaduan['tingkat_dampak'] == 'rendah' ? 'selected' : ''; ?>>Rendah (Dampak individual)</option>
                                    <option value="sedang" <?= $pengaduan['tingkat_dampak'] == 'sedang' ? 'selected' : ''; ?>>Sedang (Dampak keluarga/RT)</option>
                                    <option value="tinggi" <?= $pengaduan['tingkat_dampak'] == 'tinggi' ? 'selected' : ''; ?>>Tinggi (Dampak RW/Kelurahan)</option>
                                    <option value="mendesak" <?= $pengaduan['tingkat_dampak'] == 'mendesak' ? 'selected' : ''; ?>>Mendesak (Keselamatan jiwa/bencana)</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-xs font-semibold">Jumlah Warga Terdampak</label>
                                <input type="number" name="jumlah_terdampak" class="form-control form-select-sm" min="1" value="<?= (int)$pengaduan['jumlah_terdampak']; ?>" required>
                            </div>

                            <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center mt-2">
                                <span class="text-xs text-muted">Hasil Evaluasi Saat Ini:</span>
                                <div>
                                    <?= get_priority_badge($pengaduan['prioritas']); ?>
                                    <span class="badge bg-dark text-xs"><?= (int)$pengaduan['skor_prioritas']; ?> Poin</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Update Status Alur -->
                        <div class="mb-3">
                            <label class="form-label font-semibold"><i class="fa-solid fa-rotate text-info me-1"></i> 3. Update Status Penanganan</label>
                            <select name="status_baru" class="form-select bg-light fw-bold" required>
                                <option value="Pengaduan Masuk" <?= $pengaduan['status'] == 'Pengaduan Masuk' ? 'selected' : ''; ?>>1. Pengaduan Masuk</option>
                                <option value="Diverifikasi" <?= $pengaduan['status'] == 'Diverifikasi' ? 'selected' : ''; ?>>2. Diverifikasi</option>
                                <option value="Diklasifikasikan" <?= $pengaduan['status'] == 'Diklasifikasikan' ? 'selected' : ''; ?>>3. Diklasifikasikan</option>
                                <option value="Prioritas Ditentukan" <?= $pengaduan['status'] == 'Prioritas Ditentukan' ? 'selected' : ''; ?>>4. Prioritas Ditentukan</option>
                                <option value="Diproses" <?= $pengaduan['status'] == 'Diproses' ? 'selected' : ''; ?>>5. Diproses</option>
                                <option value="Selesai" <?= $pengaduan['status'] == 'Selesai' ? 'selected' : ''; ?>>6. Selesai (Tindak Lanjut Tuntas)</option>
                                <option value="Ditolak" <?= $pengaduan['status'] == 'Ditolak' ? 'selected' : ''; ?>>7. Ditolak (Tidak Memenuhi Syarat)</option>
                            </select>
                        </div>

                        <!-- 4. Tanggapan Petugas -->
                        <div class="mb-3">
                            <label class="form-label font-semibold"><i class="fa-solid fa-comment-dots text-primary me-1"></i> 4. Tanggapan Resmi Petugas</label>
                            <textarea name="isi_tanggapan" rows="3" class="form-control bg-light" placeholder="Tuliskan instruksi, hasil tindak lanjut lapangan, atau alasan penolakan..."></textarea>
                        </div>

                        <!-- 5. Bukti Penyelesaian -->
                        <div class="mb-3">
                            <label class="form-label font-semibold"><i class="fa-solid fa-upload text-success me-1"></i> 5. Unggah Bukti Penyelesaian (Opsional)</label>
                            <input type="file" name="bukti_penyelesaian" class="form-control form-control-sm bg-light" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <small class="text-muted text-xs">Foto penanganan lapangan atau surat keputusan resmi.</small>
                        </div>

                        <!-- 6. Catatan Internal Audit -->
                        <div class="mb-4">
                            <label class="form-label text-xs font-semibold">Catatan Log Perubahan (Internal)</label>
                            <input type="text" name="catatan_update" class="form-control form-control-sm bg-light" placeholder="Catatan singkat perubahan status...">
                        </div>

                        <button type="submit" name="action_update" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan &amp; Tanggapan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
