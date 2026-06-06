<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/auth.php';

// Get redirect parameter to preserve it on login failure redirects
$redirectQuery = !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '';

// CSRF check — skip jika token belum ada di session (first load issue)
if (isset($_SESSION['csrf_token']) &&
    (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token'])) {
    $_SESSION['auth_error'] = 'Token tidak valid. Silakan muat ulang halaman.';
    header('Location: ' . BASE_URL . 'auth/login.php' . $redirectQuery);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['auth_error'] = 'Username dan password wajib diisi.';
    $_SESSION['auth_old']   = ['username' => $username];
    header('Location: ' . BASE_URL . 'auth/login.php' . $redirectQuery);
    exit;
}

$user = attemptLogin($username, $password);

if (!$user) {
    $_SESSION['auth_error'] = 'Username atau password salah.';
    $_SESSION['auth_old']   = ['username' => $username];
    header('Location: ' . BASE_URL . 'auth/login.php' . $redirectQuery);
    exit;
}

if (!empty($user['__banned__'])) {
    $reason = !empty($user['ban_reason']) ? ' Alasan: ' . $user['ban_reason'] : '';
    $_SESSION['auth_error'] = 'Akun kamu telah dinonaktifkan oleh admin.' . $reason;
    $_SESSION['auth_old']   = ['username' => $username];
    header('Location: ' . BASE_URL . 'auth/login.php' . $redirectQuery);
    exit;
}

loginUser($user);

// Redirect: admin → /kampusstore/admin/, user → homepage atau halaman asal
$role = $user['role'] ?? 'user';
if (in_array($role, ['admin', 'moderator'])) {
    $redirect = BASE_URL . 'admin/';
} else {
    $redirect = $_GET['redirect'] ?? BASE_URL . 'index.php';
    if (!preg_match('/^\/?[a-zA-Z0-9\/_\-\.?=&]*$/', $redirect)) {
        $redirect = BASE_URL . 'index.php';
    }
}

header('Location: ' . $redirect);
exit;
