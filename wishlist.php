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
    header('Location: ' . BASE_URL . 'wishlist.php'); exit;
}

$stmt = $db->prepare('
    SELECT p.id, p.title, p.price, p.is_nego, p.`condition`, p.image, p.status,
           c.name AS cat_name,
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/wishlist.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="wl-wrap">
  <h1 class="page-title"><i class="fas fa-heart"></i> Wishlist <span class="mb-1">(<?= count($items) ?> barang)</span></h1>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="empty-icon">🤍</div>
      <div class="empty-title">Wishlist kamu kosong</div>
      <p class="empty-desc">Klik ikon <i class="fas fa-heart"></i> pada barang untuk menyimpannya di sini.</p>
      <a href="index.php" class="hero-btn-primary" style="display:inline-flex;gap:6px"><i class="fas fa-magnifying-glass"></i> Jelajahi Barang</a>
    </div>
  <?php else: ?>
    <div class="wl-grid">
      <?php foreach ($items as $p):
        $img  = BASE_URL . getProductImage($p['image']);
        $cond = $condLabels[$p['condition']] ?? 'Bekas';
        $isSold = $p['status'] === 'sold';
      ?>
      <div class="wl-card">
        <div class="wl-card-img-wrap">
          <img src="<?= e($img) ?>" class="wl-card-img" alt="<?= e($p['title']) ?>"
               onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'" loading="lazy"/>
          <?php if ($isSold): ?>
            <div class="sold-overlay"><span class="sold-pill"><i class="fas fa-check"></i> Terjual</span></div>
          <?php endif; ?>
          <!-- Remove button (AJAX enabled, fallbacks to POST form if JS is unavailable) -->
          <button type="button" class="wl-remove-btn" title="Hapus dari wishlist"
            onclick="removeWishlistItem(this, <?= (int)$p['id'] ?>)">✕</button>
        </div>
        <div class="wl-card-body">
          <div class="wl-card-cat"><?= e($p['cat_name']) ?> · <?= $cond ?></div>
          <a href="product.php?id=<?= (int)$p['id'] ?>" class="wl-card-title"><?= e($p['title']) ?></a>
          <div>
            <span class="wl-card-price"><?= formatRupiah($p['price']) ?></span>
            <?php if ($p['is_nego']): ?><span class="wl-card-nego">Nego</span><?php endif; ?>
          </div>
          <div class="wl-card-seller"><?= e($p['seller_name']) ?><?= $p['seller_verified'] ? ' <i class="fas fa-check" style="color:#16a34a"></i>' : '' ?></div>
          <div class="saved-on">Disimpan <?= date('d M Y', strtotime($p['saved_at'])) ?></div>
        </div>
        <div class="wl-card-footer">
          <a href="product.php?id=<?= (int)$p['id'] ?>" class="btn-view-sm">
            <?= $isSold ? 'Lihat Detail' : 'Lihat Barang' ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
<script>
function removeWishlistItem(btn, productId) {
  if (!confirm('Hapus dari wishlist?')) return;
  btn.disabled = true;

  fetch(window.BASE_URL + 'api/wishlist_toggle.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'product_id=' + encodeURIComponent(productId)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'removed') {
      const card = btn.closest('.wl-card');
      if (card) {
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => {
          card.remove();
          // Cek apakah wishlist sekarang kosong
          const grid = document.querySelector('.wl-grid');
          if (grid && grid.querySelectorAll('.wl-card').length === 0) {
            location.reload(); // Reload untuk memuat tampilan empty state yang rapi
          } else {
            // Update counter wishlist di header
            const titleSpan = document.querySelector('.page-title span');
            if (titleSpan) {
              const count = grid.querySelectorAll('.wl-card').length;
              titleSpan.textContent = `(${count} barang)`;
            }
          }
        }, 300);
      }
    } else {
      btn.disabled = false;
    }
  })
  .catch(() => {
    btn.disabled = false;
  });
}
</script>
</body>
</html>
