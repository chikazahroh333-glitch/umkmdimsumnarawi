<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$errors  = [];
$dir_foto = 'img/profil/';   // folder foto profil (dibuat otomatis kalau belum ada)
$ext_foto = null;

// ---------- Proses simpan ----------
if (isset($_POST['simpan'])) {
    $username = trim($_POST['username']);
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $hp       = trim($_POST['hp']);
    $alamat   = trim($_POST['alamat']);

    $password_lama  = $_POST['password_lama'];
    $password_baru  = $_POST['password_baru'];
    $password_ulang = $_POST['password_ulang'];

    // --- Validasi data profil ---
    if ($username === '') {
        $errors[] = "Username tidak boleh kosong.";
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    // Cek username sudah dipakai user lain atau belum
    if ($username !== '') {
        $cek = mysqli_prepare($koneksi, "SELECT id FROM tb_user WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($cek, "si", $username, $id_user);
        mysqli_stmt_execute($cek);
        if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
            $errors[] = "Username sudah dipakai orang lain, coba yang lain.";
        }
    }

    // --- Validasi ganti password (hanya kalau diisi) ---
    $ganti_password = ($password_baru !== '' || $password_ulang !== '' || $password_lama !== '');

    if ($ganti_password) {
        // Ambil password saat ini dari database
        $q = mysqli_prepare($koneksi, "SELECT password FROM tb_user WHERE id = ?");
        mysqli_stmt_bind_param($q, "i", $id_user);
        mysqli_stmt_execute($q);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

        if ($password_lama !== $row['password']) {
            $errors[] = "Password lama salah.";
        }
        if (strlen($password_baru) < 4) {
            $errors[] = "Password baru minimal 4 karakter.";
        }
        if ($password_baru !== $password_ulang) {
            $errors[] = "Konfirmasi password baru tidak cocok.";
        }
    }

    // --- Validasi foto profil (hanya kalau ada file yang dipilih) ---
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload foto gagal, coba lagi.";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Ukuran foto maksimal 2 MB.";
        } else {
            $info    = getimagesize($_FILES['foto']['tmp_name']);
            $tipe_ok = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
            if (!$info || !isset($tipe_ok[$info[2]])) {
                $errors[] = "Foto harus berformat JPG, PNG, atau WEBP.";
            } else {
                $ext_foto = $tipe_ok[$info[2]];
            }
        }
    }

    // --- Simpan kalau tidak ada error ---
    if (empty($errors)) {
        if ($ganti_password) {
            $sql = "UPDATE tb_user
                    SET username = ?, nama = ?, email = ?, hp = ?, alamat = ?, password = ?
                    WHERE id = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param(
                $stmt, "ssssssi",
                $username, $nama, $email, $hp, $alamat, $password_baru, $id_user
            );
        } else {
            $sql = "UPDATE tb_user
                    SET username = ?, nama = ?, email = ?, hp = ?, alamat = ?
                    WHERE id = ?";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param(
                $stmt, "sssssi",
                $username, $nama, $email, $hp, $alamat, $id_user
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            // ----- proses foto profil -----
            $q = mysqli_prepare($koneksi, "SELECT foto FROM tb_user WHERE id = ?");
            mysqli_stmt_bind_param($q, "i", $id_user);
            mysqli_stmt_execute($q);
            $foto_lama  = mysqli_fetch_assoc(mysqli_stmt_get_result($q))['foto'] ?? null;
            $foto_final = $foto_lama;

            if ($ext_foto !== null) {
                if (!is_dir($dir_foto)) {
                    mkdir($dir_foto, 0755, true);
                }
                $nama_baru = 'user' . $id_user . '_' . time() . '.' . $ext_foto;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir_foto . $nama_baru)) {
                    $foto_final = $nama_baru;
                }
            } elseif (isset($_POST['hapus_foto'])) {
                $foto_final = null;
            }

            if ($foto_final !== $foto_lama) {
                $f = mysqli_prepare($koneksi, "UPDATE tb_user SET foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($f, "si", $foto_final, $id_user);
                mysqli_stmt_execute($f);
                // hapus file lama supaya folder tidak menumpuk
                if (!empty($foto_lama) && file_exists($dir_foto . basename($foto_lama))) {
                    unlink($dir_foto . basename($foto_lama));
                }
            }

            // Username di session ikut diperbarui supaya navbar langsung berubah
            $_SESSION['username'] = $username;
            set_swal('success', 'Profil diperbarui', 'Perubahan profilmu sudah disimpan.');
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = "Gagal menyimpan: " . mysqli_error($koneksi);
        }
    }
}

// Kalau ada error validasi / gagal simpan, tampilkan lewat SweetAlert
if (!empty($errors)) {
    $daftar = '<ul style="text-align:left;margin:0;padding-left:1.2rem">';
    foreach ($errors as $e) {
        $daftar .= '<li>' . htmlspecialchars($e) . '</li>';
    }
    $daftar .= '</ul>';
    set_swal('error', 'Gagal menyimpan profil', $daftar, true);
}

// ---------- Ambil data untuk mengisi form ----------
$stmt = mysqli_prepare($koneksi, "SELECT * FROM tb_user WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Kalau form gagal disimpan, tampilkan lagi apa yang tadi diketik user
if (isset($_POST['simpan'])) {
    $user['username'] = $_POST['username'];
    $user['nama']     = $_POST['nama'];
    $user['email']    = $_POST['email'];
    $user['hp']       = $_POST['hp'];
    $user['alamat']   = $_POST['alamat'];
}

function isi($teks) {
    return htmlspecialchars($teks ?? '');
}
?>
<!doctype html>
<html lang="id" data-bs-theme="auto">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Profil</title>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet" />
  </head>
  <body class="bg-body-tertiary">
    <?php include 'navbar.php'; ?>

    <main class="container" style="padding-top: 90px; padding-bottom: 40px;">
      <div class="row justify-content-center">
        <div class="col-lg-8">

          <form method="post" enctype="multipart/form-data">
            <div class="card shadow-sm mb-4">
              <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-4">Edit Profil</h1>

                <?php
                $path_foto = !empty($user['foto']) ? $dir_foto . basename($user['foto']) : '';
                $ada_foto  = $path_foto !== '' && file_exists($path_foto);
                $inisial   = strtoupper(mb_substr(($user['nama'] ?? '') !== '' ? $user['nama'] : $user['username'], 0, 1));
                ?>
                <div class="d-flex align-items-center gap-3 mb-4">
                  <?php if ($ada_foto): ?>
                    <img src="<?= isi($path_foto) ?>" alt="Foto profil" class="rounded-circle border"
                         style="width: 88px; height: 88px; object-fit: cover;">
                  <?php else: ?>
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fs-2 fw-bold"
                         style="width: 88px; height: 88px;"><?= isi($inisial) ?></div>
                  <?php endif; ?>
                  <div class="flex-grow-1">
                    <label class="form-label mb-1" for="foto">Foto profil</label>
                    <input type="file" class="form-control form-control-sm" id="foto" name="foto"
                           accept="image/jpeg,image/png,image/webp" />
                    <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                    <?php if ($ada_foto): ?>
                      <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="hapus_foto" id="hapus_foto">
                        <label class="form-check-label small" for="hapus_foto">Hapus foto profil</label>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="username">Username</label>
                  <input type="text" class="form-control" id="username"
                         name="username" value="<?= isi($user['username']) ?>" required />
                </div>

                <div class="mb-3">
                  <label class="form-label" for="nama">Nama lengkap</label>
                  <input type="text" class="form-control" id="nama"
                         name="nama" value="<?= isi($user['nama']) ?>" />
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email"
                           name="email" value="<?= isi($user['email']) ?>" />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label" for="hp">No. HP</label>
                    <input type="text" class="form-control" id="hp"
                           name="hp" value="<?= isi($user['hp']) ?>" />
                  </div>
                </div>

                <div class="mb-1">
                  <label class="form-label" for="alamat">Alamat</label>
                  <textarea class="form-control" id="alamat" name="alamat"
                            rows="3"><?= isi($user['alamat']) ?></textarea>
                </div>
              </div>
            </div>

            <div class="card shadow-sm mb-4">
              <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Ganti Password</h2>
                <p class="text-body-secondary small mb-4">
                  Kosongkan ketiga kolom ini kalau tidak ingin mengganti password.
                </p>

                <div class="mb-3">
                  <label class="form-label" for="password_lama">Password lama</label>
                  <input type="password" class="form-control" id="password_lama"
                         name="password_lama" autocomplete="current-password" />
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label" for="password_baru">Password baru</label>
                    <input type="password" class="form-control" id="password_baru"
                           name="password_baru" autocomplete="new-password" />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label" for="password_ulang">Ulangi password baru</label>
                    <input type="password" class="form-control" id="password_ulang"
                           name="password_ulang" autocomplete="new-password" />
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" name="simpan" class="btn btn-primary px-4">
                Simpan perubahan
              </button>
              <a href="profile.php" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>

        </div>
      </div>
    </main>

    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
  <?php swal_render(); ?>
</body>
</html>