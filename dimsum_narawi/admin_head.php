<?php $judul_halaman = $judul_halaman ?? 'Admin'; ?>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($judul_halaman) ?> · Dimsum Narawi</title>
  <script src="assets/js/color-modes.js"></script>
  <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <meta name="theme-color" content="#7A1F2B" />
  <link href="dashboard.css" rel="stylesheet" />
  <style>
    :root {
      --dimsum-maroon: #7A1F2B;
      --dimsum-maroon-dark: #5A1620;
      --dimsum-gold: #D9A441;
      font-family: 'Inter', system-ui, sans-serif;
    }
    h1, h2, h3 { font-family: 'Fraunces', Georgia, serif; }
    .bi { vertical-align: -0.125em; fill: currentColor; }
    .bd-mode-toggle { z-index: 1500; }
    .bd-mode-toggle .bi { width: 1em; height: 1em; }
    .bd-mode-toggle .dropdown-menu .active .bi { display: block !important; }
    .btn-bd-primary {
      --bd-violet-bg: var(--dimsum-maroon);
      --bd-violet-rgb: 122, 31, 43;
      --bs-btn-font-weight: 600;
      --bs-btn-color: var(--bs-white);
      --bs-btn-bg: var(--bd-violet-bg);
      --bs-btn-border-color: var(--bd-violet-bg);
      --bs-btn-hover-color: var(--bs-white);
      --bs-btn-hover-bg: var(--dimsum-maroon-dark);
      --bs-btn-hover-border-color: var(--dimsum-maroon-dark);
      --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
      --bs-btn-active-color: var(--bs-btn-hover-color);
      --bs-btn-active-bg: var(--dimsum-maroon-dark);
      --bs-btn-active-border-color: var(--dimsum-maroon-dark);
    }
    header.navbar { background-color: var(--dimsum-maroon) !important; }
    .sidebar .nav-link.active {
      background-color: var(--dimsum-maroon);
      color: #fff !important;
    }
    .sidebar .nav-link:not(.active):hover {
      color: var(--dimsum-maroon);
    }
    .thumb-produk {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      object-fit: cover;
    }
    .thumb-placeholder {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      background: linear-gradient(135deg, var(--dimsum-maroon), #B3452F);
    }
  </style>
</head>