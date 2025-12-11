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

// Jika ada API key, barulah kita panggil API
if ($apiKey) {
    $url = "https://newsdata.io/api/1/news?apikey={$apiKey}&country=id&category={$category}";

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .navbar-brand { font-weight: 700; }
        .nav-link.active { font-weight: 600; color: #0d47a1 !important; }
        .category-btn {
            padding: 12px 24px;
            margin: 5px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid #e0e0e0;
            background: white;
            color: #333;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .category-btn:hover {
            border-color: #667eea;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }
        .category-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .news-card {
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .news-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.25);
        }
        .news-img {
            height: 200px;
            object-fit: cover;
            position: relative;
        }
        .news-img::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.1));
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            color: #1a1a2e;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .category-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%, #f093fb 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        .category-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,50 Q300,0 600,50 T1200,50 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.1)"/></svg>') no-repeat bottom;
            background-size: cover;
            pointer-events: none;
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
        .btn-outline-primary {
            border-color: #667eea;
            color: #667eea;
            transition: all 0.3s;
        }
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
        }
        .filter-card {
            background: white;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-radius: 16px;
        }
        .text-muted-category {
            color: #667eea;
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
        <p class="lead mb-0">Berita terkini dari kategori <?= htmlspecialchars($categories[$category] ?? $category) ?> di Indonesia</p>
    </div>
</div>

<div class="container">

    <!-- FILTER KATEGORI -->
    <div class="filter-card mb-4">
        <div class="card-body">
            <p class="text-muted-category mb-3">🔖 PILIH KATEGORI</p>
            <div class="text-center">
                <?php foreach ($categories as $slug => $name): ?>
                    <a href="?category=<?= urlencode($slug) ?>" 
                       class="category-btn <?= $category === $slug ? 'active' : '' ?>">
                        <?= htmlspecialchars($name) ?>
                    </a>
                <?php endforeach; ?>
            </div>
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
            <a href="?category=<?= urlencode($category) ?>&page=<?= urlencode($nextPage) ?>"
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
