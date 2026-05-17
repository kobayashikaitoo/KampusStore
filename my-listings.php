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
    if (!$ownProd) { $_SESSION['listing_err'] = 'Produk tidak ditemukan.'; header('Location: /my-listings.php'); exit; }
    switch ($action) {
        case 'delete':
            if ($ownProd['image'] && str_contains($ownProd['image'], 'uploads/')) {
                $f = __DIR__ . '/' . $ownProd['image']; if (file_exists($f)) unlink($f);
            }
            $db->prepare('DELETE FROM products WHERE id = ?')->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang dihapus.'; break;
        case 'mark_sold':
            $db->prepare("UPDATE products SET status='sold' WHERE id=?")->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang ditandai terjual. 🎉'; break;
        case 'activate':
            $db->prepare("UPDATE products SET status='active' WHERE id=?")->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang diaktifkan.'; break;
        case 'deactivate':
            $db->prepare("UPDATE products SET status='inactive' WHERE id=?")->execute([$pid]);
            $_SESSION['listing_msg'] = 'Barang disembunyikan.'; break;
    }
    header('Location: /my-listings.php'); exit;
}

$filter = $_GET['status'] ?? 'all';
$where  = 'p.seller_id = ?'; $params = [$uid];
if (in_array($filter, ['active','sold','inactive'])) { $where .= ' AND p.status = ?'; $params[] = $filter; }
$stmt = $db->prepare("SELECT p.*, c.name AS cat_name, c.icon AS cat_icon FROM products p JOIN categories c ON p.category_id = c.id WHERE $where ORDER BY p.created_at DESC");
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
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .ml-wrap{max-width:860px;margin:0 auto;padding:32px 24px 80px}
    .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
    .page-title{font-size:22px;font-weight:800;color:var(--ink)}
    .btn-new{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:var(--primary);color:white;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;transition:background .2s}
    .btn-new:hover{background:var(--primary-dark)}
    .status-tabs{display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap}
    .status-tab{padding:7px 16px;border-radius:999px;font-size:13px;font-weight:600;text-decoration:none;color:var(--body);background:white;border:1.5px solid var(--hairline);transition:all .2s}
    .status-tab.active{background:var(--primary);color:white;border-color:var(--primary)}
    .listing-card{background:white;border:1px solid var(--hairline);border-radius:16px;overflow:hidden;display:grid;grid-template-columns:120px 1fr;margin-bottom:12px;transition:box-shadow .2s}
    .listing-card:hover{box-shadow:0 4px 16px rgba(0,0,0,0.08)}
    @media(max-width:480px){.listing-card{grid-template-columns:80px 1fr}}
    .listing-img{width:100%;aspect-ratio:1;object-fit:cover;background:var(--surface);display:block}
    .listing-img-ph{width:100%;aspect-ratio:1;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:32px}
    .listing-body{padding:16px;display:flex;flex-direction:column;gap:6px}
    .listing-title{font-size:15px;font-weight:700;color:var(--ink);line-height:1.3;text-decoration:none}
    .listing-title:hover{color:var(--primary)}
    .listing-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .listing-price{font-size:15px;font-weight:700;color:var(--ink)}
    .lbadge{font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px}
    .lb-active{background:#f0fdf4;color:#16a34a}
    .lb-sold{background:#eff6ff;color:#2563eb}
    .lb-inactive{background:#f8fafc;color:var(--muted)}
    .listing-date{font-size:12px;color:var(--muted)}
    .listing-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:4px}
    .act-btn{padding:5px 12px;border-radius:8px;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid var(--hairline);background:white;color:var(--ink);transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
    .act-btn:hover{border-color:var(--primary);color:var(--primary)}
    .act-btn.danger:hover{border-color:#ef4444;color:#ef4444;background:#fef2f2}
    .act-btn.success{color:#16a34a;border-color:#22c55e}.act-btn.success:hover{background:#f0fdf4}
    .alert{padding:12px 16px;border-radius:12px;font-size:14px;margin-bottom:20px}
    .alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
    .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
    .empty-state{text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid var(--hairline)}
    .views-pill{font-size:11px;color:var(--muted);background:var(--surface);padding:2px 8px;border-radius:999px}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="ml-wrap">
  <div class="page-header">
    <h1 class="page-title">📦 Barang Saya</h1>
    <a href="/sell.php" class="btn-new">+ Tambah Barang</a>
  </div>

  <?php if ($msg): ?><div class="alert alert-success">✅ <?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error">⚠️ <?= e($err) ?></div><?php endif; ?>

  <div class="status-tabs">
    <?php
    $tabs = ['all'=>'Semua','active'=>'✅ Aktif','sold'=>'🏷️ Terjual','inactive'=>'⏸️ Tersembunyi'];
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
      <div style="font-size:48px;margin-bottom:12px">📭</div>
      <div style="font-size:16px;font-weight:600;color:var(--ink);margin-bottom:8px">Belum ada barang</div>
      <p style="color:var(--muted);font-size:14px;margin-bottom:20px">Mulai jual barang bekas kamu!</p>
      <a href="/sell.php" class="btn-new">➕ Posting Sekarang</a>
    </div>
  <?php else: ?>
    <?php foreach ($listings as $p):
      $img  = $p['image'] ? '/'.$p['image'] : null;
      $cond = $condLabels[$p['condition']] ?? 'Bekas';
      $sbMap = ['active'=>'lb-active','sold'=>'lb-sold','inactive'=>'lb-inactive'];
      $slMap = ['active'=>'Aktif','sold'=>'Terjual','inactive'=>'Tersembunyi'];
    ?>
    <div class="listing-card">
      <?php if ($img): ?>
        <img src="<?= e($img) ?>" class="listing-img" alt="<?= e($p['title']) ?>"
             onerror="this.src='/assets/images/placeholder.png'"/>
      <?php else: ?>
        <div class="listing-img-ph">📦</div>
      <?php endif; ?>
      <div class="listing-body">
        <a href="/product.php?id=<?= (int)$p['id'] ?>" class="listing-title"><?= e($p['title']) ?></a>
        <div class="listing-meta">
          <span class="listing-price"><?= formatRupiah($p['price']) ?></span>
          <span class="lbadge <?= $sbMap[$p['status']] ?? '' ?>"><?= $slMap[$p['status']] ?? '' ?></span>
          <span class="views-pill">👁️ <?= number_format($p['views']) ?></span>
        </div>
        <div class="listing-date"><?= e($p['cat_icon']) ?> <?= e($p['cat_name']) ?> · <?= $cond ?> · <?= date('d M Y', strtotime($p['created_at'])) ?></div>
        <div class="listing-actions">
          <a href="/product.php?id=<?= (int)$p['id'] ?>" class="act-btn">👁️ Lihat</a>
          <?php if ($p['status'] === 'active'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="mark_sold"/>
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
              <button class="act-btn success">✅ Tandai Terjual</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="deactivate"/>
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
              <button class="act-btn">⏸️ Sembunyikan</button>
            </form>
          <?php elseif ($p['status'] === 'inactive'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="activate"/>
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
              <button class="act-btn success">✅ Aktifkan</button>
            </form>
          <?php endif; ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Hapus permanen?')">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
            <button class="act-btn danger">🗑️ Hapus</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
