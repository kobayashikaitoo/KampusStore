<?php
/**
 * product_grid.php — Query produk dari DB dengan filter kategori & sort
 * Dipanggil dari index.php yang sudah ada $db dari getDB()
 */

if (!isset($db)) {
    require_once __DIR__ . '/../config/db.php';
    $db = getDB();
}

// ── Filter & Sort dari query string ────────────────────────────
$catSlug    = $_GET['cat']    ?? 'semua';
$sortBy     = $_GET['sort']   ?? 'terbaru';
$searchQ    = trim($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;
$offset     = ($page - 1) * $perPage;

// ── Build WHERE ────────────────────────────────────────────────
$where  = ["p.status = 'active'"];
$params = [];

if ($catSlug && $catSlug !== 'semua') {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if ($searchQ) {
    $where[]  = '(p.title LIKE ? OR p.location LIKE ?)';
    $params[] = "%$searchQ%";
    $params[] = "%$searchQ%";
}

$whereSQL = implode(' AND ', $where);

// ── Sort ───────────────────────────────────────────────────────
$orderMap = [
    'terbaru'   => 'p.created_at DESC',
    'termurah'  => 'p.price ASC',
    'termahal'  => 'p.price DESC',
    'terpopuler'=> 'p.views DESC',
];
$orderSQL = $orderMap[$sortBy] ?? 'p.created_at DESC';

// ── Count total (untuk pagination) ────────────────────────────
$countStmt = $db->prepare("
    SELECT COUNT(*) FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u      ON p.seller_id   = u.id
    WHERE $whereSQL
");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalProducts / $perPage);

// ── Main query ─────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT
        p.id, p.title, p.price, p.is_nego, p.condition,
        p.location, p.image, p.created_at, p.views,
        c.slug  AS cat_slug,
        u.id    AS seller_id,
        u.username AS seller_username,
        u.name  AS seller_name,
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

// ── Wishlist status for current user ──────────────────────────
$wishlistSet = [];
if (isLoggedIn()) {
    $wStmt = $db->prepare("
        SELECT product_id FROM wishlists WHERE user_id = ?
    ");
    $wStmt->execute([$_SESSION['user_id']]);
    $wishlistSet = array_flip($wStmt->fetchAll(PDO::FETCH_COLUMN));
}

// ── Condition label map ────────────────────────────────────────
$condLabels = [
    'like_new' => ['label' => 'Seperti Baru', 'class' => 'cond-like-new'],
    'good'     => ['label' => 'Kondisi Baik',  'class' => 'cond-good'],
    'fair'     => ['label' => 'Cukup Baik',    'class' => 'cond-fair'],
    'used'     => ['label' => 'Bekas',         'class' => 'cond-used'],
];

$animDelays = ['d1','d2','d3','d4','d5','d6','d7','d8'];
?>

<section class="ks-section" id="products">
  <div class="ks-container">
    <div class="section-head">
      <h2 class="section-title">
        <?php if ($searchQ): ?>
          🔍 Hasil untuk "<?= htmlspecialchars($searchQ) ?>"
          <span style="font-size:14px;font-weight:400;color:var(--muted);margin-left:8px">(<?= $totalProducts ?> barang)</span>
        <?php elseif ($catSlug && $catSlug !== 'semua'): ?>
          Kategori: <?= htmlspecialchars(ucfirst($catSlug)) ?>
        <?php else: ?>
          Barang Terbaru 🔥
        <?php endif; ?>
      </h2>
      <div style="display:flex;align-items:center;gap:8px">
        <!-- Sort -->
        <select id="sort-select" class="filter-pill" style="cursor:pointer;padding:8px 14px"
          onchange="applySort(this.value)">
          <option value="terbaru"    <?= $sortBy==='terbaru'    ? 'selected':'' ?>>✨ Terbaru</option>
          <option value="termurah"   <?= $sortBy==='termurah'   ? 'selected':'' ?>>💰 Termurah</option>
          <option value="termahal"   <?= $sortBy==='termahal'   ? 'selected':'' ?>>💎 Termahal</option>
          <option value="terpopuler" <?= $sortBy==='terpopuler' ? 'selected':'' ?>>🔥 Terpopuler</option>
        </select>
        <a href="/index.php" class="view-all" style="<?= ($catSlug==='semua'&&!$searchQ) ? 'display:none' : '' ?>">
          Reset ✕
        </a>
      </div>
    </div>

    <?php if (empty($products)): ?>
      <!-- Empty State -->
      <div style="text-align:center;padding:80px 20px">
        <div style="font-size:64px;margin-bottom:16px">📭</div>
        <h3 style="font-size:20px;font-weight:700;color:var(--ink);margin-bottom:8px">Belum ada barang</h3>
        <p style="font-size:14px;color:var(--body);margin-bottom:24px">
          <?= $searchQ ? 'Coba kata kunci lain atau hapus filter.' : 'Jadilah yang pertama jual di kategori ini!' ?>
        </p>
        <a href="<?= isLoggedIn() ? '/sell.php' : '/auth/login.php' ?>" class="hero-btn-primary" style="display:inline-flex">
          ➕ Jual Sekarang
        </a>
      </div>

    <?php else: ?>
      <div class="product-grid" id="product-grid">
        <?php foreach ($products as $i => $p):
          $delay    = $animDelays[$i % 8];
          $cond     = $condLabels[$p['condition']] ?? $condLabels['good'];
          $initials = strtoupper(mb_substr($p['seller_name'], 0, 1));
          $isSaved  = isset($wishlistSet[$p['id']]);
          $imgSrc   = $p['image'] ? '/' . htmlspecialchars($p['image']) : '/assets/images/placeholder.png';
        ?>
        <article
          class="product-card anim-fiu <?= $delay ?>"
          data-cat="<?= htmlspecialchars($p['cat_slug']) ?>"
          data-id="<?= (int)$p['id'] ?>"
        >
          <!-- Image -->
          <a href="/product.php?id=<?= (int)$p['id'] ?>" class="card-img-wrap" style="display:block;text-decoration:none">
            <img
              src="<?= $imgSrc ?>"
              alt="<?= htmlspecialchars($p['title']) ?>"
              class="card-img"
              loading="lazy"
              onerror="this.src='/assets/images/placeholder.png'"
            />
            <!-- Condition Badge -->
            <span class="cond-badge <?= $cond['class'] ?>">
              <?= $cond['label'] ?>
            </span>
          </a>

          <!-- Wishlist Button -->
          <button
            class="wishlist-btn <?= $isSaved ? 'saved' : '' ?>"
            data-id="<?= (int)$p['id'] ?>"
            aria-label="Simpan ke wishlist"
            onclick="toggleWishlist(this)"
          ><?= $isSaved ? '♥' : '♡' ?></button>

          <!-- Card Body -->
          <div class="card-body">
            <!-- Seller -->
            <div class="card-seller">
              <a href="/seller.php?id=<?= (int)$p['seller_id'] ?>" style="display:flex;align-items:center;gap:6px;text-decoration:none;flex:1;min-width:0">
                <div class="seller-av"><?= $initials ?></div>
                <span class="seller-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['seller_name']) ?></span>
              </a>
              <?php if ($p['seller_verified']): ?>
                <span class="seller-badge-v" title="Verified Student">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="var(--primary)"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  Verified
                </span>
              <?php endif; ?>
            </div>

            <!-- Title -->
            <a href="/product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none">
              <h3 class="card-title"><?= htmlspecialchars($p['title']) ?></h3>
            </a>

            <!-- Location -->
            <?php if ($p['location']): ?>
            <span class="card-location">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= htmlspecialchars($p['location']) ?>
            </span>
            <?php endif; ?>

            <!-- Price -->
            <div class="card-price-row">
              <span class="card-price"><?= formatRupiah($p['price']) ?></span>
              <?php if ($p['is_nego']): ?>
                <span class="card-nego">Nego</span>
              <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="card-actions">
              <a href="/product.php?id=<?= (int)$p['id'] ?>" class="btn-add" style="text-decoration:none;justify-content:center">
                👁️ Lihat Detail
              </a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:40px">
        <?php
          $base = '?cat=' . urlencode($catSlug) . '&sort=' . urlencode($sortBy) . ($searchQ ? '&q='.urlencode($searchQ) : '');
        ?>
        <?php if ($page > 1): ?>
          <a href="<?= $base ?>&page=<?= $page-1 ?>" style="display:flex;align-items:center;gap:4px;padding:8px 16px;border:1.5px solid var(--hairline);border-radius:10px;text-decoration:none;font-size:14px;font-weight:500;color:var(--ink);transition:all .2s" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--hairline)'">← Prev</a>
        <?php endif; ?>

        <?php for ($pg = max(1, $page-2); $pg <= min($totalPages, $page+2); $pg++): ?>
          <a href="<?= $base ?>&page=<?= $pg ?>" style="
            display:flex;align-items:center;justify-content:center;
            width:38px;height:38px;border-radius:10px;
            border:1.5px solid <?= $pg===$page ? 'var(--primary)' : 'var(--hairline)' ?>;
            background:<?= $pg===$page ? 'var(--primary)' : 'white' ?>;
            color:<?= $pg===$page ? 'white' : 'var(--ink)' ?>;
            font-size:14px;font-weight:600;text-decoration:none;
            transition:all .2s;
          "><?= $pg ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= $base ?>&page=<?= $page+1 ?>" style="display:flex;align-items:center;gap:4px;padding:8px 16px;border:1.5px solid var(--hairline);border-radius:10px;text-decoration:none;font-size:14px;font-weight:500;color:var(--ink);transition:all .2s" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--hairline)'">Next →</a>
        <?php endif; ?>
      </div>
      <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:10px">
        Menampilkan <?= count($products) ?> dari <?= $totalProducts ?> barang
      </p>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<script>
// Sort redirect
function applySort(val) {
  const url = new URL(window.location.href);
  url.searchParams.set('sort', val);
  url.searchParams.delete('page');
  window.location.href = url.toString();
}

// Category tabs → server-side filter
document.querySelectorAll('.cat-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    const url = new URL(window.location.href);
    url.searchParams.set('cat', this.dataset.cat);
    url.searchParams.delete('page');
    window.location.href = url.toString();
  });
});

// Mark active tab based on current URL
(function() {
  const params = new URLSearchParams(window.location.search);
  const cat = params.get('cat') || 'semua';
  document.querySelectorAll('.cat-tab').forEach(t => {
    t.classList.toggle('active', t.dataset.cat === cat);
  });
})();
</script>
