<?php
/**
 * SIPENSO - Keyword-Based Complaint Classification Engine
 * 
 * Menggunakan pendekatan Keyword-Weight Scoring untuk mengklasifikasikan
 * pengaduan masyarakat ke dalam 5 kategori otomatis berdasarkan analisis teks.
 * 
 * Algoritma:
 * 1. Tokenisasi teks input (lowercase, hapus tanda baca)
 * 2. Hitung bobot keyword per kategori yang cocok
 * 3. Hitung confidence score (%) per kategori
 * 4. Kembalikan kategori dengan skor tertinggi
 */

/**
 * Dictionary Kata Kunci dengan Bobot per Kategori
 * Bobot: 3 = Sangat spesifik, 2 = Spesifik, 1 = Umum/pendukung
 */
function get_classification_dictionary(): array {
    return [
        'KAT-BANSOS' => [
            'label' => 'Bantuan Sosial (PKH, BPNT, DTKS)',
            'ikon'  => 'fa-hand-holding-heart',
            'warna' => 'success',
            'keywords' => [
                // Bobot 3 - Sangat spesifik
                'bpnt' => 3, 'pkh' => 3, 'dtks' => 3, 'sembako' => 3,
                'bansos' => 3, 'kartu keluarga sejahtera' => 3, 'kks' => 3,
                'program keluarga harapan' => 3, 'bantuan pangan' => 3,
                'blt' => 3, 'banpres' => 3, 'pip' => 3, 'kip' => 3,
                // Bobot 2 - Spesifik
                'bantuan sosial' => 2, 'subsidi' => 2, 'miskin' => 2,
                'tidak mampu' => 2, 'penerima manfaat' => 2,
                'kemiskinan' => 2, 'prasejahtera' => 2, 'kurang mampu' => 2,
                'raskin' => 2, 'beras' => 2, 'keluarga miskin' => 2,
                'pendataan' => 2, 'verifikasi data' => 2, 'sktm' => 2,
                'surat keterangan tidak mampu' => 2,
                // Bobot 1 - Umum
                'bantuan' => 1, 'cair' => 1, 'dana' => 1, 'salur' => 1,
                'daftar' => 1, 'terdaftar' => 1, 'ekonomi' => 1,
            ]
        ],

        'KAT-DISLANSIA' => [
            'label' => 'Disabilitas & Lansia Terlantarkan',
            'ikon'  => 'fa-wheelchair',
            'warna' => 'info',
            'keywords' => [
                // Bobot 3 - Sangat spesifik
                'disabilitas' => 3, 'difabel' => 3, 'cacat' => 3,
                'lansia' => 3, 'lanjut usia' => 3, 'jompo' => 3,
                'nenek' => 3, 'kakek' => 3, 'terlantarkan' => 3,
                'tuna netra' => 3, 'tuna rungu' => 3, 'tuna wicara' => 3,
                'tuna daksa' => 3, 'tuna grahita' => 3,
                // Bobot 2 - Spesifik
                'panti' => 2, 'panti sosial' => 2, 'panti jompo' => 2,
                'pendampingan' => 2, 'kursi roda' => 2, 'tongkat' => 2,
                'kelumpuhan' => 2, 'lumpuh' => 2, 'amnesia' => 2,
                'alzheimer' => 2, 'tidak berdaya' => 2, 'perawatan' => 2,
                'sendirian' => 2, 'sebatang kara' => 2,
                // Bobot 1 - Umum
                'tua' => 1, 'lemah' => 1, 'sakit' => 1, 'fisik' => 1,
                'medis' => 1, 'kesehatan' => 1, 'kondisi' => 1,
            ]
        ],

        'KAT-ANAK' => [
            'label' => 'Perlindungan Anak & ABH',
            'ikon'  => 'fa-child',
            'warna' => 'warning',
            'keywords' => [
                // Bobot 3 - Sangat spesifik
                'anak' => 3, 'balita' => 3, 'bayi' => 3, 'abh' => 3,
                'eksploitasi anak' => 3, 'perlindungan anak' => 3,
                'anak terlantarkan' => 3, 'yatim' => 3, 'piatu' => 3,
                'putus sekolah' => 3, 'anak jalanan' => 3,
                'pekerja anak' => 3, 'kekerasan anak' => 3,
                // Bobot 2 - Spesifik
                'p2tp2a' => 2, 'lpsk' => 2, 'bullying' => 2,
                'penganiayaan' => 2, 'pelecehan' => 2, 'adopsi' => 2,
                'hak asuh' => 2, 'wali' => 2, 'asuhan' => 2,
                'sekolah' => 2, 'pelajar' => 2, 'remaja' => 2,
                'gizi buruk' => 2, 'stunting' => 2, 'kurang gizi' => 2,
                'terlantar' => 2, 'yayasan' => 2,
                // Bobot 1 - Umum
                'kecil' => 1, 'bocah' => 1, 'siswa' => 1, 'murid' => 1,
                'bermain' => 1, 'trauma' => 1, 'psikologis' => 1,
            ]
        ],

        'KAT-BENCANA' => [
            'label' => 'Korban Bencana Sosial & Alam',
            'ikon'  => 'fa-house-flood-water',
            'warna' => 'danger',
            'keywords' => [
                // Bobot 3 - Sangat spesifik
                'bencana' => 3, 'kebakaran' => 3, 'banjir' => 3,
                'gempa' => 3, 'longsor' => 3, 'tsunami' => 3,
                'korban bencana' => 3, 'tanggap darurat' => 3,
                'pengungsian' => 3, 'pengungsi' => 3,
                // Bobot 2 - Spesifik
                'rumah terbakar' => 2, 'terendam' => 2, 'banjir bandang' => 2,
                'angin puting beliung' => 2, 'logistik' => 2,
                'evakuasi' => 2, 'tenda' => 2, 'posko' => 2,
                'rusak berat' => 2, 'rusak parah' => 2,
                'kehilangan rumah' => 2, 'hancur' => 2,
                'bpbd' => 2, 'tagana' => 2, 'kedaruratan' => 2,
                // Bobot 1 - Umum
                'hujan' => 1, 'air' => 1, 'api' => 1, 'asap' => 1,
                'rusak' => 1, 'rumah' => 1, 'tempat tinggal' => 1,
                'darurat' => 1, 'bantuan' => 1,
            ]
        ],

        'KAT-GEPENG' => [
            'label' => 'Penanganan PMKS (Gepeng/Tunawisma)',
            'ikon'  => 'fa-person-walking',
            'warna' => 'secondary',
            'keywords' => [
                // Bobot 3 - Sangat spesifik
                'gelandangan' => 3, 'pengemis' => 3, 'gepeng' => 3,
                'tunawisma' => 3, 'pmks' => 3, 'tidak punya rumah' => 3,
                'orang terlantar' => 3, 'orang gila' => 3,
                'penyandang masalah' => 3, 'kesejahteraan sosial' => 3,
                'psikotik' => 3, 'odgj' => 3, 'ganguan jiwa' => 3,
                'gangguan jiwa' => 3,
                // Bobot 2 - Spesifik
                'tempat umum' => 2, 'jalan raya' => 2, 'jalanan' => 2,
                'pasar' => 2, 'terminal' => 2, 'stasiun' => 2,
                'jembatan' => 2, 'kolong jembatan' => 2,
                'penertiban' => 2, 'razia' => 2, 'satpol' => 2,
                'pembinaan' => 2, 'rehabilitasi sosial' => 2,
                // Bobot 1 - Umum
                'liar' => 1, 'berkeliaran' => 1, 'tidur' => 1,
                'tidur di' => 1, 'mengganggu' => 1, 'umum' => 1,
                'ketertiban' => 1, 'kebersihan' => 1,
            ]
        ],
    ];
}

/**
 * Tokenisasi teks: lowercase, hapus tanda baca, normalisasi spasi
 */
function tokenize_text(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    // Hapus tanda baca kecuali spasi
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    // Normalisasi spasi ganda
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text;
}

/**
 * Main classifier function
 * 
 * @param string $text  Teks bebas (judul + isi_laporan pengaduan)
 * @param object|null $pdo   PDO connection (untuk lookup kategori_id dari DB)
 * 
 * @return array [
 *   'kode'        => string  kode_kategori (KAT-BANSOS, dll),
 *   'nama'        => string  nama_kategori,
 *   'ikon'        => string  Font Awesome icon class,
 *   'warna'       => string  Bootstrap color name,
 *   'skor'        => int     raw score,
 *   'confidence'  => float   0–100 confidence percentage,
 *   'per_kategori'=> array   skor & confidence tiap kategori (untuk grafik detail),
 *   'kategori_id' => int|null ID dari tabel kategori (jika $pdo tersedia),
 * ]
 */
function classify_complaint(string $text, $pdo = null): array {
    $dictionary = get_classification_dictionary();
    $normalized = tokenize_text($text);

    $scores = [];
    foreach ($dictionary as $kode => $cat) {
        $skor = 0;
        foreach ($cat['keywords'] as $keyword => $bobot) {
            if (mb_strpos($normalized, $keyword) !== false) {
                // Bonus jika keyword muncul lebih dari sekali
                $count = mb_substr_count($normalized, $keyword);
                $skor += $bobot * min($count, 3); // cap at 3x
            }
        }
        $scores[$kode] = $skor;
    }

    // Hitung total skor untuk confidence
    $total = array_sum($scores);

    // Buat detail per kategori
    $per_kategori = [];
    foreach ($dictionary as $kode => $cat) {
        $per_kategori[$kode] = [
            'label'      => $cat['label'],
            'ikon'       => $cat['ikon'],
            'warna'      => $cat['warna'],
            'skor'       => $scores[$kode],
            'confidence' => $total > 0 ? round(($scores[$kode] / $total) * 100, 1) : 0.0,
        ];
    }

    // Sort descending by score
    uasort($per_kategori, fn($a, $b) => $b['skor'] <=> $a['skor']);

    // Ambil winner
    $winner_kode = array_key_first($per_kategori);
    $winner      = $per_kategori[$winner_kode];

    // Jika total skor 0 (tidak ada keyword match) → tidak bisa diklasifikasikan
    if ($total === 0) {
        return [
            'kode'        => null,
            'nama'        => 'Tidak Dikenali',
            'ikon'        => 'fa-question-circle',
            'warna'       => 'secondary',
            'skor'        => 0,
            'confidence'  => 0.0,
            'per_kategori'=> $per_kategori,
            'kategori_id' => null,
        ];
    }

    // Ambil kategori_id dari DB jika $pdo tersedia
    $kategori_id = null;
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM kategori WHERE kode_kategori = :kode LIMIT 1");
            $stmt->execute(['kode' => $winner_kode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $kategori_id = (int)$row['id'];
        } catch (Exception $e) {
            // silent fail
        }
    }

    return [
        'kode'        => $winner_kode,
        'nama'        => $winner['label'],
        'ikon'        => $winner['ikon'],
        'warna'       => $winner['warna'],
        'skor'        => $winner['skor'],
        'confidence'  => $winner['confidence'],
        'per_kategori'=> $per_kategori,
        'kategori_id' => $kategori_id,
    ];
}
