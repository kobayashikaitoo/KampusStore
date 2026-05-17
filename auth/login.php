<?php
session_start();
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
  <link rel="stylesheet" href="../assets/css/custom.css"/>
  <style>
    body { min-height: 100vh; display: flex; flex-direction: column; background: var(--surface); }
    .auth-wrap {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 40px 16px;
    }
    .auth-card {
      width: 100%; max-width: 440px;
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      border: 1px solid rgba(226,232,240,0.7);
      border-radius: 24px;
      padding: 40px 36px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.08);
    }
    @media(max-width:480px){.auth-card{padding:32px 24px;border-radius:20px}}

    .auth-logo { text-align: center; margin-bottom: 28px; }
    .auth-logo a { text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .auth-logo-text {
      font-size: 22px; font-weight: 800; letter-spacing: -0.5px;
      background: linear-gradient(135deg,#2563eb,#7c3aed);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .auth-title { font-size: 22px; font-weight: 700; color: var(--ink); margin-bottom: 4px; text-align: center; }
    .auth-sub { font-size: 14px; color: var(--body); text-align: center; margin-bottom: 28px; }

    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      font-size: 16px; pointer-events: none; color: var(--muted);
    }
    .form-input {
      width: 100%; height: 48px;
      background: white; border: 1.5px solid var(--hairline);
      border-radius: 12px; padding: 0 44px 0 42px;
      font-family: inherit; font-size: 15px; color: var(--ink);
      transition: border-color .2s, box-shadow .2s; outline: none;
    }
    .form-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    .form-input.error { border-color: #ef4444; }
    .form-input.error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
    .toggle-pw {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; font-size: 16px;
      color: var(--muted); padding: 0; line-height: 1;
      transition: color .15s;
    }
    .toggle-pw:hover { color: var(--ink); }

    .alert-error {
      background: #fef2f2; border: 1px solid #fecaca;
      border-radius: 12px; padding: 12px 16px;
      font-size: 14px; color: #dc2626; margin-bottom: 20px;
      display: flex; align-items: center; gap: 8px;
    }
    .alert-success {
      background: #f0fdf4; border: 1px solid #bbf7d0;
      border-radius: 12px; padding: 12px 16px;
      font-size: 14px; color: #16a34a; margin-bottom: 20px;
      display: flex; align-items: center; gap: 8px;
    }

    .btn-auth {
      width: 100%; height: 50px;
      background: var(--primary); color: white;
      font-family: inherit; font-size: 15px; font-weight: 600;
      border: none; border-radius: 12px; cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s;
      margin-top: 4px;
    }
    .btn-auth:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.3); }
    .btn-auth:active { transform: translateY(0); }
    .btn-auth.loading { opacity: .7; cursor: not-allowed; }

    .auth-divider {
      display: flex; align-items: center; gap: 12px;
      margin: 20px 0; color: var(--muted); font-size: 13px;
    }
    .auth-divider::before, .auth-divider::after {
      content: ''; flex: 1; height: 1px; background: var(--hairline);
    }

    .auth-footer { text-align: center; margin-top: 20px; font-size: 14px; color: var(--body); }
    .auth-footer a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }

    .username-hint { font-size: 12px; color: var(--muted); margin-top: 5px; }
    .username-hint.ok { color: #16a34a; }
    .username-hint.err { color: #dc2626; }
  </style>
</head>
<body>

<div class="auth-wrap">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <a href="/index.php">
        <span style="font-size:24px">🏪</span>
        <span class="auth-logo-text">KampusStore</span>
      </a>
    </div>

    <h1 class="auth-title">Selamat datang kembali!</h1>
    <p class="auth-sub">Masuk ke akun KampusStore kamu.</p>

    <!-- Alert -->
    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="process_login.php" id="login-form">
      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?? (
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16))
      ) ?>"/>

      <!-- Username -->
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
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
          <a href="forgot.php" style="float:right;font-size:12px;font-weight:400;color:var(--primary);text-decoration:none">Lupa password?</a>
        </label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input
            type="password" id="password" name="password"
            class="form-input"
            placeholder="••••••••"
            autocomplete="current-password" required
          />
          <button type="button" class="toggle-pw" onclick="togglePassword('password', this)" aria-label="Tampilkan password">👁️</button>
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

<script>
function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  if (field.type === 'password') {
    field.type = 'text';
    btn.textContent = '🙈';
  } else {
    field.type = 'password';
    btn.textContent = '👁️';
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
