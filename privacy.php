<?php
session_start();
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kebijakan Privasi — KampusStore</title>
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

<div class="ks-container" style="padding:40px 0">
  <h1 style="font-size:28px;font-weight:800;margin-bottom:16px">Kebijakan Privasi</h1>
  <p style="color:var(--body);margin-bottom:20px">KampusStore menghargai privasi pengguna dan menjaga data seperlunya.</p>

  <div style="display:grid;gap:14px;color:var(--body);font-size:14px;line-height:1.7">
    <div><strong>1. Data</strong> — Data digunakan untuk kebutuhan layanan dan tidak dijual ke pihak lain.</div>
    <div><strong>2. Keamanan</strong> — Kami berusaha menjaga keamanan data pengguna.</div>
    <div><strong>3. Perubahan</strong> — Kebijakan dapat diperbarui sesuai kebutuhan.</div>
  </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
</body>
</html>
