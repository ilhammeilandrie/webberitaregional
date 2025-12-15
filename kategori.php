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
$country   = "id"; // berita khusus Indonesia
$category  = $_GET['category'] ?? 'business'; // kategori default
$city      = $_GET['city'] ?? ''; // kota opsional
$page      = $_GET['page'] ?? '';

$newsList = [];
$nextPage = null;

// Daftar kategori yang tersedia di NewsData.io
$categories = [
    'business'   => 'Bisnis',
    'entertainment' => 'Hiburan',
    'health'     => 'Kesehatan',
    'politics'   => 'Politik',
    'science'    => 'Sains',
    'sports'     => 'Olahraga',
    'technology' => 'Teknologi',
    'world'      => 'Dunia'
];

// List 20 kota besar Indonesia
$cities = [
    "Jakarta","Surabaya","Bandung","Medan","Makassar","Semarang",
    "Palembang","Tangerang","Depok","Bekasi","Bogor","Batam",
    "Pontianak","Balikpapan","Samarinda","Denpasar","Malang",
    "Padang","Pekanbaru","Banjarmasin"
];

// Jika ada API key, barulah kita panggil API
if ($apiKey) {
    $url = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&category={$category}";

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

// Untuk menandai menu navbar yang aktif
$activePage = 'kategori';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Berita - Berita Regional Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #3d1a4d 0%, #2d1b3d 100%);
            font-family: 'Poppins', sans-serif;
        }
        
        /* NAVBAR STYLING */
        .navbar-wrapper {
            background: linear-gradient(135deg, #2e0854 0%, #4a148c 50%, #3d0066 100%);
            padding: 20px 0;
            box-shadow: 0 8px 25px rgba(58, 1, 116, 0.5);
        }
        
        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .navbar-brand-section {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-shrink: 0;
        }
        
        .navbar-brand-text {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: white;
            writing-mode: horizontal-tb;
            white-space: nowrap;
        }

        /* Make header and titles use the same strong, condensed style as navbar */
        .category-header h1,
        .navbar-brand-text,
        .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            letter-spacing: 2px;
            color: #ffffff;
        }

        .category-header h1 {
            text-transform: uppercase;
            font-size: 2.4rem;
            margin: 0;
        }

        .card-title {
            font-size: 1rem;
            text-transform: none;
            color: #1a1a1a;
            letter-spacing: 0.6px;
        }
        
        /* navbar-divider removed: no vertical image */
        
        .nav-links {
            display: flex;
            gap: 40px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .nav-links .nav-item {
            position: relative;
        }
        
        .nav-links .nav-link {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            padding: 5px 0;
            text-decoration: none;
        }
        
        .nav-links .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #fff;
            transition: width 0.3s ease;
        }
        
        .nav-links .nav-link:hover {
            color: #ffffff !important;
        }
        
        .nav-links .nav-link:hover::after {
            width: 100%;
        }
        
        .nav-links .nav-link.active {
            color: #ffffff !important;
        }
        
        .nav-links .nav-link.active::after {
            width: 100%;
        }

        /* category button (kept from main but adapted to purple theme) */
        .category-btn {
            padding: 12px 24px;
            margin: 5px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.03);
            color: #fff;
            text-decoration: none;
            display: inline-block;
        }
        .category-btn:hover {
            border-color: rgba(255,255,255,0.15);
            color: #fff;
            background: rgba(255,255,255,0.06);
            transform: translateY(-2px);
        }
        .category-btn.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(58,1,116,0.3);
        }

        .news-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(74, 20, 140, 0.2);
            background: white;
        }
        .news-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(58, 1, 116, 0.3);
        }
        .news-img {
            height: 180px;
            object-fit: cover;
        }
        .category-header {
            background: linear-gradient(135deg, #2e0854 0%, #4a148c 50%, #3d0066 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 8px 25px rgba(58, 1, 116, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        /* decorative header background removed */
        
        .category-header h1 {
            font-weight: 800;
            margin: 0;
            position: relative;
            z-index: 1;
            font-size: 2.5rem;
        }
        
        .category-header p {
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a148c 0%, #3d0066 100%) !important;
            border: none !important;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(58, 1, 116, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58, 1, 116, 0.5);
        }
        
        .btn-outline-primary {
            border-color: #4a148c !important;
            color: #4a148c !important;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #4a148c 0%, #3d0066 100%) !important;
            border-color: transparent !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(58, 1, 116, 0.4);
        }
        
        .card {
            border-color: rgba(74, 20, 140, 0.2) !important;
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .form-select, .form-control {
            border-color: #b39ddb !important;
            background-color: rgba(255, 255, 255, 0.95);
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #4a148c !important;
            box-shadow: 0 0 0 0.2rem rgba(74, 20, 140, 0.25) !important;
        }
        
        .form-label {
            color: #2d2d2d;
            font-weight: 600;
        }
        
        .text-muted {
            color: #555 !important;
        }
    </style>
</head>

<body>

<!-- NAVBAR SEDERHANA (SELALU TAMPIL) -->
<div class="navbar-wrapper">
    <div class="container">
        <div class="navbar-container">
            <div class="navbar-brand-section">
                <div class="navbar-brand-text">BERITA REGIONAL INDONESIA</div>
            </div>

            <ul class="nav-links">
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="index.php">
                        HOME
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'kategori' ? 'active' : '' ?>" href="kategori.php">
                        KATEGORI
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="about.php">
                        TENTANG
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- HEADER KATEGORI -->
<div class="category-header" style="margin-top: 30px;">
    <div class="container">
        <h1><?= htmlspecialchars($categories[$category] ?? $category) ?></h1>
        <p class="lead mb-0">
            Berita terkini dari kategori <?= htmlspecialchars($categories[$category] ?? $category) ?>
            <?php if (!empty($city)): ?>
                di <?= htmlspecialchars($city) ?>
            <?php else: ?>
                di Indonesia
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container">

    <!-- FILTER KATEGORI -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p class="text-muted mb-3"><strong>Filter Berita:</strong></p>
            <form method="GET" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Pilih Kategori</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $slug => $name): ?>
                            <option value="<?= urlencode($slug) ?>" <?= $category === $slug ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Pilih Kota</label>
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
            <div class="alert alert-warning text-center w-100">
                Tidak ada berita ditemukan untuk kategori ini.
            </div>
        <?php endif; ?>

        <?php foreach ($newsList as $news): ?>
            <div class="col-md-4 mb-4">
                <div class="card news-card shadow-sm h-100">

                    <?php if (!empty($news['image_url'])): ?>
                        <img src="<?= htmlspecialchars($news['image_url']) ?>" class="news-img card-img-top" alt="<?= htmlspecialchars($news['title'] ?? '') ?>">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/600x400?text=No+Image" class="news-img card-img-top" alt="No Image">
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
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
            <a href="?category=<?= urlencode($category) ?>&city=<?= urlencode($city) ?>&page=<?= urlencode($nextPage) ?>"
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
