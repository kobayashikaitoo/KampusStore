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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/products.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/footer.css"/>
</head>
<body>

  <?php require_once __DIR__ . '/components/navbar.php'; ?>

  <?php if ($successMsg): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        showToast('<?= addslashes($successMsg) ?>', 'success', 3000);
      });
    </script>
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
