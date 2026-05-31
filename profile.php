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

if (!$user) { logoutUser(); header('Location: ' . BASE_URL . 'auth/login.php'); exit; }

$msg = $_SESSION['profile_msg'] ?? null;
$err = $_SESSION['profile_err'] ?? null;
unset($_SESSION['profile_msg'], $_SESSION['profile_err']);

// Handle edit profile POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $campus  = trim($_POST['campus']  ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $bio     = trim($_POST['bio']     ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $whatsapp = trim($_POST['whatsapp_number'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $_SESSION['profile_err'] = 'Nama harus 2–100 karakter.';
    } else {
        $db->prepare('UPDATE users SET name=?, campus=?, faculty=?, bio=?, phone=?, whatsapp_number=? WHERE id=?')
           ->execute([$name, $campus, $faculty, $bio, $phone, $whatsapp, $uid]);
        $_SESSION['name']         = $name; // update session
        $_SESSION['profile_msg']  = 'Profil berhasil diperbarui!';
    }
    header('Location: ' . BASE_URL . 'profile.php'); exit;
}

// Stats
$listingStmt = $db->prepare('SELECT COUNT(*) FROM products WHERE seller_id=? AND status="active"');
$listingStmt->execute([$uid]);
$listingCount = (int)$listingStmt->fetchColumn();

$soldStmt = $db->prepare('SELECT COUNT(*) FROM products WHERE seller_id=? AND status="sold"');
$soldStmt->execute([$uid]);
$soldCount = (int)$soldStmt->fetchColumn();

$wishStmt = $db->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id=?');
$wishStmt->execute([$uid]);
$wishCount = (int)$wishStmt->fetchColumn();

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/profile.css"/>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="pf-wrap">

  <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= e($err) ?></div><?php endif; ?>

  <!-- Profile Header -->
  <div class="pf-header">
    <div class="pf-banner"></div>
    <div class="pf-header-body">
      <div>
        <div class="pf-av-wrap"><div class="pf-av"><?= $initials ?></div></div>
        <div class="pf-name"><?= e($user['name']) ?></div>
        <div class="pf-username">@<?= e($user['username']) ?></div>
        <div class="pf-badges">
          <?php if ($user['is_trusted']): ?>
            <span class="pf-badge badge-t"><i class="fas fa-medal"></i> Trusted Seller</span>
          <?php endif; ?>
          <span class="pf-badge badge-r"><i class="fas fa-calendar"></i> Bergabung <?= $joinDate ?></span>
          <?php if (in_array($user['role'], ['admin','moderator'])): ?>
            <span class="pf-badge badge-t"><i class="fas fa-bolt"></i> <?= ucfirst($user['role']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php if (in_array($user['role'], ['admin','moderator'])): ?>
        <a href="<?= BASE_URL ?>admin/" class="btn-outline-lnk"><i class="fas fa-bolt"></i> Panel Admin</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="pf-stats">
    <a href="my-listings.php?status=active" class="pf-stat">
      <div class="pf-stat-num"><?= $listingCount ?></div>
      <div class="pf-stat-lbl"><i class="fas fa-box"></i> Barang Dijual</div>
    </a>
    <a href="my-listings.php?status=sold" class="pf-stat">
      <div class="pf-stat-num"><?= $soldCount ?></div>
      <div class="pf-stat-lbl"><i class="fas fa-check"></i> Terjual</div>
    </a>
    <a href="wishlist.php" class="pf-stat">
      <div class="pf-stat-num"><?= $wishCount ?></div>
      <div class="pf-stat-lbl"><i class="fas fa-heart"></i> Wishlist</div>
    </a>
  </div>

  <!-- Edit Profile Form -->
  <div class="pf-card">
    <div class="pf-section-title"><i class="fas fa-pen"></i> Edit Profil</div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label" for="name">Nama Lengkap *</label>
        <input type="text" id="name" name="name" class="form-input"
               value="<?= e($user['name']) ?>" maxlength="100" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" class="form-input" value="<?= e($user['username']) ?>" disabled/>
        <div class="mt-1 text-muted" style="font-size:12px">Username tidak bisa diubah.</div>
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
      <div class="form-group">
        <label class="form-label" for="whatsapp_number"><i class="fab fa-whatsapp"></i> Nomor WhatsApp</label>
        <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-input"
               value="<?= e($user['whatsapp_number'] ?? '') ?>" maxlength="20"/>
      </div>
      <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
    </form>

    <div class="divider"></div>

    <!-- Quick links + logout -->
    <div class="btn-logout-row">
      <a href="my-listings.php" class="btn-outline-lnk"><i class="fas fa-box"></i> Barang Saya</a>
      <a href="wishlist.php" class="btn-outline-lnk"><i class="fas fa-heart"></i> Wishlist</a>
      <a href="auth/logout.php" class="btn-logout"><i class="fas fa-door-open"></i> Keluar</a>
    </div>
  </div>

</div>

<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
</body>
</html>
