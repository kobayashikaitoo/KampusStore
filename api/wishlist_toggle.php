<?php
// api/wishlist_toggle.php — AJAX endpoint untuk toggle wishlist ke DB
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Harus login
if (!isLoggedIn()) {
    echo json_encode(['status' => 'unauthenticated', 'redirect' => BASE_URL . 'auth/login.php']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$userId    = (int)$_SESSION['user_id'];

if (!$productId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product']);
    exit;
}

$db = getDB();

// Cek sudah ada di wishlist?
$stmt = $db->prepare('SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?');
$stmt->execute([$userId, $productId]);
$exists = $stmt->fetchColumn();

if ($exists) {
    // Remove from wishlist
    $db->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
        ->execute([$userId, $productId]);
    echo json_encode(['status' => 'removed', 'saved' => false]);
} else {
    // Add to wishlist
    try {
        $db->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)')
           ->execute([$userId, $productId]);
        echo json_encode(['status' => 'added', 'saved' => true]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error']);
    }
}
