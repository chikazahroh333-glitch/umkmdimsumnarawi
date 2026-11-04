<?php
// ============================================================
// Helper SweetAlert2 - di-include otomatis lewat koneksi.php
//
// Cara pakai:
//   1. Sebelum redirect / di tengah proses, panggil:
//        set_swal('success', 'Berhasil', 'Data sudah disimpan.');
//   2. Di bagian bawah halaman (sebelum tag body penutup), panggil:
//        swal_render();
//   3. Konfirmasi (hapus, logout, dll) cukup tambah atribut di link atau form:
//        data-swal-confirm="Yakin mau hapus?"  data-swal-title="Hapus data?"
//        data-swal-yes="Ya, hapus"             data-swal-icon="warning"
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simpan pesan di session, nanti ditampilkan di halaman berikutnya (atau halaman ini juga).
function set_swal($icon, $title, $text = '', $html = false) {
    $_SESSION['swal'] = [
        'icon'  => $icon,    // success | error | warning | info | question
        'title' => $title,
        'text'  => $text,
        'html'  => $html,    // true kalau $text berisi HTML
    ];
}

// Cetak script SweetAlert2 + tampilkan pesan yang tersimpan (kalau ada).
function swal_render() {
    $flash = $_SESSION['swal'] ?? null;
    unset($_SESSION['swal']);
    $flash_json = json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
  var MAROON = '#7A1F2B';
  var flash = <?= $flash_json ?>;

  // Dialog konfirmasi (muncul di tengah). Kalau CDN gagal dimuat, pakai confirm() biasa.
  function tanya(el, ifYes) {
    var teks = el.getAttribute('data-swal-confirm') || 'Yakin mau melanjutkan?';
    if (typeof Swal === 'undefined') {
      if (window.confirm(teks)) ifYes();
      return;
    }
    Swal.fire({
      position: 'center',
      icon: el.getAttribute('data-swal-icon') || 'warning',
      title: el.getAttribute('data-swal-title') || 'Yakin?',
      text: teks,
      showCancelButton: true,
      confirmButtonText: el.getAttribute('data-swal-yes') || 'Ya, lanjutkan',
      cancelButtonText: 'Batal',
      confirmButtonColor: MAROON,
      cancelButtonColor: '#6c757d',
      reverseButtons: true,
      focusCancel: true
    }).then(function (r) { if (r.isConfirmed) ifYes(); });
  }

  // Link (<a data-swal-confirm>)
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[data-swal-confirm]');
    if (!a) return;
    e.preventDefault();
    tanya(a, function () { window.location.href = a.href; });
  });

  // Form (<form data-swal-confirm>) — nama tombol submit tetap ikut terkirim
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches || !f.matches('form[data-swal-confirm]')) return;
    e.preventDefault();
    var tombol = e.submitter;
    tanya(f, function () {
      if (tombol && tombol.name) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = tombol.name; h.value = tombol.value || '1';
        f.appendChild(h);
      }
      f.submit();
    });
  });

  // Pesan hasil aksi (berhasil / gagal)
  if (flash && typeof Swal !== 'undefined') {
    var opsi = {
      position: 'center',
      icon: flash.icon,
      title: flash.title,
      confirmButtonColor: MAROON
    };
    if (flash.html) { opsi.html = flash.text; } else { opsi.text = flash.text; }
    if (flash.icon === 'success' && !flash.html) {  // sukses biasa nutup sendiri
      opsi.timer = 2200;
      opsi.timerProgressBar = true;
    }
    Swal.fire(opsi);
  }
})();
</script>
<?php
}