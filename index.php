<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

$successMsg = $_SESSION['auth_success'] ?? null;
unset($_SESSION['auth_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>KampusStore — Marketplace Barang Bekas Mahasiswa</title>
  <meta name="description" content="KampusStore adalah platform jual-beli barang bekas khusus komunitas kampus Indonesia. Temukan laptop, buku, kebutuhan kos, dan lainnya dengan harga ramah kantong."/>
  <meta name="theme-color" content="#2563eb"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/custom.css"/>
</head>
<body>

  <?php require_once __DIR__ . '/components/navbar.php'; ?>

  <?php if ($successMsg): ?>
    <div id="success-banner" style="
      position:fixed;top:80px;left:50%;transform:translateX(-50%);
      background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;
      padding:12px 20px;font-size:14px;font-weight:500;color:#16a34a;
      box-shadow:0 4px 16px rgba(0,0,0,0.08);z-index:500;
      animation:fadeInUp .4s ease both;
    ">
      ✅ <?= htmlspecialchars($successMsg) ?>
    </div>
    <script>setTimeout(()=>{const b=document.getElementById('success-banner');if(b)b.style.opacity='0';},3000)</script>
  <?php endif; ?>

  <main>
    <?php require_once __DIR__ . '/components/hero.php'; ?>
    <?php require_once __DIR__ . '/components/category_strip.php'; ?>
    <?php require_once __DIR__ . '/components/product_grid.php'; ?>
    <?php require_once __DIR__ . '/components/footer.php'; ?>
  </main>

  <script src="assets/js/main.js" defer></script>
</body>
</html>
