<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

requireLogin();

$db   = getDB();
$uid  = (int)$_SESSION['user_id'];

// Load fresh from DB (bukan hanya session)
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) { logoutUser(); header('Location: /auth/login.php'); exit; }

$msg = $_SESSION['profile_msg'] ?? null;
$err = $_SESSION['profile_err'] ?? null;
unset($_SESSION['profile_msg'], $_SESSION['profile_err']);

// Handle edit profile POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $campus  = trim($_POST['campus']  ?? '');
    $faculty = trim($_POST['faculty'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $_SESSION['profile_err'] = 'Nama harus 2–100 karakter.';
    } else {
        $db->prepare('UPDATE users SET name=?, campus=?, faculty=? WHERE id=?')
           ->execute([$name, $campus, $faculty, $uid]);
        $_SESSION['name']         = $name; // update session
        $_SESSION['profile_msg']  = 'Profil berhasil diperbarui!';
    }
    header('Location: /profile.php'); exit;
}

// Stats
$listingCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid AND status='active'")->fetchColumn();
$soldCount    = (int)$db->query("SELECT COUNT(*) FROM products WHERE seller_id=$uid AND status='sold'")->fetchColumn();
$wishCount    = (int)$db->query("SELECT COUNT(*) FROM wishlists WHERE user_id=$uid")->fetchColumn();

$initials = strtoupper(mb_substr($user['name'], 0, 1));
$joinDate  = date('d M Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profil Saya — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .pf-wrap{max-width:800px;margin:0 auto;padding:32px 24px 80px}

    /* Header card */
    .pf-header{background:white;border:1px solid var(--hairline);border-radius:24px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.05);margin-bottom:20px}
    .pf-banner{height:110px;background:linear-gradient(135deg,#2563eb 0%,#7c3aed 100%)}
    .pf-header-body{padding:0 28px 28px;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .pf-av-wrap{margin-top:-38px}
    .pf-av{width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;color:white;border:4px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.15)}
    .pf-name{font-size:20px;font-weight:800;color:var(--ink);margin-top:10px;letter-spacing:-0.3px}
    .pf-username{font-size:14px;color:var(--muted);margin-bottom:10px}
    .pf-badges{display:flex;gap:8px;flex-wrap:wrap}
    .pf-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 12px;border-radius:999px}
    .badge-v{background:#eff6ff;color:#2563eb}
    .badge-t{background:#fdf4ff;color:#7c3aed}
    .badge-r{background:#f8fafc;color:var(--body)}

    /* Stats */
    .pf-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
    @media(max-width:480px){.pf-stats{grid-template-columns:1fr 1fr}}
    .pf-stat{background:white;border:1px solid var(--hairline);border-radius:16px;padding:18px;text-align:center;text-decoration:none;transition:box-shadow .2s,transform .15s;display:block}
    .pf-stat:hover{box-shadow:0 4px 16px rgba(0,0,0,0.08);transform:translateY(-2px)}
    .pf-stat-num{font-size:26px;font-weight:800;color:var(--ink);letter-spacing:-0.5px}
    .pf-stat-lbl{font-size:12px;color:var(--muted);margin-top:4px}

    /* Edit form */
    .pf-card{background:white;border:1px solid var(--hairline);border-radius:20px;padding:28px;box-shadow:0 2px 16px rgba(0,0,0,0.05);margin-bottom:16px}
    .pf-section-title{font-size:15px;font-weight:700;color:var(--ink);margin-bottom:18px;display:flex;align-items:center;gap:8px}
    .form-group{margin-bottom:16px}
    .form-label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px}
    .form-input{width:100%;border:1.5px solid var(--hairline);border-radius:12px;padding:11px 14px;font-family:inherit;font-size:15px;color:var(--ink);background:white;outline:none;transition:border-color .2s,box-shadow .2s}
    .form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
    .form-input[disabled]{background:var(--surface);color:var(--muted);cursor:not-allowed}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:540px){.form-grid{grid-template-columns:1fr}}
    .btn-save{height:46px;padding:0 24px;background:var(--primary);color:white;border:none;border-radius:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s,transform .15s}
    .btn-save:hover{background:var(--primary-dark);transform:translateY(-1px)}
    .btn-logout-row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    .btn-logout{display:inline-flex;align-items:center;gap:8px;background:#fef2f2;color:#dc2626;font-family:inherit;font-size:14px;font-weight:600;padding:10px 20px;border-radius:12px;border:1.5px solid #fecaca;cursor:pointer;text-decoration:none;transition:background .2s}
    .btn-logout:hover{background:#fee2e2}
    .btn-outline-lnk{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border:1.5px solid var(--hairline);border-radius:12px;font-size:14px;font-weight:600;color:var(--ink);text-decoration:none;transition:all .2s}
    .btn-outline-lnk:hover{border-color:var(--primary);color:var(--primary)}
    .alert{padding:12px 16px;border-radius:12px;font-size:14px;margin-bottom:16px}
    .alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
    .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
    .divider{height:1px;background:var(--hairline);margin:20px 0}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="pf-wrap">

  <?php if ($msg): ?><div class="alert alert-success">✅ <?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error">⚠️ <?= e($err) ?></div><?php endif; ?>

  <!-- Profile Header -->
  <div class="pf-header">
    <div class="pf-banner"></div>
    <div class="pf-header-body">
      <div>
        <div class="pf-av-wrap"><div class="pf-av"><?= $initials ?></div></div>
        <div class="pf-name"><?= e($user['name']) ?></div>
        <div class="pf-username">@<?= e($user['username']) ?></div>
        <div class="pf-badges">
          <?php if ($user['is_verified']): ?>
            <span class="pf-badge badge-v">✓ Verified Student</span>
          <?php endif; ?>
          <?php if ($user['is_trusted']): ?>
            <span class="pf-badge badge-t">🏅 Trusted Seller</span>
          <?php endif; ?>
          <span class="pf-badge badge-r">📅 Bergabung <?= $joinDate ?></span>
          <?php if (in_array($user['role'], ['admin','moderator'])): ?>
            <span class="pf-badge" style="background:#fdf4ff;color:#7c3aed">⚡ <?= ucfirst($user['role']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php if (in_array($user['role'], ['admin','moderator'])): ?>
        <a href="/admin/" class="btn-outline-lnk">⚡ Panel Admin</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="pf-stats">
    <a href="/my-listings.php?status=active" class="pf-stat">
      <div class="pf-stat-num"><?= $listingCount ?></div>
      <div class="pf-stat-lbl">📦 Barang Dijual</div>
    </a>
    <a href="/my-listings.php?status=sold" class="pf-stat">
      <div class="pf-stat-num"><?= $soldCount ?></div>
      <div class="pf-stat-lbl">✅ Terjual</div>
    </a>
    <a href="/wishlist.php" class="pf-stat">
      <div class="pf-stat-num"><?= $wishCount ?></div>
      <div class="pf-stat-lbl">♥ Wishlist</div>
    </a>
  </div>

  <!-- Edit Profile Form -->
  <div class="pf-card">
    <div class="pf-section-title">✏️ Edit Profil</div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label" for="name">Nama Lengkap *</label>
        <input type="text" id="name" name="name" class="form-input"
               value="<?= e($user['name']) ?>" maxlength="100" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" class="form-input" value="<?= e($user['username']) ?>" disabled/>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">Username tidak bisa diubah.</div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="campus">Kampus</label>
          <input type="text" id="campus" name="campus" class="form-input"
                 value="<?= e($user['campus'] ?? '') ?>" maxlength="100" placeholder="cth: Universitas Gadjah Mada"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="faculty">Fakultas / Jurusan</label>
          <input type="text" id="faculty" name="faculty" class="form-input"
                 value="<?= e($user['faculty'] ?? '') ?>" maxlength="100" placeholder="cth: Teknik Elektro"/>
        </div>
      </div>
      <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
    </form>

    <div class="divider"></div>

    <!-- Quick links + logout -->
    <div class="btn-logout-row">
      <a href="/my-listings.php" class="btn-outline-lnk">📦 Barang Saya</a>
      <a href="/wishlist.php" class="btn-outline-lnk">♥ Wishlist</a>
      <a href="/auth/logout.php" class="btn-logout">🚪 Keluar</a>
    </div>
  </div>

</div>

<script src="/assets/js/main.js" defer></script>
</body>
</html>
