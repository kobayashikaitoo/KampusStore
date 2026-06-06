<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../config/db.php';

requireAdmin();

$db   = getDB();
$logs = $db->query('
    SELECT l.*, u.username AS admin_username
    FROM admin_logs l
    JOIN users u ON l.admin_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 100
')->fetchAll();

$pageTitle = 'Activity Log';
require_once __DIR__ . '/layout_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title"><i class="fas fa-history"></i> Activity Log (<?= count($logs) ?> terbaru)</span>
  </div>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr><th>Admin</th><th>Aksi</th><th>Target</th><th>Target ID</th><th>Waktu</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <?php
        $actionColors = [
          'BAN_USER'         => '#fef2f2','UNBAN_USER'        => '#f0fdf4',
          'DELETE_USER'      => '#fef2f2','VERIFY_USER'       => '#eff6ff',
          'DELETE_PRODUCT'   => '#fef2f2','DEACTIVATE_PRODUCT'=> '#fffbeb',
          'ACTIVATE_PRODUCT' => '#f0fdf4','MARK_SOLD'         => '#f0fdf4',
          'MAKE_MOD'         => '#fdf4ff','REVOKE_MOD'        => '#fffbeb',
        ];
        $bg = $actionColors[$log['action']] ?? 'transparent';
      ?>
      <tr style="background:<?= $bg ?>">
        <td><span style="font-size:13px;font-weight:600">@<?= htmlspecialchars($log['admin_username']) ?></span></td>
        <td><code style="font-size:12px;background:rgba(0,0,0,.06);padding:2px 8px;border-radius:6px"><?= htmlspecialchars($log['action']) ?></code></td>
        <td style="font-size:13px;color:var(--body)"><?= htmlspecialchars($log['target']) ?></td>
        <td style="font-size:13px;color:var(--muted)"><?= $log['target_id'] ?? '—' ?></td>
        <td style="font-size:12px;color:var(--muted)"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($logs)): ?>
      <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted)">Belum ada aktivitas.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
