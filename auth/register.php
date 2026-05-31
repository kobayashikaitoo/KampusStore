<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/auth.php';

redirectIfLoggedIn();

$error = $_SESSION['auth_error'] ?? null;
$old   = $_SESSION['auth_old']   ?? [];
unset($_SESSION['auth_error'], $_SESSION['auth_old']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Daftar — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css?v=2"/>
</head>
<body class="auth-page">
<div class="auth-wrap">
  <div class="auth-card auth-card-wide">
    <div class="auth-logo">
      <a href="../index.php">
        <i class="fas fa-store" style="font-size:24px"></i>
        <span class="auth-logo-text">KampusStore</span>
      </a>
    </div>
    <h1 class="auth-title">Buat akun baru</h1>
    <p class="auth-sub">Bergabung dengan komunitas kampus Indonesia</p>

    <?php if ($error): ?>
      <div class="alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="process_register.php" id="reg-form" class="register-form">
      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?? (
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16))
      ) ?>"/>

      <div class="form-grid">
        <!-- Name -->
        <div class="form-group">
          <label class="form-label" for="name">Nama Lengkap</label>
          <div class="input-wrap">
            <input type="text" id="name" name="name" class="form-input"
              value="<?= htmlspecialchars($old['name'] ?? '') ?>"
              placeholder="Nama kamu" required autocomplete="name"/>
          </div>
        </div>

        <!-- Username -->
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-at"></i></span>
            <input type="text" id="username" name="username"
              class="form-input <?= isset($old['username']) ? 'is-error' : '' ?>"
              value="<?= htmlspecialchars($old['username'] ?? '') ?>"
              placeholder="username_unik" required autocomplete="username"
              minlength="3" maxlength="30"
              pattern="[a-zA-Z0-9_]+"
              oninput="checkUsername(this)"/>
          </div>
          <div class="field-hint" id="username-hint">Huruf, angka, dan _ saja (3–30 karakter)</div>
        </div>

        <!-- Email Kampus -->
        <div class="form-group">
          <label class="form-label" for="email">Email Kampus</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-envelope"></i></span>
            <input type="email" id="email" name="email" class="form-input"
              value="<?= htmlspecialchars($old['email'] ?? '') ?>"
              placeholder="nama@student.unsrat.ac.id" required autocomplete="email"/>
          </div>
          <div class="field-hint">Gunakan email @student.unsrat.ac.id</div>
        </div>

        <!-- Campus -->
        <div class="form-group">
          <label class="form-label" for="campus">Kampus</label>
          <div class="input-wrap">
            <input type="text" id="campus" name="campus" class="form-input"
              value="<?= htmlspecialchars($old['campus'] ?? '') ?>"
              placeholder="Nama universitas" autocomplete="organization"/>
          </div>
        </div>

        <!-- Faculty -->
        <div class="form-group">
          <label class="form-label" for="faculty">Fakultas</label>
          <div class="input-wrap">
            <input type="text" id="faculty" name="faculty" class="form-input"
              value="<?= htmlspecialchars($old['faculty'] ?? '') ?>"
              placeholder="Fak. Teknik" autocomplete="off"/>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" class="form-input with-toggle"
              placeholder="Min. 8 karakter" required minlength="8"
              autocomplete="new-password" oninput="checkStrength(this.value)"/>
            <button type="button" class="toggle-pw" onclick="togglePassword('password',this)" aria-label="Toggle"><i class="fas fa-eye"></i></button>
          </div>
          <div class="pw-strength">
            <div class="pw-bar"><div class="pw-fill" id="pw-fill"></div></div>
            <span class="pw-label" id="pw-label">Masukkan password</span>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label class="form-label" for="password_confirm">Konfirmasi Password</label>
          <div class="input-wrap">
            <span class="input-icon"><i class="fas fa-key"></i></span>
            <input type="password" id="password_confirm" name="password_confirm"
              class="form-input with-toggle" placeholder="Ulangi password" required
              autocomplete="new-password" oninput="checkConfirm()"/>
            <button type="button" class="toggle-pw" onclick="togglePassword('password_confirm',this)" aria-label="Toggle"><i class="fas fa-eye"></i></button>
          </div>
          <div class="field-hint" id="confirm-hint"></div>
        </div>
      </div>

      <button type="submit" class="btn-auth" id="reg-btn">Buat Akun <i class="fas fa-party-popper"></i></button>
      <p class="terms-note">
        Dengan mendaftar, kamu menyetujui <a href="<?= BASE_URL ?>terms.php">Syarat &amp; Ketentuan</a> dan <a href="<?= BASE_URL ?>privacy.php">Kebijakan Privasi</a> KampusStore.
      </p>
    </form>

    <div class="auth-footer">
      Sudah punya akun? <a href="login.php">Masuk di sini</a>
    </div>
  </div>
</div>

<script>
function togglePassword(id, btn) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
  btn.innerHTML = f.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}

const usernames = new Set(); // client-side debounce cache

function checkUsername(input) {
  const val = input.value;
  const hint = document.getElementById('username-hint');
  const ok = /^[a-zA-Z0-9_]{3,30}$/.test(val);
  input.classList.toggle('is-ok', ok);
  input.classList.toggle('is-error', val.length > 0 && !ok);
  hint.className = 'field-hint ' + (ok ? 'ok' : (val.length > 0 ? 'err' : ''));
  hint.innerHTML = ok
    ? '<i class="fas fa-check"></i> Username valid'
    : val.length > 0
      ? 'Hanya huruf, angka, dan _ (3–30 karakter)'
      : 'Huruf, angka, dan _ saja (3–30 karakter)';
}

function checkStrength(pw) {
  const fill  = document.getElementById('pw-fill');
  const label = document.getElementById('pw-label');
  let score = 0;
  if (pw.length >= 8)          score++;
  if (/[A-Z]/.test(pw))        score++;
  if (/[0-9]/.test(pw))        score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const map = {
    0: {w:'0%',   bg:'#e2e8f0', t:'Terlalu pendek'},
    1: {w:'25%',  bg:'#ef4444', t:'Lemah'},
    2: {w:'50%',  bg:'#f59e0b', t:'Cukup'},
    3: {w:'75%',  bg:'#3b82f6', t:'Kuat'},
    4: {w:'100%', bg:'#22c55e', t:'Sangat kuat <i class="fas fa-thumbs-up"></i>'},
  };
  fill.style.width = map[score].w;
  fill.style.background = map[score].bg;
  label.innerHTML = map[score].t;
  label.style.color = map[score].bg;
}

function checkConfirm() {
  const pw  = document.getElementById('password').value;
  const cpw = document.getElementById('password_confirm').value;
  const el  = document.getElementById('confirm-hint');
  if (!cpw) { el.innerHTML = ''; return; }
  if (pw === cpw) {
    el.className = 'field-hint ok'; el.innerHTML = '<i class="fas fa-check"></i> Password cocok';
  } else {
    el.className = 'field-hint err'; el.innerHTML = 'Password tidak cocok';
  }
}


document.getElementById('reg-form').addEventListener('submit', function(e) {
  const pw  = document.getElementById('password').value;
  const cpw = document.getElementById('password_confirm').value;
  if (pw !== cpw) {
    e.preventDefault();
    document.getElementById('confirm-hint').textContent = 'Password tidak cocok!';
    document.getElementById('confirm-hint').className = 'field-hint err';
    return;
  }
  const btn = document.getElementById('reg-btn');
  btn.textContent = 'Mendaftarkan…';
  btn.disabled = true;
});
</script>
<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
</body>
</html>
