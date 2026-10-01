<?php
session_start();
include 'koneksi.php';

// Kalau belum login, tendang ke halaman login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

// Ambil data user yang sedang login
$stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_user WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_user']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    // Session ada tapi datanya sudah tidak ada di database
    session_destroy();
    header('Location: login.php');
    exit;
}

// Foto profil (kalau belum ada, tampilkan lingkaran dengan huruf awal nama)
$path_foto = !empty($user['foto']) ? 'img/profil/' . basename($user['foto']) : '';
$ada_foto  = $path_foto !== '' && file_exists($path_foto);
$inisial   = strtoupper(mb_substr(($user['nama'] ?? '') !== '' ? $user['nama'] : $user['username'], 0, 1));

// Helper biar aman dari XSS dan tidak kosong melompong kalau NULL
function tampil($teks) {
    return $teks !== null && $teks !== ''
        ? nl2br(htmlspecialchars($teks))
        : '<span class="text-body-secondary fst-italic">Belum diisi</span>';
}
function rp($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}
function kelas_status($status) {
    switch ($status) {
        case 'Selesai':             return 'bg-success';
        case 'Dibatalkan':          return 'bg-secondary';
        case 'Diproses':            return 'bg-info text-dark';
        case 'Menunggu Pembayaran': return 'bg-warning text-dark';
        default:                    return 'bg-warning text-dark';
    }
}

// Riwayat transaksi (hanya untuk akun pelanggan)
$riwayat = null;
if ($user['role'] === 'pelanggan') {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_transaksi WHERE id_pelanggan = ? ORDER BY tanggal DESC, id_transaksi DESC");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_user']);
    mysqli_stmt_execute($stmt);
    $riwayat = mysqli_stmt_get_result($stmt);
}
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profil Saya</title>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet" />
  </head>
  <body class="bg-body-tertiary">
    <?php include 'navbar.php'; ?>

    <main class="container" style="padding-top: 90px; padding-bottom: 40px;">
      <div class="row justify-content-center">
        <div class="col-lg-8">

          <div class="card shadow-sm">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($ada_foto): ?>
                    <img src="<?= htmlspecialchars($path_foto) ?>" alt="Foto profil" class="rounded-circle border"
                         style="width: 96px; height: 96px; object-fit: cover;">
                  <?php else: ?>
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fs-1 fw-bold"
                         style="width: 96px; height: 96px;"><?= htmlspecialchars($inisial) ?></div>
                  <?php endif; ?>
                  <div>
                    <h1 class="h4 fw-bold mb-1">Profil Saya</h1>
                    <span class="badge bg-secondary"><?= htmlspecialchars($user['role']) ?></span>
                  </div>
                </div>
                <a href="edit_profile.php" class="btn btn-primary">Edit profil</a>
              </div>

              <table class="table table-borderless align-middle mb-0">
                <tbody>
                  <tr>
                    <th class="w-25 text-body-secondary fw-normal">Username</th>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                  </tr>
                  <tr>
                    <th class="text-body-secondary fw-normal">Nama lengkap</th>
                    <td><?= tampil($user['nama']) ?></td>
                  </tr>
                  <tr>
                    <th class="text-body-secondary fw-normal">Email</th>
                    <td><?= tampil($user['email']) ?></td>
                  </tr>
                  <tr>
                    <th class="text-body-secondary fw-normal">No. HP</th>
                    <td><?= tampil($user['hp']) ?></td>
                  </tr>
                  <tr>
                    <th class="text-body-secondary fw-normal">Alamat</th>
                    <td><?= tampil($user['alamat']) ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <?php if ($riwayat !== null): ?>
          <div class="card shadow-sm mt-4">
            <div class="card-body p-4">
              <h2 class="h5 fw-bold mb-3">Riwayat Transaksi</h2>

              <?php if (mysqli_num_rows($riwayat) === 0): ?>
                <p class="text-body-secondary mb-0">Belum ada transaksi.</p>
              <?php else: ?>
                <?php while ($trx = mysqli_fetch_assoc($riwayat)):
                    // ambil daftar barang yang dibeli di transaksi ini
                    $stmtItem = mysqli_prepare($koneksi, "SELECT d.jumlah, p.nama AS nama_produk, p.harga, p.foto
                                                          FROM tb_detail d
                                                          LEFT JOIN tb_produk p ON d.id_produk = p.id
                                                          WHERE d.id_transaksi = ?");
                    mysqli_stmt_bind_param($stmtItem, "i", $trx['id_transaksi']);
                    mysqli_stmt_execute($stmtItem);
                    $items = mysqli_stmt_get_result($stmtItem);
                ?>
                <div class="border rounded-3 p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                      <span class="fw-semibold">Invoice #<?= (int) $trx['id_transaksi'] ?></span>
                      <span class="text-body-secondary small ms-2"><?= htmlspecialchars(date('d M Y', strtotime($trx['tanggal']))) ?></span>
                    </div>
                    <span class="badge <?= kelas_status($trx['status']) ?>"><?= htmlspecialchars($trx['status']) ?></span>
                  </div>

                  <?php while ($it = mysqli_fetch_assoc($items)):
                      $path_foto_produk = 'img/' . $it['foto'];
                      $ada_foto_produk  = !empty($it['foto']) && file_exists($path_foto_produk);
                  ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                      <?php if ($ada_foto_produk): ?>
                        <img src="<?= htmlspecialchars($path_foto_produk) ?>" alt="<?= htmlspecialchars($it['nama_produk'] ?? '') ?>"
                             class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                      <?php else: ?>
                        <div class="rounded bg-secondary-subtle d-flex align-items-center justify-content-center text-body-secondary"
                             style="width: 48px; height: 48px; font-size: .6rem;">Foto</div>
                      <?php endif; ?>
                      <div class="flex-grow-1">
                        <div><?= htmlspecialchars($it['nama_produk'] ?: '(produk sudah dihapus)') ?></div>
                        <div class="text-body-secondary small"><?= (int) $it['jumlah'] ?> x <?= rp($it['harga']) ?></div>
                      </div>
                      <div class="fw-semibold"><?= rp($it['harga'] * $it['jumlah']) ?></div>
                    </div>
                  <?php endwhile; ?>

                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-top pt-2 mt-2">
                    <div class="text-body-secondary small">
                      <?= htmlspecialchars($trx['metode_kirim'] ?: '-') ?> &middot; <?= htmlspecialchars($trx['metode_bayar'] ?: '-') ?>
                    </div>
                    <div class="fw-bold">Total: <?= rp($trx['total_harga']) ?></div>
                  </div>

                  <div class="text-end mt-2">
                    <a href="cetak.php?id=<?= (int) $trx['id_transaksi'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Cetak Invoice</a>
                  </div>
                </div>
                <?php endwhile; ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <p class="mt-3">
            <a href="index.php" class="text-decoration-none">&laquo; Kembali ke beranda</a>
          </p>
        </div>
      </div>
    </main>

    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
  <?php swal_render(); ?>
</body>
</html>