<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_transaksi = (int) ($_GET['id'] ?? 0);

// ----- ambil transaksi + data pembeli -----
$stmt = mysqli_prepare($koneksi, "SELECT t.*, u.nama, u.username, u.email, u.hp, u.alamat
                                  FROM tb_transaksi t
                                  LEFT JOIN tb_user u ON t.id_pelanggan = u.id
                                  WHERE t.id_transaksi = ?");
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$trx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trx) {
    header('Location: index.php');
    exit;
}

// ----- kontrol akses -----
// Admin boleh cetak invoice siapa saja.
// Pelanggan hanya boleh cetak invoice miliknya sendiri.
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
if (!$is_admin && (int) $trx['id_pelanggan'] !== (int) $_SESSION['id_user']) {
    header('Location: index.php');
    exit;
}

// ----- item yang dibeli -----
$stmt = mysqli_prepare($koneksi, "SELECT d.jumlah, p.nama AS nama_produk, p.harga
                                  FROM tb_detail d
                                  LEFT JOIN tb_produk p ON d.id_produk = p.id
                                  WHERE d.id_transaksi = ?");
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

function esc($teks) {
    return htmlspecialchars($teks ?? '');
}
function rp($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

// halaman kembali tergantung siapa yang membuka
$link_kembali = $is_admin ? 'transaksi.php' : 'profile.php';
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice #<?= (int) $trx['id_transaksi'] ?> · Dimsum Narawi</title>
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
        font-family: 'Inter', system-ui, sans-serif;
      }
      body {
        background-color: var(--dimsum-cream);
        color: var(--dimsum-ink);
      }
      h1, h2, h3 {
        font-family: 'Fraunces', Georgia, serif;
      }
      .btn-bd-primary {
        --bd-violet-bg: var(--dimsum-maroon);
        --bs-btn-font-weight: 600;
        --bs-btn-color: var(--bs-white);
        --bs-btn-bg: var(--bd-violet-bg);
        --bs-btn-border-color: var(--bd-violet-bg);
        --bs-btn-hover-bg: var(--dimsum-maroon-dark);
        --bs-btn-hover-border-color: var(--dimsum-maroon-dark);
      }
      .invoice-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(43, 27, 23, 0.08);
      }
      .invoice-brand {
        color: var(--dimsum-maroon);
        font-weight: 700;
      }
      .invoice-total-row td {
        border-top: 2px solid var(--dimsum-ink);
        font-weight: 700;
      }

      /* Tampilan saat dicetak / disimpan sebagai PDF: sembunyikan tombol & rapikan */
      @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .invoice-card { box-shadow: none; border-radius: 0; }
        main { padding: 0 !important; }
      }
    </style>
  </head>
  <body>
    <main class="container" style="max-width: 800px; padding-top: 40px; padding-bottom: 40px;">
      <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="<?= esc($link_kembali) ?>" class="text-decoration-none">&laquo; Kembali</a>
        <button onclick="window.print()" class="btn btn-bd-primary btn-sm">Cetak / Simpan sebagai PDF</button>
      </div>

      <div class="invoice-card p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 pb-4 border-bottom">
          <div>
            <h1 class="h3 invoice-brand mb-1">Dimsum Narawi</h1>
            <p class="text-muted mb-0 small">Invoice pembelian</p>
          </div>
          <div class="text-md-end">
            <p class="mb-1"><strong>Invoice #<?= (int) $trx['id_transaksi'] ?></strong></p>
            <p class="mb-1 text-muted small"><?= esc(date('d M Y', strtotime($trx['tanggal']))) ?></p>
            <?php
            $status = $trx['status'] ?? 'Diproses';
            $kelas_badge = $status === 'Menunggu Pembayaran' ? 'text-bg-warning'
                          : ($status === 'Dibatalkan' ? 'text-bg-secondary'
                          : ($status === 'Selesai' ? 'text-bg-success' : 'text-bg-info'));
            ?>
            <span class="badge <?= $kelas_badge ?>"><?= esc($status) ?></span>
          </div>
        </div>

        <?php if ($status === 'Menunggu Pembayaran'): ?>
          <div class="alert alert-warning">
            <strong>Belum lunas.</strong> Invoice ini adalah tagihan, bukan bukti pembayaran.
            Segera transfer sejumlah <strong><?= rp($trx['total_harga']) ?></strong> sesuai metode
            <strong><?= esc($trx['metode_bayar']) ?></strong> yang dipilih, lalu konfirmasi ke admin
            toko agar pesanan segera diproses.
          </div>
        <?php endif; ?>

        <div class="row mb-4">
          <div class="col-md-6">
            <p class="text-muted small mb-1">Ditagihkan kepada</p>
            <p class="mb-0 fw-semibold"><?= esc($trx['nama'] ?: $trx['username']) ?></p>
            <?php if (!empty($trx['hp'])): ?><p class="mb-0 small"><?= esc($trx['hp']) ?></p><?php endif; ?>
            <?php if (!empty($trx['alamat'])): ?><p class="mb-0 small"><?= esc($trx['alamat']) ?></p><?php endif; ?>
          </div>
          <div class="col-md-6 text-md-end">
            <?php if (!empty($trx['metode_bayar'])): ?>
              <p class="text-muted small mb-1">Cara Bayar</p>
              <p class="mb-2"><?= esc($trx['metode_bayar']) ?></p>
            <?php endif; ?>
            <?php if (!empty($trx['metode_kirim'])): ?>
              <p class="text-muted small mb-1">Cara Kirim</p>
              <p class="mb-0"><?= esc($trx['metode_kirim']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Produk</th>
                <th class="text-center">Jumlah</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($it = mysqli_fetch_assoc($items)): ?>
              <tr>
                <td><?= esc($it['nama_produk'] ?: '(produk dihapus)') ?></td>
                <td class="text-center"><?= (int) $it['jumlah'] ?></td>
                <td class="text-end"><?= rp($it['harga']) ?></td>
                <td class="text-end"><?= rp($it['harga'] * $it['jumlah']) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
            <tfoot>
              <?php if ((int) ($trx['biaya_kirim'] ?? 0) > 0): ?>
                <tr>
                  <td colspan="3" class="text-end">Biaya Pengiriman</td>
                  <td class="text-end"><?= rp($trx['biaya_kirim']) ?></td>
                </tr>
              <?php endif; ?>
              <tr class="invoice-total-row">
                <td colspan="3" class="text-end">Total yang harus dibayar</td>
                <td class="text-end"><?= rp($trx['total_harga']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <p class="text-muted small mt-4 mb-0">Terima kasih sudah berbelanja di Dimsum Narawi &mdash; simpan invoice ini sebagai bukti pembayaran.</p>
      </div>
    </main>
  <?php swal_render(); ?>
</body>
</html>