<?php
// Ambil kategori dari DB jika belum tersedia
if (!isset($db)) {
    require_once __DIR__ . '/../config/db.php';
    $db = getDB();
}
$categories = $db->query('SELECT * FROM categories ORDER BY id')->fetchAll();
$activeCat  = $_GET['cat']  ?? 'semua';
$activeSort = $_GET['sort'] ?? 'terbaru';
?>
<div class="cat-strip-wrap">
  <div class="ks-container">
    <div class="cat-strip" id="cat-strip">
      <button class="cat-tab <?= $activeCat==='semua' ? 'active' : '' ?>" data-cat="semua">Semua</button>
      <?php foreach ($categories as $cat): ?>
        <button class="cat-tab <?= $activeCat===$cat['slug'] ? 'active' : '' ?>"
          data-cat="<?= htmlspecialchars($cat['slug']) ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>
