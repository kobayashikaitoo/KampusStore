<?php
session_start();
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

requireLogin(); // redirect ke login jika belum login

$user  = currentUser();
$uid   = $_SESSION['user_id'];
$error = $_SESSION['sell_error'] ?? null;
$old   = $_SESSION['sell_old']   ?? [];
unset($_SESSION['sell_error'], $_SESSION['sell_old']);

// Ambil kategori dari DB
$db   = getDB();
$cats = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Check if user has WhatsApp number
$userStmt = $db->prepare('SELECT whatsapp_number FROM users WHERE id = ?');
$userStmt->execute([$uid]);
$userCheck = $userStmt->fetch();
$hasWhatsApp = !empty($userCheck['whatsapp_number']);

// Proses POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (int)preg_replace('/\D/', '', $_POST['price'] ?? '0');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition   = $_POST['condition'] ?? 'good';
    $location    = trim($_POST['location'] ?? '');
    $is_nego     = isset($_POST['is_nego']) ? 1 : 0;

    $old = compact('title','description','price','category_id','condition','location','is_nego');

    // Validasi
    $validConditions = ['like_new','good','fair','used'];
    if (empty($title) || $price <= 0 || !$category_id || !in_array($condition, $validConditions)) {
        $_SESSION['sell_error'] = 'Judul, harga, kategori, dan kondisi wajib diisi.';
        $_SESSION['sell_old']   = $old;
        header('Location: ' . BASE_URL . 'sell.php');
        exit;
    }

    // Handle upload gambar (Maks. 10 gambar)
    $imageJson = null;
    if (!empty($_FILES['image']['name'][0])) {
        $allowed = ['image/jpeg','image/png','image/webp'];
        $imagePaths = [];
        $filesCount = count($_FILES['image']['name']);
        
        if ($filesCount > 10) {
            $_SESSION['sell_error'] = 'Maksimal 10 gambar yang diperbolehkan.';
            $_SESSION['sell_old']   = $old;
            header('Location: ' . BASE_URL . 'sell.php');
            exit;
        }

        for ($i = 0; $i < $filesCount; $i++) {
            if ($_FILES['image']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ftype = mime_content_type($_FILES['image']['tmp_name'][$i]);
            if (!in_array($ftype, $allowed)) {
                $_SESSION['sell_error'] = 'Format gambar harus JPG, PNG, atau WebP.';
                $_SESSION['sell_old']   = $old;
                header('Location: ' . BASE_URL . 'sell.php');
                exit;
            }
            $ext      = pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION);
            $filename = 'prod_' . uniqid() . '_' . $i . '.' . $ext;
            $dest     = __DIR__ . '/assets/images/uploads/' . $filename;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
            move_uploaded_file($_FILES['image']['tmp_name'][$i], $dest);
            $imagePaths[] = 'assets/images/uploads/' . $filename;
        }
        
        if (!empty($imagePaths)) {
            $imageJson = json_encode($imagePaths);
        }
    }

    // Insert ke DB
    $stmt = $db->prepare('
        INSERT INTO products (seller_id, category_id, title, description, price, is_nego, `condition`, location, image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $user['id'], $category_id, $title, $description,
        $price, $is_nego, $condition, $location, $imageJson
    ]);

    $_SESSION['auth_success'] = 'Barang berhasil diposting!';
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Jual Barang — KampusStore</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sell.css"/>
  <style>
    .upload-zone:hover,
    .upload-zone.drag-over{
      border-color:var(--primary);
      background:var(--primary-light);
      box-shadow:0 0 0 4px rgba(37,99,235,0.08);
    }
    .upload-zone input[type=file]{
      position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;
    }
    .upload-icon{
      width:64px;height:64px;border-radius:16px;
      background:white;
      border:1.5px solid #e2e8f0;
      box-shadow:0 2px 8px rgba(0,0,0,0.06);
      display:flex;align-items:center;justify-content:center;
      font-size:28px;
      margin:0 auto 16px;
      transition:transform .2s ease;
    }
    .upload-zone:hover .upload-icon{ transform:scale(1.08) translateY(-2px); }
    .upload-main-text{font-size:15px;font-weight:700;color:var(--ink);margin-bottom:4px}
    .upload-sub-text{font-size:13px;color:var(--muted)}
    .upload-browse{
      display:inline-block;margin-top:12px;
      font-size:13px;font-weight:600;color:var(--primary);
      background:white;border:1.5px solid rgba(37,99,235,0.25);
      padding:6px 16px;border-radius:999px;
      pointer-events:none;
    }
    /* Preview */
    .upload-preview-wrap{
      display:none;
      margin-top:24px;
      position:relative;
    }
    .preview-header{
      display:flex;align-items:center;justify-content:space-between;
      margin-bottom:16px;
      padding-bottom:12px;
      border-bottom:1px solid var(--hairline);
    }
    .preview-title{
      font-size:14px;font-weight:600;color:var(--ink);
      display:flex;align-items:center;gap:8px;
    }
    .image-count-badge{
      display:inline-flex;align-items:center;justify-content:center;
      background:var(--primary);color:white;
      border-radius:50%;width:24px;height:24px;
      font-size:12px;font-weight:700;
    }
    #preview-gallery{
      display:grid;grid-template-columns:repeat(auto-fill, minmax(140px, 1fr));
      gap:12px;margin-bottom:16px;
      padding:12px;background:var(--bg-light);
      border-radius:12px;border:1px solid var(--hairline);
    }
    .preview-item{
      position:relative;aspect-ratio:1;border-radius:10px;
      overflow:hidden;border:1px solid var(--hairline);
      background:white;
    }
    .preview-item img{
      width:100%;height:100%;object-fit:cover;
      transition:transform .2s ease;
    }
    .preview-item:hover img{
      transform:scale(1.05);
    }
    .preview-item-remove{
      position:absolute;top:6px;right:6px;
      width:32px;height:32px;border-radius:50%;
      background:rgba(0,0,0,0.6);color:white;
      border:none;cursor:pointer;font-size:14px;
      display:flex;align-items:center;justify-content:center;
      transition:background .15s;
      opacity:0;
    }
    .preview-item:hover .preview-item-remove{
      opacity:1;
    }
    .preview-item-remove:hover{background:rgba(239,68,68,0.9)}
    .upload-actions{
      display:flex;gap:10px;flex-wrap:wrap;
    }
    .btn-add-more{
      flex:1;min-width:160px;
      display:flex;align-items:center;justify-content:center;gap:6px;
      padding:12px 16px;background:var(--primary);color:white;
      border:none;border-radius:10px;
      font-size:14px;font-weight:600;cursor:pointer;
      transition:background .2s;
    }
    .btn-add-more:hover{background:var(--primary-dark)}
    .btn-remove-all{
      flex:1;min-width:160px;
      display:flex;align-items:center;justify-content:center;gap:6px;
      padding:12px 16px;background:#fef2f2;color:#dc2626;
      border:1.5px solid #fecaca;border-radius:10px;
      font-size:14px;font-weight:600;cursor:pointer;
      transition:background .2s;
    }
    .btn-remove-all:hover{background:#fee2e2}
    .nego-toggle{display:flex;align-items:center;gap:10px}
    .toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0}
    .toggle-switch input{display:none}
    .toggle-slider{
      position:absolute;inset:0;border-radius:999px;
      background:var(--hairline);cursor:pointer;transition:background .2s;
    }
    .toggle-slider::before{
      content:'';position:absolute;width:18px;height:18px;border-radius:50%;
      background:white;top:3px;left:3px;transition:transform .2s;
    }
    .toggle-switch input:checked ~ .toggle-slider{background:var(--primary)}
    .toggle-switch input:checked ~ .toggle-slider::before{transform:translateX(20px)}
    .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px 16px;font-size:14px;color:#dc2626;margin-bottom:20px}
    .btn-submit{
      width:100%;height:52px;background:var(--primary);color:white;
      font-family:inherit;font-size:16px;font-weight:600;
      border:none;border-radius:14px;cursor:pointer;
      transition:background .2s,transform .15s,box-shadow .2s;
    }
    .btn-submit:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 8px 24px rgba(37,99,235,.3)}
    .btn-submit:active{transform:translateY(0)}
    .char-count{font-size:12px;color:var(--muted);text-align:right;margin-top:4px}
  </style>
</head>
<body class="page-container">
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="sell-wrap">
  <h1 class="page-title"><i class="fas fa-box"></i> Posting Barang</h1>
  <p class="page-sub">Isi detail barang yang ingin kamu jual kepada sesama mahasiswa.</p>

  <?php if ($error): ?>
    <div class="alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="sell-card">
    <form method="POST" enctype="multipart/form-data" id="sell-form" <?= !$hasWhatsApp ? 'style="pointer-events:none;opacity:0.5"' : '' ?>>

      <!-- Info Dasar -->
      <div class="form-section">
        <div class="section-label">Informasi Barang</div>
        <div class="form-group">
          <label class="form-label" for="title">Judul Iklan *</label>
          <input type="text" id="title" name="title" class="form-input"
            value="<?= htmlspecialchars($old['title'] ?? '') ?>"
            placeholder="cth: Laptop ASUS VivoBook 14 Intel i5 — Mulus" required maxlength="200"
            oninput="document.getElementById('title-count').textContent=this.value.length+'/200'"/>
          <div class="char-count"><span id="title-count"><?= strlen($old['title'] ?? '') ?>/200</span></div>
        </div>
        <div class="form-group">
          <label class="form-label" for="description">Deskripsi</label>
          <textarea id="description" name="description" class="form-textarea"
            placeholder="Ceritakan kondisi detail, alasan jual, kelengkapan, dll."
            maxlength="2000"
            oninput="document.getElementById('desc-count').textContent=this.value.length+'/2000'"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
          <div class="char-count"><span id="desc-count"><?= strlen($old['description'] ?? '') ?>/2000</span></div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="category_id">Kategori *</label>
            <select id="category_id" name="category_id" class="form-select" required>
              <option value="">Pilih kategori…</option>
              <?php foreach ($cats as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($old['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="location">Lokasi</label>
            <input type="text" id="location" name="location" class="form-input"
              value="<?= htmlspecialchars($old['location'] ?? '') ?>"
              placeholder="cth: Fak. Teknik / Kos Melati"/>
          </div>
        </div>
      </div>

      <!-- Kondisi -->
      <div class="form-section">
        <div class="section-label">Kondisi Barang *</div>
        <div class="condition-grid">
          <?php
            $conditions = [
              'like_new' => ['label'=>'Seperti Baru','emoji'=>'<i class="fas fa-star"></i>'],
              'good'     => ['label'=>'Kondisi Baik','emoji'=>'<i class="fas fa-thumbs-up"></i>'],
              'fair'     => ['label'=>'Cukup Baik','emoji'=>'<i class="fas fa-smile"></i>'],
              'used'     => ['label'=>'Bekas','emoji'=>'<i class="fas fa-box"></i>'],
            ];
            foreach ($conditions as $val => $c):
              $checked = ($old['condition'] ?? 'good') === $val ? 'checked' : '';
          ?>
          <div>
            <input type="radio" id="cond-<?= $val ?>" name="condition" value="<?= $val ?>"
              class="cond-option" <?= $checked ?> required/>
            <label for="cond-<?= $val ?>" class="cond-label">
              <span class="cond-emoji"><?= $c['emoji'] ?></span>
              <?= $c['label'] ?>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Harga -->
      <div class="form-section">
        <div class="section-label">Harga</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="price">Harga (Rp) *</label>
            <div class="price-wrap">
              <span class="price-prefix">Rp</span>
              <input type="number" id="price" name="price" class="form-input price-input"
                value="<?= $old['price'] ?? '' ?>"
                placeholder="0" min="1000" required
                oninput="this.value=this.value.replace(/^0+/,'')"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Harga Nego?</label>
            <div class="nego-toggle" style="margin-top:8px">
              <label class="toggle-switch">
                <input type="checkbox" name="is_nego" id="is_nego" <?= ($old['is_nego'] ?? 1) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
              <span style="font-size:14px;color:var(--body)" id="nego-label">
                <?= ($old['is_nego'] ?? 1) ? 'Harga bisa nego' : 'Harga fix' ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="section-label">Foto Produk (Maksimal 10 Foto)</div>

        <div class="upload-zone" id="upload-zone" onclick="document.getElementById('image').click()">
          <input type="file" id="image" name="image[]"
            accept="image/jpeg,image/png,image/webp"
            onclick="event.stopPropagation()"
            multiple
            onchange="previewImages(this)"/>

          <div id="upload-placeholder">
            <div class="upload-icon"><i class="fas fa-images" style="font-size: 24px;"></i></div>
            <div class="upload-main-text">Drag &amp; drop foto-foto di sini</div>
            <div class="upload-sub-text">atau</div>
            <span class="upload-browse">Pilih hingga 10 foto</span>
            <div class="upload-sub-text" style="margin-top:10px">JPG, PNG, WebP · Maks. 10 gambar (Maks. 5 MB per gambar)</div>
          </div>
        </div>

        <!-- Preview setelah foto dipilih -->
        <div class="upload-preview-wrap" id="upload-preview-wrap" style="display:none;">
          <div class="preview-header">
            <div class="preview-title">
              <i class="fas fa-images" style="font-size:16px;"></i>
              Foto yang dipilih
              <span class="image-count-badge" id="image-count">0</span>
            </div>
          </div>
          <div id="preview-gallery"></div>
          <div class="upload-actions">
            <button type="button" class="btn-add-more" id="btn-add-more" onclick="document.getElementById('image').click(); event.preventDefault();">
              <i class="fas fa-plus"></i> Tambah Foto
            </button>
            <button type="button" class="btn-remove-all" id="btn-remove-all" onclick="removeAllImages(); event.preventDefault();">
              <i class="fas fa-trash"></i> Hapus Semua
            </button>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submit-btn">
        <i class="fas fa-rocket"></i> Posting Barang Sekarang
      </button>
    </form>
  </div>
</div>

<script src="assets/js/main.js" defer></script>
<script>
document.getElementById('is_nego').addEventListener('change', function() {
  document.getElementById('nego-label').textContent = this.checked ? 'Harga bisa nego' : 'Harga fix';
});

// Store untuk menyimpan file yang dipilih dengan ID unik
let selectedFiles = [];
let fileCounter = 0;

function previewImages(input) {
  if (!input.files || input.files.length === 0) return;
  
  // Tambahkan file baru ke selectedFiles
  for (let file of input.files) {
    // Cek ukuran file
    if (file.size > 5 * 1024 * 1024) {
      alert(`File "${file.name}" melebihi ukuran maksimal 5 MB.`);
      continue;
    }
    
    // Cek duplikat
    if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
      continue;
    }
    
    const fileId = fileCounter++;
    selectedFiles.push({ id: fileId, file: file });
  }
  
  // Cek max 10 file
  if (selectedFiles.length > 10) {
    selectedFiles = selectedFiles.slice(0, 10);
    alert('Maksimal 10 gambar. File berlebih telah dihapus.');
  }
  
  // Update file input
  updateFileInput();
  renderPreview();
}

function updateFileInput() {
  const dt = new DataTransfer();
  selectedFiles.forEach(item => dt.items.add(item.file));
  document.getElementById('image').files = dt.files;
}

function renderPreview() {
  const container = document.getElementById('preview-gallery');
  container.innerHTML = '';
  
  selectedFiles.forEach(item => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-item';
      div.dataset.fileId = item.id;
      div.innerHTML = `
        <img src="${e.target.result}" alt="Preview">
        <button type="button" class="preview-item-remove" data-file-id="${item.id}">
          <i class="fas fa-trash"></i>
        </button>
      `;
      container.appendChild(div);
    };
    reader.readAsDataURL(item.file);
  });
  
  // Update counter dan tampilkan preview wrap, sembunyikan upload zone jika ada gambar
  const hasFiles = selectedFiles.length > 0;
  document.getElementById('image-count').textContent = selectedFiles.length;
  document.getElementById('upload-zone').style.display = hasFiles ? 'none' : 'block';
  document.getElementById('upload-placeholder').style.display = hasFiles ? 'none' : 'block';
  document.getElementById('upload-preview-wrap').style.display = hasFiles ? 'block' : 'none';
}

function removeAllImages() {
  document.getElementById('image').value = '';
  selectedFiles = [];
  fileCounter = 0;
  document.getElementById('preview-gallery').innerHTML = '';
  document.getElementById('upload-zone').style.display = 'block';
  document.getElementById('upload-placeholder').style.display = 'block';
  document.getElementById('upload-preview-wrap').style.display = 'none';
}

// Event delegation untuk delete button
document.getElementById('preview-gallery').addEventListener('click', function(evt) {
  if (evt.target.closest('.preview-item-remove')) {
    evt.preventDefault();
    const fileId = parseInt(evt.target.closest('.preview-item-remove').dataset.fileId, 10);
    selectedFiles = selectedFiles.filter(item => item.id !== fileId);
    updateFileInput();
    
    if (selectedFiles.length === 0) {
      removeAllImages();
    } else {
      const element = document.querySelector(`[data-file-id="${fileId}"]`);
      if (element) element.remove();
      document.getElementById('image-count').textContent = selectedFiles.length;
    }
  }
});

// Drag & Drop
const zone = document.getElementById('upload-zone');
zone.addEventListener('dragover', e => { 
  e.preventDefault(); 
  zone.classList.add('drag-over'); 
});

zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));

zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.classList.remove('drag-over');
  if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    previewImages({ files: e.dataTransfer.files });
  }
});

document.getElementById('sell-form').addEventListener('submit', function() {
  const btn = document.getElementById('submit-btn');
  btn.textContent = '⏳ Memposting…';
  btn.disabled = true;
});

<?php if (!$hasWhatsApp): ?>
// Show popup notification if WhatsApp not set
document.addEventListener('DOMContentLoaded', () => {
  showToast('⚠️ Tambahkan nomor WhatsApp di profil Anda untuk mulai berjualan', 'warning', 6000);
});
<?php endif; ?>
</script>
</body>
</html>
