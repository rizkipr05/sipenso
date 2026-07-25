<?php
/**
 * Global Helper Functions & Utilities
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function base_url($path = '') {
    // Dynamic base URL detection for XAMPP
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // Normalize path to root project directory
    $pos = strpos($script_dir, '/sistempengaduan');
    if ($pos !== false) {
        $root_dir = substr($script_dir, 0, $pos + strlen('/sistempengaduan'));
    } else {
        $root_dir = '/sistempengaduan';
    }
    
    return rtrim($protocol . '://' . $host . $root_dir, '/') . '/' . ltrim($path, '/');
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show shadow-sm rounded-3 my-3" role="alert">
                    <i class="fa-solid ' . ($flash['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') . ' me-2"></i>' . $flash['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function check_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function check_role($allowed_roles = []) {
    check_login();
    $current_role = $_SESSION['role'] ?? '';
    if (!in_array($current_role, (array)$allowed_roles)) {
        set_flash('danger', 'Anda tidak memiliki hak akses untuk halaman tersebut.');
        if ($current_role == 'admin') {
            header('Location: ' . base_url('admin/dashboard.php'));
        } elseif ($current_role == 'petugas') {
            header('Location: ' . base_url('petugas/dashboard.php'));
        } else {
            header('Location: ' . base_url('pelapor/dashboard.php'));
        }
        exit;
    }
}

function generate_ticket_number() {
    $datePart = date('Ymd');
    $randomPart = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    return 'TKT-' . $datePart . '-' . $randomPart;
}

function format_tanggal($datetime, $with_time = true) {
    if (empty($datetime)) return '-';
    $time = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', $time);
    $m = $bulan[(int)date('m', $time)];
    $y = date('Y', $time);
    
    $result = "$d $m $y";
    if ($with_time) {
        $result .= ' pukul ' . date('H:i', $time) . ' WIB';
    }
    return $result;
}

function get_status_badge($status) {
    switch ($status) {
        case 'Pengaduan Masuk':
            return '<span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-inbox me-1"></i> Pengaduan Masuk</span>';
        case 'Diverifikasi':
            return '<span class="badge bg-info text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-clipboard-check me-1"></i> Diverifikasi</span>';
        case 'Diklasifikasikan':
            return '<span class="badge bg-primary px-3 py-2 rounded-pill"><i class="fa-solid fa-tags me-1"></i> Diklasifikasikan</span>';
        case 'Prioritas Ditentukan':
            return '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-layer-group me-1"></i> Prioritas Ditentukan</span>';
        case 'Diproses':
            return '<span class="badge bg-purple text-white px-3 py-2 rounded-pill" style="background-color: #6f42c1;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Diproses</span>';
        case 'Selesai':
            return '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check-double me-1"></i> Selesai</span>';
        case 'Ditolak':
            return '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-times-circle me-1"></i> Ditolak</span>';
        default:
            return '<span class="badge bg-secondary px-3 py-2 rounded-pill">' . htmlspecialchars($status) . '</span>';
    }
}

function get_priority_badge($priority) {
    switch (strtolower($priority)) {
        case 'mendesak':
            return '<span class="badge bg-danger px-3 py-2 rounded-pill text-uppercase fw-bold"><i class="fa-solid fa-fire me-1"></i> Mendesak</span>';
        case 'tinggi':
            return '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill text-uppercase fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Tinggi</span>';
        case 'sedang':
            return '<span class="badge bg-info text-dark px-3 py-2 rounded-pill text-uppercase fw-bold"><i class="fa-solid fa-circle-info me-1"></i> Sedang</span>';
        case 'rendah':
            return '<span class="badge bg-secondary px-3 py-2 rounded-pill text-uppercase fw-bold"><i class="fa-solid fa-arrow-down me-1"></i> Rendah</span>';
        default:
            return '<span class="badge bg-light text-dark px-3 py-2 rounded-pill">' . htmlspecialchars($priority) . '</span>';
    }
}

/**
 * Rule-Based Priority Evaluation Matrix (Deterministic logic as per Social Services criteria)
 */
function calculate_priority($tingkat_dampak, $jumlah_terdampak = 1) {
    $skor = 0;
    
    // Impact Weight
    switch (strtolower($tingkat_dampak)) {
        case 'mendesak':
            $skor += 5;
            break;
        case 'tinggi':
            $skor += 4;
            break;
        case 'sedang':
            $skor += 2;
            break;
        case 'rendah':
        default:
            $skor += 1;
            break;
    }
    
    // Scale Weight
    if ($jumlah_terdampak > 50) {
        $skor += 5;
    } elseif ($jumlah_terdampak > 10) {
        $skor += 3;
    } elseif ($jumlah_terdampak > 1) {
        $skor += 2;
    } else {
        $skor += 1;
    }
    
    // Priority Category Threshold
    if ($skor >= 9) {
        $prioritas = 'Mendesak';
    } elseif ($skor >= 6) {
        $prioritas = 'Tinggi';
    } elseif ($skor >= 4) {
        $prioritas = 'Sedang';
    } else {
        $prioritas = 'Rendah';
    }
    
    return [
        'skor' => $skor,
        'prioritas' => $prioritas
    ];
}

/**
 * Log user activity into log_aktivitas table
 */
function log_activity($user_id, $aktivitas, $keterangan = null, $pdo = null) {
    if (!$pdo) {
        global $pdo;
    }
    if (!$pdo) {
        require_once __DIR__ . '/database.php';
    }
    if (!$pdo) return false;
    
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address, user_agent) VALUES (:uid, :act, :desc, :ip, :ua)");
        return $stmt->execute([
            'uid' => $user_id,
            'act' => $aktivitas,
            'desc' => $keterangan,
            'ip' => $ip,
            'ua' => $ua
        ]);
    } catch (\Exception $e) {
        return false;
    }
}
