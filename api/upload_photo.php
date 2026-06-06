<?php
// ============================================================
// KampusStore — Upload Profile Photo API
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// Validate file
if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['photo']['error'] ?? -1;
    $errorMsg = match($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (maks 2 MB).',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih.',
        default => 'Gagal upload file.'
    };
    http_response_code(400);
    echo json_encode(['error' => $errorMsg]);
    exit;
}

$file     = $_FILES['photo'];
$maxSize  = 2 * 1024 * 1024; // 2 MB
$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// Size check
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File terlalu besar. Maks 2 MB.']);
    exit;
}

// MIME check (using finfo for security)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format file tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.']);
    exit;
}

// Extension map
$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
$ext = $extMap[$mimeType];

// Build target path
$uploadDir = __DIR__ . '/../assets/images/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename  = 'avatar_' . $uid . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $uploadDir . $filename;
$relPath    = 'assets/images/avatars/' . $filename;

// Delete old avatar if exists
$db   = getDB();
$stmt = $db->prepare('SELECT profile_photo FROM users WHERE id = ?');
$stmt->execute([$uid]);
$oldPhoto = $stmt->fetchColumn();

if (!empty($oldPhoto)) {
    $oldFile = __DIR__ . '/../' . $oldPhoto;
    if (file_exists($oldFile)) {
        @unlink($oldFile);
    }
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan file. Coba lagi.']);
    exit;
}

// Update DB
$db->prepare('UPDATE users SET profile_photo = ? WHERE id = ?')
   ->execute([$relPath, $uid]);

// Update session
$_SESSION['profile_photo'] = $relPath;

echo json_encode([
    'success' => true,
    'url'     => BASE_URL . $relPath,
    'msg'     => 'Foto profil berhasil diperbarui!'
]);
