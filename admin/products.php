<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/db.php';

requireAdmin();

$db  = getDB();
$msg = $_SESSION['admin_msg'] ?? null;
$err = $_SESSION['admin_err'] ?? null;
unset($_SESSION['admin_msg'], $_SESSION['admin_err']);

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if (!$productId) { header('Location: ' . BASE_URL . 'admin/products.php'); exit; }

    switch ($action) {
        case 'delete':
            // Hapus gambar jika ada
            $p = $db->prepare('SELECT image FROM products WHERE id=?');
            $p->execute([$productId]);
            $prod = $p->fetch();
            if ($prod && $prod['image']) {
                $images = getProductAllImages($prod['image']);
                foreach ($images as $img) {
                    if (str_contains($img, 'uploads/')) {
                        $file = __DIR__ . '/../' . $img;
                        if (file_exists($file)) @unlink($file);
                    }
                }
            }
            $db->prepare('DELETE FROM products WHERE id=?')->execute([$productId]);
            adminLog('DELETE_PRODUCT', 'products', $productId);
            $_SESSION['admin_msg'] = 'Produk berhasil dihapus.';
            break;

        case 'deactivate':
            $db->prepare('UPDATE products SET status="inactive" WHERE id=?')->execute([$productId]);
            adminLog('DEACTIVATE_PRODUCT', 'products', $productId);
            $_SESSION['admin_msg'] = 'Produk dinonaktifkan.';
            break;

        case 'activate':
            $db->prepare('UPDATE products SET status="active" WHERE id=?')->execute([$productId]);
            adminLog('ACTIVATE_PRODUCT', 'products', $productId);
            $_SESSION['admin_msg'] = 'Produk diaktifkan kembali.';
            break;

        case 'mark_sold':
            $db->prepare('UPDATE products SET status="sold" WHERE id=?')->execute([$productId]);
            adminLog('MARK_SOLD', 'products', $productId);
            $_SESSION['admin_msg'] = 'Produk ditandai terjual.';
            break;
    }
    header('Location: ' . BASE_URL . 'admin/products.php'); exit;
}

// ── Query products ───────────────────────────────────────────
$search     = trim($_GET['q']      ?? '');
$filterStat = $_GET['status']      ?? '';
$filterCat  = (int)($_GET['cat']   ?? 0);

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(p.title LIKE ? OR u.username LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterStat) {
    $where[]  = 'p.status = ?';
    $params[] = $filterStat;
}
if ($filterCat) {
    $where[]  = 'p.category_id = ?';
    $params[] = $filterCat;
}

$sql = '
    SELECT p.*, u.username AS seller_username, c.name AS cat_name
    FROM products p
    JOIN users u ON p.seller_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY p.created_at DESC
';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $db->query('SELECT * FROM categories ORDER BY order_index ASC')->fetchAll();

$pageTitle = 'Kelola Produk';
require_once __DIR__ . '/layout_header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title"><i class="fas fa-box"></i> Produk (<?= count($products) ?>)</span>
    <form method="GET" class="table-toolbar">
      <div class="search-box">
        <i class="fas fa-search" style="color:var(--muted)"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul / seller…"/>
      </div>
      <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="active"   <?= $filterStat==='active'   ? 'selected':'' ?>><i class="fas fa-check"></i> Aktif</option>
        <option value="sold"     <?= $filterStat==='sold'     ? 'selected':'' ?>><i class="fas fa-tag"></i> Terjual</option>
        <option value="inactive" <?= $filterStat==='inactive' ? 'selected':'' ?>><i class="fas fa-pause"></i> Nonaktif</option>
      </select>
      <select name="cat" class="filter-select" onchange="this.form.submit()">
        <option value="0">Semua Kategori</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filterCat==$c['id'] ? 'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary-sm">Filter</button>
    </form>
  </div>

  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead>
      <tr><th>Produk</th><th>Seller</th><th>Kategori</th><th>Harga</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <?php if ($p['image']): ?>
              <img src="<?= BASE_URL . htmlspecialchars(getProductImage($p['image'])) ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0" alt=""/>
            <?php else: ?>
              <div style="width:40px;height:40px;border-radius:8px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0"><i class="fas fa-box"></i></div>
            <?php endif; ?>
            <div style="max-width:200px">
              <div style="font-size:14px;font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['title']) ?></div>
              <div style="font-size:12px;color:var(--muted)"><?= $p['condition'] ?> · <?= $p['is_nego'] ? 'Nego' : 'Fix' ?></div>
            </div>
          </div>
        </td>
        <td><span style="font-size:13px">@<?= htmlspecialchars($p['seller_username']) ?></span></td>
        <td><span style="font-size:13px">📦 <?= htmlspecialchars($p['cat_name']) ?></span></td>
        <td style="font-size:13px;font-weight:600">Rp <?= number_format($p['price'],0,',','.') ?></td>
        <td>
          <span class="badge badge-<?= $p['status'] ?>">
            <?php $sl=['active'=>'<i class="fas fa-check"></i> Aktif','sold'=>'<i class="fas fa-tag"></i> Terjual','inactive'=>'<i class="fas fa-pause"></i> Nonaktif']; echo $sl[$p['status']] ?? $p['status']; ?>
          </span>
        </td>
        <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:5px;flex-wrap:wrap">
            <?php if ($p['status'] === 'active'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="deactivate"/>
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
                <button type="submit" class="btn-action btn-ban"><i class="fas fa-pause"></i> Nonaktif</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="mark_sold"/>
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
                <button type="submit" class="btn-action btn-verify"><i class="fas fa-tag"></i> Sold</button>
              </form>
            <?php elseif ($p['status'] === 'inactive'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="activate"/>
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
                <button type="submit" class="btn-action btn-unban"><i class="fas fa-check"></i> Aktifkan</button>
              </form>
            <?php endif; ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus produk ini permanen?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
              <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i> Hapus</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($products)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">Tidak ada produk ditemukan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
