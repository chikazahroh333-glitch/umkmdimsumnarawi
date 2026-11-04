<?php
// navbar.php — dipakai bersama di index.php, profile.php, edit_profile.php, keranjang.php
// Pastikan session_start() sudah dipanggil di file yang meng-include ini.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----- jumlah barang di keranjang (untuk angka di ikon) -----
$jumlah_keranjang_nav = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $it_nav) {
        $jumlah_keranjang_nav += (int) $it_nav['jumlah'];
    }
}

// ----- foto profil kecil di navbar (kalau user sudah punya) -----
$foto_nav = '';
if (isset($_SESSION['id_user']) && isset($koneksi)) {
    try {
        $stmt_nav = mysqli_prepare($koneksi, "SELECT foto FROM tb_user WHERE id = ?");
        mysqli_stmt_bind_param($stmt_nav, "i", $_SESSION['id_user']);
        mysqli_stmt_execute($stmt_nav);
        $row_nav = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_nav));
        if (!empty($row_nav['foto']) && file_exists('img/profil/' . basename($row_nav['foto']))) {
            $foto_nav = 'img/profil/' . basename($row_nav['foto']);
        }
    } catch (Throwable $e) {
        // kolom foto belum ada / query gagal: abaikan, navbar tetap tampil tanpa foto
    }
}
?>
<header data-bs-theme="dark">
  <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">Dimsum Narawi</a>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarCollapse"
        aria-controls="navbarCollapse"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav me-auto mb-2 mb-md-0">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php#menu">Menu</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php#kontak">Kontak</a>
          </li>
        </ul>

        <ul class="navbar-nav align-items-md-center mb-2 mb-md-0">
          <?php if (isset($_SESSION['username'])) { ?>
            <!-- Ikon keranjang -->
            <li class="nav-item">
              <a class="nav-link" href="keranjang.php" aria-label="Keranjang">
                <span class="position-relative d-inline-block">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                  </svg>
                  <?php if ($jumlah_keranjang_nav > 0) { ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                      <?= $jumlah_keranjang_nav > 99 ? '99+' : $jumlah_keranjang_nav ?>
                      <span class="visually-hidden">barang di keranjang</span>
                    </span>
                  <?php } ?>
                </span>
                <span class="d-md-none ms-2">Keranjang</span>
              </a>
            </li>

            <!-- Akun -->
            <li class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <?php if ($foto_nav !== '') { ?>
                  <img src="<?= htmlspecialchars($foto_nav) ?>" alt="" class="rounded-circle"
                       style="width: 28px; height: 28px; object-fit: cover;">
                <?php } else { ?>
                  <span class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                        style="width: 28px; height: 28px; font-size: 0.8rem;">
                    <?= htmlspecialchars(strtoupper(mb_substr($_SESSION['username'], 0, 1))) ?>
                  </span>
                <?php } ?>
                Halo, <?= htmlspecialchars($_SESSION['username']) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php">Profil saya</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item text-danger" href="logout.php" data-swal-title="Logout?" data-swal-confirm="Kamu yakin mau keluar dari akun?" data-swal-icon="question" data-swal-yes="Ya, logout">Logout</a></li>
              </ul>
            </li>
          <?php } else { ?>
            <li class="nav-item">
              <a class="nav-link" href="login.php">Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="register.php">Register</a>
            </li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </nav>
</header>