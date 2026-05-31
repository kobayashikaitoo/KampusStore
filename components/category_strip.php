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

<script>
document.addEventListener('DOMContentLoaded', () => {
  const strip = document.getElementById('cat-strip');
  if (!strip) return;

  // 1. Terjemahkan Scroll Roda Mouse (Scroll wheel vertikal -> Horizontal)
  strip.addEventListener('wheel', (e) => {
    if (strip.scrollWidth > strip.clientWidth) {
      e.preventDefault();
      strip.scrollLeft += e.deltaY * 0.8; // Kecepatan scroll halus
    }
  }, { passive: false });

  // 2. Fitur Drag-to-Scroll (Klik dan Geser dengan Mouse di PC)
  let isDown = false;
  let startX;
  let scrollLeft;

  strip.addEventListener('mousedown', (e) => {
    isDown = true;
    strip.style.cursor = 'grabbing';
    startX = e.pageX - strip.offsetLeft;
    scrollLeft = strip.scrollLeft;
  });

  strip.addEventListener('mouseleave', () => {
    isDown = false;
    strip.style.cursor = 'grab';
  });

  strip.addEventListener('mouseup', () => {
    isDown = false;
    strip.style.cursor = 'grab';
  });

  strip.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - strip.offsetLeft;
    const walk = (x - startX) * 1.5; // Sensitivitas geser
    strip.scrollLeft = scrollLeft - walk;
  });

  // Set kursor awal
  strip.style.cursor = 'grab';
});
</script>

