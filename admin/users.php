<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../config/db.php';

requireAdmin();

$db  = getDB();
$msg = $_SESSION['admin_msg'] ?? null;
$err = $_SESSION['admin_err'] ?? null;
unset($_SESSION['admin_msg'], $_SESSION['admin_err']);

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if (!$targetId) { header('Location: ' . BASE_URL . 'admin/users.php'); exit; }

    // Cegah admin hapus/ban dirinya sendiri
    if ($targetId === (int)$_SESSION['user_id']) {
        $_SESSION['admin_err'] = 'Tidak bisa melakukan aksi pada akun sendiri.';
        header('Location: ' . BASE_URL . 'admin/users.php'); exit;
    }

    // Cegah mod ubah sesama admin (hanya super admin)
    $target = $db->prepare('SELECT * FROM users WHERE id = ?');
    $target->execute([$targetId]);
    $targetUser = $target->fetch();

    if ($targetUser['role'] === 'admin' && !isSuperAdmin()) {
        $_SESSION['admin_err'] = 'Hanya super admin yang bisa memodifikasi akun admin lain.';
        header('Location: ' . BASE_URL . 'admin/users.php'); exit;
    }

    switch ($action) {
        case 'ban':
            $reason = trim($_POST['ban_reason'] ?? '');
            $db->prepare('UPDATE users SET is_banned=1, ban_reason=? WHERE id=?')
               ->execute([$reason ?: null, $targetId]);
            adminLog('BAN_USER', 'users', $targetId);
            $_SESSION['admin_msg'] = "Akun @{$targetUser['username']} berhasil dibanned.";
            break;

        case 'unban':
            $db->prepare('UPDATE users SET is_banned=0, ban_reason=NULL WHERE id=?')
               ->execute([$targetId]);
            adminLog('UNBAN_USER', 'users', $targetId);
            $_SESSION['admin_msg'] = "Akun @{$targetUser['username']} berhasil di-unban.";
            break;

        case 'verify':
            $db->prepare('UPDATE users SET is_verified=1 WHERE id=?')->execute([$targetId]);
            adminLog('VERIFY_USER', 'users', $targetId);
            $_SESSION['admin_msg'] = "Akun @{$targetUser['username']} berhasil diverifikasi.";
            break;

        case 'unverify':
            $db->prepare('UPDATE users SET is_verified=0 WHERE id=?')->execute([$targetId]);
            adminLog('UNVERIFY_USER', 'users', $targetId);
            $_SESSION['admin_msg'] = "Verifikasi @{$targetUser['username']} dicabut.";
            break;

        case 'make_moderator':
            if (!isSuperAdmin()) { $_SESSION['admin_err'] = 'Hanya super admin.'; break; }
            $db->prepare('UPDATE users SET role="moderator" WHERE id=?')->execute([$targetId]);
            adminLog('MAKE_MOD', 'users', $targetId);
            $_SESSION['admin_msg'] = "@{$targetUser['username']} dijadikan Moderator.";
            break;

        case 'make_user':
            if (!isSuperAdmin()) { $_SESSION['admin_err'] = 'Hanya super admin.'; break; }
            $db->prepare('UPDATE users SET role="user" WHERE id=?')->execute([$targetId]);
            adminLog('REVOKE_MOD', 'users', $targetId);
            $_SESSION['admin_msg'] = "Role @{$targetUser['username']} direset ke User.";
            break;

        case 'delete':
            if (!isSuperAdmin()) { $_SESSION['admin_err'] = 'Hanya super admin yang bisa hapus akun.'; break; }
            $db->prepare('DELETE FROM users WHERE id=?')->execute([$targetId]);
            adminLog('DELETE_USER', 'users', $targetId);
            $_SESSION['admin_msg'] = "Akun @{$targetUser['username']} dihapus permanen.";
            break;
    }
    header('Location: ' . BASE_URL . 'admin/users.php'); exit;
}

// ── Query users ──────────────────────────────────────────────
$search     = trim($_GET['q'] ?? '');
$filterRole = $_GET['role']   ?? '';
$filterBan  = $_GET['banned'] ?? '';

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(username LIKE ? OR name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterRole) {
    $where[]  = 'role = ?';
    $params[] = $filterRole;
}
if ($filterBan !== '') {
    $where[]  = 'is_banned = ?';
    $params[] = (int)$filterBan;
}

$sql   = 'SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
$stmt  = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Kelola Pengguna';
require_once __DIR__ . '/layout_header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title">👥 Pengguna (<?= count($users) ?>)</span>
    <form method="GET" class="table-toolbar">
      <div class="search-box">
        <span>🔍</span>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari username / nama…"/>
      </div>
      <select name="role" class="filter-select" onchange="this.form.submit()">
        <option value="">Semua Role</option>
        <option value="user"      <?= $filterRole==='user'      ? 'selected':'' ?>><i class="fas fa-user"></i> User</option>
        <option value="moderator" <?= $filterRole==='moderator' ? 'selected':'' ?>><i class="fas fa-shield"></i> Moderator</option>
        <option value="admin"     <?= $filterRole==='admin'     ? 'selected':'' ?>><i class="fas fa-bolt"></i> Admin</option>
      </select>
      <select name="banned" class="filter-select" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="0" <?= $filterBan==='0' ? 'selected':'' ?>><i class="fas fa-check"></i> Aktif</option>
        <option value="1" <?= $filterBan==='1' ? 'selected':'' ?>><i class="fas fa-ban"></i> Banned</option>
      </select>
      <button type="submit" class="btn-primary-sm">Filter</button>
    </form>
  </div>

  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Pengguna</th><th>Role</th><th>Status</th>
        <th>Verified</th><th>Bergabung</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div class="user-row">
            <div class="user-av" style="<?= $u['is_banned'] ? 'opacity:.4' : '' ?>"><?= strtoupper(substr($u['name'],0,1)) ?></div>
            <div>
              <div class="user-row-name" style="<?= $u['is_banned'] ? 'text-decoration:line-through;color:var(--muted)' : '' ?>">
                <?= htmlspecialchars($u['name']) ?>
              </div>
              <div class="user-row-sub">@<?= htmlspecialchars($u['username']) ?></div>
              <?php if ($u['ban_reason']): ?>
                <div style="font-size:11px;color:var(--danger);margin-top:2px">Alasan: <?= htmlspecialchars($u['ban_reason']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td>
          <span class="badge badge-<?= $u['role'] ?>">
            <?= $u['role']==='admin' ? '<i class="fas fa-bolt"></i> Admin' : ($u['role']==='moderator' ? '<i class="fas fa-shield"></i> Mod' : '<i class="fas fa-user"></i> User') ?>
          </span>
        </td>
        <td>
          <span class="badge <?= $u['is_banned'] ? 'badge-banned' : 'badge-active' ?>">
            <?= $u['is_banned'] ? '<i class="fas fa-ban"></i> Banned' : '<i class="fas fa-check"></i> Aktif' ?>
          </span>
        </td>
        <td>
          <span class="badge <?= $u['is_verified'] ? 'badge-active' : 'badge-inactive' ?>">
            <?= $u['is_verified'] ? '<i class="fas fa-check"></i>' : '–' ?>
          </span>
        </td>
        <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
          <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <!-- Ban/Unban -->
              <?php if (!$u['is_banned']): ?>
                <button class="btn-action btn-ban" onclick="banUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"><i class="fas fa-ban"></i> Ban</button>
              <?php else: ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="unban"/>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" class="btn-action btn-unban"><i class="fas fa-check"></i> Unban</button>
                </form>
              <?php endif; ?>
              <!-- Verify -->
              <?php if (!$u['is_verified']): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="verify"/>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" class="btn-action btn-verify"><i class="fas fa-check"></i> Verify</button>
                </form>
              <?php else: ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="unverify"/>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" class="btn-action btn-view">Unverify</button>
                </form>
              <?php endif; ?>
              <!-- Role (super admin only) -->
              <?php if (isSuperAdmin() && $u['role'] === 'user'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="make_moderator"/>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" class="btn-action btn-verify"><i class="fas fa-shield"></i> Mod</button>
                </form>
              <?php elseif (isSuperAdmin() && $u['role'] === 'moderator'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="make_user"/>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" class="btn-action btn-view">Revoke Mod</button>
                </form>
              <?php endif; ?>
              <!-- Delete (super admin only) -->
              <?php if (isSuperAdmin()): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus permanen akun @<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>? Aksi ini tidak bisa dibatalkan.')">
                  <input type="hidden" name="action" value="delete"/>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                </form>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <span style="font-size:12px;color:var(--muted)">Akun kamu</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($users)): ?>
      <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">Tidak ada pengguna ditemukan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Ban Modal -->
<div id="ban-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:white;border-radius:20px;padding:32px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <h3 style="font-size:18px;font-weight:700;margin-bottom:8px"><i class="fas fa-ban"></i> Ban Pengguna</h3>
    <p id="ban-modal-sub" style="font-size:14px;color:var(--body);margin-bottom:20px"></p>
    <form method="POST" id="ban-form">
      <input type="hidden" name="action" value="ban"/>
      <input type="hidden" name="user_id" id="ban-user-id"/>
      <label style="font-size:13px;font-weight:600;color:var(--ink);display:block;margin-bottom:6px">Alasan Ban (opsional)</label>
      <textarea name="ban_reason" style="width:100%;border:1.5px solid var(--hairline);border-radius:10px;padding:10px;font-family:inherit;font-size:14px;resize:none;height:80px;outline:none" placeholder="Melanggar aturan komunitas…"></textarea>
      <div style="display:flex;gap:10px;margin-top:16px">
        <button type="submit" style="flex:1;height:44px;background:var(--danger);color:white;border:none;border-radius:10px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer">🚫 Ban</button>
        <button type="button" onclick="closeBanModal()" style="flex:1;height:44px;background:var(--surface);color:var(--ink);border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function banUser(id, username) {
  document.getElementById('ban-user-id').value = id;
  document.getElementById('ban-modal-sub').textContent = 'Kamu akan memban akun @' + username;
  const m = document.getElementById('ban-modal');
  m.style.display = 'flex';
}
function closeBanModal() {
  document.getElementById('ban-modal').style.display = 'none';
}
document.getElementById('ban-modal').addEventListener('click', function(e) {
  if (e.target === this) closeBanModal();
});
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
