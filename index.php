<?php
// ----------------------------
// KONFIGURASI API NEWSDATA.IO
// ----------------------------

// API key yang dipakai di seluruh aplikasi
$apiKey         = null;
$errorMessage   = null; // error fatal (mis. response rusak), sebaiknya tetap ditampilkan
$warningMessage = null; // peringatan non-fatal (mis. API key belum diset) — halaman tetap jalan

// 1. Coba baca dari file config.local.php (untuk LOCALHOST)
$configFile = __DIR__ . '/config.local.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    if (is_array($config) && !empty($config['NEWS_API_KEY'])) {
        $apiKey = $config['NEWS_API_KEY'];
    }
}

// 2. Kalau belum ada, coba baca dari ENV (GitHub Actions pakai ini)
if (!$apiKey) {
    $envKey = getenv('NEWS_API_KEY');
    if (!empty($envKey)) {
        $apiKey = $envKey;
    }
}

// 3. Jika tetap kosong, simpan pesan peringatan tapi halaman tetap jalan
if (!$apiKey) {
    $warningMessage = "API key NewsData.io belum dikonfigurasi. 
    <br>Buat file <code>config.local.php</code> atau set <strong>NEWS_API_KEY</strong> sebagai environment variable. 
    <br>Untuk saat ini, halaman akan menampilkan contoh berita pilihan.";
}

// ----------------------------
// PARAMETER FILTER
// ----------------------------
$country = "id"; // berita khusus Indonesia
$city    = $_GET['city'] ?? '';
$page    = $_GET['page'] ?? '';

// Debug toggle via query
$showDebug = isset($_GET['debug']) && $_GET['debug'] === '1';

// Helper: fetch URL with file_get_contents or cURL fallback
function fetch_url($url) {
    $resp = @file_get_contents($url);
    if ($resp !== false && $resp !== '') return $resp;

    if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $out = curl_exec($ch);
        curl_close($ch);
        if ($out !== false && $out !== '') return $out;
    }

    return null;
}

// cURL fetch helper that returns more debug info (http code and any curl error)
function curl_fetch($url) {
    if (!function_exists('curl_version')) return ['body' => null, 'http_code' => 0, 'error' => 'cURL not available'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body' => $body !== false ? $body : null, 'http_code' => intval($code), 'error' => $err];
}


// Normalize/sanitize news items from API so we always have a usable title (fall back to host or description)
function sanitize_items($items) {
    $out = [];
    foreach ($items as $n) {
        if (!is_array($n)) continue;
        $title = trim($n['title'] ?? '');
        $link = trim($n['link'] ?? '');
        // if no useful title and no link, skip the item
        if ($title === '' && $link === '') continue;
        $n['title'] = $title !== '' ? $title : (trim($n['description'] ?? '') ?: (parse_url($link, PHP_URL_HOST) ?: 'Berita tanpa judul'));
        $n['description'] = $n['description'] ?? '';
        $n['image_url'] = $n['image_url'] ?? '';
        $n['link'] = $link;
        $n['pubDate'] = $n['pubDate'] ?? null;
        $out[] = $n;
    }
    return array_values($out);
}

$newsList = [];
$nextPage = null;
$trendingList = []; // fallback trending headlines when primary list is empty

// Jika ada API key, barulah kita panggil API
if ($apiKey) {
    $url = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&language=id";

    if (!empty($city)) {
        $url .= "&q=" . urlencode($city);
    }

    if (!empty($page)) {
        $url .= "&page=" . urlencode($page);
    }

    $response = fetch_url($url);
    $fetchDebug = null;

    // If initial fetch failed and debug is on, try cURL for more information
    if ($response === null && $showDebug) {
        $fetchDebug = curl_fetch($url);
        if (!empty($fetchDebug['body'])) {
            $response = $fetchDebug['body'];
        }
    }

    if ($response === null) {
        $errorMessage = "Gagal mengambil data dari API NewsData.io.";
        if ($showDebug && is_array($fetchDebug)) {
            $err = substr($fetchDebug['error'] ?? '', 0, 300);
            $code = intval($fetchDebug['http_code'] ?? 0);
            $errorMessage .= " Detail: HTTP {$code}. Error: {$err}.";
        }
    } else {
        $data = json_decode($response, true);

        if (!is_array($data)) {
            $errorMessage = "Response API bukan JSON valid.";
        } else {
            $newsList = $data['results'] ?? [];
            $nextPage = $data['nextPage'] ?? null;

            // Prefer Indonesia-only items: if a result explicitly states a non-id language or country, exclude it.
            $newsList = array_values(array_filter($newsList, function($n) {
                if (isset($n['language']) && $n['language'] !== 'id') return false;
                if (isset($n['source']['country']) && $n['source']['country'] !== 'id') return false;

                // If explicit markers indicate Indonesia, keep it
                if ((isset($n['language']) && $n['language'] === 'id') || (isset($n['source']['country']) && strpos($n['source']['country'], 'id') !== false)) {
                    return true;
                }

                // Heuristics: accept Indonesian domains or place names
                if (!empty($n['link']) && stripos($n['link'], '.id') !== false) return true;
                if (!empty($n['title']) && preg_match('/\b(Jakarta|Bandung|Surabaya|Indonesia|Bali|Sumatra|Jawa|Sulawesi|Papua)\b/i', $n['title'])) return true;

                // Otherwise keep (avoid filtering out too aggressively)
                return true;
            }));

            // Sanitize items to ensure titles exist and remove empty entries
            $newsList = sanitize_items($newsList);

            // If we got no results, try a fallback fetch for trending Indonesian headlines
            if (empty($newsList) && $apiKey) {
                $turl = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&language=id";
                $tresponse = fetch_url($turl);
                if ($tresponse !== null) {
                    $tdata = json_decode($tresponse, true);
                    if (is_array($tdata)) {
                        $trendingList = $tdata['results'] ?? [];
                        // apply same basic filter
                        $trendingList = array_values(array_filter($trendingList, function($n) {
                            if (isset($n['language']) && $n['language'] !== 'id') return false;
                            if (isset($n['source']['country']) && $n['source']['country'] !== 'id') return false;
                            return true;
                        }));
                    }
                }
            }
        }
    }
}

// Proactive fetch: get trending headlines even if $newsList has items
if ($apiKey) {
    try {
        $trendUrl = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&language=id&page=1";
        $trendResp = fetch_url($trendUrl);
        if ($trendResp !== null) {
            $tdata = json_decode($trendResp, true);
            if (is_array($tdata)) {
                $trendingList = $tdata['results'] ?? [];
                $trendingList = array_values(array_filter($trendingList, function($n) {
                    if (isset($n['language']) && $n['language'] !== 'id') return false;
                    if (isset($n['source']['country']) && $n['source']['country'] !== 'id') return false;
                    return true;
                }));

                // sort by pubDate desc so newest/trending appear first
                usort($trendingList, function($a, $b) {
                    $ta = isset($a['pubDate']) ? strtotime($a['pubDate']) : 0;
                    $tb = isset($b['pubDate']) ? strtotime($b['pubDate']) : 0;
                    return $tb <=> $ta;
                });

                // Sanitize trending items too
                $trendingList = sanitize_items($trendingList);
            }
        }
    } catch (Exception $e) {
        // ignore
    }


}

// Jika API tidak mengembalikan trending sama sekali, sediakan fallback statis
// agar beranda selalu memiliki minimal 6 berita dengan visual yang rapi.
if (empty($trendingList)) {
    $trendingList = [
        [
            'title'       => 'Pemerintah Genjot Pembangunan Infrastruktur Transportasi',
            'description' => 'Proyek jalan dan jembatan strategis terus dilanjutkan untuk memperkuat konektivitas antardaerah.',
            'image_url'   => 'https://images.unsplash.com/photo-1505842679542-4976ac3b0fef?auto=format&fit=crop&w=1200&q=80',
            'link'        => 'https://www.kompas.com/tag/infrastruktur',
            'pubDate'     => date('c', strtotime('-3 hours')),
        ],
        [
            'title'       => 'BMKG Peringatkan Potensi Hujan Lebat di Sejumlah Wilayah',
            'description' => 'Masyarakat diminta waspada banjir lokal dan angin kencang akibat hujan berintensitas tinggi.',
            'image_url'   => 'https://images.unsplash.com/photo-1501999635878-71cb5379c2d8?auto=format&fit=crop&w=1200&q=80',
            'link'        => 'https://www.bmkg.go.id/berita/',
            'pubDate'     => date('c', strtotime('-6 hours')),
        ],
        [
            'title'       => 'UMKM Lokal Manfaatkan Platform Digital untuk Tingkatkan Penjualan',
            'description' => 'Pelaku usaha kecil memanfaatkan marketplace dan media sosial untuk menjangkau pasar lebih luas.',
            'image_url'   => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80',
            'link'        => 'https://www.cnnindonesia.com/ekonomi',
            'pubDate'     => date('c', strtotime('-1 day')),
        ],
        [
            'title'       => 'Pariwisata Domestik Mulai Bergeliat di Berbagai Daerah',
            'description' => 'Destinasi wisata lokal kembali ramai dikunjungi wisatawan nusantara.',
            'image_url'   => 'https://images.unsplash.com/photo-1526779259212-939e64788e3c?auto=format&fit=crop&w=1200&q=80',
            'link'        => 'https://travel.kompas.com/',
            'pubDate'     => date('c', strtotime('-2 days')),
        ],
        [
            'title'       => 'Petani Manfaatkan Teknologi untuk Tingkatkan Produktivitas',
            'description' => 'Penggunaan aplikasi cuaca dan sistem irigasi modern bantu petani mengoptimalkan hasil panen.',
            'image_url'   => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=1200&q=80',
            'link'        => 'https://www.kompas.com/tag/pertanian',
            'pubDate'     => date('c', strtotime('-3 days')),
        ],
        [
            'title'       => 'Generasi Muda Gencar Kembangkan Startup di Kota-Kota Besar',
            'description' => 'Ekosistem startup semakin berkembang dengan hadirnya inkubator dan coworking space di berbagai daerah.',
            'image_url'   => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=80',
            'link'        => 'https://www.cnnindonesia.com/teknologi',
            'pubDate'     => date('c', strtotime('-4 days')),
        ],
    ];
}

// Jika daftar utama kosong tetapi trending sudah ada, gunakan trending
// sebagai sumber berita utama (hero + daftar berita terbaru).
if (empty($newsList) && !empty($trendingList)) {
    $newsList = $trendingList;
}

// --------------------------
// LIST 24 KOTA BESAR INDONESIA
// --------------------------
$cities = [
    "Jakarta","Surabaya","Bandung","Medan","Makassar","Semarang",
    "Palembang","Tangerang","Depok","Bekasi","Bogor","Batam",
    "Pontianak","Balikpapan","Samarinda","Denpasar","Malang",
    "Padang","Pekanbaru","Banjarmasin","Yogyakarta","Manado",
    "Jayapura","Banda Aceh"
];

// Untuk menandai menu navbar yang aktif
$activePage = 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Regional Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .navbar-brand { font-weight: 700; }
        .nav-link.active { font-weight: 600; color: #0d47a1 !important; }
        .news-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
        }
        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 18px rgba(0,0,0,0.2);
        }
        .news-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display:block;
        }
        /* Versi background, memberi visual meski gambar gagal dimuat */
        .news-thumb {
            width: 100%;
            height: 200px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: linear-gradient(135deg,#eceff1,#cfd8dc);
            background-size: cover;
            background-position: center;
        }
        .featured-img {
            height: 420px;
            object-fit: cover;
            width: 100%;
        }
        .hero-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(0,0,0,0.12);
        }
        .featured-title {
            font-size: 1.6rem;
            font-weight: 700;
        }
        .welcome-hero { background: linear-gradient(90deg,#0d47a1,#1976d2); color:#fff; padding:2.2rem; border-radius:12px; box-shadow:0 10px 30px rgba(13,71,161,0.12); }
        .welcome-hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: 0.4rem; }
        .welcome-hero p { opacity: .95; }
        .cta-btn { background:#fff; color:#1976d2; font-weight:600; border-radius:8px; padding:.55rem 1rem; box-shadow:0 6px 18px rgba(0,0,0,0.08); }
        .news-card .card-body { min-height: 140px; }
        .lead-hero { margin-bottom: 1rem; }
        .badge-trending { background:#ff5252; color:#fff; padding:.25rem .5rem; border-radius:6px; font-size:.75rem; font-weight:700; margin-right:.5rem; }
        .trending-title { font-size: 1rem; font-weight:700; }
        .source-badge { font-size:.75rem; color:#6c757d; margin-left:.4rem; }
    </style>
</head>

<body>

<!-- NAVBAR SEDERHANA (SELALU TAMPIL) -->
<nav class="navbar bg-white shadow-sm mb-4">
    <div class="container d-flex align-items-center">
        <a class="navbar-brand text-primary" href="index.php">
            <strong>Berita Regional Indonesia</strong>
        </a>

        <ul class="nav ms-auto">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="index.php">
                    Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'kategori' ? 'active' : '' ?>" href="kategori.php">
                    Kategori
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="about.php">
                    About
                </a>
            </li>
        </ul>
    </div>
</nav>

<div class="container">

    <div class="mb-4">
        <div class="card welcome-hero">
            <div class="card-body text-center">
                <h1>Selamat datang di Berita Regional Indonesia</h1>
                <p class="lead mb-3">Sumber terpercaya untuk berita terbaru dari seluruh pelosok negeri. Temukan berita terkini, mendalam, dan relevan untuk daerah Anda.</p>
                <a href="kategori.php?category=all" class="btn cta-btn">Jelajahi Berita Lainnya</a>
            </div>
        </div>
    </div>

    <!-- FILTER KOTA (dihapus) -->

    <?php if ($warningMessage): ?>
        <div class="alert alert-warning text-center">
            <?= $warningMessage ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger text-center">
            <?= $errorMessage ?>
        </div>
    <?php endif; ?>

        <div class="mb-4">
            <h5 class="mb-1">Terbaru dari seluruh wilayah Indonesia</h5>
            <p class="text-muted mb-0">Update berita regional secara real-time</p>
        </div>

        <?php if ($showDebug): ?>
            <div class="alert alert-info small">
                <strong>Debug:</strong>
                API key: <strong><?= !empty($apiKey) ? 'detected' : 'not found' ?></strong> •
                News items: <strong><?= count($newsList) ?></strong> •
                Trending items: <strong><?= count($trendingList) ?></strong>
                <span class="ms-2">| <a href="?debug=1">Refresh</a></span>
            </div>
            <?php if (isset($response)): ?>
                <div class="small mb-3">
                    <strong>Response preview (truncated):</strong>
                    <pre style="max-height:180px;overflow:auto;background:#f8f9fa;border:1px solid #e9ecef;padding:.75rem;"><?= htmlspecialchars(substr($response,0,2000)) ?></pre>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($trendingList)): ?>
            <h5 class="mb-3">Berita Terkini di Indonesia</h5>
            <div class="row mb-4">
                <?php foreach (array_slice($trendingList, 0, 6) as $t): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card news-card shadow-sm h-100">
                            <?php $timg = !empty($t['image_url']) ? $t['image_url'] : 'https://source.unsplash.com/600x400/?news,indonesia'; ?>
                            <img src="<?= htmlspecialchars($timg) ?>" class="news-img card-img-top" alt="<?= htmlspecialchars($t['title'] ?? '') ?>" loading="lazy">

                            <div class="card-body">
                                <?php $tlink = !empty($t['link']) ? $t['link'] : 'https://www.google.com/search?q=' . urlencode($t['title'] ?? 'berita'); ?>
                                <a href="<?= htmlspecialchars($tlink) ?>" target="_blank" class="stretched-link"></a>
                                <span class="badge-trending">Baru</span>
                                <h6 class="card-title trending-title"><?= htmlspecialchars(!empty($t['title']) ? $t['title'] : (parse_url($t['link'] ?? '', PHP_URL_HOST) ?: 'Berita')) ?></h6>
                                <p class="text-muted small mb-2"><?= isset($t['pubDate']) ? date("d M Y H:i", strtotime($t['pubDate'])) : '' ?> <span class="source-badge">• <?= htmlspecialchars(parse_url($t['link'] ?? '', PHP_URL_HOST) ?? '') ?></span></p>
                                <?php if (!empty($t['description'])): ?>
                                    <p class="card-text"><?= htmlspecialchars(substr($t['description'], 0, 120)) ?>...</p>
                                <?php endif; ?>
                                <?php if (!empty($t['link'])): ?>
                                    <a href="<?= htmlspecialchars($t['link']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">Baca selengkapnya</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php if (!$errorMessage && !empty($newsList)): ?>
        <?php $featured = array_shift($newsList); ?>
        <div class="card mb-4 hero-card">
            <div class="row g-0">
                <div class="col-md-6">
                    <?php $fimg = !empty($featured['image_url']) ? $featured['image_url'] : 'https://source.unsplash.com/900x600/?news,indonesia'; ?>
                    <img src="<?= htmlspecialchars($fimg) ?>" class="featured-img" alt="<?= htmlspecialchars($featured['title'] ?? '') ?>">
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="card-body">
                        <?php $flink = !empty($featured['link']) ? $featured['link'] : 'https://www.google.com/search?q=' . urlencode($featured['title'] ?? 'berita'); ?>
                        <a href="<?= htmlspecialchars($flink) ?>" target="_blank" class="stretched-link"></a>
                        <p class="text-muted small mb-1">
                            <?= isset($featured['pubDate']) ? date("d M Y H:i", strtotime($featured['pubDate'])) : '' ?>
                        </p>
                        <h2 class="featured-title"><?= htmlspecialchars(!empty($featured['title']) ? $featured['title'] : (parse_url($featured['link'] ?? '', PHP_URL_HOST) ?: 'Berita')) ?></h2>
                        <?php if (!empty($featured['description'])): ?>
                            <p class="mt-3">
                                <?= htmlspecialchars(substr($featured['description'], 0, 220)) ?>...
                            </p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($flink) ?>" target="_blank" class="btn btn-primary">
                            Baca selengkapnya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- NEWS LIST -->
    <div class="row">
        <div class="col-12">
            <?php if ($errorMessage || !empty($newsList)): ?>

                <?php $topStories = !empty($trendingList) ? array_slice($trendingList, 0, 4) : array_slice($newsList, 0, 4); ?>

                <h5 id="top-stories" class="lead-hero">Top Stories</h5>
                <div class="row mb-4">
                    <?php foreach ($topStories as $news): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card news-card shadow-sm h-100">
                                <?php $nimg = !empty($news['image_url']) ? $news['image_url'] : 'https://source.unsplash.com/600x400/?news,indonesia'; ?>
                                <img src="<?= htmlspecialchars($nimg) ?>" class="news-img card-img-top" alt="<?= htmlspecialchars($news['title'] ?? '') ?>" loading="lazy">

                                <div class="card-body">
                                    <?php $nlink = !empty($news['link']) ? $news['link'] : 'https://www.google.com/search?q=' . urlencode($news['title'] ?? 'berita'); ?>
                                    <a href="<?= htmlspecialchars($nlink) ?>" target="_blank" class="stretched-link"></a>
                                    <h6 class="card-title"><?= htmlspecialchars(!empty($news['title']) ? $news['title'] : (parse_url($news['link'] ?? '', PHP_URL_HOST) ?: 'Berita')) ?></h6>
                                    <p class="text-muted small mb-2"><?= isset($news['pubDate']) ? date("d M Y H:i", strtotime($news['pubDate'])) : '' ?> <span class="source-badge">• <?= htmlspecialchars(parse_url($news['link'] ?? '', PHP_URL_HOST) ?? '') ?></span></p>
                                    <?php if (!empty($news['description'])): ?>
                                        <p class="card-text"><?= htmlspecialchars(substr($news['description'], 0, 140)) ?>...</p>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($nlink) ?>" target="_blank" class="btn btn-outline-primary btn-sm">Baca selengkapnya</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="mb-3">Berita Terbaru</h5>
                <div class="row">
                    <?php $more = array_slice($newsList, 4); foreach ($more as $news): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card news-card shadow-sm h-100">
                                <div class="row g-0">
                                    <div class="col-5">
                                        <?php $simg = !empty($news['image_url']) ? $news['image_url'] : 'https://source.unsplash.com/600x400/?news,indonesia'; ?>
                                        <img src="<?= htmlspecialchars($simg) ?>" class="news-img" alt="<?= htmlspecialchars($news['title'] ?? '') ?>" loading="lazy">
                                    </div>
                                    <div class="col-7">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars(!empty($news['title']) ? $news['title'] : (parse_url($news['link'] ?? '', PHP_URL_HOST) ?: 'Berita')) ?></h6>
                                            <p class="text-muted small mb-2"><?= isset($news['pubDate']) ? date("d M Y H:i", strtotime($news['pubDate'])) : '' ?></p>
                                            <?php if (!empty($news['description'])): ?>
                                                <p class="card-text small"><?= htmlspecialchars(substr($news['description'], 0, 100)) ?>...</p>
                                            <?php endif; ?>
                                                <?php $mlink = !empty($news['link']) ? $news['link'] : 'https://www.google.com/search?q=' . urlencode($news['title'] ?? 'berita'); ?>
                                                <a href="<?= htmlspecialchars($mlink) ?>" target="_blank" class="btn btn-outline-primary btn-sm">Baca</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

<div class="text-center mt-4 mb-4 text-muted">
    Regional News Indonesia © <?= date("Y") ?>
</div>

</body>
</html>
