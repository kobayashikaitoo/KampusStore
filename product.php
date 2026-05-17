<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: /index.php'); exit; }

// Fetch product
$stmt = $db->prepare('
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
           u.id AS seller_id, u.username AS seller_username,
           u.name AS seller_name, u.campus AS seller_campus,
           u.is_verified AS seller_verified, u.is_trusted AS seller_trusted,
           u.created_at AS seller_joined
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE p.id = ? AND p.status != "inactive"
    LIMIT 1
');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    header('Location: /index.php');
    exit;
}

// Increment views
$db->prepare('UPDATE products SET views = views + 1 WHERE id = ?')->execute([$id]);

// Wishlist status
$isSaved = false;
if (isLoggedIn()) {
    $wStmt = $db->prepare('SELECT 1 FROM wishlists WHERE user_id=? AND product_id=?');
    $wStmt->execute([$_SESSION['user_id'], $id]);
    $isSaved = (bool)$wStmt->fetchColumn();
}

// Related products (same category, not this one)
$relStmt = $db->prepare('
    SELECT p.id, p.title, p.price, p.is_nego, p.condition, p.image,
           u.name AS seller_name, u.is_verified AS seller_verified
    FROM products p JOIN users u ON p.seller_id = u.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = "active"
    ORDER BY RAND() LIMIT 4
');
$relStmt->execute([$product['category_id'], $id]);
$related = $relStmt->fetchAll();

// Seller listing count
$cntStmt = $db->prepare('SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = "active"');
$cntStmt->execute([$product['seller_id']]);
$sellerListings = (int)$cntStmt->fetchColumn();

$condLabels = [
    'like_new' => ['label'=>'Seperti Baru','color'=>'#16a34a','bg'=>'#f0fdf4'],
    'good'     => ['label'=>'Kondisi Baik','color'=>'#2563eb','bg'=>'#eff6ff'],
    'fair'     => ['label'=>'Cukup Baik',  'color'=>'#d97706','bg'=>'#fffbeb'],
    'used'     => ['label'=>'Bekas',        'color'=>'#64748b','bg'=>'#f8fafc'],
];
$cond = $condLabels[$product['condition']] ?? $condLabels['good'];
$imgSrc = $product['image'] ? '/' . $product['image'] : '/assets/images/placeholder.png';
$pageTitle = e($product['title']) . ' — KampusStore';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <meta name="description" content="<?= e(truncate($product['description'] ?? $product['title'], 120)) ?>"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .pd-wrap{max-width:1100px;margin:0 auto;padding:32px 24px 80px}

    /* Breadcrumb */
    .breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--muted);margin-bottom:24px;flex-wrap:wrap}
    .breadcrumb a{color:var(--body);text-decoration:none}.breadcrumb a:hover{color:var(--primary);text-decoration:underline}
    .breadcrumb-sep{color:var(--hairline)}

    /* Main grid */
    .pd-grid{display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start}
    @media(max-width:860px){.pd-grid{grid-template-columns:1fr}}

    /* Image */
    .pd-img-wrap{background:white;border-radius:20px;overflow:hidden;border:1px solid var(--hairline);aspect-ratio:4/3}
    .pd-img{width:100%;height:100%;object-fit:contain;padding:24px}
    .pd-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:80px}

    /* Info Card */
    .pd-info{background:white;border:1px solid var(--hairline);border-radius:20px;padding:28px;box-shadow:0 2px 16px rgba(0,0,0,0.05)}
    .pd-cat{font-size:12px;font-weight:600;color:var(--primary);letter-spacing:0.3px;margin-bottom:10px}
    .pd-title{font-size:22px;font-weight:800;color:var(--ink);line-height:1.3;letter-spacing:-0.4px;margin-bottom:12px}
    .pd-price-row{display:flex;align-items:center;gap:10px;margin-bottom:16px}
    .pd-price{font-size:28px;font-weight:800;color:var(--ink);letter-spacing:-0.5px}
    .pd-nego{font-size:13px;font-weight:600;color:#d97706;background:#fffbeb;padding:3px 10px;border-radius:999px;border:1px solid #fde68a}
    .pd-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
    .pd-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 12px;border-radius:999px}
    .pd-divider{height:1px;background:var(--hairline);margin:20px 0}

    /* Meta info */
    .pd-meta{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
    .pd-meta-item label{font-size:11px;font-weight:700;color:var(--muted);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:0.4px}
    .pd-meta-item span{font-size:14px;font-weight:500;color:var(--ink)}

    /* Description */
    .pd-desc-title{font-size:14px;font-weight:700;color:var(--ink);margin-bottom:8px}
    .pd-desc{font-size:14px;color:var(--body);line-height:1.7;white-space:pre-wrap;word-break:break-word}

    /* Seller card */
    .seller-card{background:white;border:1px solid var(--hairline);border-radius:20px;padding:24px;margin-top:16px;box-shadow:0 2px 16px rgba(0,0,0,0.05)}
    .seller-card-head{display:flex;align-items:center;gap:14px;margin-bottom:16px}
    .seller-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:white;flex-shrink:0}
    .seller-info-name{font-size:16px;font-weight:700;color:var(--ink)}
    .seller-info-sub{font-size:12px;color:var(--muted);margin-top:2px}
    .seller-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
    .seller-stat{background:var(--surface);border-radius:10px;padding:10px;text-align:center}
    .seller-stat-num{font-size:18px;font-weight:700;color:var(--ink)}
    .seller-stat-lbl{font-size:11px;color:var(--muted)}

    /* CTA buttons */
    .btn-primary{width:100%;height:50px;background:var(--primary);color:white;border:none;border-radius:14px;font-family:inherit;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;transition:background .2s,transform .15s,box-shadow .2s;margin-bottom:10px}
    .btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,.3)}
    .btn-outline{width:100%;height:50px;background:white;color:var(--ink);border:1.5px solid var(--hairline);border-radius:14px;font-family:inherit;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;transition:all .2s;margin-bottom:10px}
    .btn-outline:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
    .btn-wishlist{width:100%;height:44px;background:white;border:1.5px solid var(--hairline);border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s}
    .btn-wishlist:hover{border-color:#ef4444;color:#ef4444;background:#fef2f2}
    .btn-wishlist.saved{border-color:#ef4444;color:#ef4444;background:#fef2f2}

    /* Related */
    .related-section{margin-top:48px}
    .related-title{font-size:18px;font-weight:700;color:var(--ink);margin-bottom:20px}
    .related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
    .related-card{background:white;border:1px solid var(--hairline);border-radius:16px;overflow:hidden;text-decoration:none;transition:transform .2s,box-shadow .2s;display:block}
    .related-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.1)}
    .related-img{width:100%;aspect-ratio:4/3;object-fit:cover;background:var(--surface)}
    .related-body{padding:12px}
    .related-title-text{font-size:13px;font-weight:600;color:var(--ink);margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .related-price{font-size:14px;font-weight:700;color:var(--ink)}
    .related-seller{font-size:11px;color:var(--muted);margin-top:3px}

    /* Status badge */
    .status-sold{background:#f0fdf4;color:#16a34a;padding:6px 16px;border-radius:8px;font-size:13px;font-weight:600;display:inline-block;margin-bottom:12px}
    .status-inactive{background:#f8fafc;color:var(--muted);padding:6px 16px;border-radius:8px;font-size:13px;font-weight:600;display:inline-block;margin-bottom:12px}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="pd-wrap">
  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/index.php">🏪 KampusStore</a>
    <span class="breadcrumb-sep">›</span>
    <a href="/index.php?cat=<?= e($product['cat_slug']) ?>"><?= $product['cat_icon'] ?> <?= e($product['cat_name']) ?></a>
    <span class="breadcrumb-sep">›</span>
    <span><?= e(truncate($product['title'], 50)) ?></span>
  </nav>

  <div class="pd-grid">
    <!-- Left: Image -->
    <div>
      <div class="pd-img-wrap">
        <?php if ($product['image']): ?>
          <img src="<?= $imgSrc ?>" alt="<?= e($product['title']) ?>" class="pd-img"
               onerror="this.src='/assets/images/placeholder.png'"/>
        <?php else: ?>
          <div class="pd-img-placeholder">📦</div>
        <?php endif; ?>
      </div>

      <?php if (!empty($related)): ?>
      <div class="related-section">
        <div class="related-title">🔗 Barang Serupa</div>
        <div class="related-grid">
          <?php foreach ($related as $r): ?>
            <a href="/product.php?id=<?= (int)$r['id'] ?>" class="related-card">
              <img src="<?= $r['image'] ? '/'.e($r['image']) : '/assets/images/placeholder.png' ?>"
                   alt="<?= e($r['title']) ?>" class="related-img"
                   onerror="this.src='/assets/images/placeholder.png'" loading="lazy"/>
              <div class="related-body">
                <div class="related-title-text"><?= e($r['title']) ?></div>
                <div class="related-price"><?= formatRupiah($r['price']) ?></div>
                <div class="related-seller">@<?= e($r['seller_name']) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: Info + Seller -->
    <div>
      <div class="pd-info">
        <div class="pd-cat"><?= $product['cat_icon'] ?> <?= e($product['cat_name']) ?></div>

        <?php if ($product['status'] === 'sold'): ?>
          <div class="status-sold">✅ Barang ini sudah terjual</div>
        <?php endif; ?>

        <h1 class="pd-title"><?= e($product['title']) ?></h1>

        <div class="pd-price-row">
          <span class="pd-price"><?= formatRupiah($product['price']) ?></span>
          <?php if ($product['is_nego']): ?>
            <span class="pd-nego">Harga Nego</span>
          <?php endif; ?>
        </div>

        <div class="pd-badges">
          <span class="pd-badge" style="background:<?= $cond['bg'] ?>;color:<?= $cond['color'] ?>">
            ✦ <?= $cond['label'] ?>
          </span>
          <?php if ($product['seller_verified']): ?>
            <span class="pd-badge" style="background:#eff6ff;color:#2563eb">✓ Verified Student</span>
          <?php endif; ?>
          <?php if ($product['seller_trusted']): ?>
            <span class="pd-badge" style="background:#fdf4ff;color:#7c3aed">🏅 Trusted Seller</span>
          <?php endif; ?>
        </div>

        <div class="pd-meta">
          <?php if ($product['location']): ?>
          <div class="pd-meta-item">
            <label>📍 Lokasi</label>
            <span><?= e($product['location']) ?></span>
          </div>
          <?php endif; ?>
          <div class="pd-meta-item">
            <label>📅 Diposting</label>
            <span><?= date('d M Y', strtotime($product['created_at'])) ?></span>
          </div>
          <div class="pd-meta-item">
            <label>👁️ Dilihat</label>
            <span><?= number_format($product['views']) ?>×</span>
          </div>
          <div class="pd-meta-item">
            <label>🏷️ Status</label>
            <span><?= $product['status'] === 'active' ? 'Tersedia' : ucfirst($product['status']) ?></span>
          </div>
        </div>

        <?php if ($product['description']): ?>
          <div class="pd-divider"></div>
          <div class="pd-desc-title">📝 Deskripsi Barang</div>
          <div class="pd-desc"><?= e($product['description']) ?></div>
        <?php endif; ?>

        <?php if ($product['status'] === 'active'): ?>
        <div class="pd-divider"></div>

        <?php if (isLoggedIn() && $_SESSION['user_id'] === (int)$product['seller_id']): ?>
          <!-- Own product -->
          <a href="/my-listings.php" class="btn-outline">📦 Kelola Barang Saya</a>
        <?php else: ?>
          <!-- CTA -->
          <a href="/seller.php?id=<?= (int)$product['seller_id'] ?>" class="btn-primary">
            💬 Chat Penjual
          </a>
          <button
            class="btn-wishlist <?= $isSaved ? 'saved' : '' ?>"
            id="wishlist-btn"
            data-id="<?= (int)$id ?>"
            onclick="toggleWishlistDetail(this)"
          >
            <span id="wishlist-icon"><?= $isSaved ? '♥' : '♡' ?></span>
            <span id="wishlist-label"><?= $isSaved ? 'Tersimpan di Wishlist' : 'Simpan ke Wishlist' ?></span>
          </button>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (isLoggedIn() && $_SESSION['user_id'] !== (int)$product['seller_id']): ?>
          <div style="text-align:center;margin-top:16px">
            <a href="/report.php?type=product&id=<?= (int)$id ?>" style="font-size:12px;color:var(--muted);text-decoration:none;transition:color .2s" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--muted)'">🚩 Laporkan barang ini</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Seller Card -->
      <div class="seller-card">
        <div class="seller-card-head">
          <a href="/seller.php?id=<?= (int)$product['seller_id'] ?>" style="display:flex;align-items:center;gap:14px;text-decoration:none;flex:1">
            <div class="seller-avatar"><?= strtoupper(mb_substr($product['seller_name'], 0, 1)) ?></div>
            <div>
              <div class="seller-info-name"><?= e($product['seller_name']) ?></div>
              <div class="seller-info-sub">@<?= e($product['seller_username']) ?>
                <?php if ($product['seller_campus']): ?> · <?= e($product['seller_campus']) ?><?php endif; ?>
              </div>
            </div>
          </a>
        </div>
        <div class="seller-stats">
          <div class="seller-stat">
            <div class="seller-stat-num"><?= $sellerListings ?></div>
            <div class="seller-stat-lbl">Barang Aktif</div>
          </div>
          <div class="seller-stat">
            <div class="seller-stat-num"><?= date('Y') - date('Y', strtotime($product['seller_joined'])) > 0 ? date('Y') - date('Y', strtotime($product['seller_joined'])) . 'th' : 'Baru' ?></div>
            <div class="seller-stat-lbl">Bergabung</div>
          </div>
        </div>
        <a href="/seller.php?id=<?= (int)$product['seller_id'] ?>" class="btn-outline" style="margin-bottom:0">
          👤 Lihat Profil Penjual
        </a>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/main.js" defer></script>
<script>
function toggleWishlistDetail(btn) {
  const id = btn.dataset.id;
  btn.disabled = true;

  fetch('/api/wishlist_toggle.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'product_id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'unauthenticated') {
      window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
      return;
    }
    const icon  = document.getElementById('wishlist-icon');
    const label = document.getElementById('wishlist-label');
    if (data.saved) {
      icon.textContent  = '♥';
      label.textContent = 'Tersimpan di Wishlist';
      btn.classList.add('saved');
    } else {
      icon.textContent  = '♡';
      label.textContent = 'Simpan ke Wishlist';
      btn.classList.remove('saved');
    }
    btn.style.transform = 'scale(1.03)';
    setTimeout(() => { btn.style.transform = ''; btn.disabled = false; }, 250);
  })
  .catch(() => { btn.disabled = false; });
}
</script>
</body>
</html>
