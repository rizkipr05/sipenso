<?php
$page_title = "Buat Pengaduan Baru - SIPENSO";
$active_nav = "buat";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../config/classifier.php';

check_role(['pelapor']);

$user_id = $_SESSION['user_id'];
$error = '';
$kategori_list = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_id     = (int)($_POST['kategori_id'] ?? 0);
    $judul           = sanitize($_POST['judul'] ?? '');
    $isi_laporan     = sanitize($_POST['isi_laporan'] ?? '');
    $lokasi_kejadian = sanitize($_POST['lokasi_kejadian'] ?? '');
    $tingkat_dampak  = sanitize($_POST['tingkat_dampak'] ?? 'sedang');
    $jumlah_terdampak= max(1, (int)($_POST['jumlah_terdampak'] ?? 1));

    if (empty($judul) || empty($isi_laporan) || empty($lokasi_kejadian) || $kategori_id <= 0) {
        $error = 'Harap lengkapi semua kolom bertanda bintang (*).';
    } else {
        if ($pdo) {
            try {
                $pdo->beginTransaction();
                $nomor_tiket = generate_ticket_number();
                $calc = calculate_priority($tingkat_dampak, $jumlah_terdampak);
                $skor_prioritas = $calc['skor'];
                $prioritas = $calc['prioritas'];

                $stmt = $pdo->prepare("INSERT INTO pengaduan (nomor_tiket, user_id, kategori_id, judul, isi_laporan, lokasi_kejadian, tingkat_dampak, jumlah_terdampak, skor_prioritas, prioritas, status) 
                                       VALUES (:tiket, :uid, :kat_id, :judul, :isi, :lokasi, :dampak, :jumlah, :skor, :prio, 'Pengaduan Masuk')");
                $stmt->execute([
                    'tiket'   => $nomor_tiket,
                    'uid'     => $user_id,
                    'kat_id'  => $kategori_id,
                    'judul'   => $judul,
                    'isi'     => $isi_laporan,
                    'lokasi'  => $lokasi_kejadian,
                    'dampak'  => $tingkat_dampak,
                    'jumlah'  => $jumlah_terdampak,
                    'skor'    => $skor_prioritas,
                    'prio'    => $prioritas
                ]);
                $pengaduan_id = $pdo->lastInsertId();

                if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['lampiran']['tmp_name'];
                    $file_name = $_FILES['lampiran']['name'];
                    $file_size = $_FILES['lampiran']['size'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                    if (in_array($file_ext, $allowed_exts)) {
                        $new_filename = 'lampiran_' . $pengaduan_id . '_' . time() . '.' . $file_ext;
                        $target_dir = __DIR__ . '/../assets/uploads/';
                        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                        if (move_uploaded_file($file_tmp, $target_dir . $new_filename)) {
                            $stmtLamp = $pdo->prepare("INSERT INTO lampiran (pengaduan_id, nama_file, tipe_file, ukuran_file, jenis_lampiran) VALUES (:pid, :fname, :ftype, :fsize, 'bukti_pelapor')");
                            $stmtLamp->execute(['pid' => $pengaduan_id, 'fname' => $new_filename, 'ftype' => $_FILES['lampiran']['type'], 'fsize' => $file_size]);
                        }
                    }
                }

                $stmtLog = $pdo->prepare("INSERT INTO riwayat_status (pengaduan_id, user_id, status_lama, status_baru, catatan) VALUES (:pid, :uid, NULL, 'Pengaduan Masuk', 'Pengaduan berhasil dikirim oleh pelapor')");
                $stmtLog->execute(['pid' => $pengaduan_id, 'uid' => $user_id]);

                // Log activity
                log_activity($user_id, 'Membuat Pengaduan', 'Berhasil mengirim pengaduan baru: Tiket ' . $nomor_tiket, $pdo);

                $pdo->commit();
                set_flash('success', 'Pengaduan Anda telah berhasil terkirim! Nomor tiket Anda: <strong>' . $nomor_tiket . '</strong>');
                header('Location: ' . base_url('pelapor/detail.php?id=' . $pengaduan_id));
                exit;

            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="wrapper-admin">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content" class="bg-light">
        <div class="container-fluid">

            <div class="row g-4">
                <!-- Left: Main Form -->
                <div class="col-lg-8">
                    <div class="card card-custom border-0 shadow-lg overflow-hidden">
                        <div class="card-header bg-primary text-white p-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-paper-plane fa-lg"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-0">Formulir Pengaduan Masyarakat</h4>
                                    <p class="text-white-50 small mb-0">Isi data pengaduan Anda dengan jelas dan akurat</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i><?= $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form action="" method="POST" enctype="multipart/form-data" id="formPengaduan">

                                <!-- Judul -->
                                <div class="mb-3">
                                    <label class="form-label font-semibold">Judul Pengaduan <span class="text-danger">*</span></label>
                                    <input type="text" name="judul" id="inputJudul" class="form-control bg-light"
                                        placeholder="Contoh: Bantuan BPNT Belum Cair / Lansia Terlantarkan Butuh Pendampingan"
                                        value="<?= sanitize($_POST['judul'] ?? ''); ?>" required>
                                </div>

                                <!-- Kategori (dengan badge saran model) -->
                                <div class="mb-3">
                                    <label class="form-label font-semibold d-flex align-items-center justify-content-between">
                                        <span>Kategori Pengaduan <span class="text-danger">*</span></span>
                                        <span id="ai-suggest-badge" class="d-none">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">
                                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                                                <span id="ai-suggest-label">Saran Model</span>
                                                <span id="ai-suggest-conf" class="ms-1 fw-bold"></span>
                                            </span>
                                        </span>
                                    </label>
                                    <select name="kategori_id" id="selectKategori" class="form-select bg-light" required>
                                        <option value="">-- Pilih Kategori (Diisi otomatis oleh model) --</option>
                                        <?php foreach ($kategori_list as $kat): ?>
                                            <option value="<?= $kat['id']; ?>"
                                                data-kode="<?= sanitize($kat['kode_kategori']); ?>"
                                                <?= ($_POST['kategori_id'] ?? 0) == $kat['id'] ? 'selected' : ''; ?>>
                                                <?= sanitize($kat['nama_kategori']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="ai-suggest-info" class="d-none mt-2 p-2 rounded-3 border" style="background: #f0f7ff;">
                                        <small class="text-muted d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-robot text-primary"></i>
                                            <span id="ai-suggest-detail">Model menganalisis teks Anda...</span>
                                        </small>
                                    </div>
                                </div>

                                <!-- Dampak & Jumlah -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label font-semibold">Perkiraan Tingkat Dampak <span class="text-danger">*</span></label>
                                        <select name="tingkat_dampak" class="form-select bg-light" required>
                                            <option value="rendah">Rendah (Dampak individual/ringan)</option>
                                            <option value="sedang" selected>Sedang (Dampak keluarga/RT)</option>
                                            <option value="tinggi">Tinggi (Dampak warga se-RW/Kelurahan)</option>
                                            <option value="mendesak">Mendesak (Ancaman keselamatan jiwa/bencana)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label font-semibold">Jumlah Warga Terdampak (Orang)</label>
                                        <input type="number" name="jumlah_terdampak" class="form-control bg-light" min="1" value="1">
                                    </div>
                                </div>

                                <!-- Lokasi -->
                                <div class="mb-3">
                                    <label class="form-label font-semibold">Lokasi Kejadian Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="lokasi_kejadian" class="form-control bg-light"
                                        placeholder="Alamat detail, RT/RW, Kelurahan, Kecamatan"
                                        value="<?= sanitize($_POST['lokasi_kejadian'] ?? ''); ?>" required>
                                </div>

                                <!-- Isi Laporan -->
                                <div class="mb-3">
                                    <label class="form-label font-semibold">Isi Pengaduan / Kronologi Rincian <span class="text-danger">*</span></label>
                                    <textarea name="isi_laporan" id="inputIsi" rows="5" class="form-control bg-light"
                                        placeholder="Jelaskan secara rinci pengaduan Anda, nama warga terdampak, kronologi, serta bantuan yang dibutuhkan..." required><?= sanitize($_POST['isi_laporan'] ?? ''); ?></textarea>
                                </div>

                                <!-- Lampiran -->
                                <div class="mb-4">
                                    <label class="form-label font-semibold">Unggah Foto atau Dokumen Pendukung (Opsional)</label>
                                    <input type="file" name="lampiran" class="form-control bg-light" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                    <small class="text-muted">Format: JPG, PNG, PDF, DOCX (Maks. 5MB)</small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <a href="<?= base_url('pelapor/dashboard.php'); ?>" class="btn btn-outline-secondary rounded-pill px-4">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Kirim Pengaduan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right: AI Classification Panel -->
                <div class="col-lg-4">
                    <div class="card card-custom border-0 shadow-sm position-sticky" style="top: 80px;">
                        <div class="card-header border-0 p-3" style="background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%);">
                            <h6 class="fw-bold mb-0 text-white"><i class="fa-solid fa-brain me-2 text-primary"></i> Klasifikasi Otomatis</h6>
                            <p class="text-white-50 text-xs mb-0">Model menganalisis teks pengaduan Anda secara real-time</p>
                        </div>
                        <div class="card-body p-3" id="classifyPanel">
                            <!-- Default state -->
                            <div id="classify-empty" class="text-center py-4">
                                <i class="fa-solid fa-keyboard fa-2x text-muted mb-2 d-block"></i>
                                <p class="text-muted small mb-0">Ketik judul atau isi laporan untuk melihat saran kategori dari model.</p>
                            </div>
                            <!-- Loading state -->
                            <div id="classify-loading" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                <p class="text-muted small mb-0">Menganalisis teks...</p>
                            </div>
                            <!-- Result state -->
                            <div id="classify-result" class="d-none">
                                <div class="d-flex align-items-center gap-2 mb-3 p-3 rounded-3 border" id="classify-winner-box">
                                    <div id="classify-winner-icon" class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width:40px;height:40px;min-width:40px;">
                                        <i class="fa-solid fa-tags"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs text-muted">Saran Kategori Terkuat</div>
                                        <div class="fw-bold text-dark" id="classify-winner-name">-</div>
                                        <div class="text-xs text-muted">Confidence: <strong id="classify-winner-conf">-</strong></div>
                                    </div>
                                </div>

                                <div class="mb-1">
                                    <p class="text-xs text-muted fw-bold text-uppercase mb-2">Skor Per Kategori:</p>
                                    <div id="classify-bars"></div>
                                </div>

                                <button type="button" id="btn-apply-category" class="btn btn-sm btn-primary w-100 mt-2 rounded-3 fw-semibold">
                                    <i class="fa-solid fa-check me-1"></i> Gunakan Kategori Ini
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const inputJudul    = document.getElementById('inputJudul');
    const inputIsi      = document.getElementById('inputIsi');
    const selectKategori= document.getElementById('selectKategori');
    const badge         = document.getElementById('ai-suggest-badge');
    const badgeLabel    = document.getElementById('ai-suggest-label');
    const badgeConf     = document.getElementById('ai-suggest-conf');
    const aiInfo        = document.getElementById('ai-suggest-info');
    const aiDetail      = document.getElementById('ai-suggest-detail');

    const panelEmpty    = document.getElementById('classify-empty');
    const panelLoading  = document.getElementById('classify-loading');
    const panelResult   = document.getElementById('classify-result');
    const winnerBox     = document.getElementById('classify-winner-box');
    const winnerIcon    = document.getElementById('classify-winner-icon');
    const winnerName    = document.getElementById('classify-winner-name');
    const winnerConf    = document.getElementById('classify-winner-conf');
    const barsContainer = document.getElementById('classify-bars');
    const btnApply      = document.getElementById('btn-apply-category');

    const colorMap = {
        success: '#198754', info: '#0dcaf0', warning: '#ffc107',
        danger: '#dc3545', secondary: '#6c757d', primary: '#0d6efd'
    };

    let debounceTimer = null;
    let lastResult = null;

    function showPanel(state) {
        panelEmpty.classList.add('d-none');
        panelLoading.classList.add('d-none');
        panelResult.classList.add('d-none');
        if (state === 'empty') panelEmpty.classList.remove('d-none');
        else if (state === 'loading') panelLoading.classList.remove('d-none');
        else if (state === 'result') panelResult.classList.remove('d-none');
    }

    function classifyText() {
        const text = (inputJudul.value + ' ' + inputIsi.value).trim();
        if (text.length < 8) {
            showPanel('empty');
            badge.classList.add('d-none');
            aiInfo.classList.add('d-none');
            return;
        }

        showPanel('loading');

        fetch('<?= base_url('api/classify.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.kode) {
                showPanel('empty');
                badge.classList.add('d-none');
                return;
            }

            lastResult = data;
            showPanel('result');

            // Winner box
            const color = colorMap[data.warna] || '#6c757d';
            winnerBox.style.background = color + '15';
            winnerBox.style.borderColor = color + '55';
            winnerIcon.style.background = color;
            winnerIcon.innerHTML = `<i class="fa-solid ${data.ikon}"></i>`;
            winnerName.textContent = data.nama;
            winnerConf.textContent = data.confidence + '%';

            // Per-category bars
            barsContainer.innerHTML = '';
            for (const [kode, cat] of Object.entries(data.per_kategori)) {
                const barColor = colorMap[cat.warna] || '#6c757d';
                barsContainer.innerHTML += `
                    <div class="mb-2">
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-muted">${cat.label.split('(')[0].trim()}</span>
                            <span class="fw-bold" style="color:${barColor}">${cat.confidence}%</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 4px;">
                            <div class="progress-bar" role="progressbar"
                                style="width: ${cat.confidence}%; background-color: ${barColor};"
                                aria-valuenow="${cat.confidence}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>`;
            }

            // Badge suggestion near dropdown
            badge.classList.remove('d-none');
            badgeLabel.textContent = data.nama.split('(')[0].trim();
            badgeConf.textContent = data.confidence + '%';
            aiInfo.classList.remove('d-none');
            aiDetail.textContent = `Model menyarankan kategori "${data.nama}" dengan confidence ${data.confidence}%.`;

            // Auto-select if confidence ≥ 60% and kategori_id is available
            if (data.confidence >= 60 && data.kategori_id) {
                selectKategori.value = data.kategori_id;
            }
        })
        .catch(() => showPanel('empty'));
    }

    function onInput() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(classifyText, 700);
    }

    inputJudul.addEventListener('input', onInput);
    inputIsi.addEventListener('input', onInput);

    btnApply && btnApply.addEventListener('click', function() {
        if (lastResult && lastResult.kategori_id) {
            selectKategori.value = lastResult.kategori_id;
            this.textContent = '✓ Kategori Diterapkan';
            this.disabled = true;
            setTimeout(() => {
                this.innerHTML = '<i class="fa-solid fa-check me-1"></i> Gunakan Kategori Ini';
                this.disabled = false;
            }, 2000);
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
