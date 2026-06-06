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
    $where[]  = 'p.title LIKE ?';
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
        p.id, p.title, p.price, p.is_nego, p.`condition`,
        p.image, p.created_at, p.views, p.location,
        c.slug  AS cat_slug,
        u.id    AS seller_id,
        u.username AS seller_username,
        u.name  AS seller_name,
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
          Barang Terbaru <i class="fas fa-fire" style="color:#ef4444"></i>
        <?php endif; ?>
      </h2>
      <div style="display:flex;align-items:center;gap:8px">
        <!-- Sort -->
        <select id="sort-select" class="filter-pill" style="cursor:pointer;padding:8px 14px"
          onchange="applySort(this.value)">
          <option value="terbaru"    <?= $sortBy==='terbaru'    ? 'selected':'' ?>><i class="fas fa-star"></i> Terbaru</option>
          <option value="termurah"   <?= $sortBy==='termurah'   ? 'selected':'' ?>><i class="fas fa-coins"></i> Termurah</option>
          <option value="termahal"   <?= $sortBy==='termahal'   ? 'selected':'' ?>><i class="fas fa-gem"></i> Termahal</option>
          <option value="terpopuler" <?= $sortBy==='terpopuler' ? 'selected':'' ?>><i class="fas fa-fire" style="color:#ef4444"></i> Terpopuler</option>
        </select>
        <a href="index.php" class="view-all" style="<?= ($catSlug==='semua'&&!$searchQ) ? 'display:none' : '' ?>">
          Reset ✕
        </a>
      </div>
    </div>

    <?php if (empty($products)): ?>
      <!-- Empty State -->
      <div style="text-align:center;padding:80px 20px">
        <div style="font-size:64px;margin-bottom:16px"><i class="fas fa-inbox"></i></div>
        <h3 style="font-size:20px;font-weight:700;color:var(--ink);margin-bottom:8px">Belum ada barang</h3>
        <p style="font-size:14px;color:var(--body);margin-bottom:24px">
          <?= $searchQ ? 'Coba kata kunci lain atau hapus filter.' : 'Jadilah yang pertama jual di kategori ini!' ?>
        </p>
        <a href="<?= isLoggedIn() ? BASE_URL . 'sell.php' : BASE_URL . 'auth/login.php' ?>" class="hero-btn-primary" style="display:inline-flex">
          <i class="fas fa-plus"></i> Jual Sekarang
        </a>
      </div>

    <?php else: ?>
      <div class="product-grid" id="product-grid">
        <?php foreach ($products as $i => $p):
          $delay    = $animDelays[$i % 8];
          $cond     = $condLabels[$p['condition']] ?? $condLabels['good'];
          $initials = strtoupper(mb_substr($p['seller_name'], 0, 1));
          $isSaved  = isset($wishlistSet[$p['id']]);
          $imgSrc   = BASE_URL . htmlspecialchars(getProductImage($p['image']));
        ?>
        <article
          class="product-card anim-fiu <?= $delay ?>"
          data-cat="<?= htmlspecialchars($p['cat_slug']) ?>"
          data-id="<?= (int)$p['id'] ?>"
        >
          <!-- Image -->
          <a href="product.php?id=<?= (int)$p['id'] ?>" class="card-img-wrap" style="display:block;text-decoration:none">
            <img
              src="<?= $imgSrc ?>"
              alt="<?= htmlspecialchars($p['title']) ?>"
              class="card-img"
              loading="lazy"
              onerror="this.src='<?= BASE_URL ?>assets/images/placeholder.png'"
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
          ><?= $isSaved ? '<i class="fas fa-heart" style="color:#dc2626"></i>' : '<i class="fas fa-heart"></i>' ?></button>

          <!-- Card Body -->
          <div class="card-body">
            <!-- Seller -->
            <div class="card-seller">
              <a href="seller.php?id=<?= (int)$p['seller_id'] ?>" style="display:flex;align-items:center;gap:6px;text-decoration:none;flex:1;min-width:0">
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
                <span class="seller-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['seller_name']) ?></span>
              </a>
            </div>

            <!-- Title -->
            <a href="product.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none">
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
              <a href="product.php?id=<?= (int)$p['id'] ?>" class="btn-add" style="text-decoration:none;justify-content:center">
                <i class="fas fa-eye"></i> Lihat Detail
              </a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pg-wrap">
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:40px">
          <?php if ($page > 1): ?>
            <button class="pg-btn" data-page="<?= $page-1 ?>"
                    style="display:flex;align-items:center;gap:4px;padding:8px 16px;border:1.5px solid var(--hairline);border-radius:10px;background:white;font-size:14px;font-weight:500;color:var(--ink);cursor:pointer;transition:all .2s">← Prev</button>
          <?php endif; ?>
          <?php for ($pg = max(1, $page-2); $pg <= min($totalPages, $page+2); $pg++): ?>
            <button class="pg-btn <?= $pg===$page ? 'pg-active' : '' ?>" data-page="<?= $pg ?>"
                    style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;border:1.5px solid <?= $pg===$page ? 'var(--primary)' : 'var(--hairline)' ?>;background:<?= $pg===$page ? 'var(--primary)' : 'white' ?>;color:<?= $pg===$page ? 'white' : 'var(--ink)' ?>;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s">
              <?= $pg ?>
            </button>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <button class="pg-btn" data-page="<?= $page+1 ?>"
                    style="display:flex;align-items:center;gap:4px;padding:8px 16px;border:1.5px solid var(--hairline);border-radius:10px;background:white;font-size:14px;font-weight:500;color:var(--ink);cursor:pointer;transition:all .2s">Next →</button>
          <?php endif; ?>
        </div>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-top:10px">
          Menampilkan <?= count($products) ?> dari <?= $totalProducts ?> barang
        </p>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<script>
// ── State ──────────────────────────────────────────────────
const _grid = {
  cat:  '<?= htmlspecialchars($catSlug) ?>',
  sort: '<?= htmlspecialchars($sortBy) ?>',
  q:    '<?= htmlspecialchars(addslashes($searchQ)) ?>',
  page: <?= $page ?>,
};

// ── Fetch & render products ─────────────────────────────────
function loadProducts(params = {}, pushState = true) {
  Object.assign(_grid, params);

  const qs = new URLSearchParams({
    cat:  _grid.cat,
    sort: _grid.sort,
    q:    _grid.q,
    page: _grid.page,
  }).toString();

  // Update browser URL without reload
  if (pushState) {
    history.pushState(_grid, '', '?' + qs);
  }

  // Show skeleton
  const section = document.getElementById('products');
  const gridWrap = section.querySelector('.ks-container');
  gridWrap.style.opacity = '0.4';
  gridWrap.style.transition = 'opacity .2s';

  fetch('<?= BASE_URL ?>api/products.php?' + qs)
    .then(r => r.json())
    .then(data => {
      // Update title
      const titleEl = section.querySelector('.section-title');
      if (titleEl) titleEl.innerHTML = data.title;

      // Update reset button visibility
      const resetBtn = section.querySelector('.view-all');
      if (resetBtn) {
        resetBtn.style.display = (_grid.cat === 'semua' && !_grid.q) ? 'none' : '';
      }

      // Replace grid content
      const existingGrid = section.querySelector('#product-grid');
      const emptyState   = section.querySelector('[style*="padding:80px"]');
      const target = existingGrid || emptyState;

      const tmp = document.createElement('div');
      tmp.innerHTML = data.html;
      const newContent = tmp.firstElementChild;

      if (target) {
        target.replaceWith(newContent);
      } else {
        gridWrap.querySelector('.ks-container') ? gridWrap.querySelector('.ks-container').appendChild(newContent) : gridWrap.appendChild(newContent);
      }

      // Replace pagination
      let pgWrap = section.querySelector('.pg-wrap');
      if (!pgWrap) {
        pgWrap = document.createElement('div');
        pgWrap.className = 'pg-wrap';
        gridWrap.appendChild(pgWrap);
      }
      pgWrap.innerHTML = data.pagination;

      // Bind pagination buttons
      section.querySelectorAll('.pg-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          loadProducts({ page: parseInt(btn.dataset.page) });
          section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      });

      // Update active category tab
      document.querySelectorAll('.cat-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.cat === _grid.cat);
      });

      gridWrap.style.opacity = '1';
    })
    .catch(() => { gridWrap.style.opacity = '1'; });
}

// ── Category tabs ───────────────────────────────────────────
document.querySelectorAll('.cat-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    if (this.dataset.cat === _grid.cat) return;
    loadProducts({ cat: this.dataset.cat, page: 1 });
  });
});

// ── Reset filter button ──────────────────────────────────────
document.querySelector('.view-all')?.addEventListener('click', function(e) {
  e.preventDefault();
  const searchInput = document.getElementById('nav-search-input');
  if (searchInput) searchInput.value = '';
  loadProducts({ cat: 'semua', q: '', page: 1 });
});

// ── Sort select ─────────────────────────────────────────────
function applySort(val) {
  loadProducts({ sort: val, page: 1 });
}

// ── Pagination (initial server-rendered links → handled by pg-wrap after first fetch) ──
document.querySelectorAll('.pg-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    loadProducts({ page: parseInt(btn.dataset.page) });
    document.getElementById('products').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

// ── Browser back/forward ────────────────────────────────────
window.addEventListener('popstate', (e) => {
  if (e.state) {
    Object.assign(_grid, e.state);
    document.getElementById('sort-select').value = _grid.sort;
    loadProducts({}, false);
  }
});
</script>
