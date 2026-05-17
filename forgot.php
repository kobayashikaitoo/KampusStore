<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lupa Password — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
    .auth-card{background:white;width:100%;max-width:400px;border-radius:24px;border:1px solid var(--hairline);padding:32px;box-shadow:0 12px 40px rgba(0,0,0,0.08);text-align:center}
    .auth-title{font-size:24px;font-weight:800;color:var(--ink);margin-bottom:8px}
    .auth-sub{font-size:14px;color:var(--muted);margin-bottom:24px;line-height:1.5}
    .form-group{margin-bottom:16px;text-align:left}
    .form-label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px}
    .form-input{width:100%;border:1.5px solid var(--hairline);border-radius:12px;padding:11px 14px;font-family:inherit;font-size:15px;color:var(--ink);background:white;outline:none;transition:border-color .2s}
    .form-input:focus{border-color:var(--primary)}
    .btn-submit{width:100%;height:46px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;margin-bottom:16px}
    .btn-submit:hover{background:var(--primary-dark)}
    .btn-back{font-size:14px;font-weight:600;color:var(--muted);text-decoration:none}
    .btn-back:hover{color:var(--ink)}
  </style>
</head>
<body>
  <div class="auth-card">
    <div style="font-size:48px;margin-bottom:16px">🔐</div>
    <h1 class="auth-title">Lupa Password?</h1>
    <p class="auth-sub">Masukkan email kampus kamu. Kami akan mengirimkan instruksi untuk mereset password (Fitur sedang dalam pengembangan).</p>
    
    <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Link reset password pura-pura telah dikirim ke email kamu!'); window.location='/auth/login.php';">
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" class="form-input" placeholder="kamu@student.univ.edu" required/>
      </div>
      <button type="submit" class="btn-submit">Kirim Link Reset</button>
      <a href="/auth/login.php" class="btn-back">← Kembali ke Login</a>
    </form>
  </div>
</body>
</html>
