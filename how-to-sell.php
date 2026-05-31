<?php
session_start();
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cara Berjualan — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/footer.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="guide-wrap">
  <div class="guide-header">
    <h1 class="guide-title">Cara Berjualan di KampusStore</h1>
    <p class="guide-sub">Ubah barang bekas kuliahmu jadi uang saku tambahan. Ikuti 3 langkah mudah ini untuk mulai berjualan kepada sesama mahasiswa.</p>
  </div>

  <div class="step-card">
    <div class="step-num">1</div>
    <div>
      <div class="step-title">Daftar & Verifikasi Akun</div>
      <div class="step-desc">Buat akun menggunakan email mahasiswa kamu (@student.unsrat.ac.id). Setelah terdaftar, akun kamu otomatis dianggap valid sebagai mahasiswa kampus.</div>
    </div>
  </div>

  <div class="step-card">
    <div class="step-num">2</div>
    <div>
      <div class="step-title">Posting Barang Bekasmu</div>
      <div class="step-desc">Klik tombol "Jual Barang" di pojok kanan atas. Foto barangmu dengan jelas, tulis deskripsi yang jujur tentang kondisinya, dan tentukan harga yang menarik. Jangan lupa pilih apakah harga bisa dinego atau tidak!</div>
    </div>
  </div>

  <div class="step-card">
    <div class="step-num">3</div>
    <div>
      <div class="step-title">Chat & COD (Cash on Delivery)</div>
      <div class="step-desc">Pembeli yang tertarik akan langsung menghubungi kamu melalui tombol Chat (Email/WhatsApp). Sepakati harga dan tentukan lokasi ketemuan di sekitar area kampus agar transaksi aman dan mudah.</div>
    </div>
  </div>

  <div class="cta-box">
    <h2>Sudah siap untuk berjualan?</h2>
    <p>Ratusan mahasiswa sedang mencari barang yang mungkin tidak kamu pakai lagi.</p>
    <a href="sell.php" class="btn-white">Mulai Jual Barang Sekarang</a>
  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
</body>
</html>
