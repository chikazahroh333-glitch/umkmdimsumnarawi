<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

try {
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_produk WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_swal('success', 'Berhasil dihapus', 'Produk sudah dihapus.');
    } else {
        set_swal('error', 'Gagal menghapus', 'Produk tidak ditemukan.');
    }
} catch (mysqli_sql_exception $e) {
    // biasanya karena produk sudah dipakai di tabel transaksi (foreign key)
    set_swal('error', 'Gagal menghapus', 'Produk ini tidak bisa dihapus karena sudah ada di data transaksi.');
}

header('Location: dashboard.php');
exit;
