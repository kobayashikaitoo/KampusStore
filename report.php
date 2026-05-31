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
    header('Location: ' . BASE_URL . 'index.php'); exit;
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
    header('Location: ' . BASE_URL . 'index.php'); exit;
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/report.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="report-wrap">
  <div class="report-card">
    <?php if ($success): ?>
      <div class="success-state">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h2 class="success-title">Laporan Diterima</h2>
        <p class="success-desc">Terima kasih telah membantu menjaga keamanan KampusStore. Admin kami akan segera meninjau laporan ini.</p>
        <a href="index.php" class="btn-back-report">Kembali ke Beranda</a>
      </div>
    <?php else: ?>
      <h1 class="report-title"><i class="fas fa-flag"></i> Buat Laporan</h1>
      <p class="report-sub">Kamu akan melaporkan <?= $type === 'product' ? 'barang' : 'pengguna' ?>: <strong><?= e($targetName) ?></strong>. Pastikan laporanmu valid dan bukan spam.</p>
      
      <?php if ($error): ?>
        <div class="alert-report-error"><i class="fas fa-triangle-exclamation"></i> <?= e($error) ?></div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="report-form-group">
          <label class="report-label" for="reason">Jelaskan alasannya *</label>
          <textarea id="reason" name="reason" class="report-input report-textarea" placeholder="Contoh: Barang palsu, penipuan, atau konten tidak pantas..." required></textarea>
        </div>
        <button type="submit" class="btn-submit-report">Kirim Laporan</button>
        <a href="javascript:history.back()" class="btn-back-report">Batal</a>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
