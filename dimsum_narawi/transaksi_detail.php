<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

function esc($teks) {
    return htmlspecialchars($teks ?? '');
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

$id = (int) ($_GET['id'] ?? 0);

// ----- data transaksi + pembeli -----
$stmt = mysqli_prepare($koneksi, "SELECT t.*, u.nama, u.username, u.email, u.hp, u.alamat
                                  FROM tb_transaksi t
                                  LEFT JOIN tb_user u ON t.id_pelanggan = u.id
                                  WHERE t.id_transaksi = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$trx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trx) {
    header('Location: transaksi.php');
    exit;
}

// ----- item yang dibeli (harga diambil dari tb_produk) -----
$stmt = mysqli_prepare($koneksi, "SELECT d.jumlah, p.nama AS nama_produk, p.harga
                                  FROM tb_detail d
                                  LEFT JOIN tb_produk p ON d.id_produk = p.id
                                  WHERE d.id_transaksi = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

$judul_halaman = 'Transaksi';
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
<?php include 'admin_head.php'; ?>
<body>
<?php include 'admin_nav.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Detail Transaksi #<?= (int) $trx['id_transaksi'] ?></h1>
        <a href="transaksi.php" class="btn btn-outline-secondary">&larr; Kembali</a>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">Info Transaksi</div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-4">Tanggal</dt>
                <dd class="col-sm-8"><?= esc(date('d M Y', strtotime($trx['tanggal']))) ?></dd>
                <?php if (!empty($trx['status'])): ?>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8"><span class="badge <?= kelas_status($trx['status']) ?>"><?= esc($trx['status']) ?></span></dd>
                <?php endif; ?>
                <?php if (!empty($trx['metode_bayar'])): ?>
                <dt class="col-sm-4">Cara Bayar</dt>
                <dd class="col-sm-8"><?= esc($trx['metode_bayar']) ?></dd>
                <?php endif; ?>
                <?php if (!empty($trx['metode_kirim'])): ?>
                <dt class="col-sm-4">Cara Kirim</dt>
                <dd class="col-sm-8"><?= esc($trx['metode_kirim']) ?></dd>
                <?php endif; ?>
                <dt class="col-sm-4">Total</dt>
                <dd class="col-sm-8 fw-semibold"><?= rp($trx['total_harga']) ?></dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">Data Pembeli</div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-4">Nama</dt>
                <dd class="col-sm-8"><?= esc($trx['nama'] ?: '-') ?></dd>
                <dt class="col-sm-4">Username</dt>
                <dd class="col-sm-8"><?= esc($trx['username'] ?: '-') ?></dd>
                <dt class="col-sm-4">No. HP</dt>
                <dd class="col-sm-8"><?= esc($trx['hp'] ?: '-') ?></dd>
                <dt class="col-sm-4">Alamat</dt>
                <dd class="col-sm-8"><?= esc($trx['alamat'] ?: '-') ?></dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <h2 class="h4">Barang yang Dibeli</h2>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Produk</th>
              <th class="text-end">Harga</th>
              <th class="text-center">Jumlah</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($it = mysqli_fetch_assoc($items)): ?>
            <tr>
              <td><?= esc($it['nama_produk'] ?: '(produk dihapus)') ?></td>
              <td class="text-end"><?= rp($it['harga']) ?></td>
              <td class="text-center"><?= (int) $it['jumlah'] ?></td>
              <td class="text-end"><?= rp($it['harga'] * $it['jumlah']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" class="text-end">Total</th>
              <th class="text-end"><?= rp($trx['total_harga']) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </main>
  </div>
</div>

<script src="assets/dist/js/bootstrap.bundle.min.js"></script>
<?php swal_render(); ?>
</body>
</html>