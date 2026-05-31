<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

requireLogin();

$db   = getDB();
$uid  = (int)$_SESSION['user_id'];
$msg  = $_SESSION['listing_msg'] ?? null;
$err  = $_SESSION['listing_err'] ?? null;
unset($_SESSION['listing_msg'], $_SESSION['listing_err']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['product_id'] ?? 0);
    $own    = $db->prepare('SELECT id, image FROM products WHERE id = ? AND seller_id = ?');
    $own->execute([$pid, $uid]);
    $ownProd = $own->fetch();
    if (!$ownProd) { $_SESSION['listing_err'] = 'Produk tidak ditemukan.'; header('Location: ' . BASE_URL . 'my-listings.php'); exit; }
    switch ($action) {
        case 'delete':
            if ($ownProd['image'] && str_contains($ownProd['image'], 'uploads/')) {
                $f = __DIR__ . '/' . $ownProd['image']; if (file_exists($f)) unlink($f);
            }
            $db->prepare('DELETE FROM products WHERE id = ?')->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang dihapus.'; break;
        case 'mark_sold':
            $db->prepare("UPDATE products SET status='sold' WHERE id=?")->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang ditandai terjual.'; break;
        case 'activate':
            $db->prepare("UPDATE products SET status='active' WHERE id=?")->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang diaktifkan.'; break;
        case 'deactivate':
            $db->prepare("UPDATE products SET status='inactive' WHERE id=?")->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang disembunyikan.'; break;
    }
    header('Location: ' . BASE_URL . 'my-listings.php'); exit;
}

$filter = $_GET['status'] ?? 'all';
$where  = 'p.seller_id = ?'; $params = [$uid];
if (in_array($filter, ['active','sold','inactive'])) { $where .= ' AND p.status = ?'; $params[] = $filter; }
$stmt = $db->prepare("SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE $where ORDER BY p.created_at DESC");
$stmt->execute($params);
$listings = $stmt->fetchAll();

$cntStmt = $db->prepare("SELECT status, COUNT(*) AS n FROM products WHERE seller_id=? GROUP BY status");
$cntStmt->execute([$uid]); $countMap = array_column($cntStmt->fetchAll(), 'n', 'status');
$condLabels = ['like_new'=>'Seperti Baru','good'=>'Kondisi Baik','fair'=>'Cukup Baik','used'=>'Bekas'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barang Saya — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/my-listings.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="ml-wrap">
  <div class="page-header">
    <h1 class="page-title"><i class="fas fa-box"></i> Barang Saya</h1>
    <a href="sell.php" class="btn-new">+ Tambah Barang</a>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= e($err) ?></div><?php endif; ?>

  <div class="status-tabs">
    <?php
    $tabs = ['all'=>'Semua','active'=>'<i class="fas fa-check"></i> Aktif','sold'=>'<i class="fas fa-tag"></i> Terjual','inactive'=>'<i class="fas fa-pause"></i> Tersembunyi'];
    foreach ($tabs as $val => $label):
      $cnt = $val === 'all' ? array_sum($countMap) : ($countMap[$val] ?? 0);
    ?>
      <a href="?status=<?= $val ?>" class="status-tab <?= $filter===$val ? 'active' : '' ?>">
        <?= $label ?> (<?= $cnt ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($listings)): ?>
    <div class="empty-state">
      <div class="empty-icon" style="font-size:48px"><i class="fas fa-inbox"></i></div>
      <div class="empty-title">Belum ada barang</div>
      <p class="empty-desc">Mulai jual barang bekas kamu!</p>
      <a href="sell.php" class="btn-new"><i class="fas fa-plus"></i> Posting Sekarang</a>
    </div>
  <?php else: ?>
    <?php foreach ($listings as $p):
      $img  = !empty($p['image']) ? BASE_URL . getProductImage($p['image']) : null;
      $cond = $condLabels[$p['condition']] ?? 'Bekas';
      $sbMap = ['active'=>'lb-active','sold'=>'lb-sold','inactive'=>'lb-inactive'];
      $slMap = ['active'=>'Aktif','sold'=>'Terjual','inactive'=>'Tersembunyi'];
    ?>
    <div class="listing-row">
      <div class="listing-left">
        <?php if ($img): ?>
          <img src="<?= e($img) ?>" style="width:100%; height:100%; object-fit:cover; display:block;" alt="<?= e($p['title']) ?>"
               onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'"/>
        <?php else: ?>
          <div class="listing-img-ph"><i class="fas fa-box" style="font-size:32px;color:var(--muted)"></i></div>
        <?php endif; ?>
      </div>
      <div class="listing-right">
        <div class="listing-info">
          <a href="product.php?id=<?= (int)$p['id'] ?>" class="listing-title" style="display:block; text-decoration:none; font-size:16px; font-weight:700; color:var(--ink); margin-bottom:6px;"><?= e($p['title']) ?></a>
          <div class="listing-meta" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
            <span class="listing-price" style="font-weight:700; color:var(--primary); font-size:15px;"><?= formatRupiah($p['price']) ?></span>
            <span class="lbadge <?= $sbMap[$p['status']] ?? '' ?>"><?= $slMap[$p['status']] ?? '' ?></span>
            <span class="views-pill"><i class="fas fa-eye"></i> <?= number_format($p['views']) ?></span>
          </div>
          <div class="listing-date" style="font-size:12px; color:var(--muted)">📦 <?= e($p['cat_name']) ?> · ✦ <?= $cond ?> · 📅 <?= date('d M Y', strtotime($p['created_at'])) ?></div>
        </div>
        <div class="listing-actions">
          <a href="product.php?id=<?= (int)$p['id'] ?>" class="act-btn"><i class="fas fa-eye"></i> Lihat</a>
          <?php if ($p['status'] === 'active'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="mark_sold"/>
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
              <button class="act-btn success" style="border-color:#bbf7d0;color:#15803d;background:#f0fdf4;"><i class="fas fa-check"></i> Tandai Terjual</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="deactivate"/>
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
              <button class="act-btn"><i class="fas fa-pause"></i> Sembunyikan</button>
            </form>
          <?php elseif ($p['status'] === 'inactive'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="activate"/>
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
              <button class="act-btn success" style="border-color:#bbf7d0;color:#15803d;background:#f0fdf4;"><i class="fas fa-check"></i> Aktifkan</button>
            </form>
          <?php endif; ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Hapus permanen?')">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
            <button class="act-btn danger" style="border-color:#fecaca;color:#dc2626;background:#fef2f2;"><i class="fas fa-trash"></i> Hapus</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
</body>
</html>
