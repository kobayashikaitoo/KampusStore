<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: ' . BASE_URL . 'index.php'); exit; }

// Fetch product
$stmt = $db->prepare('
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
           u.id AS seller_id, u.username AS seller_username,
           u.name AS seller_name, u.campus AS seller_campus,
           u.is_verified AS seller_verified, u.is_trusted AS seller_trusted,
           u.whatsapp_number AS seller_whatsapp,
           u.profile_photo AS seller_photo,
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
    header('Location: ' . BASE_URL . 'index.php');
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
    SELECT p.id, p.title, p.price, p.is_nego, p.`condition`, p.image,
           u.name AS seller_name, u.is_verified AS seller_verified
    FROM products p JOIN users u ON p.seller_id = u.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = "active"
    ORDER BY RAND() LIMIT 8
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
$imgSrc = $product['image'] ? BASE_URL . $product['image'] : BASE_URL . 'assets/images/placeholder.png';
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/product-detail.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="pd-wrap">
  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="index.php">KampusStore</a>
    <span class="breadcrumb-sep">›</span>
    <a href="index.php?cat=<?= e($product['cat_slug']) ?>"><?= e($product['cat_name']) ?></a>
    <span class="breadcrumb-sep">›</span>
    <span><?= e(truncate($product['title'], 50)) ?></span>
  </nav>

  <div class="pd-grid">
    <!-- Left: Image -->
    <div>
      <div class="pd-img-wrap" style="position:relative; aspect-ratio:4/3; border-radius:20px; overflow:hidden; border:1px solid var(--hairline); background:white;">
        <?php 
        $images = getProductAllImages($product['image']);
        if (count($images) > 1): 
        ?>
          <!-- Slider Container -->
          <div class="pd-slider" id="pd-slider" style="display:flex; width:100%; height:100%; overflow-x:auto; scroll-snap-type:x mandatory; scroll-behavior:smooth; -webkit-overflow-scrolling:touch;">
            <?php foreach ($images as $idx => $img): 
              $fullPath = BASE_URL . $img;
            ?>
              <div style="flex:0 0 100%; width:100%; height:100%; scroll-snap-align:start; display:flex; align-items:center; justify-content:center; padding:16px;">
                <img src="<?= $fullPath ?>" alt="<?= e($product['title']) ?> - <?= $idx + 1 ?>" style="width:100%; height:100%; object-fit:contain; border-radius:12px;" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'"/>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Slider dots -->
          <div style="position:absolute; bottom:12px; left:50%; transform:translateX(-50%); display:flex; gap:6px; z-index:10; background:rgba(0,0,0,0.3); padding:4px 10px; border-radius:999px;">
            <?php foreach ($images as $idx => $img): ?>
              <button onclick="scrollToSlide(<?= $idx ?>)" class="slide-dot" style="width:7px; height:7px; border-radius:50%; border:none; background:rgba(255,255,255,<?= $idx === 0 ? '1' : '0.4' ?>); cursor:pointer; padding:0; transition:background .2s;"></button>
            <?php endforeach; ?>
          </div>
          
          <script>
            function scrollToSlide(idx) {
              const slider = document.getElementById('pd-slider');
              const width = slider.clientWidth;
              slider.scrollTo({ left: width * idx, behavior: 'smooth' });
              
              // Update dots active state
              const dots = document.querySelectorAll('.slide-dot');
              dots.forEach((dot, i) => {
                dot.style.background = i === idx ? 'rgba(255,255,255,1)' : 'rgba(255,255,255,0.4)';
              });
            }
            
            // Listen to scroll to update dots
            document.getElementById('pd-slider').addEventListener('scroll', function() {
              const width = this.clientWidth;
              const idx = Math.round(this.scrollLeft / width);
              const dots = document.querySelectorAll('.slide-dot');
              dots.forEach((dot, i) => {
                dot.style.background = i === idx ? 'rgba(255,255,255,1)' : 'rgba(255,255,255,0.4)';
              });
            });
          </script>
        <?php else: 
          $singleImg = BASE_URL . getProductImage($product['image']);
        ?>
          <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:16px;">
            <img src="<?= $singleImg ?>" alt="<?= e($product['title']) ?>" style="width:100%; height:100%; object-fit:contain; border-radius:12px;" onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'"/>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Right: Info + Seller -->
    <div>
      <div class="pd-info">
        <div class="pd-cat"><?= e($product['cat_name']) ?></div>

        <?php if ($product['status'] === 'sold'): ?>
          <div class="status-sold">Barang ini sudah terjual</div>
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
          <?php if ($product['seller_trusted']): ?>
            <span class="pd-badge" style="background:#fdf4ff;color:#7c3aed">🏅 Trusted Seller</span>
          <?php endif; ?>
        </div>

        <div class="pd-meta">
          <?php if ($product['location']): ?>
          <div class="pd-meta-item">
            <label><i class="fas fa-map-pin"></i> Lokasi</label>
            <span><?= e($product['location']) ?></span>
          </div>
          <?php endif; ?>
          <div class="pd-meta-item">
            <label><i class="far fa-calendar-alt"></i> Diposting</label>
            <span><?= date('d M Y', strtotime($product['created_at'])) ?></span>
          </div>
          <div class="pd-meta-item">
            <label><i class="fas fa-eye"></i> Dilihat</label>
            <span><?= number_format($product['views']) ?>×</span>
          </div>
          <div class="pd-meta-item">
            <label><i class="fas fa-tag"></i> Status</label>
            <span><?= $product['status'] === 'active' ? 'Tersedia' : ucfirst($product['status']) ?></span>
          </div>
        </div>

        <?php if ($product['description']): ?>
          <div class="pd-divider"></div>
          <div class="pd-desc-title"><i class="fas fa-file-alt"></i> Deskripsi Barang</div>
          <div class="pd-desc"><?= e($product['description']) ?></div>
        <?php endif; ?>

        <?php if ($product['status'] === 'active'): ?>
        <div class="pd-divider"></div>

        <?php if (isLoggedIn() && $_SESSION['user_id'] === (int)$product['seller_id']): ?>
          <!-- Own product -->
          <a href="my-listings.php" class="btn-outline"><i class="fas fa-box"></i> Kelola Barang Saya</a>
        <?php else: ?>
          <!-- CTA -->
          <div class="pd-actions-row">
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $product['seller_whatsapp']) ?>" target="_blank" class="btn-primary" style="margin-bottom:0">
              <i class="fab fa-whatsapp"></i> Chat WhatsApp
            </a>
            <button
              class="btn-wishlist <?= $isSaved ? 'saved' : '' ?>"
              id="wishlist-btn"
              data-id="<?= (int)$id ?>"
              onclick="toggleWishlistDetail(this)"
              style="margin-bottom:0"
            >
              <i class="fas fa-heart"></i> <span id="wishlist-label"><?= $isSaved ? 'Disimpan' : 'Simpan' ?></span>
            </button>
          </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (isLoggedIn() && $_SESSION['user_id'] !== (int)$product['seller_id']): ?>
          <div style="text-align:center;margin-top:16px">
            <a href="report.php?type=product&id=<?= (int)$id ?>" style="font-size:12px;color:var(--muted);text-decoration:none;transition:color .2s" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--muted)'">Laporkan barang ini</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Seller Card -->
      <div class="seller-card">
        <div class="seller-card-head">
          <a href="seller.php?id=<?= (int)$product['seller_id'] ?>" style="display:flex;align-items:center;gap:14px;text-decoration:none;flex:1">
            <div class="seller-avatar">
              <?php if (!empty($product['seller_photo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($product['seller_photo']) ?>" alt="Foto <?= e($product['seller_name']) ?>"
                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
                <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:inherit;font-weight:inherit;">
                  <?= strtoupper(mb_substr($product['seller_name'], 0, 1)) ?>
                </span>
              <?php else: ?>
                <?= strtoupper(mb_substr($product['seller_name'], 0, 1)) ?>
              <?php endif; ?>
            </div>
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
        <a href="seller.php?id=<?= (int)$product['seller_id'] ?>" class="btn-outline" style="margin-bottom:0">
          Lihat Profil Penjual
        </a>
      </div>
    </div>
  </div>

  <?php if (!empty($related)): ?>
  <div class="related-section" style="margin-top: 40px; border-top: 1px solid var(--hairline); padding-top: 40px;">
    <div class="related-title">🔗 Barang Serupa</div>
    <div class="related-grid">
      <?php foreach ($related as $r): ?>
        <a href="product.php?id=<?= (int)$r['id'] ?>" class="related-card">
          <img src="<?= $r['image'] ? BASE_URL.htmlspecialchars(getProductImage($r['image'])) : BASE_URL.'assets/images/placeholder.png' ?>"
               alt="<?= e($r['title']) ?>" class="related-img"
               onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'" loading="lazy"/>
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

<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
<script>
function toggleWishlistDetail(btn) {
  const id = btn.dataset.id;
  btn.disabled = true;

  fetch(window.BASE_URL + 'api/wishlist_toggle.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'product_id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'unauthenticated') {
      window.location.href = '<?php echo BASE_URL; ?>auth/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
      return;
    }
    const icon  = document.getElementById('wishlist-icon');
    const label = document.getElementById('wishlist-label');
    if (data.saved) {
      label.textContent = 'Disimpan';
      btn.classList.add('saved');
    } else {
      label.textContent = 'Simpan';
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
