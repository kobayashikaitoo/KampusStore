<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

requireLogin();

$db = getDB();
$uid = (int)$_SESSION['user_id'];
$type = $_GET['type'] ?? ''; // 'product' or 'user'
$targetId = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['product', 'user']) || !$targetId) {
    header('Location: /index.php'); exit;
}

$targetName = '';
if ($type === 'product') {
    $stmt = $db->prepare('SELECT title FROM products WHERE id=?');
    $stmt->execute([$targetId]);
    $targetName = $stmt->fetchColumn();
} else {
    $stmt = $db->prepare('SELECT username FROM users WHERE id=?');
    $stmt->execute([$targetId]);
    $targetName = '@' . $stmt->fetchColumn();
}

if (!$targetName) {
    header('Location: /index.php'); exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    if (empty($reason)) {
        $error = 'Alasan laporan tidak boleh kosong.';
    } else {
        $productId = $type === 'product' ? $targetId : null;
        $userId = $type === 'user' ? $targetId : null;
        
        $stmt = $db->prepare('INSERT INTO reports (reporter_id, product_id, user_id, reason, status) VALUES (?, ?, ?, ?, "open")');
        $stmt->execute([$uid, $productId, $userId, $reason]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Laporkan — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .report-wrap{max-width:500px;margin:60px auto;padding:0 24px}
    .report-card{background:white;border-radius:24px;border:1px solid var(--hairline);padding:32px;box-shadow:0 4px 24px rgba(0,0,0,0.06)}
    .report-title{font-size:22px;font-weight:800;color:var(--ink);margin-bottom:8px}
    .report-sub{font-size:14px;color:var(--muted);margin-bottom:24px;line-height:1.5}
    .form-group{margin-bottom:16px}
    .form-label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px}
    .form-input{width:100%;border:1.5px solid var(--hairline);border-radius:12px;padding:11px 14px;font-family:inherit;font-size:15px;color:var(--ink);background:white;outline:none;transition:border-color .2s}
    .form-input:focus{border-color:var(--primary)}
    textarea.form-input{min-height:120px;resize:vertical}
    .btn-submit{width:100%;height:46px;background:#ef4444;color:white;border:none;border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s}
    .btn-submit:hover{background:#dc2626}
    .btn-back{width:100%;height:46px;background:white;color:var(--ink);border:1.5px solid var(--hairline);border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;margin-top:12px;transition:all .2s}
    .btn-back:hover{background:var(--surface)}
    .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px;border-radius:12px;font-size:14px;margin-bottom:20px}
    .success-state{text-align:center;padding:20px 0}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="report-wrap">
  <div class="report-card">
    <?php if ($success): ?>
      <div class="success-state">
        <div style="font-size:64px;margin-bottom:16px">✅</div>
        <h2 style="font-size:20px;font-weight:700;color:var(--ink);margin-bottom:8px">Laporan Diterima</h2>
        <p style="font-size:14px;color:var(--muted);margin-bottom:24px">Terima kasih telah membantu menjaga keamanan KampusStore. Admin kami akan segera meninjau laporan ini.</p>
        <a href="/index.php" class="btn-back">Kembali ke Beranda</a>
      </div>
    <?php else: ?>
      <h1 class="report-title">🚩 Buat Laporan</h1>
      <p class="report-sub">Kamu akan melaporkan <?= $type === 'product' ? 'barang' : 'pengguna' ?>: <strong><?= e($targetName) ?></strong>. Pastikan laporanmu valid dan bukan spam.</p>
      
      <?php if ($error): ?><div class="alert-error">⚠️ <?= e($error) ?></div><?php endif; ?>
      
      <form method="POST">
        <div class="form-group">
          <label class="form-label" for="reason">Jelaskan alasannya *</label>
          <textarea id="reason" name="reason" class="form-input" placeholder="Contoh: Barang palsu, penipuan, atau konten tidak pantas..." required></textarea>
        </div>
        <button type="submit" class="btn-submit">Kirim Laporan</button>
        <a href="javascript:history.back()" class="btn-back">Batal</a>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
