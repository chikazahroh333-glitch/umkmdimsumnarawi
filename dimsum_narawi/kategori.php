<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$aksi = $_GET['aksi'] ?? '';
$id_edit = null;
$nama_kategori = '';
$pesan_error = '';

// ================= AMBIL DATA UNTUK EDIT =================
if ($aksi === 'edit' && isset($_GET['id'])) {
    $id_edit = (int) $_GET['id'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_kategori WHERE id_kategori = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_edit);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($data) {
        $nama_kategori = $data['nama_kategori'];
    }
}

// ================= SIMPAN (TAMBAH / EDIT) =================
if (isset($_POST['simpan'])) {
    $nama_kategori = trim($_POST['nama_kategori']);

    if ($nama_kategori === '') {
        $pesan_error = 'Nama kategori tidak boleh kosong.';
        set_swal('warning', 'Data belum lengkap', $pesan_error);
    } elseif ($aksi === 'edit' && isset($_POST['id_kategori'])) {
        $id_edit = (int) $_POST['id_kategori'];
        $stmt = mysqli_prepare($koneksi, "UPDATE tb_kategori SET nama_kategori = ? WHERE id_kategori = ?");
        mysqli_stmt_bind_param($stmt, "si", $nama_kategori, $id_edit);
        mysqli_stmt_execute($stmt);
        set_swal('success', 'Berhasil diubah', 'Kategori "' . $nama_kategori . '" berhasil diperbarui.');
        header('Location: kategori.php');
        exit;
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tb_kategori (nama_kategori) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nama_kategori);
        mysqli_stmt_execute($stmt);
        set_swal('success', 'Berhasil ditambah', 'Kategori "' . $nama_kategori . '" berhasil ditambahkan.');
        header('Location: kategori.php');
        exit;
    }
}

// ================= HAPUS =================
if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id_hapus = (int) $_GET['id'];

    $cek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jumlah FROM tb_produk WHERE id_kategori = ?");
    mysqli_stmt_bind_param($cek, "i", $id_hapus);
    mysqli_stmt_execute($cek);
    $jumlah_produk = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($cek))['jumlah'];

    if ($jumlah_produk > 0) {
        $pesan_error = "Kategori ini masih punya {$jumlah_produk} produk. Pindahkan atau hapus dulu produknya sebelum menghapus kategori ini.";
        set_swal('error', 'Kategori tidak bisa dihapus', $pesan_error);
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_kategori WHERE id_kategori = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_hapus);
        mysqli_stmt_execute($stmt);
        set_swal('success', 'Berhasil dihapus', 'Kategori sudah dihapus.');
        header('Location: kategori.php');
        exit;
    }
}

function esc($teks) {
    return htmlspecialchars($teks ?? '');
}

$judul_halaman = 'Kelola Kategori';
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
<?php include 'admin_head.php'; ?>
<body>
<?php include 'admin_nav.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?= $aksi === 'edit' ? 'Edit Kategori' : 'Kelola Kategori' ?></h1>
      </div>

      <form method="post" action="<?= $aksi === 'edit' ? 'kategori.php?aksi=edit' : 'kategori.php' ?>" class="row g-3 mb-5">
        <div class="col-md-6">
          <label for="nama_kategori" class="form-label">Nama Kategori</label>
          <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" value="<?= esc($nama_kategori) ?>" required>
        </div>
        <?php if ($aksi === 'edit'): ?>
          <input type="hidden" name="id_kategori" value="<?= (int) $id_edit ?>">
        <?php endif; ?>
        <div class="col-12">
          <button type="submit" name="simpan" class="btn btn-bd-primary"><?= $aksi === 'edit' ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
          <?php if ($aksi === 'edit'): ?>
            <a href="kategori.php" class="btn btn-outline-secondary">Batal</a>
          <?php endif; ?>
        </div>
      </form>

      <h2 class="h4">Daftar Kategori</h2>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Nama Kategori</th>
              <th>Jumlah Produk</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $hasil = mysqli_query($koneksi, "SELECT k.*, (SELECT COUNT(*) FROM tb_produk p WHERE p.id_kategori = k.id_kategori) AS jumlah_produk FROM tb_kategori k ORDER BY k.id_kategori");
            while ($row = mysqli_fetch_assoc($hasil)):
            ?>
            <tr>
              <td><?= esc($row['nama_kategori']) ?></td>
              <td><?= (int) $row['jumlah_produk'] ?></td>
              <td class="text-nowrap">
                <a href="kategori.php?aksi=edit&id=<?= (int) $row['id_kategori'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="kategori.php?aksi=hapus&id=<?= (int) $row['id_kategori'] ?>" class="btn btn-sm btn-danger" data-swal-title="Hapus kategori?" data-swal-confirm="Kategori '<?= esc($row['nama_kategori']) ?>' akan dihapus." data-swal-yes="Ya, hapus">Hapus</a>
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