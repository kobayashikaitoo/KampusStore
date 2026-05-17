<?php
session_start();
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
  <link rel="stylesheet" href="../assets/css/custom.css"/>
  <style>
    body { min-height: 100vh; display: flex; flex-direction: column; background: var(--surface); }
    .auth-wrap {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 40px 16px;
    }
    .auth-card {
      width: 100%; max-width: 460px;
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      border: 1px solid rgba(226,232,240,0.7);
      border-radius: 24px; padding: 40px 36px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.08);
    }
    @media(max-width:480px){.auth-card{padding:32px 20px;border-radius:20px}}
    .auth-logo { text-align: center; margin-bottom: 24px; }
    .auth-logo a { text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .auth-logo-text {
      font-size: 22px; font-weight: 800; letter-spacing: -0.5px;
      background: linear-gradient(135deg,#2563eb,#7c3aed);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .auth-title { font-size: 22px; font-weight: 700; color: var(--ink); margin-bottom: 4px; text-align: center; }
    .auth-sub { font-size: 14px; color: var(--body); text-align: center; margin-bottom: 24px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media(max-width:400px){.form-grid{grid-template-columns:1fr}}
    .form-group { margin-bottom: 16px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      font-size: 15px; pointer-events: none; color: var(--muted);
    }
    .form-input {
      width: 100%; height: 46px;
      background: white; border: 1.5px solid var(--hairline);
      border-radius: 12px; padding: 0 14px 0 42px;
      font-family: inherit; font-size: 14px; color: var(--ink);
      transition: border-color .2s, box-shadow .2s; outline: none;
    }
    .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .form-input.is-error { border-color: #ef4444; }
    .form-input.is-ok { border-color: #22c55e; }
    .toggle-pw {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; font-size: 15px;
      color: var(--muted); padding: 0; transition: color .15s;
    }
    .toggle-pw:hover { color: var(--ink); }
    .field-hint { font-size: 12px; margin-top: 4px; color: var(--muted); }
    .field-hint.ok { color: #16a34a; }
    .field-hint.err { color: #dc2626; }
    .alert-error {
      background: #fef2f2; border: 1px solid #fecaca;
      border-radius: 12px; padding: 12px 16px;
      font-size: 14px; color: #dc2626; margin-bottom: 18px;
    }
    .pw-strength { margin-top: 6px; }
    .pw-bar {
      height: 4px; border-radius: 2px; background: var(--hairline);
      overflow: hidden; margin-bottom: 4px;
    }
    .pw-fill { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0; }
    .pw-label { font-size: 11px; color: var(--muted); }
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
    .btn-auth:disabled { opacity:.6; cursor:not-allowed; transform:none; }
    .terms-note { font-size: 12px; color: var(--muted); text-align: center; margin-top: 12px; line-height: 1.6; }
    .terms-note a { color: var(--primary); text-decoration: none; }
    .auth-footer { text-align: center; margin-top: 18px; font-size: 14px; color: var(--body); }
    .auth-footer a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="/index.php">
        <span style="font-size:24px">🏪</span>
        <span class="auth-logo-text">KampusStore</span>
      </a>
    </div>
    <h1 class="auth-title">Buat akun baru</h1>
    <p class="auth-sub">Bergabung dengan komunitas kampus Indonesia 🎓</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="process_register.php" id="reg-form">
      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?? (
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16))
      ) ?>"/>

      <div class="form-grid">
        <!-- Name -->
        <div class="form-group">
          <label class="form-label" for="name">Nama Lengkap</label>
          <div class="input-wrap">
            <span class="input-icon">📝</span>
            <input type="text" id="name" name="name" class="form-input"
              value="<?= htmlspecialchars($old['name'] ?? '') ?>"
              placeholder="Nama kamu" required autocomplete="name"/>
          </div>
        </div>

        <!-- Username -->
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <div class="input-wrap">
            <span class="input-icon">@</span>
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

        <!-- Campus -->
        <div class="form-group">
          <label class="form-label" for="campus">Kampus</label>
          <div class="input-wrap">
            <span class="input-icon">🏛️</span>
            <input type="text" id="campus" name="campus" class="form-input"
              value="<?= htmlspecialchars($old['campus'] ?? '') ?>"
              placeholder="Nama universitas" autocomplete="organization"/>
          </div>
        </div>

        <!-- Faculty -->
        <div class="form-group">
          <label class="form-label" for="faculty">Fakultas</label>
          <div class="input-wrap">
            <span class="input-icon">🎓</span>
            <input type="text" id="faculty" name="faculty" class="form-input"
              value="<?= htmlspecialchars($old['faculty'] ?? '') ?>"
              placeholder="Fak. Teknik" autocomplete="off"/>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="password" name="password" class="form-input"
              placeholder="Min. 8 karakter" required minlength="8"
              autocomplete="new-password" oninput="checkStrength(this.value)"/>
            <button type="button" class="toggle-pw" onclick="togglePassword('password',this)" aria-label="Toggle">👁️</button>
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
            <span class="input-icon">🔑</span>
            <input type="password" id="password_confirm" name="password_confirm"
              class="form-input" placeholder="Ulangi password" required
              autocomplete="new-password" oninput="checkConfirm()"/>
            <button type="button" class="toggle-pw" onclick="togglePassword('password_confirm',this)" aria-label="Toggle">👁️</button>
          </div>
          <div class="field-hint" id="confirm-hint"></div>
        </div>
      </div>

      <button type="submit" class="btn-auth" id="reg-btn">Buat Akun 🎉</button>
      <p class="terms-note">
        Dengan mendaftar, kamu menyetujui <a href="#">Syarat &amp; Ketentuan</a> dan <a href="#">Kebijakan Privasi</a> KampusStore.
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
  btn.textContent = f.type === 'password' ? '👁️' : '🙈';
}

const usernames = new Set(); // client-side debounce cache

function checkUsername(input) {
  const val = input.value;
  const hint = document.getElementById('username-hint');
  const ok = /^[a-zA-Z0-9_]{3,30}$/.test(val);
  input.classList.toggle('is-ok', ok);
  input.classList.toggle('is-error', val.length > 0 && !ok);
  hint.className = 'field-hint ' + (ok ? 'ok' : (val.length > 0 ? 'err' : ''));
  hint.textContent = ok
    ? '✓ Username valid'
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
    4: {w:'100%', bg:'#22c55e', t:'Sangat kuat 💪'},
  };
  fill.style.width = map[score].w;
  fill.style.background = map[score].bg;
  label.textContent = map[score].t;
  label.style.color = map[score].bg;
}

function checkConfirm() {
  const pw  = document.getElementById('password').value;
  const cpw = document.getElementById('password_confirm').value;
  const el  = document.getElementById('confirm-hint');
  if (!cpw) { el.textContent = ''; return; }
  if (pw === cpw) {
    el.className = 'field-hint ok'; el.textContent = '✓ Password cocok';
  } else {
    el.className = 'field-hint err'; el.textContent = 'Password tidak cocok';
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
</body>
</html>
