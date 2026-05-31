<?php
// ============================================================
// KampusStore — Admin Helper Functions
// ============================================================

require_once __DIR__ . '/auth.php';

/**
 * Cek apakah user punya role admin atau moderator.
 */
function isAdmin(): bool
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'moderator']);
}

/**
 * Cek apakah user adalah super admin.
 */
function isSuperAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect jika bukan admin.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (!isAdmin()) {
        http_response_code(403);
        die('403 — Akses ditolak. Halaman ini khusus admin.');
    }
}

/**
 * Log aksi admin ke tabel admin_logs.
 */
function adminLog(string $action, string $target, ?int $targetId = null): void
{
    try {
        $db = getDB();
        $db->prepare(
            'INSERT INTO admin_logs (admin_id, action, target, target_id) VALUES (?, ?, ?, ?)'
        )->execute([$_SESSION['user_id'], $action, $target, $targetId]);
    } catch (Exception $e) {
        // Fail silently — jangan interrupt admin action
    }
}

/**
 * Ambil statistik ringkas untuk dashboard.
 */
function getDashboardStats(): array
{
    $db = getDB();
    return [
        'total_users'    => $db->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
        'banned_users'   => $db->query('SELECT COUNT(*) FROM users WHERE is_banned = 1')->fetchColumn(),
        'total_products' => $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'active_products'=> $db->query('SELECT COUNT(*) FROM products WHERE status = "active"')->fetchColumn(),
        'sold_products'  => $db->query('SELECT COUNT(*) FROM products WHERE status = "sold"')->fetchColumn(),
        'total_wishlists'=> $db->query('SELECT COUNT(*) FROM wishlists')->fetchColumn(),
    ];
}
