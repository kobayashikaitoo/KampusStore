<?php
/**
 * Script untuk menambah akun admin
 * Jalankan: http://localhost/kampusstore/add-admin.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/auth.php';

// Cegah akses jika sudah ada admin
$db = getDB();
$stmt = $db->prepare('SELECT COUNT(*) as count FROM users WHERE role = "admin"');
$stmt->execute();
$result = $stmt->fetch();

if ($result['count'] > 0 && !isset($_POST['force'])) {
    $hasAdmin = true;
} else {
    $hasAdmin = false;
}

// Initialize variables
$username = '';
$name = '';
$password = '';
$password_confirm = '';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $success = false;

    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username hanya boleh huruf, angka, dan underscore (3–30 karakter)';
    }

    if (empty($name)) {
        $errors[] = 'Nama harus diisi';
    }

    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Password tidak cocok';
    }

    // Cek username sudah ada
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = 'Username sudah digunakan';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        try {
            $stmt = $db->prepare(
                'INSERT INTO users (username, name, password, role, is_verified, is_trusted) 
                 VALUES (?, ?, ?, ?, 1, 1)'
            );
            $stmt->execute([$username, $name, $hash, 'admin']);
            
            $success = true;
            $username = $name = $password = $password_confirm = '';
        } catch (Exception $e) {
            $errors[] = 'Gagal menambah admin: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tambah Admin — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css"/>
</head>
<body class="auth-page">

<div class="auth-wrap">
  <div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
      <a href="<?= BASE_URL ?>index.php">
        <i class="fas fa-store" style="font-size:24px"></i>
        <span class="auth-logo-text">KampusStore</span>
      </a>
    </div>

    <h1 class="auth-title">Tambah Admin</h1>
    <p class="auth-sub">Buat akun administrator baru untuk sistem.</p>

    <!-- Success Message -->
    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle" style="margin-right: 6px"></i> Akun admin berhasil ditambahkan!
        <div style="margin-top: 8px; font-size: 13px">
          Username: <strong><?= htmlspecialchars($username) ?></strong>
        </div>
        <a href="<?= BASE_URL ?>admin/" class="btn-auth" style="margin-top:16px; display:flex; align-items:center; justify-content:center; text-decoration:none">→ Masuk ke Panel Admin</a>
      </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
      <div class="alert-error" style="flex-direction:column; align-items:flex-start">
        <?php foreach ($errors as $error): ?>
          <div>• <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Warning message (if admin already exists) -->
    <?php if ($hasAdmin && !$_POST): ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Sudah ada akun admin di sistem. Anda dapat menambah admin lagi dengan mengisi formulir di bawah ini.
      </div>
    <?php endif; ?>

    <?php if (!$hasAdmin || $_POST || !empty($errors)): ?>
      <form method="POST">
        <!-- Username -->
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-user"></i></span>
            <input type="text" id="username" name="username" class="form-input"
                   value="<?= htmlspecialchars($username ?? '') ?>" 
                   placeholder="admin" required autofocus autocomplete="username"/>
          </div>
        </div>

        <!-- Name -->
        <div class="form-group">
          <label class="form-label" for="name">Nama Lengkap</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-id-card"></i></span>
            <input type="text" id="name" name="name" class="form-input"
                   value="<?= htmlspecialchars($name ?? '') ?>" 
                   placeholder="Administrator" required autocomplete="name"/>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-lock"></i></span>
            <input type="password" id="password" name="password" class="form-input"
                   placeholder="Minimal 8 karakter" required autocomplete="new-password"/>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label class="form-label" for="password_confirm">Konfirmasi Password</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-key"></i></span>
            <input type="password" id="password_confirm" name="password_confirm" class="form-input"
                   placeholder="Ulangi password" required autocomplete="new-password"/>
          </div>
        </div>

        <button type="submit" class="btn-auth">Tambah Admin</button>
      </form>
    <?php else: ?>
      <div style="text-align:center; padding: 12px 0; color: var(--body);">
        <p>Admin sudah ditambahkan sebelumnya.</p>
        <p style="font-size: 13px; margin-top: 12px; color: var(--muted);">Jika ingin menambah admin lagi, silakan isi form setelah merefresh halaman ini.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
