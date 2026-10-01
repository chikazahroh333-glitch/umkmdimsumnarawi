<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// Palet gradient buat placeholder foto produk — dipilih bergilir per produk
// biar tiap kartu nggak keliatan seragam kayak template.
$palet_placeholder = [
    ['#7A1F2B', '#B3452F'], // merah marun -> bata
    ['#8A5A00', '#C98A1E'], // cokelat tua -> emas
    ['#5C3A21', '#8A5A3A'], // cokelat kayu
    ['#6B2737', '#A63D4F'], // maroon keunguan
];

function nama_file_aman($teks) {
    return htmlspecialchars($teks ?? '');
}

// Ambil semua kategori, tiap kategori nanti diquery produknya masing-masing.
$kategoriList = mysqli_query($koneksi, "SELECT * FROM tb_kategori ORDER BY id_kategori ASC");

// Foto hero carousel — taruh file dengan nama ini di folder img/ untuk menggantikan ikon.
// Kalau filenya belum ada, otomatis fallback ke ikon steamer seperti sekarang.
$hero_foto_1 = 'img/hero-1.jpg';
$hero_foto_2 = 'img/hero-2.jpg';
$ada_hero_1  = file_exists($hero_foto_1);
$ada_hero_2  = file_exists($hero_foto_2);
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dimsum Narawi — dimsum rumahan, dikukus fresh setiap hari." />
    <title>Dimsum Narawi</title>
    <script src="assets/js/color-modes.js"></script>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#7A1F2B" />
    <style>
      :root {
        --dimsum-maroon: #7A1F2B;
        --dimsum-maroon-dark: #5A1620;
        --dimsum-gold: #D9A441;
        --dimsum-cream: #FBF3E7;
        --dimsum-ink: #2B1B17;
        --dimsum-card-bg: #FFFFFF;
        --dimsum-badge-bg: #FBF3E7;
        --dimsum-badge-text: #7A1F2B;
        --dimsum-price-color: #7A1F2B;
        --dimsum-sub-text: #7a685f;
        font-family: 'Inter', system-ui, sans-serif;
      }
      [data-bs-theme="dark"] {
        --dimsum-cream: #1E1512;
        --dimsum-ink: #F3E9DD;
        --dimsum-card-bg: #2A1F1B;
        --dimsum-badge-bg: #3A2A23;
        --dimsum-badge-text: #E8B65A;
        --dimsum-price-color: #E8B65A;
        --dimsum-sub-text: #C9B6A8;
      }
      html {
        scroll-behavior: smooth;
      }
      body {
        background-color: var(--dimsum-cream);
        color: var(--dimsum-ink);
      }
      #menu {
        scroll-margin-top: 90px; /* biar heading kategori nggak ketutup navbar fixed-top */
      }
      h1, h2, h3, .brand-font {
        font-family: 'Fraunces', Georgia, serif;
      }
      .bi { vertical-align: -0.125em; fill: currentColor; }
      .bd-mode-toggle { z-index: 1500; }
      .bd-mode-toggle .bi { width: 1em; height: 1em; }
      .bd-mode-toggle .dropdown-menu .active .bi { display: block !important; }
      .btn-bd-primary {
        --bs-btn-font-weight: 600;
        --bs-btn-color: #fff;
        --bs-btn-bg: var(--dimsum-maroon);
        --bs-btn-border-color: var(--dimsum-maroon);
        --bs-btn-hover-bg: var(--dimsum-maroon-dark);
        --bs-btn-hover-border-color: var(--dimsum-maroon-dark);
        --bs-btn-active-bg: var(--dimsum-maroon-dark);
        --bs-btn-active-border-color: var(--dimsum-maroon-dark);
      }

      /* ---------- Hero carousel ---------- */
      .hero-slide {
        min-height: 62vh;
        display: flex;
        align-items: center;
        background: linear-gradient(120deg, var(--dimsum-maroon) 0%, #9C3A2E 100%);
        color: #fff;
        position: relative;
        overflow: hidden;
      }
      .hero-slide.alt {
        background: linear-gradient(120deg, #5A3A1E 0%, var(--dimsum-gold) 140%);
      }
      .hero-slide .container {
        position: relative;
        z-index: 2; /* di atas panah carousel (z-index: 1) biar tombol beneran keklik */
      }
      .hero-slide::after {
        content: "";
        position: absolute;
        right: -6%;
        bottom: -18%;
        width: 480px;
        height: 480px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        pointer-events: none; /* biar lingkaran dekorasi ini nggak nangkep klik tombol */
      }
      .hero-slide .steam-icon {
        width: 120px;
        height: 120px;
        opacity: 0.9;
      }
      .hero-slide-photo {
        width: 100%;
        max-width: 380px;
        height: 320px;
        object-fit: cover;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      }
      .hero-eyebrow {
        color: var(--dimsum-gold);
        font-weight: 600;
        letter-spacing: 0.02em;
      }
      .hero-slide.alt .hero-eyebrow { color: #fff5e0; }
      .hero-headline {
        font-size: clamp(2rem, 4.5vw, 3.4rem);
        line-height: 1.08;
        max-width: 14ch;
      }
      .hero-slide .lead { max-width: 42ch; color: rgba(255,255,255,0.9); }
      .carousel-indicators [data-bs-target] {
        background-color: rgba(255,255,255,0.55);
      }
      .carousel-indicators .active { background-color: #fff; }
      /* Perlambat gerakan geser slide (bawaan Bootstrap 0.6 detik) */
      #heroCarousel .carousel-item {
        transition: transform 1.3s ease-in-out;
      }

      /* ---------- Section heading ---------- */
      .section-heading {
        font-size: clamp(1.6rem, 2.6vw, 2.1rem);
        margin-bottom: 0.25rem;
      }
      .section-sub {
        color: var(--dimsum-sub-text);
      }

      /* ---------- Kartu produk ---------- */
      .produk-card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        background: var(--dimsum-card-bg);
        box-shadow: 0 4px 18px rgba(43, 27, 23, 0.08);
      }
      .produk-thumb {
        height: 170px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
      }
      .produk-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .produk-thumb .steam-icon {
        width: 56px;
        height: 56px;
        color: rgba(255,255,255,0.9);
      }
      .produk-thumb .placeholder-label {
        position: absolute;
        bottom: 8px;
        left: 12px;
        font-size: 0.7rem;
        color: rgba(255,255,255,0.75);
      }
      .harga-tag {
        color: var(--dimsum-price-color);
        font-family: 'Fraunces', serif;
        font-weight: 600;
      }
      .badge-kategori {
        background: var(--dimsum-badge-bg);
        color: var(--dimsum-badge-text);
        font-weight: 500;
      }
      footer.border-top {
        border-color: rgba(122, 31, 43, 0.15) !important;
      }
      [data-bs-theme="dark"] footer.border-top {
        border-color: rgba(232, 182, 90, 0.2) !important;
      }

      /* ---------- Kenapa Pilih Kami ---------- */
      .alasan-section {
        background: var(--dimsum-card-bg);
      }
      .alasan-icon {
        font-size: 2rem;
        line-height: 1;
      }

      /* ---------- Kontak & Lokasi ---------- */
      .kontak-section {
        background: linear-gradient(120deg, var(--dimsum-maroon) 0%, #9C3A2E 100%);
        color: #fff;
        border-radius: 18px;
      }
      .kontak-section a {
        color: #fff;
      }
      .kontak-link {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
      }
    </style>
  </head>
  <body>
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
      <symbol id="check2" viewBox="0 0 16 16">
        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"></path>
      </symbol>
      <symbol id="circle-half" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"></path>
      </symbol>
      <symbol id="moon-stars-fill" viewBox="0 0 16 16">
        <path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"></path>
        <path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.734 1.734 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.734 1.734 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.734 1.734 0 0 0 1.097-1.097l.387-1.162zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L13.863.1z"></path>
      </symbol>
      <symbol id="sun-fill" viewBox="0 0 16 16">
        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"></path>
      </symbol>
      <!-- Ikon steamer basket sederhana, dipakai untuk hero & placeholder foto produk -->
      <symbol id="steam-basket" viewBox="0 0 64 64">
        <path d="M10 30h44l-4 22a4 4 0 0 1-4 3.3H18a4 4 0 0 1-4-3.3L10 30z" fill="none" stroke="currentColor" stroke-width="2.5"/>
        <path d="M14 30l3-7h30l3 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M20 23l2-6M32 23v-7M44 23l-2-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M22 8c2 3 2 5 0 8M32 6c2 3 2 5 0 8M42 8c2 3 2 5 0 8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
      </symbol>
    </svg>

    <div class="dropdown position-fixed bottom-0 end-0 mb-3 me-3 bd-mode-toggle">
      <button class="btn btn-bd-primary py-2 dropdown-toggle d-flex align-items-center" id="bd-theme" type="button" aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme (auto)">
        <svg class="bi my-1 theme-icon-active" aria-hidden="true"><use href="#circle-half"></use></svg>
        <span class="visually-hidden" id="bd-theme-text">Toggle theme</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme-text">
        <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false"><svg class="bi me-2 opacity-50" aria-hidden="true"><use href="#sun-fill"></use></svg>Light<svg class="bi ms-auto d-none" aria-hidden="true"><use href="#check2"></use></svg></button></li>
        <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false"><svg class="bi me-2 opacity-50" aria-hidden="true"><use href="#moon-stars-fill"></use></svg>Dark<svg class="bi ms-auto d-none" aria-hidden="true"><use href="#check2"></use></svg></button></li>
        <li><button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true"><svg class="bi me-2 opacity-50" aria-hidden="true"><use href="#circle-half"></use></svg>Auto<svg class="bi ms-auto d-none" aria-hidden="true"><use href="#check2"></use></svg></button></li>
      </ul>
    </div>

    <?php include 'navbar.php'; ?>

    <main>
      <!-- ================= HERO CAROUSEL ================= -->
      <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="false">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        </div>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="hero-slide">
              <div class="container">
                <div class="row align-items-center">
                  <div class="col-lg-8">
                    <p class="hero-eyebrow mb-2">Dimsum Narawi</p>
                    <h1 class="hero-headline mb-3 text-white">Dimsum rumahan, dikukus fresh tiap hari</h1>
                    <p class="lead mb-4">Nggak pakai bahan pengawet, dibuat dari resep rumahan yang dikukus langsung sebelum sampai ke kamu.</p>
                    <a href="#menu" class="btn btn-light btn-lg fw-semibold" style="color: var(--dimsum-maroon);">Lihat menu</a>
                  </div>
                  <div class="col-lg-4 d-none d-lg-flex justify-content-center">
                    <?php if ($ada_hero_1): ?>
                      <img src="<?= htmlspecialchars($hero_foto_1) ?>" alt="Dimsum Narawi" class="hero-slide-photo">
                    <?php else: ?>
                      <svg class="steam-icon"><use href="#steam-basket"></use></svg>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="hero-slide alt">
              <div class="container">
                <div class="row align-items-center">
                  <div class="col-lg-8">
                    <p class="hero-eyebrow mb-2">Promo Minggu Ini</p>
                    <h1 class="hero-headline mb-3 text-white">Pesan 5 porsi, gratis 1 porsi</h1>
                    <p class="lead mb-4">Cocok buat nemenin kumpul bareng keluarga atau teman kantor.</p>
                    <a href="#menu" class="btn btn-light btn-lg fw-semibold" style="color: var(--dimsum-maroon);">Pesan sekarang</a>
                  </div>
                  <div class="col-lg-4 d-none d-lg-flex justify-content-center">
                    <?php if ($ada_hero_2): ?>
                      <img src="<?= htmlspecialchars($hero_foto_2) ?>" alt="Promo Dimsum Narawi" class="hero-slide-photo">
                    <?php else: ?>
                      <svg class="steam-icon"><use href="#steam-basket"></use></svg>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Sebelumnya</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Berikutnya</span>
        </button>
      </div>

      <!-- ================= KENAPA PILIH KAMI ================= -->
      <section class="alasan-section py-5">
        <div class="container">
          <div class="row g-4 text-center">
            <div class="col-md-4">
              <div class="alasan-icon mb-2">🌿</div>
              <h3 class="h5 fw-bold">Tanpa Pengawet</h3>
              <p class="section-sub mb-0">Dibuat dari bahan segar dan dikukus langsung sebelum dikirim.</p>
            </div>
            <div class="col-md-4">
              <div class="alasan-icon mb-2">🚴</div>
              <h3 class="h5 fw-bold">Pengiriman Cepat</h3>
              <p class="section-sub mb-0">Pesan hari ini, kami proses dan kirim secepatnya ke alamatmu.</p>
            </div>
            <div class="col-md-4">
              <div class="alasan-icon mb-2">⭐</div>
              <h3 class="h5 fw-bold">Resep Rumahan</h3>
              <p class="section-sub mb-0">Resep turun-temurun yang sudah dicoba banyak pelanggan setia.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= DAFTAR PRODUK PER KATEGORI ================= -->
      <div class="container py-5" id="menu">
        <?php
        $ada_kategori = false;
        while ($kat = mysqli_fetch_assoc($kategoriList)):
            $ada_kategori = true;

            $stmtProduk = mysqli_prepare($koneksi, "SELECT * FROM tb_produk WHERE id_kategori = ? ORDER BY nama ASC");
            mysqli_stmt_bind_param($stmtProduk, "i", $kat['id_kategori']);
            mysqli_stmt_execute($stmtProduk);
            $produkList = mysqli_stmt_get_result($stmtProduk);
            $jumlah_produk = mysqli_num_rows($produkList);
        ?>
        <section class="mb-5">
          <h2 class="section-heading"><?= nama_file_aman($kat['nama_kategori']) ?></h2>

          <?php if ($jumlah_produk === 0): ?>
            <p class="section-sub mb-0">Belum ada produk di kategori ini.</p>
          <?php else: ?>
          <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mt-1">
            <?php
            $i = 0;
            while ($p = mysqli_fetch_assoc($produkList)):
                $warna = $palet_placeholder[$i % count($palet_placeholder)];
                $i++;
                $path_foto = 'img/' . $p['foto'];
                $ada_foto  = !empty($p['foto']) && file_exists($path_foto);
            ?>
            <div class="col">
              <div class="card produk-card h-100">
                <div class="produk-thumb" style="background: linear-gradient(135deg, <?= $warna[0] ?>, <?= $warna[1] ?>);">
                  <?php if ($ada_foto): ?>
                    <img src="<?= htmlspecialchars($path_foto) ?>" alt="<?= nama_file_aman($p['nama']) ?>">
                  <?php else: ?>
                    <svg class="steam-icon"><use href="#steam-basket"></use></svg>
                    <span class="placeholder-label">Foto menyusul</span>
                  <?php endif; ?>
                </div>
                <div class="card-body d-flex flex-column">
                  <span class="badge badge-kategori mb-2 align-self-start"><?= nama_file_aman($kat['nama_kategori']) ?></span>
                  <h3 class="h6 fw-bold mb-1"><?= nama_file_aman($p['nama']) ?></h3>
                  <p class="card-text text-muted small flex-grow-1">
                    <?php
                    $deskripsi = trim($p['deskripsi']);
                    echo nama_file_aman(mb_strlen($deskripsi) > 80 ? mb_substr($deskripsi, 0, 80) . '…' : $deskripsi);
                    ?>
                  </p>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="d-flex gap-2">
                      <form action="keranjang.php?id=<?= (int) $p['id'] ?>" method="post">
                        <input type="hidden" name="jumlah" value="1">
                        <button type="submit" name="add" class="btn btn-sm btn-outline-primary">Beli</button>
                      </form>
                      <a href="detail.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                    </div>
                    <span class="harga-tag">Rp <?= number_format($p['harga'], 0, ',', '.') ?></span>
                  </div>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
          <?php endif; ?>
        </section>
        <?php endwhile; ?>

        <?php if (!$ada_kategori): ?>
          <p class="section-sub">Belum ada kategori produk. Tambahkan lewat dashboard admin dulu ya.</p>
        <?php endif; ?>
      </div>

      <!-- ================= KONTAK & LOKASI ================= -->
      <!-- TODO: ganti nomor WA, Instagram, dan alamat di bawah ini dengan data asli tokomu -->
      <section class="container pb-5" id="kontak">
        <div class="kontak-section p-4 p-md-5">
          <div class="row align-items-center g-4">
            <div class="col-lg-7">
              <h2 class="h3 fw-bold mb-2">Kontak &amp; Lokasi</h2>
              <p class="mb-0" style="opacity:.9;">Ada pertanyaan soal pesanan atau mau pesan dalam jumlah banyak? Hubungi kami langsung.</p>
            </div>
            <div class="col-lg-5">
              <ul class="list-unstyled mb-0">
                <li class="kontak-link mb-2">
                  <span>📱</span>
                  <a href="https://wa.me/6281234567890" target="_blank" rel="noopener">WhatsApp: 0812-3456-7890</a>
                </li>
                <li class="kontak-link mb-2">
                  <span>📷</span>
                  <a href="https://instagram.com/dimsumnarawi" target="_blank" rel="noopener">@dimsumnarawi</a>
                </li>
                <li class="kontak-link mb-0">
                  <span>📍</span>
                  <span>Jl. Perjuangan No. 123, Cirebon</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <footer class="container py-4 border-top">
        <p class="float-end"><a href="#">Kembali ke atas</a></p>
        <p class="text-muted mb-0">&copy; 2025–2026 Dimsum Narawi.</p>
      </footer>
    </main>

    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
  <?php swal_render(); ?>
</body>
</html>