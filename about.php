<?php $activePage = 'about'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>About - Berita Regional Indonesia</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

	<style>
		:root { --brand: #0d47a1; --brand-soft: #e8f1ff; }
		body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
		.page-container { max-width: 1080px; }
		.navbar-brand { font-weight: 700; }
		.nav-link.active { font-weight: 600; color: #0d47a1 !important; }
		.hero { border-radius: 16px; overflow: hidden; background: linear-gradient(120deg, #0d47a1, #1e88e5); color: #fff; }
		.hero .card-body { padding: 2.4rem 2.6rem; }
		.card-body { padding: 2.1rem; }
		.section-card { border: 1px solid #e6ecf5; border-radius: 14px; }
		.section-title { font-size: 1.05rem; letter-spacing: 0.02em; color: #0d47a1; text-transform: uppercase; }
		.info-badge { background: rgba(255, 255, 255, 0.15); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); }
		.feature-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 6px 20px rgba(13, 71, 161, 0.08); }
		.feature-icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: var(--brand-soft); color: var(--brand); font-weight: 600; }
		.step-list li { margin-bottom: 10px; padding: 8px 12px; border-radius: 10px; background: #f7f9fc; border: 1px solid #e9eef5; }
		.tech-badge { background: #0d47a115; color: #0d47a1; border: 1px solid #0d47a120; padding: 8px 12px; border-radius: 12px; font-weight: 600; }
		.footer-note { font-size: 0.9rem; }
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
				<a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="index.php">Home</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?= $activePage === 'kategori' ? 'active' : '' ?>" href="kategori.php">Kategori</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="about.php">About</a>
			</li>
		</ul>
	</div>
</nav>

<div class="container page-container">
	<div class="card shadow-sm mb-4 hero">
		<div class="card-body">
			<div class="d-flex flex-wrap justify-content-between align-items-center">
				<div>
					<div class="badge info-badge mb-2">Portal berita lokal & regional</div>
					<h3 class="card-title mb-2">WebBeritaRegional</h3>
					<p class="mb-0" style="opacity: .9;">Ringkas, terkurasi, dan siap dibaca dari mana saja.</p>
				</div>
				<div class="text-end">
					<span class="badge bg-light text-dark me-1">Cepat</span>
					<span class="badge bg-light text-dark me-1">Responsif</span>
					<span class="badge bg-light text-dark">Realtime</span>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-4">
		<div class="col-md-6">
			<div class="card section-card shadow-sm">
				<div class="card-body">
					<div class="section-title mb-2">Apa itu WebBeritaRegional?</div>
					<p class="mb-0">
						WebBeritaRegional adalah portal berita yang mengkurasi kabar lokal dan regional di Indonesia
						berdasarkan kota dan kategori. Tujuannya memberi ringkasan cepat sehingga pembaca langsung
						mendapat inti informasi tanpa harus membuka banyak tab.
					</p>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card section-card shadow-sm">
				<div class="card-body">
					<div class="section-title mb-2">Tentang website ini</div>
					<p class="mb-0">
						Berita diambil real-time dari NewsData.io lalu disajikan ulang dengan tampilan ringan agar mudah
						diakses di desktop maupun ponsel. Setiap halaman menyorot berita terbaru per kota yang dipilih.
					</p>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3 mt-4">
		<div class="col-12">
			<div class="section-title mb-2">Fitur-fitur utama</div>
		</div>
		<div class="col-md-4">
			<div class="card feature-card h-100">
				<div class="card-body">
					<div class="feature-icon mb-2">🏙️</div>
					<h6 class="fw-bold">Filter kota</h6>
					<p class="mb-0">Pilih domisili untuk mendapatkan kabar lokal yang relevan.</p>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card feature-card h-100">
				<div class="card-body">
					<div class="feature-icon mb-2">📰</div>
					<h6 class="fw-bold">Kurasi ringkas</h6>
					<p class="mb-0">Judul, sumber, dan tanggal tampil rapi agar cepat dibaca.</p>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card feature-card h-100">
				<div class="card-body">
					<div class="feature-icon mb-2">📱</div>
					<h6 class="fw-bold">Responsif</h6>
					<p class="mb-0">Tampilan Bootstrap 5 nyaman di ponsel maupun desktop.</p>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card feature-card h-100">
				<div class="card-body">
					<div class="feature-icon mb-2">🔎</div>
					<h6 class="fw-bold">Kategori topik</h6>
					<p class="mb-0">Dukungan kategori (nasional, teknologi, olahraga) jika disediakan API.</p>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card feature-card h-100">
				<div class="card-body">
					<div class="feature-icon mb-2">🔗</div>
					<h6 class="fw-bold">Tautan sumber</h6>
					<p class="mb-0">Klik “Baca selengkapnya” untuk langsung menuju artikel asli.</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card section-card shadow-sm mt-4">
		<div class="card-body">
			<div class="section-title mb-3">Cara menggunakan</div>
			<ol class="step-list list-unstyled mb-0">
				<li>Masuk ke beranda lalu pilih kota yang ingin dipantau.</li>
				<li>Klik kartu berita untuk membaca ringkasan singkat.</li>
				<li>Gunakan tautan “Baca selengkapnya” untuk membuka artikel asli.</li>
				<li>Ingin berganti kota atau kategori? Gunakan navigasi di bagian atas.</li>
			</ol>
		</div>
	</div>

	<div class="card section-card shadow-sm mt-4">
		<div class="card-body">
			<div class="section-title mb-3">Teknologi yang digunakan</div>
			<div class="d-flex flex-wrap gap-2">
				<span class="tech-badge">PHP 8</span>
				<span class="tech-badge">Bootstrap 5.3</span>
				<span class="tech-badge">NewsData.io API</span>
				<span class="tech-badge">cURL/HTTP Client</span>
				<span class="tech-badge">XAMPP (dev)</span>
			</div>
		</div>
	</div>

	<div class="card section-card shadow-sm mt-4">
		<div class="card-body d-flex flex-wrap justify-content-between align-items-center">
			<div>
				<div class="section-title mb-2">Kontak & Pengembangan</div>
				<p class="mb-0">Punya ide fitur atau perbaikan tampilan? Hubungi pengelola melalui email yang tersedia di repositori atau footer.</p>
			</div>
			<a href="index.php" class="btn btn-primary mt-3 mt-md-0">Kembali ke Beranda</a>
		</div>
	</div>
</div>

<div class="text-center mt-4 mb-4 text-muted">
	Regional News Indonesia © <?= date("Y") ?>
</div>

</body>
</html>
