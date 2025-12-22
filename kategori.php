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
    "Jakarta", "Surabaya", "Bandung", "Medan", "Makassar", "Semarang",
    "Palembang", "Tangerang", "Depok", "Bekasi", "Bogor", "Batam",
    "Pontianak", "Balikpapan", "Samarinda", "Denpasar", "Malang",
    "Padang", "Pekanbaru", "Banjarmasin"
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

    // Enable error reporting untuk debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $response = @file_get_contents($url);

    if ($response === false) {
        $errorMessage = "Gagal mengambil data dari API NewsData.io. Pastikan koneksi internet stabil dan API key valid.";
    } else {
        $data = json_decode($response, true);

        if (!is_array($data)) {
            $errorMessage = "Response API bukan JSON valid. Error: " . json_last_error_msg();
        } else if (isset($data['status']) && $data['status'] === 'error') {
            $errorMessage = "API Error: " . ($data['message'] ?? 'Unknown error');
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { 
            background: #f5f5f5; 
            font-family: 'Poppins', sans-serif; 
        }
        .navbar-brand { 
            font-weight: 700; 
        }
        .nav-link.active { 
            font-weight: 600; 
            color: #0d47a1 !important; 
        }
        .category-btn {
            padding: 12px 24px;
            margin: 5px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            background: white;
            color: #333;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .category-btn:hover {
            border-color: #1976d2;
            color: #1976d2;
            background: #f0f7ff;
            transform: translateY(-2px);
        }
        .category-btn.active {
            background: #1976d2;
            color: white;
            border-color: #1976d2;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }
        .news-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .news-img {
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            color: #1a1a1a;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .category-badge {
            display: inline-block;
            background: #1976d2;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .category-header {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 8px 24px rgba(25, 118, 210, 0.25);
            position: relative;
            overflow: hidden;
        }
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
            opacity: 0.95;
        }
        .filter-card {
            background: white;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 12px;
        }
        .text-muted-category {
            color: #1976d2;
            font-weight: 600;
        }
        .btn-primary {
            background: #1976d2 !important;
            border: none !important;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #1565c0 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }
        .btn-outline-primary {
            border-color: #1976d2 !important;
            color: #1976d2 !important;
        }
        .btn-outline-primary:hover {
            background: #1976d2 !important;
            border-color: transparent !important;
            color: white !important;
        }
        .form-select, .form-control {
            border-color: #ddd !important;
            transition: all 0.3s ease;
        }
        .form-select:focus, .form-control:focus {
            border-color: #1976d2 !important;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15) !important;
        }
        .form-label {
            color: #333;
            font-weight: 600;
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

<!-- HEADER KATEGORI -->
<div class="category-header">
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

    <!-- FILTER KATEGORI & KOTA -->
    <div class="filter-card mb-4">
        <div class="card-body">
            <p class="text-muted-category mb-3">🔖 PILIH KATEGORI & KOTA BESAR</p>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Pilih Kategori</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $slug => $name): ?>
                            <option value="<?= urlencode($slug) ?>" <?= $category === $slug ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Pilih Kota Besar</label>
                    <select name="city" class="form-select">
                        <option value="">Semua Kota</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
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

    <?php if (!$apiKey): ?>
        <div class="alert alert-warning text-center">
            <strong>⚠️ API Key tidak ditemukan!</strong><br>
            Pastikan file <code>config.local.php</code> ada di folder root dan berisi:
            <pre style="background:#f5f5f5; padding:10px; margin-top:10px; border-radius:5px;">
&lt;?php
return [
    'NEWS_API_KEY' => 'your_api_key_here',
];
            </pre>
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
                        <span class="category-badge">
                            <?= htmlspecialchars($categories[$category] ?? $category) ?>
                        </span>
                        
                        <h6 class="card-title">
                            <?= htmlspecialchars($news['title'] ?? '') ?>
                        </h6>

                        <p class="text-muted small mb-2">
                            <?= isset($news['pubDate']) ? date("d M Y H:i", strtotime($news['pubDate'])) : '' ?>
                        </p>

                        <p class="card-text flex-grow-1">
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

<div class="text-center mt-5 mb-5 text-muted">
    <p>Regional News Indonesia © <?= date("Y") ?></p>
    <small>Powered by NewsData.io API</small>
</div>

</body>
</html>
