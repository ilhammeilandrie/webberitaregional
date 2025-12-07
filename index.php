<?php
// ----------------------------
// KONFIGURASI API NEWSDATA.IO
// ----------------------------

// API key yang dipakai di seluruh aplikasi
$apiKey = null;
$errorMessage = null;

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

// 3. Jika tetap kosong, simpan pesan error tapi halaman tetap jalan
if (!$apiKey) {
    $errorMessage = "API key NewsData.io belum dikonfigurasi. 
    <br>Buat file <code>config.local.php</code> atau set <strong>NEWS_API_KEY</strong> sebagai environment variable.";
}

// ----------------------------
// PARAMETER FILTER
// ----------------------------
$country = "id"; // berita khusus Indonesia
$city    = $_GET['city'] ?? '';
$page    = $_GET['page'] ?? '';

$newsList = [];
$nextPage = null;

// Jika ada API key, barulah kita panggil API
if ($apiKey) {
    $url = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id";

    if (!empty($city)) {
        $url .= "&q=" . urlencode($city);
    }

    if (!empty($page)) {
        $url .= "&page=" . urlencode($page);
    }

    $response = @file_get_contents($url);

    if ($response === false) {
        $errorMessage = "Gagal mengambil data dari API NewsData.io.";
    } else {
        $data = json_decode($response, true);

        if (!is_array($data)) {
            $errorMessage = "Response API bukan JSON valid.";
        } else {
            $newsList = $data['results'] ?? [];
            $nextPage = $data['nextPage'] ?? null;
        }
    }
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

    <style>
        body {
            background: #f0f2f5;
            font-family: 'Poppins', sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .nav-link.active {
            font-weight: 600;
            color: #0d47a1 !important;
        }
        .news-card {
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
        }
        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 18px rgba(0,0,0,0.2);
        }
        .news-img {
            height: 180px;
            object-fit: cover;
        }
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

    <!-- FILTER KOTA -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Pilih Kota Besar</label>
                    <select name="city" class="form-select">
                        <option value="">Semua Kota</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $city == $c ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Tampilkan</button>
                </div>

            </form>
        </div>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger text-center">
            <?= $errorMessage ?>
        </div>
    <?php endif; ?>

    <!-- NEWS LIST -->
    <div class="row">
        <?php if (!$errorMessage && empty($newsList)): ?>
            <div class="alert alert-warning text-center">Tidak ada berita ditemukan.</div>
        <?php endif; ?>

        <?php foreach ($newsList as $news): ?>
            <div class="col-md-4 mb-4">
                <div class="card news-card shadow-sm">

                    <?php if (!empty($news['image_url'])): ?>
                        <img src="<?= htmlspecialchars($news['image_url']) ?>" class="news-img card-img-top">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/600x400?text=No+Image" class="news-img card-img-top">
                    <?php endif; ?>

                    <div class="card-body">
                        <h6 class="card-title">
                            <?= htmlspecialchars($news['title'] ?? '') ?>
                        </h6>

                        <p class="text-muted small">
                            <?= isset($news['pubDate']) ? date("d M Y H:i", strtotime($news['pubDate'])) : '' ?>
                        </p>

                        <p class="card-text">
                            <?= htmlspecialchars(substr($news['description'] ?? '', 0, 120)) ?>...
                        </p>

                        <?php if (!empty($news['link'])): ?>
                            <a href="<?= htmlspecialchars($news['link']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                Baca selengkapnya
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($nextPage): ?>
        <div class="text-center mt-4">
            <a href="?city=<?= urlencode($city) ?>&page=<?= urlencode($nextPage) ?>"
               class="btn btn-primary btn-lg">
                Load More ⬇
            </a>
        </div>
    <?php endif; ?>

</div>

<div class="text-center mt-4 mb-4 text-muted">
    Regional News Indonesia © <?= date("Y") ?>
</div>

</body>
</html>
