<?php
// Admin Layout Header — include di setiap halaman admin
// Usage: include dengan $pageTitle sudah di-set sebelumnya
if (!isset($pageTitle)) $pageTitle = 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> — KampusStore Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css"/>
  <style>
    .sidebar-link {
      padding:10px 12px;margin:1px 8px;
      border-radius:8px;text-decoration:none;
      color:rgba(255,255,255,.7);font-size:14px;font-weight:500;
      transition:background .15s,color .15s;
    }
    .sidebar-link:hover{background:rgba(255,255,255,.07);color:white}
    .sidebar-link.active{background:rgba(37,99,235,.5);color:white}
    .sidebar-link span:first-child{font-size:17px;width:22px;text-align:center}
    .sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid rgba(255,255,255,.08)}
    .sidebar-user{display:flex;align-items:center;gap:10px}
    .sidebar-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0}
    .sidebar-uname{font-size:13px;font-weight:600;color:white}
    .sidebar-role{font-size:11px;color:rgba(255,255,255,.45)}
    .sidebar-logout{
      display:block;text-align:center;margin-top:10px;
      color:rgba(255,255,255,.5);font-size:13px;text-decoration:none;
      padding:8px;border-radius:8px;transition:background .15s,color .15s;
    }
    .sidebar-logout:hover{background:rgba(239,68,68,.15);color:#f87171}

    /* Main */
    .admin-main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh}
    .admin-topbar{
      background:white;border-bottom:1px solid var(--hairline);
      padding:0 28px;height:60px;
      display:flex;align-items:center;justify-content:space-between;
      position:sticky;top:0;z-index:50;
    }
    .topbar-title{font-size:17px;font-weight:700;color:var(--ink)}
    .topbar-breadcrumb{font-size:13px;color:var(--muted);margin-top:1px}
    .topbar-right{display:flex;align-items:center;gap:12px}
    .admin-content{padding:28px;flex:1}

    /* Cards */
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px}
    .stat-card{
      background:white;border:1px solid var(--hairline);border-radius:16px;
      padding:20px;position:relative;overflow:hidden;
      transition:box-shadow .2s,transform .2s;
    }
    .stat-card:hover{box-shadow:0 4px 20px rgba(0,0,0,0.08);transform:translateY(-2px)}
    .stat-card-icon{font-size:28px;margin-bottom:12px}
    .stat-card-num{font-size:28px;font-weight:800;color:var(--ink);letter-spacing:-1px;line-height:1}
    .stat-card-label{font-size:13px;color:var(--body);margin-top:4px}
    .stat-card-accent{position:absolute;top:0;right:0;width:80px;height:80px;border-radius:0 16px 0 100%;opacity:.08}

    /* Table */
    .admin-card{background:white;border:1px solid var(--hairline);border-radius:16px;overflow:hidden;margin-bottom:24px}
    .admin-card-header{padding:18px 20px;border-bottom:1px solid var(--hairline);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
    .admin-card-title{font-size:15px;font-weight:700;color:var(--ink)}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;padding:10px 16px;background:var(--surface);text-align:left;border-bottom:1px solid var(--hairline)}
    .admin-table td{padding:12px 16px;border-bottom:1px solid var(--hairline);font-size:14px;color:var(--ink);vertical-align:middle}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover{background:var(--surface)}

    /* Badges */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700}
    .badge-admin{background:#fdf4ff;color:#7c3aed}
    .badge-mod{background:#eff6ff;color:var(--primary)}
    .badge-user{background:var(--surface);color:var(--body)}
    .badge-active{background:var(--success-light);color:#15803d}
    .badge-banned{background:var(--danger-light);color:var(--danger)}
    .badge-sold{background:#f0fdf4;color:#16a34a}
    .badge-inactive{background:var(--surface);color:var(--muted)}
    .badge-open{background:var(--warn-light);color:#92400e}
    .badge-resolved{background:var(--success-light);color:#15803d}

    /* Action buttons */
    .btn-action{
      display:inline-flex;align-items:center;gap:5px;
      padding:5px 12px;border-radius:8px;font-family:inherit;
      font-size:12px;font-weight:600;cursor:pointer;border:none;
      text-decoration:none;transition:all .15s;
    }
    .btn-ban{background:var(--danger-light);color:var(--danger)}
    .btn-ban:hover{background:#fecaca;color:#b91c1c}
    .btn-unban{background:var(--success-light);color:#15803d}
    .btn-unban:hover{background:#bbf7d0}
    .btn-verify{background:var(--primary-light);color:var(--primary)}
    .btn-verify:hover{background:#bfdbfe}
    .btn-delete{background:#fef2f2;color:var(--danger)}
    .btn-delete:hover{background:#fecaca}
    .btn-view{background:var(--surface);color:var(--body)}
    .btn-view:hover{background:var(--hairline)}
    .btn-primary-sm{background:var(--primary);color:white;padding:7px 16px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .15s}
    .btn-primary-sm:hover{background:var(--primary-dark)}

    /* Search/filter bar */
    .table-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .search-box{
      display:flex;align-items:center;gap:8px;
      background:var(--surface);border:1.5px solid var(--hairline);
      border-radius:10px;padding:0 12px;height:36px;flex:1;max-width:300px;
      transition:border-color .2s;
    }
    .search-box:focus-within{border-color:var(--primary)}
    .search-box input{border:none;outline:none;font-family:inherit;font-size:14px;background:transparent;color:var(--ink);width:100%}
    .filter-select{height:36px;padding:0 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:13px;color:var(--ink);background:white;cursor:pointer}

    /* Avatar */
    .user-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:white;flex-shrink:0}
    .user-row{display:flex;align-items:center;gap:10px}
    .user-row-name{font-size:14px;font-weight:600;color:var(--ink)}
    .user-row-sub{font-size:12px;color:var(--muted)}

    /* Alert */
    .alert{padding:12px 16px;border-radius:12px;font-size:14px;margin-bottom:20px}
    .alert-success{background:var(--success-light);color:#15803d;border:1px solid #bbf7d0}
    .alert-error{background:var(--danger-light);color:var(--danger);border:1px solid #fecaca}

    /* Responsive */
    @media(max-width:768px){
      .sidebar{display:none}
      .admin-main{margin-left:0}
    }
  </style>
</head>
<body class="admin-page">

<!-- Sidebar -->
<aside class="sidebar">
  <a href="./" class="sidebar-logo">
    <span style="font-size:20px">🏪</span>
    <span class="sidebar-logo-text">KampusStore</span>
    <span class="sidebar-badge">Admin</span>
  </a>

  <div class="sidebar-section">Overview</div>
  <a href="./" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && dirname($_SERVER['PHP_SELF']) !== '/' ? 'active' : '' ?>">
    <span><i class="fas fa-chart-line"></i></span> Dashboard
  </a>

  <div class="sidebar-section">Manajemen</div>
  <a href="users.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
    <span>👥</span> Kelola Pengguna
  </a>
  <a href="products.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>">
    <span><i class="fas fa-box"></i></span> Kelola Produk
  </a>
  <a href="reports.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
    <span><i class="fas fa-flag"></i></span> Laporan
  </a>
  <a href="logs.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : '' ?>">
    <span>📋</span> Activity Log
  </a>

  <div class="sidebar-section">Sistem</div>
  <a href="../index.php" class="sidebar-link">
    <span>🌐</span> Lihat Situs
  </a>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-av"><?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)) ?></div>
      <div>
        <div class="sidebar-uname"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
        <div class="sidebar-role"><?= ucfirst($_SESSION['role'] ?? 'admin') ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="sidebar-logout"><i class="fas fa-door-open"></i> Keluar</a>
  </div>
</aside>

<!-- Main Content -->
<main class="admin-main">
  <div class="admin-topbar">
    <div>
      <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
    </div>
    <div class="topbar-right">
      <span style="font-size:13px;color:var(--muted)"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
    </div>
  </div>
  <div class="admin-content">
