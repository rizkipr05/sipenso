<?php
$page_title = "Detail Pengaduan - SIPENSO";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/classifier.php';

check_role(['pelapor']);

$user_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$pengaduan = null;
$lampiran_list = [];
$tanggapan_list = [];
$riwayat_list = [];

if ($id > 0 && $pdo) {
    // 1. Complaint data
    $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, k.kode_kategori, u.nama_lengkap AS nama_pelapor, u.nik, u.no_hp, u.email 
                           FROM pengaduan p 
                           JOIN kategori k ON p.kategori_id = k.id 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = :id AND p.user_id = :uid LIMIT 1");
    $stmt->execute(['id' => $id, 'uid' => $user_id]);
    $pengaduan = $stmt->fetch();

    if ($pengaduan) {
        // 2. Attachments
        $stmtLamp = $pdo->prepare("SELECT * FROM lampiran WHERE pengaduan_id = :pid ORDER BY id ASC");
        $stmtLamp->execute(['pid' => $id]);
        $lampiran_list = $stmtLamp->fetchAll();

        // 3. Responses from officers
        $stmtResp = $pdo->prepare("SELECT t.*, u.nama_lengkap AS nama_petugas 
                                   FROM tanggapan t 
                                   JOIN users u ON t.petugas_id = u.id 
                                   WHERE t.pengaduan_id = :pid 
                                   ORDER BY t.created_at ASC");
        $stmtResp->execute(['pid' => $id]);
        $tanggapan_list = $stmtResp->fetchAll();

        // 4. Status audit trail
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
    set_flash('danger', 'Data pengaduan tidak ditemukan atau Anda tidak memiliki akses.');
    header('Location: ' . base_url('pelapor/riwayat.php'));
    exit;
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">
            <?= get_flash(); ?>

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="<?= base_url('pelapor/riwayat.php'); ?>" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Riwayat
        </a>
        <div>
            <button onclick="window.print();" class="btn btn-outline-primary rounded-pill px-3 no-print">
                <i class="fa-solid fa-print me-1"></i> Cetak Bukti Tiket
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card card-custom border-0 shadow-lg overflow-hidden mb-4 print-container">
        <!-- Card Header -->
        <div class="card-header bg-dark text-white p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary font-monospace fs-6 px-3 py-1"><?= sanitize($pengaduan['nomor_tiket']); ?></span>
                    <span class="badge bg-light text-dark"><?= sanitize($pengaduan['nama_kategori']); ?></span>
                </div>
                <h4 class="fw-bold mb-0 text-white"><?= sanitize($pengaduan['judul']); ?></h4>
            </div>
            <div class="text-md-end">
                <div class="mb-1"><?= get_status_badge($pengaduan['status']); ?></div>
                <div><?= get_priority_badge($pengaduan['prioritas']); ?></div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Timeline Stepper Progress -->
            <div class="mb-5 no-print">
                <h6 class="fw-bold text-muted text-uppercase text-xs tracking-wider mb-3"><i class="fa-solid fa-route text-primary me-2"></i> Status Progres Penanganan:</h6>
                <div class="timeline-stepper">
                    <?php 
                    $statuses = ['Pengaduan Masuk', 'Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses', 'Selesai'];
                    $current_status = $pengaduan['status'];
                    $current_index = array_search($current_status, $statuses);
                    if ($current_status == 'Ditolak') $current_index = -1;
                    
                    foreach ($statuses as $idx => $st):
                        $class = '';
                        if ($current_status == 'Ditolak') {
                            $class = '';
                        } else {
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

            <!-- Complaint Details Grid -->
            <div class="row g-4 mb-4">
                <div class="col-md-7">
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-file-text text-primary me-2"></i> Rincian &amp; Kronologi Laporan:</h6>
                    <div class="p-3 bg-light rounded-3 text-slate-800" style="white-space: pre-line; line-height: 1.6;">
                        <?= sanitize($pengaduan['isi_laporan']); ?>
                    </div>

                    <div class="mt-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-location-dot text-danger me-2"></i> Lokasi Kejadian:</h6>
                        <p class="bg-light p-3 rounded-3 text-muted mb-0"><?= sanitize($pengaduan['lokasi_kejadian']); ?></p>
                    </div>
                </div>

                <div class="col-md-5">
                    <?php
                    // ----- AI Classification result -----
                    $classify_text = ($pengaduan['judul'] ?? '') . ' ' . ($pengaduan['isi_laporan'] ?? '');
                    $classify_result = classify_complaint($classify_text, $pdo);
                    $colorMap = ['success'=>'#198754','info'=>'#0dcaf0','warning'=>'#ffc107','danger'=>'#dc3545','secondary'=>'#6c757d','primary'=>'#0d6efd'];
                    ?>

                    <!-- AI Classification Results -->
                    <div class="card card-custom border-0 shadow-sm mb-4">
                        <div class="card-header border-0 p-3" style="background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%);">
                            <h6 class="fw-bold mb-0 text-white"><i class="fa-solid fa-brain me-2 text-primary"></i> Klasifikasi Model AI</h6>
                            <p class="text-white-50 text-xs mb-0">Analisis otomatis berdasarkan rincian laporan yang dikirim</p>
                        </div>
                        <div class="card-body p-3">
                            <?php if ($classify_result['kode']): ?>
                                <?php $winColor = $colorMap[$classify_result['warna']] ?? '#6c757d'; ?>
                                <div class="d-flex align-items-center gap-2 p-2 rounded-3 border mb-3" style="background:<?= $winColor; ?>18; border-color:<?= $winColor; ?>44 !important;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width:38px;height:38px;min-width:38px;background:<?= $winColor; ?>;">
                                        <i class="fa-solid <?= $classify_result['ikon']; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs text-muted">Saran Kategori</div>
                                        <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= sanitize($classify_result['nama']); ?></div>
                                        <div class="text-xs" style="color:<?= $winColor; ?>">
                                            Confidence: <strong><?= $classify_result['confidence']; ?>%</strong>
                                            <?php if ($pengaduan['kategori_id'] == ($classify_result['kategori_id'] ?? -1)): ?>
                                                <span class="badge bg-success ms-1" style="font-size:0.65rem;">✓ Sesuai</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">⚠ Berbeda</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small text-center mb-0"><i class="fa-solid fa-question-circle me-1"></i> Tidak cukup teks untuk diklasifikasikan.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card bg-light border-0 rounded-3 p-3">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> Informasi Metrik &amp; Pelapor</h6>
                        
                        <table class="table table-sm table-borderless small mb-0">
                            <tr>
                                <th class="text-muted" style="width: 40%;">Pelapor:</th>
                                <td><?= sanitize($pengaduan['nama_pelapor']); ?> (NIK: <?= sanitize($pengaduan['nik']); ?>)</td>
                            </tr>
                            <tr>
                                <th class="text-muted">No. Telepon:</th>
                                <td><?= sanitize($pengaduan['no_hp']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Tanggal Dikirim:</th>
                                <td><?= format_tanggal($pengaduan['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Tingkat Dampak:</th>
                                <td><span class="text-uppercase fw-semibold"><?= sanitize($pengaduan['tingkat_dampak']); ?></span></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Jumlah Terdampak:</th>
                                <td><?= (int)$pengaduan['jumlah_terdampak']; ?> Orang</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Skor Prioritas:</th>
                                <td><span class="badge bg-dark"><?= (int)$pengaduan['skor_prioritas']; ?> Poin</span></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Uploaded Attachments -->
                    <div class="mt-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-paperclip text-primary me-2"></i> Lampiran Dokumen / Foto:</h6>
                        <?php if (!empty($lampiran_list)): ?>
                            <div class="list-group">
                                <?php foreach ($lampiran_list as $file): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-white border">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <i class="fa-solid fa-file-image text-primary fa-lg"></i>
                                            <div class="text-truncate">
                                                <small class="fw-semibold d-block text-truncate"><?= sanitize($file['nama_file']); ?></small>
                                                <span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;"><?= sanitize($file['jenis_lampiran']); ?></span>
                                            </div>
                                        </div>
                                        <a href="<?= base_url('assets/uploads/' . $file['nama_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fa-solid fa-download me-1"></i> Buka
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small italic">Tidak ada lampiran berkas yang diunggah.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Official Responses Thread -->
            <div class="mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-primary me-2"></i> Tanggapan Resmi Petugas Dinas Sosial</h5>
                
                <?php if (!empty($tanggapan_list)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($tanggapan_list as $tgp): ?>
                            <div class="card border-0 bg-light rounded-3 shadow-sm">
                                <div class="card-body p-3 p-md-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="fa-solid fa-user-shield"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><?= sanitize($tgp['nama_petugas']); ?></h6>
                                                <small class="text-muted">Petugas Dinas Sosial</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-white text-dark border"><?= format_tanggal($tgp['created_at']); ?></span>
                                    </div>
                                    <p class="mb-0 text-slate-800 mt-2" style="line-height: 1.6;"><?= nl2br(sanitize($tgp['isi_tanggapan'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary rounded-3">
                        <i class="fa-solid fa-clock me-2"></i> Belum ada tanggapan resmi dari petugas. Pengaduan sedang dalam penanganan antrean.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Audit Trail Log -->
            <div class="no-print">
                <h6 class="fw-bold text-muted text-uppercase text-xs tracking-wider mb-3"><i class="fa-solid fa-list-check me-2"></i> Catatan Riwayat Perubahan Status:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped text-xs mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Waktu Update</th>
                                <th>Pengubah Status</th>
                                <th>Status Lama</th>
                                <th>Status Baru</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat_list as $rw): ?>
                                <tr>
                                    <td><?= format_tanggal($rw['created_at']); ?></td>
                                    <td><?= sanitize($rw['nama_user']); ?></td>
                                    <td><?= $rw['status_lama'] ? get_status_badge($rw['status_lama']) : '-'; ?></td>
                                    <td><?= get_status_badge($rw['status_baru']); ?></td>
                                    <td><?= sanitize($rw['catatan']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
