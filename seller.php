<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

$db  = getDB();
$sid = (int)($_GET['id'] ?? 0);

if (!$sid) { header('Location: ' . BASE_URL . 'index.php'); exit; }

// Get seller
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND is_banned = 0 AND role = "user" LIMIT 1');
$stmt->execute([$sid]);
$seller = $stmt->fetch();

if (!$seller) {
    http_response_code(404);
    header('Location: ' . BASE_URL . 'index.php'); exit;
}

// Seller's active products
$pStmt = $db->prepare('
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.seller_id = ? AND p.status = "active"
    ORDER BY p.created_at DESC
');
$pStmt->execute([$sid]);
$listings = $pStmt->fetchAll();

// Stats
$soldStmt = $db->prepare('SELECT COUNT(*) FROM products WHERE seller_id=? AND status="sold"');
$soldStmt->execute([$sid]);
$soldCount = (int)$soldStmt->fetchColumn();
$totalCount = count($listings);

$condLabels = [
    'like_new'=>['label'=>'Seperti Baru','class'=>'cond-like-new'],
    'good'    =>['label'=>'Kondisi Baik', 'class'=>'cond-good'],
    'fair'    =>['label'=>'Cukup Baik',   'class'=>'cond-fair'],
    'used'    =>['label'=>'Bekas',         'class'=>'cond-used'],
];

$initials    = strtoupper(mb_substr($seller['name'], 0, 1));
$joinedDate  = date('M Y', strtotime($seller['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e($seller['name']) ?> — Penjual di KampusStore</title>
  <meta name="description" content="Lihat barang jual dari <?= e($seller['name']) ?> di KampusStore."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/seller.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="sl-wrap">
  <!-- Profile Header -->
  <div class="sp-header">
    <div class="sp-banner"></div>
    <div class="sp-header-body">
      <div>
        <div class="sp-av-wrap"><div class="sp-av"><?= $initials ?></div></div>
        <div class="sp-name"><?= e($seller['name']) ?></div>
        <div class="sp-username">@<?= e($seller['username']) ?>
          <?php if ($seller['campus']): ?> · <?= e($seller['campus']) ?><?php endif; ?>
          <?php if ($seller['faculty']): ?> · <?= e($seller['faculty']) ?><?php endif; ?>
        </div>
        <div class="sp-badges">
          <?php if ($seller['is_trusted']): ?>
            <span class="sp-badge" style="background:#fdf4ff;color:#7c3aed">🏅 Trusted Seller</span>
          <?php endif; ?>
          <span class="sp-badge" style="background:var(--surface);color:var(--body)">📅 Bergabung <?= $joinedDate ?></span>
        </div>
      </div>
      <?php if (isLoggedIn() && $_SESSION['user_id'] !== $sid): ?>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:center">
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $seller['whatsapp_number']) ?>" target="_blank" class="btn-chat-lg"><i class="fab fa-whatsapp"></i> Chat WhatsApp</a>
          <a href="report.php?type=user&id=<?= (int)$sid ?>" style="font-size:12px;color:var(--muted);text-decoration:none;transition:color .2s" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--muted)'">​<i class="fas fa-flag"></i> Laporkan penjual</a>
        </div>
      <?php elseif (!isLoggedIn()): ?>
        <a href="auth/login.php" class="btn-chat-lg"><i class="fas fa-key"></i> Login untuk Chat</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="sp-stats">
    <div class="sp-stat">
      <div class="sp-stat-num"><?= $totalCount ?></div>
      <div class="sp-stat-lbl"><i class="fas fa-box"></i> Barang Dijual</div>
    </div>
    <div class="sp-stat">
      <div class="sp-stat-num"><?= $soldCount ?></div>
      <div class="sp-stat-lbl"><i class="fas fa-check"></i> Terjual</div>
    </div>
  </div>

  <!-- Listings -->
  <div class="sp-grid-title"><i class="fas fa-box"></i> Barang Dijual (<?= $totalCount ?>)</div>

  <?php if (empty($listings)): ?>
    <div style="text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid var(--hairline)">
      <div style="font-size:48px;margin-bottom:12px"><i class="fas fa-inbox"></i></div>
      <div style="font-size:16px;font-weight:600;color:var(--ink);margin-bottom:6px">Belum ada barang dijual</div>
      <div style="font-size:14px;color:var(--muted)">Penjual ini belum memposting barang.</div>
    </div>
  <?php else: ?>
    <div class="sp-grid">
      <?php foreach ($listings as $p):
        $cond = $condLabels[$p['condition']] ?? $condLabels['good'];
        $img  = BASE_URL . getProductImage($p['image']);
      ?>
      <div class="spc">
        <a href="product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none">
          <div class="spc-img-wrap">
            <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" class="spc-img"
                 onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'" loading="lazy"/>
            <span class="spc-cond <?= $cond['class'] ?>"><?= $cond['label'] ?></span>
          </div>
          <div class="spc-body">
            <div class="spc-title"><?= e($p['title']) ?></div>
            <div class="spc-price"><?= formatRupiah($p['price']) ?><?= $p['is_nego'] ? ' <span style="font-size:11px;color:#d97706;font-weight:500">· Nego</span>' : '' ?></div>
            <?php if ($p['location']): ?><div class="spc-loc"><i class="fas fa-map-pin"></i> <?= e($p['location']) ?></div><?php endif; ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
</body>
</html>
