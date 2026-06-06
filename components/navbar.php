<?php
// Navbar perlu auth functions
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../functions/auth.php';
}
$navUser = currentUser();
?>
<script>
  window.BASE_URL = '<?= BASE_URL ?>';
</script>
<nav class="ks-nav" id="ks-nav">
  <div class="nav-inner">

    <!-- Logo -->
    <a href="index.php" class="nav-logo">
      <i class="fas fa-store" style="font-size:18px;color:var(--primary)"></i>
      <span class="nav-logo-text">KampusStore</span>
    </a>

    <!-- Search (Desktop) -->
    <div class="nav-search">
      <input type="text" id="nav-search-input" placeholder="Cari buku, laptop, kos…" autocomplete="off"/>
      <button class="search-btn" id="nav-search-btn" aria-label="Cari">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </div>

    <!-- Actions -->
    <div class="nav-actions">

      <!-- Nav links desktop -->
      <div class="nav-links-hide" style="display:flex;gap:4px;align-items:center">
        <a href="team.php" style="font-size:14px;font-weight:500;color:var(--body);text-decoration:none;padding:8px 12px;border-radius:8px;transition:background .2s,color .2s" onmouseover="this.style.background='var(--surface)';this.style.color='var(--ink)'" onmouseout="this.style.background='';this.style.color='var(--body)'">Tim</a>
      </div>

      <!-- Wishlist -->
      <div class="cart-wrap">
        <a href="<?= isLoggedIn() ? 'wishlist.php' : 'auth/login.php?redirect=wishlist.php' ?>" class="cart-btn" aria-label="Wishlist" style="text-decoration:none;display:flex;align-items:center;justify-content:center;color:var(--ink);font-size:18px">
          <i class="fas fa-heart"></i>
        </a>
      </div>

      <!-- Sell button -->
      <a href="<?= isLoggedIn() ? 'sell.php' : 'auth/login.php?redirect=sell.php' ?>" class="btn-sell">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Jual</span>
      </a>

      <!-- User state -->
      <?php if ($navUser): ?>
        <!-- Logged in — avatar dropdown -->
        <div style="position:relative" id="user-menu-wrap">
          <button
            onclick="toggleUserMenu()"
            style="display:flex;align-items:center;gap:8px;background:var(--primary-light);border:1.5px solid rgba(37,99,235,0.2);border-radius:999px;padding:6px 14px 6px 6px;cursor:pointer;font-family:inherit;"
            aria-label="Menu akun"
          >
            <?php if (!empty($navUser['profile_photo'])): ?>
              <img src="<?= BASE_URL . htmlspecialchars($navUser['profile_photo']) ?>" alt="Avatar"
                   style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
              <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:none;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0;">
                <?= strtoupper(substr($navUser['name'], 0, 1)) ?>
              </div>
            <?php else: ?>
              <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0;">
                <?= strtoupper(substr($navUser['name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <span style="font-size:13px;font-weight:600;color:var(--primary)"><?= htmlspecialchars($navUser['username']) ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <!-- Dropdown -->
          <div id="user-dropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:white;border:1px solid var(--hairline);border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.12);min-width:180px;overflow:hidden;z-index:200;">
            <a href="profile.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:var(--ink);font-size:14px;font-weight:500;transition:background .15s" onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background=''">
              <i class="fas fa-user"></i> Profil Saya
            </a>
            <a href="my-listings.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:var(--ink);font-size:14px;font-weight:500;transition:background .15s" onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background=''">
              <i class="fas fa-tag"></i> Barang Saya
            </a>
            <a href="wishlist.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:var(--ink);font-size:14px;font-weight:500;transition:background .15s" onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background=''">
              <i class="fas fa-heart"></i> Wishlist
            </a>
            <?php if ($navUser && in_array($navUser['role'], ['admin', 'moderator'])): ?>
              <div style="height:1px;background:var(--hairline);margin:4px 0"></div>
              <a href="admin/" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#d97706;font-size:14px;font-weight:500;transition:background .15s" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background=''">
                <i class="fas fa-cog"></i> Panel Admin
              </a>
            <?php endif; ?>
            <div style="height:1px;background:var(--hairline);margin:4px 0"></div>
            <a href="auth/logout.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#dc2626;font-size:14px;font-weight:500;transition:background .15s" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
              <i class="fas fa-door-open"></i> Keluar
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- Not logged in — always visible -->
        <a href="auth/login.php" class="btn-login btn-nav-login">
          <i class="fas fa-user"></i>
          <span class="nav-links-hide" style="display:inline">Masuk</span>
        </a>
        <a href="auth/register.php" class="btn-sell btn-nav-register" style="background:transparent;color:var(--primary);border:1.5px solid var(--primary);" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='transparent'">
          <i class="fas fa-user-plus"></i>
          <span class="nav-links-hide" style="display:inline">Daftar</span>
        </a>
      <?php endif; ?>

      <!-- Hamburger Menu Button -->
      <button class="btn-burger" id="btn-burger" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>

    </div>
  </div>
</nav>

<!-- Mobile Drawer Overlay -->
<div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>

<!-- Mobile Drawer -->
<div class="mobile-drawer" id="mobile-drawer">
  <div class="drawer-header">
    <span class="drawer-title"><i class="fas fa-store" style="color:var(--primary)"></i> KampusStore</span>
    <button class="btn-close-drawer" onclick="toggleMobileMenu()" aria-label="Tutup Menu">&times;</button>
  </div>
  
  <div class="drawer-body">
    <!-- Mobile Search -->
    <div class="mobile-search">
      <input type="text" id="mobile-search-input" placeholder="Cari barang..." autocomplete="off"/>
      <button class="mobile-search-btn" id="mobile-search-btn" aria-label="Cari">
        <i class="fas fa-search"></i>
      </button>
    </div>

    <!-- Navigation Links -->
    <div class="drawer-links">
      <a href="index.php"><i class="fas fa-home"></i> Halaman Utama</a>
      <a href="team.php"><i class="fas fa-users"></i> Tim Kami</a>
      <a href="how-to-sell.php"><i class="fas fa-info-circle"></i> Cara Menjual</a>
      <a href="privacy.php"><i class="fas fa-shield-alt"></i> Kebijakan Privasi</a>
      <a href="terms.php"><i class="fas fa-file-contract"></i> Ketentuan Layanan</a>
    </div>

    <hr class="drawer-divider" />

    <!-- User Profile Links / Auth in Drawer -->
    <div class="drawer-user-section">
      <?php if ($navUser): ?>
        <div class="drawer-user-info">
          <div class="drawer-avatar" style="overflow:hidden;padding:0;">
            <?php if (!empty($navUser['profile_photo'])): ?>
              <img src="<?= BASE_URL . htmlspecialchars($navUser['profile_photo']) ?>" alt="Avatar"
                   style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                   onerror="this.style.display='none';this.parentElement.querySelector('.nav-initial').style.display='flex'"/>
              <span class="nav-initial" style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:inherit;font-weight:inherit;">
                <?= strtoupper(substr($navUser['name'], 0, 1)) ?>
              </span>
            <?php else: ?>
              <?= strtoupper(substr($navUser['name'], 0, 1)) ?>
            <?php endif; ?>
          </div>
          <div>
            <div class="drawer-name"><?= htmlspecialchars($navUser['name']) ?></div>
            <div class="drawer-username">@<?= htmlspecialchars($navUser['username']) ?></div>
          </div>
        </div>
        
        <div class="drawer-links" style="margin-top: 10px;">
          <a href="profile.php"><i class="fas fa-user-circle"></i> Profil Saya</a>
          <a href="my-listings.php"><i class="fas fa-tags"></i> Barang Saya</a>
          <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist Saya</a>
          <?php if (in_array($navUser['role'], ['admin', 'moderator'])): ?>
            <a href="admin/" style="color:#d97706"><i class="fas fa-cog"></i> Panel Admin</a>
          <?php endif; ?>
          <a href="auth/logout.php" style="color:#dc2626"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
      <?php else: ?>
        <div class="drawer-auth-buttons">
          <a href="auth/login.php" class="btn-drawer-login">Masuk</a>
          <a href="auth/register.php" class="btn-drawer-register">Daftar Baru</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function toggleUserMenu() {
  const dd = document.getElementById('user-dropdown');
  if (dd) dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

function toggleMobileMenu() {
  const drawer = document.getElementById('mobile-drawer');
  const overlay = document.getElementById('mobile-overlay');
  const burger = document.getElementById('btn-burger');
  
  if (drawer && overlay && burger) {
    drawer.classList.toggle('active');
    overlay.classList.toggle('active');
    burger.classList.toggle('active');
    
    if (drawer.classList.contains('active')) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('user-menu-wrap');
  if (wrap && !wrap.contains(e.target)) {
    const dd = document.getElementById('user-dropdown');
    if (dd) dd.style.display = 'none';
  }
});

// Mobile Search Logic
function doMobileSearch() {
  const q = (document.getElementById('mobile-search-input')?.value || '').trim();
  if (!q) return;
  const url = new URL(window.BASE_URL + 'index.php', window.location.origin);
  url.searchParams.set('q', q);
  window.location.href = url.toString();
}

document.getElementById('mobile-search-btn')?.addEventListener('click', doMobileSearch);
document.getElementById('mobile-search-input')?.addEventListener('keydown', e => { 
  if (e.key === 'Enter') doMobileSearch(); 
});

// Pre-fill mobile search input from URL on page load
(function() {
  const params = new URLSearchParams(window.location.search);
  const q = params.get('q');
  const mobSearchInput = document.getElementById('mobile-search-input');
  if (q && mobSearchInput) mobSearchInput.value = q;
})();
</script>
