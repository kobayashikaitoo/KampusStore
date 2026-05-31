<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/auth.php';

// CSRF check
if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['auth_error'] = 'Request tidak valid. Silakan coba lagi.';
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

$username         = trim($_POST['username'] ?? '');
$name             = trim($_POST['name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$campus           = trim($_POST['campus'] ?? '');
$faculty          = trim($_POST['faculty'] ?? '');
$password         = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Simpan input untuk repopulate
$old = compact('username', 'name', 'email', 'campus', 'faculty');

// Validasi
if (empty($name) || empty($username) || empty($email) || empty($password)) {
    $_SESSION['auth_error'] = 'Nama, username, email kampus, dan password wajib diisi.';
    $_SESSION['auth_old']   = $old;
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['auth_error'] = 'Email tidak valid.';
    $_SESSION['auth_old']   = $old;
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

if (!str_ends_with($email, '@student.unsrat.ac.id')) {
    $_SESSION['auth_error'] = 'Harus menggunakan email @student.unsrat.ac.id';
    $_SESSION['auth_old']   = $old;
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['auth_error'] = 'Password minimal 8 karakter.';
    $_SESSION['auth_old']   = $old;
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['auth_error'] = 'Konfirmasi password tidak cocok.';
    $_SESSION['auth_old']   = $old;
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

// Daftarkan
$result = registerUser($username, $name, $email, $password, $campus);

if (isset($result['error'])) {
    $_SESSION['auth_error'] = $result['error'];
    $_SESSION['auth_old']   = $old;
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit;
}

// Auto-login setelah register
$db   = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$result['id']]);
$user = $stmt->fetch();

// Update faculty jika diisi
if (!empty($faculty)) {
    $db->prepare('UPDATE users SET faculty = ? WHERE id = ?')
       ->execute([$faculty, $result['id']]);
}

loginUser($user);

$_SESSION['auth_success'] = 'Akun berhasil dibuat! Selamat datang, ' . htmlspecialchars($name) . '! <i class="fas fa-party-popper"></i>';
header('Location: ' . BASE_URL . 'index.php');
exit;
