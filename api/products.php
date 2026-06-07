<?php
/**
 * KampusStore — AJAX Products API
 * Returns JSON { html, pagination, title, total, page, totalPages }
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/auth.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// ── Params ─────────────────────────────────────────────────
$catSlug = trim($_GET['cat']  ?? 'semua');
$sortBy  = trim($_GET['sort'] ?? 'terbaru');
$searchQ = trim($_GET['q']    ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset  = ($page - 1) * $perPage;

// ── WHERE ──────────────────────────────────────────────────
$where  = ["p.status = 'active'"];
$params = [];

if ($catSlug && $catSlug !== 'semua') {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if ($searchQ) {
    $where[]  = 'p.title LIKE ?';
    $params[] = "%$searchQ%";
}
$whereSQL = implode(' AND ', $where);

// ── Sort ───────────────────────────────────────────────────
$orderMap = [
    'terbaru'    => 'p.created_at DESC',
    'termurah'   => 'p.price ASC',
    'termahal'   => 'p.price DESC',
    'terpopuler' => 'p.views DESC',
];
$orderSQL = $orderMap[$sortBy] ?? 'p.created_at DESC';

// ── Count ──────────────────────────────────────────────────
$countStmt = $db->prepare("
    SELECT COUNT(*) FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u      ON p.seller_id   = u.id
    WHERE $whereSQL
");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalProducts / $perPage);
$page          = min($page, max(1, $totalPages));

// ── Products ───────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT
        p.id, p.title, p.price, p.is_nego, p.`condition`,
        p.image, p.created_at, p.views, p.location,
        c.slug  AS cat_slug,
        c.name  AS cat_name,
        u.id    AS seller_id,
        u.name  AS seller_name,
        u.username AS seller_username,
        u.profile_photo AS seller_photo,
        u.is_verified AS seller_verified,
        u.is_trusted  AS seller_trusted
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u      ON p.seller_id   = u.id
    WHERE $whereSQL
    ORDER BY $orderSQL
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// ── Wishlist ───────────────────────────────────────────────
$wishlistSet = [];
if (isLoggedIn()) {
    $wStmt = $db->prepare("SELECT product_id FROM wishlists WHERE user_id = ?");
    $wStmt->execute([$_SESSION['user_id']]);
    $wishlistSet = array_flip($wStmt->fetchAll(PDO::FETCH_COLUMN));
}

// ── Helpers ────────────────────────────────────────────────
$condLabels = [
    'like_new' => ['label' => 'Seperti Baru', 'class' => 'cond-like-new'],
    'good'     => ['label' => 'Kondisi Baik',  'class' => 'cond-good'],
    'fair'     => ['label' => 'Cukup Baik',    'class' => 'cond-fair'],
    'used'     => ['label' => 'Bekas',          'class' => 'cond-used'],
];
$animDelays = ['d1','d2','d3','d4','d5','d6','d7','d8'];

// ── Build HTML ─────────────────────────────────────────────
ob_start();

if (empty($products)) { ?>
  <div style="text-align:center;padding:80px 20px">
    <div style="font-size:64px;margin-bottom:16px"><i class="fas fa-inbox"></i></div>
    <h3 style="font-size:20px;font-weight:700;color:var(--ink);margin-bottom:8px">Belum ada barang</h3>
    <p style="font-size:14px;color:var(--body);margin-bottom:24px">
      <?= $searchQ ? 'Coba kata kunci lain atau hapus filter.' : 'Jadilah yang pertama jual di kategori ini!' ?>
    </p>
    <a href="<?= isLoggedIn() ? BASE_URL . 'sell.php' : BASE_URL . 'auth/login.php' ?>"
       class="hero-btn-primary" style="display:inline-flex">
      <i class="fas fa-plus"></i> Jual Sekarang
    </a>
  </div>
<?php } else { ?>
  <div class="product-grid" id="product-grid">
    <?php foreach ($products as $i => $p):
        $delay    = $animDelays[$i % 8];
        $cond     = $condLabels[$p['condition']] ?? $condLabels['good'];
        $initials = strtoupper(mb_substr($p['seller_name'], 0, 1));
        $isSaved  = isset($wishlistSet[$p['id']]);
        $imgSrc   = BASE_URL . htmlspecialchars(getProductImage($p['image']));
    ?>
    <article class="product-card anim-fiu <?= $delay ?>"
             data-cat="<?= htmlspecialchars($p['cat_slug']) ?>"
             data-id="<?= (int)$p['id'] ?>">
      <a href="<?= BASE_URL ?>product.php?id=<?= (int)$p['id'] ?>" class="card-img-wrap" style="display:block;text-decoration:none">
        <img src="<?= $imgSrc ?>"
             alt="<?= htmlspecialchars($p['title']) ?>"
             class="card-img" loading="lazy"
             onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'"/>
        <span class="cond-badge <?= $cond['class'] ?>"><?= $cond['label'] ?></span>
      </a>
      <button class="wishlist-btn <?= $isSaved ? 'saved' : '' ?>"
              data-id="<?= (int)$p['id'] ?>"
              aria-label="Simpan ke wishlist"
              onclick="toggleWishlist(this)">
        <?= $isSaved ? '<i class="fas fa-heart" style="color:#dc2626"></i>' : '<i class="fas fa-heart"></i>' ?>
      </button>
      <div class="card-body">
        <div class="card-seller">
          <a href="<?= BASE_URL ?>seller.php?id=<?= (int)$p['seller_id'] ?>"
             style="display:flex;align-items:center;gap:6px;text-decoration:none;flex:1;min-width:0">
            <div class="seller-av" style="overflow:hidden;padding:0;">
              <?php if (!empty($p['seller_photo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($p['seller_photo']) ?>"
                     alt="<?= htmlspecialchars($p['seller_name']) ?>"
                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
                <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;"><?= $initials ?></span>
              <?php else: ?>
                <?= $initials ?>
              <?php endif; ?>
            </div>
            <span class="seller-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($p['seller_name']) ?>
            </span>
          </a>
        </div>
        <a href="<?= BASE_URL ?>product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none">
          <h3 class="card-title"><?= htmlspecialchars($p['title']) ?></h3>
        </a>
        <?php if ($p['location']): ?>
        <span class="card-location">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <?= htmlspecialchars($p['location']) ?>
        </span>
        <?php endif; ?>
        <div class="card-price-row">
          <span class="card-price"><?= formatRupiah($p['price']) ?></span>
          <?php if ($p['is_nego']): ?><span class="card-nego">Nego</span><?php endif; ?>
        </div>
        <div class="card-actions">
          <a href="<?= BASE_URL ?>product.php?id=<?= (int)$p['id'] ?>" class="btn-add"
             style="text-decoration:none;justify-content:center">
            <i class="fas fa-eye"></i> Lihat Detail
          </a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
<?php } ?>

<?php $html = ob_get_clean();

// ── Pagination HTML ────────────────────────────────────────
ob_start();
if ($totalPages > 1): ?>
<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:40px">
  <?php if ($page > 1): ?>
    <button class="pg-btn" data-page="<?= $page-1 ?>"
            style="display:flex;align-items:center;gap:4px;padding:8px 16px;border:1.5px solid var(--hairline);border-radius:10px;background:white;font-size:14px;font-weight:500;color:var(--ink);cursor:pointer;transition:all .2s">
      ← Prev
    </button>
  <?php endif; ?>
  <?php for ($pg = max(1, $page-2); $pg <= min($totalPages, $page+2); $pg++): ?>
    <button class="pg-btn <?= $pg===$page ? 'pg-active' : '' ?>" data-page="<?= $pg ?>"
            style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;border:1.5px solid <?= $pg===$page ? 'var(--primary)' : 'var(--hairline)' ?>;background:<?= $pg===$page ? 'var(--primary)' : 'white' ?>;color:<?= $pg===$page ? 'white' : 'var(--ink)' ?>;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s">
      <?= $pg ?>
    </button>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
    <button class="pg-btn" data-page="<?= $page+1 ?>"
            style="display:flex;align-items:center;gap:4px;padding:8px 16px;border:1.5px solid var(--hairline);border-radius:10px;background:white;font-size:14px;font-weight:500;color:var(--ink);cursor:pointer;transition:all .2s">
      Next →
    </button>
  <?php endif; ?>
</div>
<p style="text-align:center;font-size:13px;color:var(--muted);margin-top:10px">
  Menampilkan <?= count($products) ?> dari <?= $totalProducts ?> barang
</p>
<?php endif;
$pagination = ob_get_clean();

// ── Title ──────────────────────────────────────────────────
if ($searchQ) {
    $title = 'Hasil untuk "' . htmlspecialchars($searchQ) . '" <span style="font-size:14px;font-weight:400;color:var(--muted);margin-left:8px">(' . $totalProducts . ' barang)</span>';
} elseif ($catSlug && $catSlug !== 'semua') {
    $title = 'Kategori: ' . htmlspecialchars(ucfirst(str_replace('-', ' ', $catSlug)));
} else {
    $title = 'Barang Terbaru <i class="fas fa-fire" style="color:#ef4444"></i>';
}

echo json_encode([
    'html'       => $html,
    'pagination' => $pagination,
    'title'      => $title,
    'total'      => $totalProducts,
    'page'       => $page,
    'totalPages' => $totalPages,
    'cat'        => $catSlug,
    'sort'       => $sortBy,
    'q'          => $searchQ,
]);
