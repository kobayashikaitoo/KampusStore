<?php
session_start();
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tim Kami — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/footer.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/team.css"/>
</head>
<body class="page-container">
  <?php require_once __DIR__ . '/components/navbar.php'; ?>

  <div class="team-header">
    <h1>Tim KampusStore</h1>
    <p>Mahasiswa berdedikasi yang membangun marketplace barang bekas untuk komunitas kampus Indonesia.</p>
  </div>

  <main>
    <div class="ks-container">
      <div class="team-grid">
        
        <!-- Member 1 -->
        <div class="team-card">
          <div class="team-card-avatar">
            <img src="assets/images/Ferdi.png" alt="Foto Anggota 1"/>
          </div>
          <div class="team-card-content">
            <h3 class="team-card-name">Friederik Ferdinand</h3>
            <p class="team-card-role">Backend Lead</p>
            <p class="team-card-id">NIM: 240211060035</p>
            <p class="team-card-desc">Mengembangkan API, database, dan logika bisnis utama KampusStore.</p>
          </div>
        </div>

        <!-- Member 2 -->
        <div class="team-card">
          <div class="team-card-avatar">
            <img src="assets/images/Iyann.jpg" alt="Foto Anggota 2"/>
          </div>
          <div class="team-card-content">
            <h3 class="team-card-name">Qatrunada Gyan Ramadhana</h3>
            <p class="team-card-role">Frontend Lead</p>
            <p class="team-card-id">NIM: 240211060052</p>
            <p class="team-card-desc">Merancang UI/UX dan mengimplementasikan frontend dengan HTML, CSS, dan JavaScript.</p>
          </div>
        </div>

        <!-- Member 3 -->
        <div class="team-card">
          <div class="team-card-avatar">
            <img src="assets/images/Taufik.png" alt="Foto Anggota 3"/>
          </div>
          <div class="team-card-content">
            <h3 class="team-card-name">Muhammad Taufik Akbar</h3>
            <p class="team-card-role">Database Admin</p>
            <p class="team-card-id">NIM: 240211060048</p>
            <p class="team-card-desc">Merancang dan mengelola struktur database MySQL untuk aplikasi.</p>
          </div>
        </div>

      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/components/footer.php'; ?>

  <script src="assets/js/main.js" defer></script>
</body>
</html>
