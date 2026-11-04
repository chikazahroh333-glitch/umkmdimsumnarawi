<?php
session_start();
session_unset();
session_destroy();

// swal.php membuka session baru, supaya pesan "berhasil logout" bisa tampil di halaman login
include 'swal.php';
set_swal('success', 'Berhasil logout', 'Sampai jumpa lagi!');
header('Location: login.php');
exit;
