<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/auth.php';

$db  = getDB();
$sid = (int)($_GET['id'] ?? 0);

if (!$sid) { header('Location: /index.php'); exit; }

// Get seller
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND is_banned = 0 AND role = "user" LIMIT 1');
$stmt->execute([$sid]);
$seller = $stmt->fetch();

if (!$seller) {
    http_response_code(404);
    header('Location: /index.php'); exit;
}

// Seller's active products
$pStmt = $db->prepare('
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
    FROM products p JOIN categories c ON p.category_id = c.id
    WHERE p.seller_id = ? AND p.status = "active"
    ORDER BY p.created_at DESC
');
$pStmt->execute([$sid]);
$listings = $pStmt->fetchAll();

// Stats
$soldCount = (int)$db->prepare('SELECT COUNT(*) FROM products WHERE seller_id=? AND status="sold"')->execute([$sid]) && $db->query("SELECT COUNT(*) FROM products WHERE seller_id=$sid AND status='sold'")->fetchColumn();
$soldCount  = (int)$db->query("SELECT COUNT(*) FROM products WHERE seller_id=$sid AND status='sold'")->fetchColumn();
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
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .sp-wrap{max-width:1000px;margin:0 auto;padding:32px 24px 80px}

    /* Profile header */
    .sp-header{background:white;border:1px solid var(--hairline);border-radius:24px;overflow:hidden;margin-bottom:28px;box-shadow:0 2px 16px rgba(0,0,0,0.05)}
    .sp-banner{height:100px;background:linear-gradient(135deg,#2563eb 0%,#7c3aed 100%)}
    .sp-header-body{padding:0 28px 28px;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px}
    .sp-av-wrap{margin-top:-36px}
    .sp-av{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;color:white;border:4px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.15)}
    .sp-name{font-size:22px;font-weight:800;color:var(--ink);margin-top:10px;letter-spacing:-0.3px}
    .sp-username{font-size:14px;color:var(--muted);margin-bottom:10px}
    .sp-badges{display:flex;gap:8px;flex-wrap:wrap}
    .sp-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 12px;border-radius:999px}

    /* Stats */
    .sp-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
    .sp-stat{background:white;border:1px solid var(--hairline);border-radius:16px;padding:20px;text-align:center}
    .sp-stat-num{font-size:28px;font-weight:800;color:var(--ink);letter-spacing:-0.5px}
    .sp-stat-lbl{font-size:12px;color:var(--muted);margin-top:4px}

    /* Products */
    .sp-grid-title{font-size:18px;font-weight:700;color:var(--ink);margin-bottom:16px}
    .sp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}

    /* Product Card (simplified) */
    .spc{background:white;border:1px solid var(--hairline);border-radius:16px;overflow:hidden;transition:transform .2s,box-shadow .2s}
    .spc:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.1)}
    .spc-img-wrap{position:relative;aspect-ratio:4/3;overflow:hidden;background:var(--surface)}
    .spc-img{width:100%;height:100%;object-fit:cover}
    .spc-cond{position:absolute;top:8px;left:8px;font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px}
    .cond-like-new{background:#f0fdf4;color:#16a34a}
    .cond-good    {background:#eff6ff;color:#2563eb}
    .cond-fair    {background:#fffbeb;color:#d97706}
    .cond-used    {background:#f8fafc;color:#64748b}
    .spc-body{padding:14px}
    .spc-title{font-size:14px;font-weight:600;color:var(--ink);margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .spc-price{font-size:15px;font-weight:700;color:var(--ink)}
    .spc-loc{font-size:12px;color:var(--muted);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .btn-chat-lg{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:var(--primary);color:white;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;transition:background .2s,transform .15s}
    .btn-chat-lg:hover{background:var(--primary-dark);transform:translateY(-1px)}
    @media(max-width:640px){.sp-stats{grid-template-columns:1fr 1fr}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="sp-wrap">
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
          <?php if ($seller['is_verified']): ?>
            <span class="sp-badge" style="background:#eff6ff;color:#2563eb">✓ Verified Student</span>
          <?php endif; ?>
          <?php if ($seller['is_trusted']): ?>
            <span class="sp-badge" style="background:#fdf4ff;color:#7c3aed">🏅 Trusted Seller</span>
          <?php endif; ?>
          <span class="sp-badge" style="background:var(--surface);color:var(--body)">📅 Bergabung <?= $joinedDate ?></span>
        </div>
      </div>
      <?php if (isLoggedIn() && $_SESSION['user_id'] !== $sid): ?>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:center">
          <a href="mailto:<?= e($seller['email'] ?? '') ?>" class="btn-chat-lg">💬 Hubungi Penjual</a>
          <a href="/report.php?type=user&id=<?= (int)$sid ?>" style="font-size:12px;color:var(--muted);text-decoration:none;transition:color .2s" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--muted)'">🚩 Laporkan penjual</a>
        </div>
      <?php elseif (!isLoggedIn()): ?>
        <a href="/auth/login.php" class="btn-chat-lg">🔑 Login untuk Chat</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="sp-stats">
    <div class="sp-stat">
      <div class="sp-stat-num"><?= $totalCount ?></div>
      <div class="sp-stat-lbl">📦 Barang Dijual</div>
    </div>
    <div class="sp-stat">
      <div class="sp-stat-num"><?= $soldCount ?></div>
      <div class="sp-stat-lbl">✅ Terjual</div>
    </div>
    <div class="sp-stat">
      <div class="sp-stat-num"><?= $seller['is_verified'] ? '✓' : '–' ?></div>
      <div class="sp-stat-lbl">Verifikasi KTM</div>
    </div>
  </div>

  <!-- Listings -->
  <div class="sp-grid-title">📦 Barang Dijual (<?= $totalCount ?>)</div>

  <?php if (empty($listings)): ?>
    <div style="text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid var(--hairline)">
      <div style="font-size:48px;margin-bottom:12px">📭</div>
      <div style="font-size:16px;font-weight:600;color:var(--ink);margin-bottom:6px">Belum ada barang dijual</div>
      <div style="font-size:14px;color:var(--muted)">Penjual ini belum memposting barang.</div>
    </div>
  <?php else: ?>
    <div class="sp-grid">
      <?php foreach ($listings as $p):
        $cond = $condLabels[$p['condition']] ?? $condLabels['good'];
        $img  = $p['image'] ? '/'.$p['image'] : '/assets/images/placeholder.png';
      ?>
      <div class="spc">
        <a href="/product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none">
          <div class="spc-img-wrap">
            <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" class="spc-img"
                 onerror="this.src='/assets/images/placeholder.png'" loading="lazy"/>
            <span class="spc-cond <?= $cond['class'] ?>"><?= $cond['label'] ?></span>
          </div>
          <div class="spc-body">
            <div class="spc-title"><?= e($p['title']) ?></div>
            <div class="spc-price"><?= formatRupiah($p['price']) ?><?= $p['is_nego'] ? ' <span style="font-size:11px;color:#d97706;font-weight:500">· Nego</span>' : '' ?></div>
            <?php if ($p['location']): ?><div class="spc-loc">📍 <?= e($p['location']) ?></div><?php endif; ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="/assets/js/main.js" defer></script>
</body>
</html>
