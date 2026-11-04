<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

function tampil($teks) {
    return ($teks !== null && $teks !== '')
        ? htmlspecialchars($teks)
        : '<span class="text-muted fst-italic">Belum diisi</span>';
}

// ================= RESET PASSWORD =================
$pesan_sukses = '';
if (isset($_POST['reset_password']) && isset($_POST['id'])) {
    $id_pelanggan = (int) $_POST['id'];

    // buat password acak 8 karakter, tanpa karakter yang gampang ketuker (0/O, 1/l/I)
    $charset = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $password_baru = '';
    for ($i = 0; $i < 8; $i++) {
        $password_baru .= $charset[random_int(0, strlen($charset) - 1)];
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE tb_user SET password = ? WHERE id = ? AND role = 'pelanggan'");
    mysqli_stmt_bind_param($stmt, "si", $password_baru, $id_pelanggan);

    if (mysqli_stmt_execute($stmt)) {
        $q = mysqli_prepare($koneksi, "SELECT username FROM tb_user WHERE id = ?");
        mysqli_stmt_bind_param($q, "i", $id_pelanggan);
        mysqli_stmt_execute($q);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

        $pesan_sukses = "Password untuk <strong>" . htmlspecialchars($u['username']) . "</strong> berhasil direset jadi: <code>" . htmlspecialchars($password_baru) . "</code> — sampaikan ke pelanggan secara langsung/pribadi, jangan lewat chat publik.";
        set_swal('success', 'Password berhasil direset', $pesan_sukses, true);
    }
}

$judul_halaman = 'Data Pelanggan';
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
<?php include 'admin_head.php'; ?>
<body>
<?php include 'admin_nav.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Data Pelanggan</h1>
      </div>

      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Username</th>
              <th>Email</th>
              <th>No. HP</th>
              <th>Alamat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $hasil = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE role = 'pelanggan' ORDER BY nama ASC");
            if (mysqli_num_rows($hasil) === 0):
            ?>
              <tr><td colspan="6" class="text-center text-muted">Belum ada pelanggan yang mendaftar.</td></tr>
            <?php
            else:
                while ($row = mysqli_fetch_assoc($hasil)):
            ?>
            <tr>
              <td><?= tampil($row['nama']) ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= tampil($row['email']) ?></td>
              <td><?= tampil($row['hp']) ?></td>
              <td><?= tampil($row['alamat']) ?></td>
              <td>
                <form method="post" class="d-inline" data-swal-title="Reset password?" data-swal-confirm="Reset password untuk <?= htmlspecialchars($row['username']) ?>? Password lama akan hilang." data-swal-yes="Ya, reset">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button type="submit" name="reset_password" class="btn btn-sm btn-outline-warning">Reset Password</button>
                </form>
              </td>
            </tr>
            <?php
                endwhile;
            endif;
            ?>
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