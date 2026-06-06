<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../functions/helpers.php';
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
        $_SESSION['admin_msg'] = 'Status laporan berhasil diperbarui.';
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

// Query join untuk mengambil info pelapor, produk terlapor beserta penjualnya, dan user terlapor
$reportsSql = "
    SELECT r.*, u.username AS reporter_username,
           p.title AS product_title, p.seller_id AS product_seller_id,
           target.username AS target_username,
           seller.username AS seller_username
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users target ON r.user_id = target.id
    LEFT JOIN users seller ON p.seller_id = seller.id
    $where
    ORDER BY r.created_at DESC
";
$reportsStmt = $db->prepare($reportsSql);
$reportsStmt->execute($params);
$reports = $reportsStmt->fetchAll();

$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Kelola Laporan';
require_once __DIR__ . '/layout_header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check"></i> <?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title"><i class="fas fa-flag"></i> Laporan Masuk (<?= count($reports) ?>)</span>
    <div class="table-toolbar">
      <?php foreach (['open'=>'<i class="fas fa-circle"></i> Terbuka','resolved'=>'<i class="fas fa-check"></i> Selesai','dismissed'=>'<i class="fas fa-circle"></i> Diabaikan','all'=>'Semua'] as $v=>$l): ?>
        <a href="?status=<?= $v ?>" class="btn-primary-sm"
           style="<?= $filter===$v ? '' : 'background:var(--surface);color:var(--ink);box-shadow:none;border:1.5px solid var(--hairline)' ?>">
          <?= $l ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Pelapor</th>
        <th>Jenis Laporan</th>
        <th>Isi Singkat</th>
        <th>Status</th>
        <th>Waktu</th>
        <th>Tindakan</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
      <tr>
        <td>
          <span style="font-size:13px;font-weight:600">@<?= e($r['reporter_username']) ?></span>
        </td>
        <td>
          <?php if ($r['product_id']): ?>
            <div style="font-size:11px;color:var(--muted);font-weight:600"><i class="fas fa-box" style="color:var(--primary)"></i> Produk</div>
            <div style="font-size:13px;font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($r['product_title'] ?? 'Produk telah dihapus') ?>">
              <?= e($r['product_title'] ?? 'Produk telah dihapus') ?>
            </div>
          <?php elseif ($r['user_id']): ?>
            <div style="font-size:11px;color:var(--muted);font-weight:600"><i class="fas fa-user" style="color:var(--primary)"></i> Pengguna</div>
            <div style="font-size:13px;font-weight:600">@<?= e($r['target_username'] ?? 'User telah dihapus') ?></div>
          <?php else: ?>
            <span style="color:var(--muted)">—</span>
          <?php endif; ?>
        </td>
        <td style="font-size:13px;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= e(truncate($r['reason'], 50)) ?>
        </td>
        <td>
          <span class="badge badge-<?= $r['status'] ?>">
            <?= $r['status'] === 'open' ? 'Terbuka' : ($r['status'] === 'resolved' ? 'Selesai' : 'Diabaikan') ?>
          </span>
        </td>
        <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:5px;align-items:center">
            <!-- Tombol Tinjau Detail (Paling Utama) -->
            <button class="btn-action btn-view" onclick="viewReport(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)">
              <i class="fas fa-eye"></i> Tinjau
            </button>
            
            <?php if ($r['status'] === 'open'): ?>
              <!-- Tombol Cepat Selesai -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>"/>
                <input type="hidden" name="action" value="resolved"/>
                <button type="submit" class="btn-action btn-unban"><i class="fas fa-check"></i> Selesai</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($reports)): ?>
      <tr>
        <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted)">
          <i class="fas fa-inbox" style="font-size:24px;margin-bottom:8px;display:block"></i>
          Tidak ada laporan saat ini.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Modal Detail Peninjauan Laporan -->
<div id="report-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.4);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px)">
  <div style="background:white;border-radius:20px;padding:28px;max-width:550px;width:90%;box-shadow:var(--shadow-lg);display:flex;flex-direction:column;gap:18px;max-height:90vh;overflow-y:auto;border:1px solid var(--hairline)">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--hairline);padding-bottom:12px">
      <h3 style="font-size:18px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px">
        <i class="fas fa-flag" style="color:var(--accent)"></i> Detail Laporan Peninjauan
      </h3>
      <button onclick="closeReportModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--muted);transition:color .2s" onmouseover="this.style.color='var(--ink)'" onmouseout="this.style.color='var(--muted)'">&times;</button>
    </div>
    
    <!-- Body -->
    <div style="display:flex;flex-direction:column;gap:14px">
      
      <!-- Pelapor & Status -->
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <span style="font-size:11px;color:var(--muted);display:block;font-weight:700;letter-spacing:0.5px">PELAPOR</span>
          <span id="rep-reporter" style="font-size:14px;font-weight:600;color:var(--ink)"></span>
        </div>
        <div>
          <span style="font-size:11px;color:var(--muted);display:block;font-weight:700;letter-spacing:0.5px;text-align:right">STATUS</span>
          <span id="rep-status-badge" style="display:inline-block;margin-top:2px"></span>
        </div>
      </div>

      <!-- Target Terlapor Card -->
      <div>
        <span style="font-size:11px;color:var(--muted);display:block;font-weight:700;letter-spacing:0.5px;margin-bottom:6px">TARGET YANG DILAPORKAN</span>
        <div style="background:var(--surface);border:1.5px solid var(--hairline);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:10px">
          <div style="font-size:12px;color:var(--body);font-weight:600;display:flex;align-items:center;gap:6px" id="rep-type-label"></div>
          <div id="rep-target-title" style="font-size:15px;font-weight:800;color:var(--ink)"></div>
          <div id="rep-target-actions" style="display:flex;gap:8px;margin-top:4px"></div>
        </div>
      </div>

      <!-- Detail Penjelasan Laporan -->
      <div>
        <span style="font-size:11px;color:var(--muted);display:block;font-weight:700;letter-spacing:0.5px">ISI LAPORAN / KRONOLOGI</span>
        <div id="rep-reason-body" style="font-size:14px;color:var(--ink);line-height:1.6;background:#f8fafc;border-left:4px solid var(--accent);border-radius:6px;padding:14px 18px;margin-top:6px;white-space:pre-wrap;box-shadow:inset 0 1px 3px rgba(0,0,0,0.02)"></div>
      </div>

      <!-- Info Tambahan Waktu -->
      <div style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;border-top:1px solid var(--hairline);padding-top:12px">
        <i class="far fa-clock"></i> Dilaporkan pada: <strong id="rep-time" style="color:var(--body)"></strong>
      </div>

    </div>

    <!-- Actions Footer -->
    <div style="display:flex;gap:10px;border-top:1px solid var(--hairline);padding-top:18px" id="rep-modal-actions">
      <form method="POST" style="flex:1">
        <input type="hidden" name="report_id" id="form-resolve-id"/>
        <input type="hidden" name="action" value="resolved"/>
        <button type="submit" style="width:100%;height:44px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:background .2s" onmouseover="this.style.background='var(--primary-dark)'" onmouseout="this.style.background='var(--primary)'"><i class="fas fa-check-circle"></i> Selesai (Resolve)</button>
      </form>
      <form method="POST" style="flex:1">
        <input type="hidden" name="report_id" id="form-dismiss-id"/>
        <input type="hidden" name="action" value="dismissed"/>
        <button type="submit" style="width:100%;height:44px;background:white;color:var(--body);border:1.5px solid var(--hairline);border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s" onmouseover="this.style.background='var(--surface)';this.style.borderColor='var(--muted)';this.style.color='var(--ink)'" onmouseout="this.style.background='white';this.style.borderColor='var(--hairline)';this.style.color='var(--body)'"><i class="fas fa-ban"></i> Abaikan (Dismiss)</button>
      </form>
    </div>
  </div>
</div>

<script>
function viewReport(r) {
  // Set data pelapor
  document.getElementById('rep-reporter').textContent = '@' + r.reporter_username;
  
  // Set isi laporan
  document.getElementById('rep-reason-body').textContent = r.reason ? r.reason : '(Tidak ada penjelasan detail)';
  
  // Set waktu terformat
  const dateVal = new Date(r.created_at);
  const formattedTime = dateVal.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
  document.getElementById('rep-time').textContent = formattedTime;

  // Set ID form aksi
  document.getElementById('form-resolve-id').value = r.id;
  document.getElementById('form-dismiss-id').value = r.id;

  // Set status badge
  const statusBadge = document.getElementById('rep-status-badge');
  statusBadge.className = 'badge badge-' + r.status;
  
  let labelText = r.status.toUpperCase();
  if (r.status === 'open') labelText = 'TERBUKA';
  if (r.status === 'resolved') labelText = 'SELESAI';
  if (r.status === 'dismissed') labelText = 'DIABAIKAN';
  statusBadge.textContent = labelText;

  // Handle target
  const typeLabel = document.getElementById('rep-type-label');
  const targetTitle = document.getElementById('rep-target-title');
  const targetActions = document.getElementById('rep-target-actions');

  typeLabel.innerHTML = '';
  targetTitle.innerHTML = '';
  targetActions.innerHTML = '';

  if (r.product_id) {
    typeLabel.innerHTML = '<i class="fas fa-box" style="color:var(--primary)"></i> PRODUK YANG DILAPORKAN';
    targetTitle.textContent = r.product_title ? r.product_title : 'Produk telah dihapus';
    
    if (r.product_title) {
      targetActions.innerHTML = `
        <a href="../product.php?id=${r.product_id}" target="_blank" class="btn-action btn-verify" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;font-size:12px;font-weight:600">
          <i class="fas fa-external-link-alt"></i> Buka Produk
        </a>
        <a href="users.php?q=${r.seller_username}" target="_blank" class="btn-action btn-ban" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;font-size:12px;font-weight:600">
          <i class="fas fa-user-shield"></i> Periksa Penjual (@${r.seller_username})
        </a>
      `;
    }
  } else if (r.user_id) {
    typeLabel.innerHTML = '<i class="fas fa-user" style="color:var(--primary)"></i> PENGGUNA YANG DILAPORKAN';
    targetTitle.textContent = r.target_username ? '@' + r.target_username : 'Pengguna telah dihapus';
    
    if (r.target_username) {
      targetActions.innerHTML = `
        <a href="users.php?q=${r.target_username}" target="_blank" class="btn-action btn-ban" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;font-size:12px;font-weight:600">
          <i class="fas fa-user-shield"></i> Tindak Pengguna (@${r.target_username})
        </a>
      `;
    }
  } else {
    typeLabel.textContent = 'Tidak diketahui';
    targetTitle.textContent = '—';
  }

  // Sembunyikan aksi jika laporan sudah berstatus selesai/diabaikan
  const footerActions = document.getElementById('rep-modal-actions');
  if (r.status === 'open') {
    footerActions.style.display = 'flex';
  } else {
    footerActions.style.display = 'none';
  }

  // Tampilkan Modal
  document.getElementById('report-modal').style.display = 'flex';
}

function closeReportModal() {
  document.getElementById('report-modal').style.display = 'none';
}

// Tutup modal jika klik luar box
document.getElementById('report-modal').addEventListener('click', function(e) {
  if (e.target === this) closeReportModal();
});
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
