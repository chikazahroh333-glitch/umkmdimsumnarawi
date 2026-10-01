<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$aksi = $_GET['aksi'] ?? '';
$status_valid = ['Menunggu Pembayaran', 'Diproses', 'Selesai', 'Dibatalkan'];

// ================= UBAH STATUS =================
if (isset($_POST['update_status']) && isset($_POST['id']) && isset($_POST['status'])) {
    $id_transaksi = (int) $_POST['id'];
    $status_baru  = $_POST['status'];

    if (in_array($status_baru, $status_valid, true)) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tb_transaksi SET status = ? WHERE id_transaksi = ?");
        mysqli_stmt_bind_param($stmt, "si", $status_baru, $id_transaksi);
        mysqli_stmt_execute($stmt);
        set_swal('success', 'Status diperbarui', 'Status pesanan #' . $id_transaksi . ' diubah menjadi "' . $status_baru . '".');
    }
    header('Location: pesanan.php');
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

$judul_halaman = 'Pesanan';
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
<?php include 'admin_head.php'; ?>
<body>
<?php include 'admin_nav.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

      <?php if ($aksi === 'detail' && isset($_GET['id'])):
          $id_transaksi = (int) $_GET['id'];

          $stmt = mysqli_prepare($koneksi, "SELECT t.*, u.nama, u.username, u.hp, u.alamat FROM tb_transaksi t JOIN tb_user u ON t.id_pelanggan = u.id WHERE t.id_transaksi = ?");
          mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
          mysqli_stmt_execute($stmt);
          $transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Detail Pesanan #<?= $id_transaksi ?></h1>
        </div>

        <?php if (!$transaksi): ?>
          <div class="alert alert-warning">Pesanan tidak ditemukan.</div>
          <a href="pesanan.php" class="btn btn-outline-secondary">Kembali</a>
        <?php else: ?>
          <div class="row mb-4">
            <div class="col-md-6">
              <table class="table table-borderless mb-0">
                <tr><th class="w-50">Tanggal</th><td><?= esc($transaksi['tanggal']) ?></td></tr>
                <tr><th>Pelanggan</th><td><?= esc($transaksi['nama'] ?: $transaksi['username']) ?></td></tr>
                <tr><th>No. HP</th><td><?= $transaksi['hp'] ? esc($transaksi['hp']) : '-' ?></td></tr>
                <tr><th>Alamat</th><td><?= $transaksi['alamat'] ? esc($transaksi['alamat']) : '-' ?></td></tr>
                <tr><th>Total</th><td class="fw-bold"><?= rp($transaksi['total_harga']) ?></td></tr>
                <tr><th>Status</th><td><span class="badge <?= kelas_status($transaksi['status']) ?>"><?= esc($transaksi['status']) ?></span></td></tr>
              </table>
            </div>
          </div>

          <h2 class="h5">Produk yang dipesan</h2>
          <div class="table-responsive mb-4">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th>Produk</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-center">Harga Satuan</th>
                  <th class="text-center">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmtItem = mysqli_prepare($koneksi, "SELECT d.jumlah, p.nama, p.harga FROM tb_detail d JOIN tb_produk p ON d.id_produk = p.id WHERE d.id_transaksi = ?");
                mysqli_stmt_bind_param($stmtItem, "i", $id_transaksi);
                mysqli_stmt_execute($stmtItem);
                $items = mysqli_stmt_get_result($stmtItem);
                while ($item = mysqli_fetch_assoc($items)):
                ?>
                <tr>
                  <td><?= esc($item['nama']) ?></td>
                  <td class="text-center"><?= (int) $item['jumlah'] ?></td>
                  <td class="text-center"><?= rp($item['harga']) ?></td>
                  <td class="text-center"><?= rp($item['jumlah'] * $item['harga']) ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <a href="pesanan.php" class="btn btn-outline-secondary">&laquo; Kembali ke daftar pesanan</a>
        <?php endif; ?>

      <?php else: ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Pesanan</h1>
        </div>
        <p class="text-muted">Pesanan yang masih menunggu ditampilkan lebih dulu. Untuk Transfer Bank/QRIS, cek dulu mutasi rekening/QRIS sebelum mengubah status jadi "Diproses". Ubah status lewat dropdown di kolom Status.</p>

        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Cara Bayar</th>
                <th class="text-center">Total</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $hasil = mysqli_query($koneksi, "SELECT t.*, u.nama, u.username FROM tb_transaksi t JOIN tb_user u ON t.id_pelanggan = u.id ORDER BY FIELD(t.status, 'Menunggu Pembayaran', 'Diproses', 'Selesai', 'Dibatalkan'), t.tanggal DESC, t.id_transaksi DESC");
              if (mysqli_num_rows($hasil) === 0):
              ?>
                <tr><td colspan="6" class="text-center text-muted">Belum ada pesanan masuk.</td></tr>
              <?php
              else:
                  while ($row = mysqli_fetch_assoc($hasil)):
              ?>
              <tr>
                <td><?= esc($row['tanggal']) ?></td>
                <td><?= esc($row['nama'] ?: $row['username']) ?></td>
                <td><?= esc($row['metode_bayar'] ?: '-') ?></td>
                <td class="text-center"><?= rp($row['total_harga']) ?></td>
                <td>
                  <form method="post" class="d-flex align-items-center gap-1">
                    <input type="hidden" name="id" value="<?= (int) $row['id_transaksi'] ?>">
                    <input type="hidden" name="update_status" value="1">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 130px;">
                      <?php foreach ($status_valid as $opsi): ?>
                        <option value="<?= $opsi ?>" <?= $row['status'] === $opsi ? 'selected' : '' ?>><?= $opsi ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td class="text-center text-nowrap">
                  <a href="pesanan.php?aksi=detail&id=<?= (int) $row['id_transaksi'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                </td>
              </tr>
              <?php
                  endwhile;
              endif;
              ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>

    </main>
  </div>
</div>

<script src="assets/dist/js/bootstrap.bundle.min.js"></script>
<?php swal_render(); ?>
</body>
</html>