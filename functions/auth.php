<?php
require_once __DIR__ . '/../config/db.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['name']        = $user['name'];
    $_SESSION['role']        = $user['role'] ?? 'user';
    $_SESSION['is_verified'] = $user['is_verified'];
    $_SESSION['is_trusted']  = $user['is_trusted'];
    $_SESSION['is_banned']   = $user['is_banned'] ?? 0;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function registerUser(string $username, string $name, string $password, string $campus = ''): array
{
    // Validasi username
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        return ['error' => 'Username hanya boleh huruf, angka, dan underscore (3–30 karakter).'];
    }

    $db = getDB();

    // Cek username sudah dipakai
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['error' => 'Username sudah digunakan. Pilih username lain.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare(
        'INSERT INTO users (username, name, password, campus) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$username, $name, $hash, $campus]);

    return ['ok' => true, 'id' => (int)$db->lastInsertId()];
}

/**
 * Login dengan username + password. Return user array atau null.
 */
function attemptLogin(string $username, string $password): ?array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }

    if ($user['is_banned']) {
        return ['__banned__' => true, 'ban_reason' => $user['ban_reason']];
    }

    return $user;
}

/**
 * Ambil data user aktif dari session.
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'          => $_SESSION['user_id'],
        'username'    => $_SESSION['username'],
        'name'        => $_SESSION['name'],
        'role'        => $_SESSION['role'] ?? 'user',
        'is_verified' => $_SESSION['is_verified'],
        'is_trusted'  => $_SESSION['is_trusted'],
        'is_banned'   => $_SESSION['is_banned'] ?? 0,
    ];
}
