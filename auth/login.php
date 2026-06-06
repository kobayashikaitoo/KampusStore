<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/auth.php';

redirectIfLoggedIn();

$error   = $_SESSION['auth_error']   ?? null;
$success = $_SESSION['auth_success'] ?? null;
$old     = $_SESSION['auth_old']     ?? [];
unset($_SESSION['auth_error'], $_SESSION['auth_success'], $_SESSION['auth_old']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Masuk — KampusStore</title>
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
      <a href="../index.php">
        <i class="fas fa-store" style="font-size:24px"></i>
        <span class="auth-logo-text">KampusStore</span>
      </a>
    </div>

    <h1 class="auth-title">Selamat datang kembali!</h1>
    <p class="auth-sub">Masuk ke akun KampusStore kamu.</p>

    <!-- Alert -->
    <?php if ($error): ?>
      <div class="alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          showToast('<?= addslashes($success) ?>', 'success', 3000);
        });
      </script>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="process_login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" id="login-form">
      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?? (
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16))
      ) ?>"/>

      <!-- Username -->
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-wrap">
          <input
            type="text" id="username" name="username"
            class="form-input <?= $error ? 'error' : '' ?>"
            value="<?= htmlspecialchars($old['username'] ?? '') ?>"
            placeholder="username_kamu"
            autocomplete="username" required autofocus
          />
        </div>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label class="form-label" for="password">
          Password
          <a href="<?= BASE_URL ?>forgot.php" style="float:right;font-size:12px;font-weight:400;color:var(--primary);text-decoration:none">Lupa password?</a>
        </label>
        <div class="input-wrap">
          <input
            type="password" id="password" name="password"
            class="form-input"
            placeholder="••••••••"
            autocomplete="current-password" required
          />
          <button type="button" class="toggle-pw" onclick="togglePassword('password', this)" aria-label="Tampilkan password"><i class="fas fa-eye"></i></button>
        </div>
      </div>

      <button type="submit" class="btn-auth" id="login-btn">Masuk →</button>
    </form>

    <div class="auth-divider">atau</div>

    <div class="auth-footer">
      Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </div>

  </div>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
<script>
function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  if (field.type === 'password') {
    field.type = 'text';
    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
  } else {
    field.type = 'password';
    btn.innerHTML = '<i class="fas fa-eye"></i>';
  }
}

document.getElementById('login-form').addEventListener('submit', function() {
  const btn = document.getElementById('login-btn');
  btn.textContent = 'Memproses…';
  btn.classList.add('loading');
  btn.disabled = true;
});
</script>
</body>
</html>
