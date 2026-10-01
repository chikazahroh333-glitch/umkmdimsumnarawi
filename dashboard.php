<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("location: login.php");
  exit;
}

$aksi = $_GET['aksi'] ?? '';
$id_edit = null;
$nama_produk = $harga = $stok = $kategori = $deskripsi = $foto = '';

// ================= AMBIL DATA UNTUK FORM EDIT =================
if ($aksi === 'edit' && isset($_GET['id'])) {
  $id_edit = (int) $_GET['id'];
  $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_produk WHERE id = ?");
  mysqli_stmt_bind_param($stmt, "i", $id_edit);
  mysqli_stmt_execute($stmt);
  $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  if ($data) {
    $nama_produk = $data['nama'];
    $harga       = $data['harga'];
    $stok        = $data['stok'];
    $kategori    = $data['id_kategori'];
    $deskripsi   = $data['deskripsi'];
    $foto        = $data['foto'];
  }
}

// ================= SIMPAN (TAMBAH / EDIT) =================
if (isset($_POST['tambah'])) {
  $nama_produk = trim($_POST['nama']);
  $harga       = (int) $_POST['harga'];
  $stok        = (int) $_POST['stok'];
  $kategori    = (int) $_POST['kategori'];
  $deskripsi   = trim($_POST['deskripsi']);
  $foto_upload = $_FILES['foto']['name'] ?? '';

  if ($aksi === 'edit' && $id_edit) {
    // ----- proses edit -----
    if (!empty($foto_upload)) {
      $foto = $foto_upload;
      move_uploaded_file($_FILES['foto']['tmp_name'], "img/" . $foto);
    } else {
      $foto = $_POST['foto_lama'];
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE tb_produk SET nama=?, harga=?, stok=?, id_kategori=?, deskripsi=?, foto=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "siiissi", $nama_produk, $harga, $stok, $kategori, $deskripsi, $foto, $id_edit);
    if (mysqli_stmt_execute($stmt)) {
      set_swal('success', 'Berhasil diubah', 'Produk "' . $nama_produk . '" berhasil diperbarui.');
      header("location: dashboard.php");
      exit;
    } else {
      set_swal('error', 'Gagal menyimpan', 'Produk gagal diperbarui, coba lagi.');
    }
  } else {
    // ----- proses tambah -----
    $foto = $foto_upload;
    if (!empty($foto)) {
      move_uploaded_file($_FILES['foto']['tmp_name'], "img/" . $foto);
    }

    $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_produk (nama, harga, stok, id_kategori, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "siiiss", $nama_produk, $harga, $stok, $kategori, $deskripsi, $foto);
    if (mysqli_stmt_execute($stmt)) {
      set_swal('success', 'Berhasil ditambah', 'Produk "' . $nama_produk . '" berhasil ditambahkan.');
      header("location: dashboard.php");
      exit;
    } else {
      set_swal('error', 'Gagal menyimpan', 'Produk gagal ditambahkan, coba lagi.');
    }
  }
}

function esc($teks) {
  return htmlspecialchars($teks ?? '');
}
function rp($angka) {
  return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}
$judul_halaman = 'Kelola Produk';
?>

<!doctype html>
<html lang="id" data-bs-theme="auto">
<?php include 'admin_head.php'; ?>
<body>
<?php include 'admin_nav.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><?= $aksi === 'edit' ? 'Edit Produk' : 'Kelola Produk' ?></h1>
          </div>

          <form action="" method="POST" enctype="multipart/form-data" class="row g-3 mb-5">
            <div class="col-md-6">
              <label for="nama" class="form-label">Nama Produk</label>
              <input type="text" class="form-control" id="nama" name="nama" value="<?= esc($nama_produk) ?>" required>
            </div>

            <div class="col-md-3">
              <label for="harga" class="form-label">Harga</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" id="harga" name="harga" value="<?= esc($harga) ?>" placeholder="cth: 26000" required>
              </div>
            </div>

            <div class="col-md-3">
              <label for="stok" class="form-label">Stok</label>
              <input type="number" class="form-control" id="stok" name="stok" value="<?= esc($stok) ?>" required>
            </div>

            <div class="col-md-4">
              <label for="inputKategori" class="form-label">Kategori</label>
              <select id="inputKategori" name="kategori" class="form-select" required>
                <option value="" disabled <?= $kategori === '' ? 'selected' : '' ?>>Pilih kategori</option>
                <?php
                $result = mysqli_query($koneksi, "SELECT * FROM tb_kategori ORDER BY id_kategori");
                while ($list = mysqli_fetch_assoc($result)):
                ?>
                  <option value="<?= (int) $list['id_kategori'] ?>" <?= ((string) $kategori === (string) $list['id_kategori']) ? 'selected' : '' ?>>
                    <?= esc($list['nama_kategori']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label for="foto" class="form-label">Foto Produk</label>
              <input class="form-control" type="file" id="foto" name="foto">
              <input type="hidden" name="foto_lama" value="<?= esc($foto) ?>">
            </div>

            <?php if (!empty($foto)): ?>
              <div class="col-md-4 d-flex align-items-end">
                <?php
                $path_foto = 'img/' . $foto;
                if (file_exists($path_foto)):
                ?>
                  <img src="<?= esc($path_foto) ?>" alt="<?= esc($nama_produk) ?>" class="thumb-produk">
                <?php else: ?>
                  <div class="thumb-placeholder"></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="col-md-6">
              <label for="deskripsi" class="form-label">Deskripsi</label>
              <textarea class="form-control" id="deskripsi" name="deskripsi" rows="2"><?= esc($deskripsi) ?></textarea>
            </div>

            <div class="col-12">
              <button type="submit" name="tambah" class="btn btn-bd-primary">
                <?= $aksi === 'edit' ? 'Simpan Perubahan' : 'Tambah Produk' ?>
              </button>
              <?php if ($aksi === 'edit'): ?>
                <a href="dashboard.php" class="btn btn-outline-secondary">Batal</a>
              <?php endif; ?>
            </div>
          </form>

          <h2 class="h4">Daftar Produk</h2>
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th>Foto</th>
                  <th>Nama</th>
                  <th>Harga</th>
                  <th>Stok</th>
                  <th>Kategori</th>
                  <th>Deskripsi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $hasil = mysqli_query($koneksi, "SELECT p.*, k.nama_kategori FROM tb_produk p JOIN tb_kategori k ON p.id_kategori = k.id_kategori ORDER BY p.id DESC");
                while ($data = mysqli_fetch_assoc($hasil)):
                    $path_foto = 'img/' . $data['foto'];
                    $ada_foto  = !empty($data['foto']) && file_exists($path_foto);
                ?>
                <tr>
                  <td>
                    <?php if ($ada_foto): ?>
                      <img src="<?= esc($path_foto) ?>" class="thumb-produk" alt="<?= esc($data['nama']) ?>">
                    <?php else: ?>
                      <div class="thumb-placeholder"></div>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($data['nama']) ?></td>
                  <td><?= rp($data['harga']) ?></td>
                  <td><?= (int) $data['stok'] ?></td>
                  <td><span class="badge text-bg-secondary"><?= esc($data['nama_kategori']) ?></span></td>
                  <td class="small text-muted"><?= esc(mb_strlen($data['deskripsi']) > 60 ? mb_substr($data['deskripsi'], 0, 60) . '…' : $data['deskripsi']) ?></td>
                  <td class="text-nowrap">
                    <a href="dashboard.php?aksi=edit&id=<?= (int) $data['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="hapus.php?aksi=hapus&id=<?= (int) $data['id'] ?>" class="btn btn-sm btn-danger" data-swal-title="Hapus produk?" data-swal-confirm="Produk '<?= esc($data['nama']) ?>' akan dihapus permanen." data-swal-yes="Ya, hapus">Hapus</a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </main>
      </div>
    </div>

    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
  <?php swal_render(); ?>
</body>
</html>