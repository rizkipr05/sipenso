<?php
/**
 * SIPENSO - Classify AJAX Endpoint
 * POST /api/classify.php
 * 
 * Request: { "text": "judul + isi laporan" }
 * Response: JSON hasil klasifikasi
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/classifier.php';

// Only allow AJAX POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get text from JSON body or POST field
$text = '';
$raw  = file_get_contents('php://input');
if (!empty($raw)) {
    $data = json_decode($raw, true);
    $text = trim($data['text'] ?? '');
} else {
    $text = trim($_POST['text'] ?? '');
}

if (empty($text) || mb_strlen($text) < 5) {
    echo json_encode([
        'success'    => false,
        'message'    => 'Teks terlalu pendek untuk diklasifikasikan.',
        'kode'       => null,
        'nama'       => null,
        'confidence' => 0,
        'per_kategori' => []
    ]);
    exit;
}

// Run classification
$result = classify_complaint($text, $pdo);

echo json_encode([
    'success'      => true,
    'kode'         => $result['kode'],
    'nama'         => $result['nama'],
    'ikon'         => $result['ikon'],
    'warna'        => $result['warna'],
    'skor'         => $result['skor'],
    'confidence'   => $result['confidence'],
    'kategori_id'  => $result['kategori_id'],
    'per_kategori' => $result['per_kategori'],
]);
