<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

// Remove from wishlist via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $db->prepare('DELETE FROM wishlists WHERE user_id=? AND product_id=?')->execute([$uid, $pid]);
    header('Location: /wishlist.php'); exit;
}

$stmt = $db->prepare('
    SELECT p.id, p.title, p.price, p.is_nego, p.condition, p.image, p.status, p.location,
           c.name AS cat_name, c.icon AS cat_icon,
           u.name AS seller_name, u.username AS seller_username,
           u.is_verified AS seller_verified,
           w.created_at AS saved_at
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
');
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

$condLabels = ['like_new'=>'Seperti Baru','good'=>'Kondisi Baik','fair'=>'Cukup Baik','used'=>'Bekas'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wishlist — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .wl-wrap{max-width:900px;margin:0 auto;padding:32px 24px 80px}
    .page-title{font-size:22px;font-weight:800;color:var(--ink);margin-bottom:24px}
    .wl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
    .wl-card{background:white;border:1px solid var(--hairline);border-radius:16px;overflow:hidden;position:relative;transition:transform .2s,box-shadow .2s}
    .wl-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.1)}
    .wl-card-img-wrap{position:relative;aspect-ratio:4/3;overflow:hidden;background:var(--surface)}
    .wl-card-img{width:100%;height:100%;object-fit:cover;display:block}
    .wl-remove-btn{position:absolute;top:8px;right:8px;width:30px;height:30px;border-radius:50%;background:rgba(0,0,0,.5);color:white;border:none;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:background .15s}
    .wl-remove-btn:hover{background:rgba(239,68,68,.85)}
    .sold-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center}
    .sold-pill{background:white;color:var(--ink);font-size:13px;font-weight:700;padding:6px 16px;border-radius:999px}
    .wl-card-body{padding:14px}
    .wl-card-cat{font-size:11px;color:var(--muted);margin-bottom:4px}
    .wl-card-title{font-size:14px;font-weight:700;color:var(--ink);margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;text-decoration:none}
    .wl-card-title:hover{color:var(--primary)}
    .wl-card-price{font-size:15px;font-weight:700;color:var(--ink)}
    .wl-card-nego{font-size:11px;color:#d97706;margin-left:6px}
    .wl-card-seller{font-size:12px;color:var(--muted);margin-top:6px}
    .wl-card-footer{padding:10px 14px;border-top:1px solid var(--hairline);display:flex;gap:8px}
    .btn-view-sm{flex:1;height:36px;border-radius:10px;background:var(--primary);color:white;font-family:inherit;font-size:13px;font-weight:600;border:none;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;transition:background .15s}
    .btn-view-sm:hover{background:var(--primary-dark)}
    .empty-state{text-align:center;padding:80px 20px;background:white;border-radius:20px;border:1px solid var(--hairline)}
    .saved-on{font-size:11px;color:var(--muted);margin-top:4px}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="wl-wrap">
  <h1 class="page-title">♥ Wishlist <span style="font-size:16px;font-weight:400;color:var(--muted)">(<?= count($items) ?> barang)</span></h1>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <div style="font-size:56px;margin-bottom:16px">🤍</div>
      <div style="font-size:18px;font-weight:700;color:var(--ink);margin-bottom:8px">Wishlist kamu kosong</div>
      <p style="color:var(--muted);font-size:14px;margin-bottom:24px">Klik ikon ♡ pada barang untuk menyimpannya di sini.</p>
      <a href="/index.php" style="display:inline-flex;align-items:center;gap:6px;padding:12px 24px;background:var(--primary);color:white;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none">🔍 Jelajahi Barang</a>
    </div>
  <?php else: ?>
    <div class="wl-grid">
      <?php foreach ($items as $p):
        $img  = $p['image'] ? '/'.$p['image'] : '/assets/images/placeholder.png';
        $cond = $condLabels[$p['condition']] ?? 'Bekas';
        $isSold = $p['status'] === 'sold';
      ?>
      <div class="wl-card">
        <div class="wl-card-img-wrap">
          <img src="<?= e($img) ?>" class="wl-card-img" alt="<?= e($p['title']) ?>"
               onerror="this.src='/assets/images/placeholder.png'" loading="lazy"/>
          <?php if ($isSold): ?>
            <div class="sold-overlay"><span class="sold-pill">✅ Terjual</span></div>
          <?php endif; ?>
          <!-- Remove button -->
          <form method="POST" style="display:contents">
            <input type="hidden" name="action" value="remove"/>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
            <button type="submit" class="wl-remove-btn" title="Hapus dari wishlist"
              onclick="return confirm('Hapus dari wishlist?')">✕</button>
          </form>
        </div>
        <div class="wl-card-body">
          <div class="wl-card-cat"><?= e($p['cat_icon']) ?> <?= e($p['cat_name']) ?> · <?= $cond ?></div>
          <a href="/product.php?id=<?= (int)$p['id'] ?>" class="wl-card-title"><?= e($p['title']) ?></a>
          <div>
            <span class="wl-card-price"><?= formatRupiah($p['price']) ?></span>
            <?php if ($p['is_nego']): ?><span class="wl-card-nego">Nego</span><?php endif; ?>
          </div>
          <div class="wl-card-seller">👤 <?= e($p['seller_name']) ?><?= $p['seller_verified'] ? ' ✓' : '' ?></div>
          <div class="saved-on">Disimpan <?= date('d M Y', strtotime($p['saved_at'])) ?></div>
        </div>
        <div class="wl-card-footer">
          <a href="/product.php?id=<?= (int)$p['id'] ?>" class="btn-view-sm">
            <?= $isSold ? '👁️ Lihat Detail' : '🔍 Lihat Barang' ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
