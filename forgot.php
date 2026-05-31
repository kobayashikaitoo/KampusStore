<?php
session_start();
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lupa Password — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/pages.css"/>
</head>
<body class="forgot-wrap">
  <div class="forgot-card">
    <div class="forgot-icon"><i class="fas fa-lock"></i></div>
    <h1 class="forgot-title">Lupa Password?</h1>
    <p class="forgot-sub">Fitur reset password masih dalam pengembangan.</p>
    <div style="margin-top:20px">
      <a href="<?= BASE_URL ?>auth/login.php" class="btn-back-forgot">← Kembali ke Login</a>
    </div>
  </div>
</body>
</html>
