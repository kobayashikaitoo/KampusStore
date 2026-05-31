<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../config/db.php';

requireAdmin();

$db = getDB();

// Resolve report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = (int)($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($rid && in_array($action, ['resolved','dismissed'])) {
        $db->prepare("UPDATE reports SET status=? WHERE id=?")->execute([$action, $rid]);
        adminLog('REPORT_' . strtoupper($action), 'reports', $rid);
        $_SESSION['admin_msg'] = 'Laporan diperbarui.';
    }
    header('Location: ' . BASE_URL . 'admin/reports.php'); exit;
}

$filter = $_GET['status'] ?? 'open';
$where  = '';
$params = [];
if ($filter !== 'all' && in_array($filter, ['open', 'investigating', 'resolved', 'dismissed'])) {
    $where  = 'WHERE r.status = ?';
    $params = [$filter];
}
$reportsSql = "
    SELECT r.*, u.username AS reporter_username,
           p.title AS product_title, target.username AS target_username
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users target ON r.user_id = target.id
    $where
    ORDER BY r.created_at DESC
";
$reportsStmt = $db->prepare($reportsSql);
$reportsStmt->execute($params);
$reports = $reportsStmt->fetchAll();

$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Laporan';
require_once __DIR__ . '/layout_header.php';
?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title"><i class="fas fa-flag"></i> Laporan (<?= count($reports) ?>)</span>
    <div class="table-toolbar">
      <?php foreach (['open'=>'<i class="fas fa-circle"></i> Terbuka','resolved'=>'<i class="fas fa-check"></i> Selesai','dismissed'=>'<i class="fas fa-circle"></i> Diabaikan','all'=>'Semua'] as $v=>$l): ?>
        <a href="?status=<?= $v ?>" class="btn-primary-sm <?= $filter===$v ? '' : '' ?>"
           style="<?= $filter===$v ? '' : 'background:var(--surface);color:var(--ink);box-shadow:none;border:1.5px solid var(--hairline)' ?>">
          <?= $l ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr><th>Pelapor</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
      <tr>
        <td><span style="font-size:13px;font-weight:600">@<?= e($r['reporter_username']) ?></span></td>
        <td>
          <?php if ($r['product_id']): ?>
            <div style="font-size:12px;color:var(--muted)"><i class="fas fa-box"></i> Produk</div>
            <div style="font-size:13px;font-weight:500;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['product_title'] ?? '—') ?></div>
          <?php elseif ($r['user_id']): ?>
            <div style="font-size:12px;color:var(--muted)"><i class="fas fa-user"></i> User</div>
            <div style="font-size:13px;font-weight:500">@<?= e($r['target_username'] ?? '—') ?></div>
          <?php else: ?>
            <span style="color:var(--muted)">—</span>
          <?php endif; ?>
        </td>
        <td style="font-size:13px;max-width:200px"><?= e(truncate($r['reason'], 60)) ?></td>
        <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
        <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        <td>
          <?php if ($r['status'] === 'open'): ?>
            <div style="display:flex;gap:5px">
              <form method="POST" style="display:inline">
                <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>"/>
                <input type="hidden" name="action" value="resolved"/>
                <button type="submit" class="btn-action btn-unban"><i class="fas fa-check"></i> Selesai</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>"/>
                <input type="hidden" name="action" value="dismissed"/>
                <button type="submit" class="btn-action btn-view">Abaikan</button>
              </form>
            </div>
          <?php else: ?>
            <span style="font-size:12px;color:var(--muted)">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($reports)): ?>
      <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">Tidak ada laporan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
