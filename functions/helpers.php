<?php
// ============================================================
// KampusStore — Global Helper Functions
// ============================================================

/**
 * Format angka menjadi format Rupiah Indonesia.
 * Contoh: 3800000  →  "Rp 3.800.000"
 */
function formatRupiah(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Menghasilkan HTML badge kondisi barang.
 *
 * @param string $condition  'like_new' | 'good' | 'fair' | 'used'
 * @return string  HTML <span> badge
 */
function conditionBadge(string $condition): string
{
    $map = [
        'like_new' => [
            'label' => 'Seperti Baru',
            'class' => 'badge-like-new',
        ],
        'good' => [
            'label' => 'Kondisi Baik',
            'class' => 'badge-good',
        ],
        'fair' => [
            'label' => 'Cukup Baik',
            'class' => 'badge-fair',
        ],
        'used' => [
            'label' => 'Bekas',
            'class' => 'badge-used',
        ],
    ];

    $data  = $map[$condition] ?? $map['good'];
    $label = htmlspecialchars($data['label']);
    $class = htmlspecialchars($data['class']);

    return "<span class=\"condition-badge {$class}\">{$label}</span>";
}

/**
 * Menghasilkan HTML seller badge (Verified Student / Trusted Seller).
 *
 * @param string $type  'verified' | 'trusted'
 * @return string  HTML <span> badge
 */
function sellerBadge(string $type): string
{
    if ($type === 'trusted') {
        return '<span class="seller-badge seller-trusted">🏅 Trusted Seller</span>';
    }
    return '<span class="seller-badge seller-verified"><i class="fas fa-check"></i> Verified Student</span>';
}

/**
 * Truncate teks panjang dengan ellipsis.
 *
 * @param string $text
 * @param int    $maxLength  default 60
 */
function truncate(string $text, int $maxLength = 60): string
{
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    return mb_substr($text, 0, $maxLength - 3) . '...';
}

/**
 * Escape output HTML untuk keamanan.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Mendapatkan URL gambar pertama produk (aman untuk string tunggal maupun JSON array).
 */
function getProductImage(?string $imageField): string
{
    if (empty($imageField)) {
        return 'assets/images/placeholder.png';
    }
    if (strpos($imageField, '[') === 0) {
        $decoded = json_decode($imageField, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded[0];
        }
    }
    return $imageField;
}

/**
 * Mendapatkan seluruh daftar URL gambar produk.
 */
function getProductAllImages(?string $imageField): array
{
    if (empty($imageField)) {
        return ['assets/images/placeholder.png'];
    }
    if (strpos($imageField, '[') === 0) {
        $decoded = json_decode($imageField, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }
    return [$imageField];
}
