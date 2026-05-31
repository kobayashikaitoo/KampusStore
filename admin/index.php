<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../config/db.php';

requireAdmin();

$pageTitle = 'Dashboard';
$stats     = getDashboardStats();
$db        = getDB();

// Recent users
$recentUsers = $db->query('SELECT id, username, name, role, is_banned, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();

// Recent products
$recentProducts = $db->query('
    SELECT p.id, p.title, p.price, p.status, p.created_at, u.username
    FROM products p JOIN users u ON p.seller_id = u.id
    ORDER BY p.created_at DESC LIMIT 5
')->fetchAll();

$msg = $_SESSION['admin_msg'] ?? null;
unset($_SESSION['admin_msg']);

require_once __DIR__ . '/layout_header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-card-accent" style="background:#2563eb"></div>
    <div class="stat-card-icon"><i class="fas fa-users"></i></div>
    <div class="stat-card-num"><?= $stats['total_users'] ?></div>
    <div class="stat-card-label">Total Pengguna</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-accent" style="background:#ef4444"></div>
    <div class="stat-card-icon"><i class="fas fa-ban"></i></div>
    <div class="stat-card-num"><?= $stats['banned_users'] ?></div>
    <div class="stat-card-label">Akun Dibanned</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-accent" style="background:#22c55e"></div>
    <div class="stat-card-icon"><i class="fas fa-box"></i></div>
    <div class="stat-card-num"><?= $stats['active_products'] ?></div>
    <div class="stat-card-label">Produk Aktif</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-accent" style="background:#f59e0b"></div>
    <div class="stat-card-icon"><i class="fas fa-check"></i></div>
    <div class="stat-card-num"><?= $stats['sold_products'] ?></div>
    <div class="stat-card-label">Produk Terjual</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-accent" style="background:#7c3aed"></div>
    <div class="stat-card-icon"><i class="fas fa-heart"></i></div>
    <div class="stat-card-num"><?= $stats['total_wishlists'] ?></div>
    <div class="stat-card-label">Total Wishlist</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-accent" style="background:#0891b2"></div>
    <div class="stat-card-icon"><i class="fas fa-pen"></i></div>
    <div class="stat-card-num"><?= $stats['total_products'] ?></div>
    <div class="stat-card-label">Total Produk</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- Recent Users -->
  <div class="admin-card">
    <div class="admin-card-header">
      <span class="admin-card-title">👥 Pengguna Terbaru</span>
      <a href="users.php" class="btn-primary-sm">Lihat semua →</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Pengguna</th><th>Role</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td>
            <div class="user-row">
              <div class="user-av"><?= strtoupper(substr($u['name'],0,1)) ?></div>
              <div>
                <div class="user-row-name"><?= htmlspecialchars($u['name']) ?></div>
                <div class="user-row-sub">@<?= htmlspecialchars($u['username']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge badge-<?= $u['role'] ?>">
              <?= $u['role'] === 'admin' ? '<i class="fas fa-bolt"></i> Admin' : ($u['role'] === 'moderator' ? '<i class="fas fa-shield"></i> Mod' : '<i class="fas fa-user"></i> User') ?>
            </span>
          </td>
          <td>
            <span class="badge <?= $u['is_banned'] ? 'badge-banned' : 'badge-active' ?>">
              <?= $u['is_banned'] ? '<i class="fas fa-ban"></i> Banned' : '<i class="fas fa-check"></i> Aktif' ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentUsers)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px">Belum ada pengguna</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Recent Products -->
  <div class="admin-card">
    <div class="admin-card-header">
      <span class="admin-card-title"><i class="fas fa-box"></i> Produk Terbaru</span>
      <a href="products.php" class="btn-primary-sm">Lihat semua →</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Produk</th><th>Harga</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recentProducts as $p): ?>
        <tr>
          <td>
            <div style="font-size:14px;font-weight:500;color:var(--ink);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['title']) ?></div>
            <div style="font-size:12px;color:var(--muted)">@<?= htmlspecialchars($p['username']) ?></div>
          </td>
          <td style="font-size:13px;font-weight:600">Rp <?= number_format($p['price'],0,',','.') ?></td>
          <td>
            <span class="badge badge-<?= $p['status'] ?>">
              <?php
                $labels = ['active'=>'<i class="fas fa-check"></i> Aktif','sold'=>'<i class="fas fa-tag"></i> Terjual','inactive'=>'<i class="fas fa-pause"></i> Nonaktif'];
                echo $labels[$p['status']] ?? $p['status'];
              ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentProducts)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px">Belum ada produk</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
