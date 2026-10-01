<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
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

// ================= HAPUS TRANSAKSI (POST + CSRF) =================
if (isset($_POST['hapus'])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        set_swal('error', 'Gagal', 'Token tidak valid, coba muat ulang halaman.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            mysqli_begin_transaction($koneksi);

            // hapus item dulu (tb_detail), baru transaksinya
            $s1 = mysqli_prepare($koneksi, "DELETE FROM tb_detail WHERE id_transaksi = ?");
            mysqli_stmt_bind_param($s1, "i", $id);
            if (!mysqli_stmt_execute($s1)) {
                throw new Exception('Gagal menghapus detail transaksi.');
            }

            $s2 = mysqli_prepare($koneksi, "DELETE FROM tb_transaksi WHERE id_transaksi = ?");
            mysqli_stmt_bind_param($s2, "i", $id);
            if (!mysqli_stmt_execute($s2)) {
                throw new Exception('Gagal menghapus transaksi.');
            }

            mysqli_commit($koneksi);
            set_swal('success', 'Berhasil dihapus', 'Transaksi #' . $id . ' berhasil dihapus.');
        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            set_swal('error', 'Gagal', 'Gagal menghapus transaksi.');
        }
    }
    header('Location: transaksi.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$judul_halaman = 'Transaksi';
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
<?php include 'admin_head.php'; ?>
<body>
<?php include 'admin_nav.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transaksi</h1>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= esc($flash['tipe']) ?> alert-dismissible fade show" role="alert">
          <?= esc($flash['pesan']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php
      $hasil = mysqli_query($koneksi, "SELECT t.*, u.nama, u.username
                                       FROM tb_transaksi t
                                       LEFT JOIN tb_user u ON t.id_pelanggan = u.id
                                       ORDER BY t.id_transaksi DESC");
      if (mysqli_num_rows($hasil) === 0):
      ?>
        <p class="text-muted">Belum ada transaksi.</p>
      <?php
      else:
          while ($row = mysqli_fetch_assoc($hasil)):
              $nama_pembeli = !empty($row['nama']) ? $row['nama'] : ($row['username'] ?? '(akun dihapus)');

              // ambil daftar barang yang dibeli di transaksi ini
              $stmtItem = mysqli_prepare($koneksi, "SELECT d.jumlah, p.nama AS nama_produk, p.harga, p.foto
                                                    FROM tb_detail d
                                                    LEFT JOIN tb_produk p ON d.id_produk = p.id
                                                    WHERE d.id_transaksi = ?");
              mysqli_stmt_bind_param($stmtItem, "i", $row['id_transaksi']);
              mysqli_stmt_execute($stmtItem);
              $items = mysqli_stmt_get_result($stmtItem);
      ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
              <span class="fw-semibold">Invoice #<?= (int) $row['id_transaksi'] ?></span>
              <span class="text-muted small ms-2"><?= esc(date('d M Y', strtotime($row['tanggal']))) ?></span>
              <div class="small text-muted">Pelanggan: <strong><?= esc($nama_pembeli) ?></strong></div>
            </div>
            <span class="badge <?= kelas_status($row['status']) ?>"><?= esc($row['status']) ?></span>
          </div>

          <?php while ($it = mysqli_fetch_assoc($items)):
              $path_foto_produk = 'img/' . $it['foto'];
              $ada_foto_produk  = !empty($it['foto']) && file_exists($path_foto_produk);
          ?>
            <div class="d-flex align-items-center gap-3 mb-2">
              <?php if ($ada_foto_produk): ?>
                <img src="<?= esc($path_foto_produk) ?>" alt="<?= esc($it['nama_produk'] ?? '') ?>"
                     class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
              <?php else: ?>
                <div class="rounded bg-secondary-subtle d-flex align-items-center justify-content-center text-muted"
                     style="width: 48px; height: 48px; font-size: .6rem;">Foto</div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <div><?= esc($it['nama_produk'] ?: '(produk sudah dihapus)') ?></div>
                <div class="text-muted small"><?= (int) $it['jumlah'] ?> x <?= rp($it['harga']) ?></div>
              </div>
              <div class="fw-semibold"><?= rp($it['harga'] * $it['jumlah']) ?></div>
            </div>
          <?php endwhile; ?>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-top pt-2 mt-2">
            <div class="text-muted small">
              <?= esc($row['metode_kirim'] ?: '-') ?> &middot; <?= esc($row['metode_bayar'] ?: '-') ?>
            </div>
            <div class="fw-bold">Total: <?= rp($row['total_harga']) ?></div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="transaksi_detail.php?id=<?= (int) $row['id_transaksi'] ?>" class="btn btn-sm btn-info">Detail</a>
            <a href="cetak.php?id=<?= (int) $row['id_transaksi'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Cetak</a>
            <form method="POST" class="d-inline"
                  data-swal-title="Hapus transaksi?" data-swal-confirm="Transaksi #<?= (int) $row['id_transaksi'] ?> akan dihapus. Data tidak bisa dikembalikan." data-swal-yes="Ya, hapus">
              <input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">
              <input type="hidden" name="id" value="<?= (int) $row['id_transaksi'] ?>">
              <button type="submit" name="hapus" class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </div>
        </div>
      </div>
      <?php
          endwhile;
      endif;
      ?>
    </main>
  </div>
</div>

<script src="assets/dist/js/bootstrap.bundle.min.js"></script>
<?php swal_render(); ?>
</body>
</html>