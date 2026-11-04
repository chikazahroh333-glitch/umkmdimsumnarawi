<?php
session_start();
include 'koneksi.php';

// ----- ambil data produk dengan aman (bukan tempel string ke SQL) -----
$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT p.*, k.nama_kategori
                                  FROM tb_produk p
                                  LEFT JOIN tb_kategori k ON p.id_kategori = k.id_kategori
                                  WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$produk) {
    header('Location: index.php');
    exit;
}

function esc($teks) {
    return htmlspecialchars($teks ?? '');
}
function rp($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

$path_foto = 'img/' . $produk['foto'];
$ada_foto  = !empty($produk['foto']) && file_exists($path_foto);
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= esc($produk['nama']) ?> · Dimsum Narawi</title>
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
        font-family: 'Inter', system-ui, sans-serif;
      }
      [data-bs-theme="dark"] {
        --dimsum-cream: #1E1512;
        --dimsum-ink: #F3E9DD;
        --dimsum-card-bg: #2A1F1B;
      }
      body {
        background-color: var(--dimsum-cream);
        color: var(--dimsum-ink);
      }
      h1, h2, h3 {
        font-family: 'Fraunces', Georgia, serif;
      }
      .bi { vertical-align: -0.125em; fill: currentColor; }
      .bd-mode-toggle { z-index: 1500; }
      .bd-mode-toggle .bi { width: 1em; height: 1em; }
      .bd-mode-toggle .dropdown-menu .active .bi { display: block !important; }
      .btn-bd-primary {
        --bd-violet-bg: var(--dimsum-maroon);
        --bd-violet-rgb: 122, 31, 43;
        --bs-btn-font-weight: 600;
        --bs-btn-color: var(--bs-white);
        --bs-btn-bg: var(--bd-violet-bg);
        --bs-btn-border-color: var(--bd-violet-bg);
        --bs-btn-hover-color: var(--bs-white);
        --bs-btn-hover-bg: var(--dimsum-maroon-dark);
        --bs-btn-hover-border-color: var(--dimsum-maroon-dark);
        --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
        --bs-btn-active-color: var(--bs-btn-hover-color);
        --bs-btn-active-bg: var(--dimsum-maroon-dark);
        --bs-btn-active-border-color: var(--dimsum-maroon-dark);
      }
      .detail-card {
        background: var(--dimsum-card-bg);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(43, 27, 23, 0.08);
        overflow: hidden;
      }
      .detail-foto {
        width: 100%;
        height: 100%;
        min-height: 320px;
        object-fit: cover;
      }
      .detail-foto-placeholder {
        width: 100%;
        height: 100%;
        min-height: 320px;
        background: linear-gradient(135deg, var(--dimsum-maroon), #B3452F);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
      }
      .jumlah-input {
        width: 90px;
      }
    </style>
  </head>
  <body>
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
      <symbol id="check2" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"></path></symbol>
      <symbol id="circle-half" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"></path></symbol>
      <symbol id="moon-stars-fill" viewBox="0 0 16 16"><path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"></path><path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.734 1.734 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.734 1.734 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.734 1.734 0 0 0 1.097-1.097l.387-1.162zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L13.863.1z"></path></symbol>
      <symbol id="sun-fill" viewBox="0 0 16 16"><path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"></path></symbol>
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

    <main class="container" style="padding-top: 90px; padding-bottom: 40px;">
      <p class="mb-4">
        <a href="index.php#menu" class="text-decoration-none">&laquo; Kembali ke menu</a>
      </p>

      <div class="detail-card">
        <div class="row g-0">
          <div class="col-md-5">
            <?php if ($ada_foto): ?>
              <img src="<?= esc($path_foto) ?>" alt="<?= esc($produk['nama']) ?>" class="detail-foto">
            <?php else: ?>
              <div class="detail-foto-placeholder">Foto menyusul</div>
            <?php endif; ?>
          </div>
          <div class="col-md-7">
            <div class="p-4 p-md-5">
              <?php if (!empty($produk['nama_kategori'])): ?>
                <span class="badge mb-2" style="background-color: var(--dimsum-gold); color: var(--dimsum-ink);">
                  <?= esc($produk['nama_kategori']) ?>
                </span>
              <?php endif; ?>

              <h1 class="h3 fw-bold mb-2"><?= esc($produk['nama']) ?></h1>
              <p class="fs-4 fw-semibold mb-3" style="color: var(--dimsum-maroon);"><?= rp($produk['harga']) ?></p>
              <p class="mb-4"><?= nl2br(esc($produk['deskripsi'])) ?></p>

              <form method="post" action="keranjang.php?id=<?= (int) $produk['id'] ?>" class="d-flex align-items-center gap-2">
                <label for="jumlah" class="form-label mb-0 me-1">Jumlah</label>
                <input type="number" id="jumlah" name="jumlah" value="1" min="1" class="form-control jumlah-input">
                <button type="submit" name="add" class="btn btn-bd-primary">Tambah ke keranjang</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="container py-4 border-top">
      <p class="text-muted mb-0">&copy; 2025–2026 Dimsum Narawi.</p>
    </footer>

    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
  <?php swal_render(); ?>
</body>
</html>