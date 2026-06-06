<?php
// ============================================================
// KampusStore — Upload Profile Banner API
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Login dulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['banner'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Request tidak valid.']);
    exit;
}

$file    = $_FILES['banner'];
$maxSize = 4 * 1024 * 1024; // 4 MB

// Size check
if ($file['size'] > $maxSize) {
    echo json_encode(['ok' => false, 'msg' => 'Ukuran file maksimal 4 MB.']);
    exit;
}

// MIME check (aman dari spoofing ekstensi)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

if (!array_key_exists($mimeType, $allowed)) {
    echo json_encode(['ok' => false, 'msg' => 'Format tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.']);
    exit;
}

$ext     = $allowed[$mimeType];
$uid     = (int)$_SESSION['user_id'];
$dir     = __DIR__ . '/../assets/images/banners/';
$relDir  = 'assets/images/banners/';

// Pastikan direktori ada
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Nama file unik
$filename = 'banner_' . $uid . '_' . time() . '.' . $ext;
$destPath = $dir . $filename;
$relPath  = $relDir . $filename;

// Hapus banner lama
$db = getDB();
$old = $db->prepare('SELECT profile_banner FROM users WHERE id = ?');
$old->execute([$uid]);
$oldBanner = $old->fetchColumn();
if ($oldBanner) {
    $oldFile = __DIR__ . '/../' . $oldBanner;
    if (file_exists($oldFile)) {
        @unlink($oldFile);
    }
}

// Pindahkan file upload
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan file.']);
    exit;
}

// Simpan ke DB
$db->prepare('UPDATE users SET profile_banner = ? WHERE id = ?')
   ->execute([$relPath, $uid]);

// Sync session
$_SESSION['profile_banner'] = $relPath;

echo json_encode([
    'ok'  => true,
    'msg' => 'Banner berhasil diperbarui!',
    'url' => (defined('BASE_URL') ? BASE_URL : '/') . $relPath,
]);
