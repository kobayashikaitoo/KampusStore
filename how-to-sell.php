<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cara Berjualan — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .guide-wrap{max-width:700px;margin:48px auto;padding:0 24px 80px}
    .guide-header{text-align:center;margin-bottom:48px}
    .guide-title{font-size:32px;font-weight:800;color:var(--ink);letter-spacing:-0.5px;margin-bottom:12px}
    .guide-sub{font-size:16px;color:var(--muted);line-height:1.6}
    .step-card{background:white;border:1px solid var(--hairline);border-radius:24px;padding:32px;margin-bottom:24px;display:flex;gap:24px;box-shadow:0 4px 24px rgba(0,0,0,0.05)}
    .step-num{width:48px;height:48px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;flex-shrink:0}
    .step-title{font-size:18px;font-weight:700;color:var(--ink);margin-bottom:8px}
    .step-desc{font-size:15px;color:var(--body);line-height:1.6}
    @media(max-width:600px){
      .step-card{flex-direction:column;gap:16px;padding:24px}
    }
    .cta-box{background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:24px;padding:40px 24px;text-align:center;color:white;margin-top:48px}
    .cta-box h2{font-size:24px;font-weight:800;margin-bottom:12px}
    .btn-white{display:inline-block;padding:12px 32px;background:white;color:#2563eb;font-weight:700;border-radius:999px;text-decoration:none;margin-top:20px;transition:transform .2s}
    .btn-white:hover{transform:translateY(-2px)}
  </style>
</head>
<body>
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
      <div class="step-desc">Buat akun menggunakan email mahasiswa kamu. Untuk mendapatkan lencana "Verified Student" yang meningkatkan kepercayaan pembeli, silakan hubungi admin kami dengan melampirkan foto KTM (Kartu Tanda Mahasiswa).</div>
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
    <a href="/sell.php" class="btn-white">Mulai Jual Barang Sekarang</a>
  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
