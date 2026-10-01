<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];

// Pilihan cara bayar & cara kirim yang boleh dipilih.
// Kunci array = value yang dikirim form, isi = biaya tambahan (khusus pengiriman).
$metode_bayar_valid = ['Transfer Bank', 'COD (Bayar di Tempat)', 'QRIS'];
$metode_kirim_valid = [
    'Ambil di Toko' => 0,
    'Diantar (Kurir)' => 10000,
];

// ================= TAMBAH KE KERANJANG =================
if (isset($_POST['add']) && isset($_GET['id'])) {
    $id_produk = (int) $_GET['id'];
    $jumlah    = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1;
    if ($jumlah < 1) {
        $jumlah = 1;
    }

    // Ambil data produk asli dari database — jangan percaya harga dari form,
    // supaya harga tidak bisa dimanipulasi lewat hidden input.
    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, harga, foto FROM tb_produk WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);
    $produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($produk) {
        if (isset($_SESSION['cart'][$id_produk])) {
            $_SESSION['cart'][$id_produk]['jumlah'] += $jumlah;
        } else {
            $_SESSION['cart'][$id_produk] = [
                'id'     => $produk['id'],
                'nama'   => $produk['nama'],
                'harga'  => $produk['harga'],
                'foto'   => $produk['foto'],
                'jumlah' => $jumlah,
            ];
        }
        set_swal('success', 'Masuk keranjang', '"' . $produk['nama'] . '" berhasil ditambahkan ke keranjang.');
    }

    header('Location: keranjang.php');
    exit;
}

// ================= CHECKOUT (dengan metode bayar & kirim) =================
if (isset($_POST['checkout'])) {
    $metode_bayar = $_POST['metode_bayar'] ?? '';
    $metode_kirim = $_POST['metode_kirim'] ?? '';

    if (empty($_SESSION['cart'])) {
        header('Location: keranjang.php');
        exit;
    }

    if (!in_array($metode_bayar, $metode_bayar_valid, true) || !array_key_exists($metode_kirim, $metode_kirim_valid)) {
        set_swal('warning', 'Belum lengkap', 'Pilih dulu cara bayar dan cara kirimnya, ya.');
        header('Location: keranjang.php');
        exit;
    }

    $subtotal_produk = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal_produk += $item['jumlah'] * $item['harga'];
    }
    $biaya_kirim = $metode_kirim_valid[$metode_kirim];
    $total       = $subtotal_produk + $biaya_kirim;

    $tanggal = date('Y-m-d');
    $stmtTransaksi = mysqli_prepare($koneksi, "INSERT INTO tb_transaksi (tanggal, id_pelanggan, total_harga, metode_bayar, metode_kirim, biaya_kirim) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmtTransaksi, "siissi", $tanggal, $id_user, $total, $metode_bayar, $metode_kirim, $biaya_kirim);
    mysqli_stmt_execute($stmtTransaksi);
    $id_transaksi = mysqli_insert_id($koneksi);

    $stmtDetail = mysqli_prepare($koneksi, "INSERT INTO tb_detail (id_transaksi, id_produk, jumlah) VALUES (?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        $id_produk_item = (int) $item['id'];
        $jumlah_item    = (int) $item['jumlah'];
        mysqli_stmt_bind_param($stmtDetail, "iii", $id_transaksi, $id_produk_item, $jumlah_item);
        mysqli_stmt_execute($stmtDetail);
    }

    unset($_SESSION['cart']);
    set_swal('success', 'Pesanan berhasil dibuat', 'Terima kasih! Pesananmu sedang kami proses.');
    header('Location: cetak.php?id=' . $id_transaksi);
    exit;
}

// ================= HAPUS =================
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_produk = (int) $_GET['id'];
    if (isset($_SESSION['cart'][$id_produk])) {
        unset($_SESSION['cart'][$id_produk]);
        set_swal('success', 'Dihapus', 'Produk dihapus dari keranjang.');
    }
    header('Location: keranjang.php');
    exit;
}

function rp($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$subtotal_produk = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $value) {
        $subtotal_produk += $value['jumlah'] * $value['harga'];
    }
}
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Keranjang · Dimsum Narawi</title>
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
      .keranjang-card {
        background: var(--dimsum-card-bg);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(43, 27, 23, 0.08);
      }
      .thumb-produk {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        object-fit: cover;
      }
      .thumb-placeholder {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--dimsum-maroon), #B3452F);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.65rem;
      }
      .pilihan-metode {
        cursor: pointer;
        border: 1px solid rgba(43, 27, 23, 0.15);
        border-radius: 10px;
        transition: border-color .15s, background-color .15s;
      }
      .pilihan-metode:hover {
        border-color: var(--dimsum-maroon);
      }
      .pilihan-metode input:checked ~ .pilihan-metode-label,
      .pilihan-metode:has(input:checked) {
        border-color: var(--dimsum-maroon);
        background-color: rgba(122, 31, 43, 0.06);
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

    <main class="container py-5">
      <h1 class="h3 mb-4">Keranjangmu</h1>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($flash['pesan']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['cart'])): ?>
        <div class="keranjang-card p-3 p-md-4 mb-4">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Produk</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-center">Harga Satuan</th>
                  <th class="text-center">Subtotal</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($_SESSION['cart'] as $value):
                    $path_foto = 'img/' . $value['foto'];
                    $ada_foto  = !empty($value['foto']) && file_exists($path_foto);
                ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <?php if ($ada_foto): ?>
                          <img src="<?= htmlspecialchars($path_foto) ?>" class="thumb-produk" alt="<?= htmlspecialchars($value['nama']) ?>">
                        <?php else: ?>
                          <div class="thumb-placeholder">Foto<br>menyusul</div>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($value['nama']) ?></span>
                      </div>
                    </td>
                    <td class="text-center"><?= (int) $value['jumlah'] ?></td>
                    <td class="text-center"><?= rp($value['harga']) ?></td>
                    <td class="text-center fw-semibold"><?= rp($value['jumlah'] * $value['harga']) ?></td>
                    <td class="text-center">
                      <a href="keranjang.php?aksi=hapus&id=<?= (int) $value['id'] ?>"
                         class="btn btn-sm btn-outline-danger"
                         data-swal-title="Hapus dari keranjang?" data-swal-confirm="Produk ini akan dihapus dari keranjangmu." data-swal-yes="Ya, hapus">
                        Hapus
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="text-end fw-semibold">Subtotal Produk</td>
                  <td class="text-center fw-bold" id="subtotalProdukText"><?= rp($subtotal_produk) ?></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <form method="post" class="keranjang-card p-3 p-md-4" data-swal-title="Beli sekarang?" data-swal-confirm="Pesanan akan dibuat sesuai cara bayar dan cara kirim yang kamu pilih." data-swal-icon="question" data-swal-yes="Ya, beli">
          <h2 class="h5 fw-bold mb-3">Pengiriman &amp; Pembayaran</h2>

          <div class="mb-4">
            <p class="fw-semibold mb-2">Cara Pengiriman</p>
            <div class="row g-2">
              <?php $i = 0; foreach ($metode_kirim_valid as $label => $biaya): $i++; ?>
                <div class="col-md-6">
                  <label class="pilihan-metode d-flex align-items-center justify-content-between p-3 mb-0 w-100">
                    <span>
                      <input class="form-check-input me-2 metode-kirim-input" type="radio" name="metode_kirim"
                             value="<?= htmlspecialchars($label) ?>" data-biaya="<?= (int) $biaya ?>"
                             <?= $i === 1 ? 'checked' : '' ?> required>
                      <?= htmlspecialchars($label) ?>
                    </span>
                    <span class="text-body-secondary small"><?= $biaya > 0 ? rp($biaya) : 'Gratis' ?></span>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-4">
            <p class="fw-semibold mb-2">Cara Pembayaran</p>
            <div class="row g-2">
              <?php foreach ($metode_bayar_valid as $j => $label): ?>
                <div class="col-md-4">
                  <label class="pilihan-metode d-flex align-items-center p-3 mb-0 w-100">
                    <input class="form-check-input me-2" type="radio" name="metode_bayar"
                           value="<?= htmlspecialchars($label) ?>" <?= $j === 0 ? 'checked' : '' ?> required>
                    <?= htmlspecialchars($label) ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="border-top pt-3">
            <div class="d-flex justify-content-between">
              <span>Subtotal Produk</span>
              <span id="ringkasSubtotal"><?= rp($subtotal_produk) ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Biaya Pengiriman</span>
              <span id="ringkasBiayaKirim">Gratis</span>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold mt-2">
              <span>Total yang harus dibayar</span>
              <span id="ringkasTotal"><?= rp($subtotal_produk) ?></span>
            </div>
          </div>

          <button type="submit" name="checkout" class="btn btn-bd-primary w-100 mt-3">Beli</button>
        </form>

        <script>
          (function () {
            var subtotalProduk = <?= (int) $subtotal_produk ?>;
            var inputsKirim = document.querySelectorAll('.metode-kirim-input');
            var elBiayaKirim = document.getElementById('ringkasBiayaKirim');
            var elTotal = document.getElementById('ringkasTotal');

            function formatRupiah(angka) {
              return 'Rp ' + angka.toLocaleString('id-ID');
            }

            function updateRingkasan() {
              var biaya = 0;
              inputsKirim.forEach(function (input) {
                if (input.checked) {
                  biaya = parseInt(input.getAttribute('data-biaya'), 10) || 0;
                }
              });
              elBiayaKirim.textContent = biaya > 0 ? formatRupiah(biaya) : 'Gratis';
              elTotal.textContent = formatRupiah(subtotalProduk + biaya);
            }

            inputsKirim.forEach(function (input) {
              input.addEventListener('change', updateRingkasan);
            });
            updateRingkasan();
          })();
        </script>
      <?php else: ?>
        <div class="keranjang-card p-5 text-center">
          <p class="mb-3">Keranjangmu masih kosong.</p>
          <a href="index.php#menu" class="btn btn-bd-primary">Lihat menu</a>
        </div>
      <?php endif; ?>
    </main>

    <footer class="container py-4 border-top">
      <p class="text-muted mb-0">&copy; 2025–2026 Dimsum Narawi.</p>
    </footer>

    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
  <?php swal_render(); ?>
</body>
</html>