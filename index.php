<?php
$page_title = "Sistem Pengaduan Masyarakat Dinas Sosial (SIPENSO)";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Quick Ticket Search Handler
$search_ticket = sanitize($_GET['tiket'] ?? '');
$ticket_result = null;
$ticket_error = '';

if (!empty($search_ticket)) {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, u.nama_lengkap AS nama_pelapor 
                               FROM pengaduan p 
                               JOIN kategori k ON p.kategori_id = k.id 
                               JOIN users u ON p.user_id = u.id 
                               WHERE p.nomor_tiket = :tiket LIMIT 1");
        $stmt->execute(['tiket' => $search_ticket]);
        $ticket_result = $stmt->fetch();
        if (!$ticket_result) {
            $ticket_error = 'Nomor tiket "' . $search_ticket . '" tidak ditemukan. Silakan periksa kembali nomor tiket Anda.';
        } else {
            // Fetch responses
            $stmtResp = $pdo->prepare("SELECT t.*, u.nama_lengkap AS nama_petugas 
                                       FROM tanggapan t 
                                       JOIN users u ON t.petugas_id = u.id 
                                       WHERE t.pengaduan_id = :pid 
                                       ORDER BY t.created_at DESC");
            $stmtResp->execute(['pid' => $ticket_result['id']]);
            $ticket_result['tanggapan'] = $stmtResp->fetchAll();
        }
    }
}

// Stats summary
$stats = ['total' => 0, 'masuk' => 0, 'proses' => 0, 'selesai' => 0];
if ($pdo) {
    $stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM pengaduan GROUP BY status");
    while ($row = $stmt->fetch()) {
        $stats['total'] += $row['count'];
        if ($row['status'] == 'Pengaduan Masuk') $stats['masuk'] += $row['count'];
        elseif (in_array($row['status'], ['Diproses', 'Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan'])) $stats['proses'] += $row['count'];
        elseif ($row['status'] == 'Selesai') $stats['selesai'] += $row['count'];
    }
}
?>

<!-- Hero Section -->
<section class="hero-section text-center text-md-start">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <span class="badge bg-primary px-3 py-2 rounded-pill text-uppercase mb-3"><i class="fa-solid fa-bullhorn me-1"></i> Layanan Pengaduan Resmi Dinas Sosial</span>
                <h1 class="display-4 fw-extrabold mb-3">Sampaikan Pengaduan Sosial Secara <span class="brand-title">Cepat &amp; Transparan</span></h1>
                <p class="lead text-slate-300 mb-4">Wadah resmi masyarakat untuk menyampaikan pengaduan terkait bantuan sosial, perlindungan anak &amp; lansia, disabilitas, dan masalah kesejahteraan sosial lainnya.</p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= base_url('pelapor/buat_pengaduan.php'); ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-lg">
                        <i class="fa-solid fa-paper-plane me-2"></i> Buat Pengaduan Sekarang
                    </a>
                    <a href="#lacak-tiket" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> Cek Status Tiket
                    </a>
                </div>
            </div>

            <!-- Quick Ticket Lookup Card -->
            <div class="col-lg-5" id="lacak-tiket">
                <div class="hero-card">
                    <h5 class="fw-bold mb-3 text-white"><i class="fa-solid fa-ticket text-primary me-2"></i> Lacak Status Tiket Pengaduan</h5>
                    <p class="text-slate-300 small mb-3">Masukkan nomor tiket pengaduan Anda (contoh: <code>TKT-20260725-A101</code>) untuk memantau progres penanganan secara langsung.</p>
                    
                    <form action="" method="GET">
                        <div class="mb-3">
                            <div class="input-group input-group-lg">
                                <input type="text" name="tiket" class="form-control rounded-start-3 border-0 bg-white" placeholder="Nomor Tiket (ex: TKT-2026...)" value="<?= htmlspecialchars($search_ticket); ?>" required>
                                <button type="submit" class="btn btn-primary px-4 rounded-end-3">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between text-slate-300 text-xs mt-3">
                        <span><i class="fa-solid fa-shield-halved text-success me-1"></i> Data Terjamin Aman</span>
                        <span><i class="fa-solid fa-bolt text-warning me-1"></i> Update Real-Time</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Ticket Result Output (If Searched) -->
<?php if (!empty($search_ticket)): ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-custom border-primary shadow-lg overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-ticket me-2"></i> Detail Tiket Pengaduan: <?= sanitize($search_ticket); ?></h5>
                        <small class="text-white-50">Hasil pencarian status dan penanganan pengaduan</small>
                    </div>
                    <a href="<?= base_url(); ?>" class="btn btn-sm btn-light rounded-pill"><i class="fa-solid fa-times me-1"></i> Tutup</a>
                </div>
                <div class="card-body p-4">
                    <?php if ($ticket_error): ?>
                        <div class="alert alert-warning my-2">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $ticket_error; ?>
                        </div>
                    <?php else: ?>
                        <!-- Ticket Information -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase text-xs font-bold">Judul Pengaduan</h6>
                                <h5 class="fw-bold text-dark mb-2"><?= sanitize($ticket_result['judul']); ?></h5>
                                <div class="mb-3">
                                    <?= get_status_badge($ticket_result['status']); ?>
                                    <?= get_priority_badge($ticket_result['prioritas']); ?>
                                </div>
                                <p class="text-muted small mb-1"><strong>Pelapor:</strong> <?= sanitize($ticket_result['nama_pelapor']); ?></p>
                                <p class="text-muted small mb-1"><strong>Kategori:</strong> <?= sanitize($ticket_result['nama_kategori']); ?></p>
                                <p class="text-muted small mb-0"><strong>Tanggal Kirim:</strong> <?= format_tanggal($ticket_result['created_at']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase text-xs font-bold">Isi Pengaduan</h6>
                                <p class="bg-light p-3 rounded-3 small text-dark mb-2"><?= nl2br(sanitize($ticket_result['isi_laporan'])); ?></p>
                                <p class="text-muted small mb-0"><strong><i class="fa-solid fa-location-dot text-danger me-1"></i> Lokasi:</strong> <?= sanitize($ticket_result['lokasi_kejadian']); ?></p>
                            </div>
                        </div>

                        <hr>

                        <!-- Timeline Workflow Indicator -->
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-route text-primary me-2"></i> Alur Progres Penanganan:</h6>
                        <div class="timeline-stepper">
                            <?php 
                            $statuses = ['Pengaduan Masuk', 'Diverifikasi', 'Diklasifikasikan', 'Prioritas Ditentukan', 'Diproses', 'Selesai'];
                            $current_status = $ticket_result['status'];
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

                        <!-- Officer Response -->
                        <h6 class="fw-bold mt-4 mb-3"><i class="fa-solid fa-comments text-primary me-2"></i> Tanggapan Petugas Dinas Sosial:</h6>
                        <?php if (!empty($ticket_result['tanggapan'])): ?>
                            <?php foreach ($ticket_result['tanggapan'] as $t): ?>
                                <div class="card border-0 bg-light rounded-3 mb-2">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark"><i class="fa-solid fa-user-shield me-1 text-primary"></i> <?= sanitize($t['nama_petugas']); ?></span>
                                            <small class="text-muted"><?= format_tanggal($t['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0 small text-slate-700"><?= nl2br(sanitize($t['isi_tanggapan'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary small mb-0">Belum ada tanggapan resmi dari petugas. Pengaduan sedang dalam antrean verifikasi.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-extrabold text-primary mb-1"><?= $stats['total']; ?></h2>
                    <p class="text-muted fw-semibold mb-0"><i class="fa-solid fa-inbox me-1"></i> Total Pengaduan</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-extrabold text-warning mb-1"><?= $stats['masuk']; ?></h2>
                    <p class="text-muted fw-semibold mb-0"><i class="fa-solid fa-clock me-1"></i> Pengaduan Masuk</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-extrabold text-info mb-1"><?= $stats['proses']; ?></h2>
                    <p class="text-muted fw-semibold mb-0"><i class="fa-solid fa-spinner me-1"></i> Sedang Diproses</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-extrabold text-success mb-1"><?= $stats['selesai']; ?></h2>
                    <p class="text-muted fw-semibold mb-0"><i class="fa-solid fa-circle-check me-1"></i> Selesai Ditangani</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Flow & Features Section -->
<section class="py-5 bg-light">
    <div class="container py-3">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase text-xs tracking-wider">Alur Pelayanan</span>
            <h2 class="fw-bold">Alur Penanganan Pengaduan Masyarakat</h2>
            <p class="text-muted">Proses pengaduan ditangani secara terstruktur sesuai standar operasional prosedur Dinas Sosial.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom h-100 p-4 border-0 text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-file-pen fa-xl"></i>
                    </div>
                    <h5 class="fw-bold mb-2">1. Pengajuan Laporan</h5>
                    <p class="text-muted small mb-0">Masyarakat mendaftarkan akun dan mengisi formulir pengaduan beserta lokasi dan bukti pendukung foto/dokumen.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 p-4 border-0 text-center">
                    <div class="bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-user-gear fa-xl"></i>
                    </div>
                    <h5 class="fw-bold mb-2">2. Verifikasi &amp; Prioritas</h5>
                    <p class="text-muted small mb-0">Petugas mengklasifikasikan kategori dan menetapkan skala prioritas penanganan (Rendah, Sedang, Tinggi, Mendesak).</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom h-100 p-4 border-0 text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-circle-check fa-xl"></i>
                    </div>
                    <h5 class="fw-bold mb-2">3. Tindak Lanjut &amp; Selesai</h5>
                    <p class="text-muted small mb-0">Petugas memproses penanganan di lapangan, memberikan tanggapan resmi, serta mengunggah bukti penyelesaian.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
$show_footer = true;
require_once __DIR__ . '/includes/footer.php'; 
?>
