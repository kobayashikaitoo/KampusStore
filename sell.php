<?php
session_start();
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

requireLogin(); // redirect ke login jika belum login

$user  = currentUser();
$error = $_SESSION['sell_error'] ?? null;
$old   = $_SESSION['sell_old']   ?? [];
unset($_SESSION['sell_error'], $_SESSION['sell_old']);

// Ambil kategori dari DB
$db   = getDB();
$cats = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();

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
        header('Location: sell.php');
        exit;
    }

    // Handle upload gambar
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp'];
        $ftype   = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $_SESSION['sell_error'] = 'Format gambar harus JPG, PNG, atau WebP.';
            $_SESSION['sell_old']   = $old;
            header('Location: sell.php');
            exit;
        }
        $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'prod_' . uniqid() . '.' . $ext;
        $dest     = __DIR__ . '/assets/images/uploads/' . $filename;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        move_uploaded_file($_FILES['image']['tmp_name'], $dest);
        $imagePath = 'assets/images/uploads/' . $filename;
    }

    // Insert ke DB
    $stmt = $db->prepare('
        INSERT INTO products (seller_id, category_id, title, description, price, is_nego, `condition`, location, image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $user['id'], $category_id, $title, $description,
        $price, $is_nego, $condition, $location, $imagePath
    ]);

    $_SESSION['auth_success'] = 'Barang berhasil diposting! 🎉';
    header('Location: /index.php');
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
  <link rel="stylesheet" href="assets/css/custom.css"/>
  <style>
    body{background:var(--surface);min-height:100vh;padding-top:68px}
    .sell-wrap{max-width:680px;margin:40px auto;padding:0 24px 80px}
    .sell-card{background:white;border:1px solid var(--hairline);border-radius:24px;padding:40px;box-shadow:0 4px 24px rgba(0,0,0,0.06)}
    @media(max-width:640px){.sell-card{padding:24px;border-radius:16px}}
    .page-title{font-size:24px;font-weight:800;color:var(--ink);margin-bottom:4px;letter-spacing:-0.4px}
    .page-sub{font-size:14px;color:var(--body);margin-bottom:32px}
    .form-section{margin-bottom:28px}
    .section-label{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:0.5px;text-transform:uppercase;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--hairline)}
    .form-group{margin-bottom:18px}
    .form-label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px}
    .form-input,.form-select,.form-textarea{
      width:100%;background:white;border:1.5px solid var(--hairline);
      border-radius:12px;padding:12px 14px;
      font-family:inherit;font-size:15px;color:var(--ink);
      transition:border-color .2s,box-shadow .2s;outline:none;
    }
    .form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
    .form-textarea{resize:vertical;min-height:100px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    @media(max-width:480px){.form-grid{grid-template-columns:1fr}}
    .price-wrap{position:relative}
    .price-prefix{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;font-weight:600;color:var(--body)}
    .price-input{padding-left:42px!important}
    .condition-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
    @media(min-width:480px){.condition-grid{grid-template-columns:repeat(4,1fr)}}
    .cond-option{display:none}
    .cond-label{
      display:flex;flex-direction:column;align-items:center;gap:4px;
      padding:12px 8px;border:1.5px solid var(--hairline);border-radius:12px;
      cursor:pointer;font-size:13px;font-weight:500;color:var(--body);
      transition:all .2s;text-align:center;
    }
    .cond-label:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
    .cond-option:checked + .cond-label{border-color:var(--primary);background:var(--primary-light);color:var(--primary)}
    .cond-emoji{font-size:22px}
    /* ── Upload Drop Zone ── */
    .upload-zone{
      display:block;
      border:2px dashed #cbd5e1;
      border-radius:16px;
      background:#f8fafc;
      padding:40px 24px;
      text-align:center;
      cursor:pointer;
      transition:border-color .25s ease, background .25s ease, box-shadow .25s ease;
      position:relative;
    }
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
      margin-top:16px;
      position:relative;
    }
    .upload-preview-img{
      width:100%;max-height:220px;
      object-fit:cover;
      border-radius:12px;
      border:1px solid var(--hairline);
      display:block;
    }
    .upload-remove{
      position:absolute;top:8px;right:8px;
      width:28px;height:28px;border-radius:50%;
      background:rgba(0,0,0,0.55);color:white;
      border:none;cursor:pointer;font-size:14px;
      display:flex;align-items:center;justify-content:center;
      transition:background .15s;
    }
    .upload-remove:hover{background:rgba(239,68,68,0.85)}
    .upload-file-name{
      font-size:12px;color:var(--body);margin-top:8px;
      text-align:center;
    }
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
<body>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="sell-wrap">
  <h1 class="page-title">📦 Posting Barang</h1>
  <p class="page-sub">Isi detail barang yang ingin kamu jual kepada sesama mahasiswa.</p>

  <?php if ($error): ?>
    <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="sell-card">
    <form method="POST" enctype="multipart/form-data" id="sell-form">

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
                  <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
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
              'like_new' => ['label'=>'Seperti Baru','emoji'=>'✨'],
              'good'     => ['label'=>'Kondisi Baik','emoji'=>'👍'],
              'fair'     => ['label'=>'Cukup Baik','emoji'=>'🙂'],
              'used'     => ['label'=>'Bekas','emoji'=>'📦'],
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

      <!-- Foto -->
      <div class="form-section">
        <div class="section-label">Foto Produk</div>

        <div class="upload-zone" id="upload-zone" onclick="document.getElementById('image').click()">
          <input type="file" id="image" name="image"
            accept="image/jpeg,image/png,image/webp"
            onclick="event.stopPropagation()"
            onchange="previewImage(this)"/>

          <div id="upload-placeholder">
            <div class="upload-icon">📸</div>
            <div class="upload-main-text">Drag &amp; drop foto di sini</div>
            <div class="upload-sub-text">atau</div>
            <span class="upload-browse">Pilih dari galeri</span>
            <div class="upload-sub-text" style="margin-top:10px">JPG, PNG, WebP · Maks. 5 MB</div>
          </div>
        </div>

        <!-- Preview setelah foto dipilih -->
        <div class="upload-preview-wrap" id="upload-preview-wrap">
          <img id="upload-preview-img" class="upload-preview-img" src="" alt="Preview"/>
          <button type="button" class="upload-remove" onclick="removeImage()" title="Hapus foto">✕</button>
          <div class="upload-file-name" id="upload-file-name"></div>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submit-btn">
        🚀 Posting Barang Sekarang
      </button>
    </form>
  </div>
</div>

<script src="assets/js/main.js" defer></script>
<script>
document.getElementById('is_nego').addEventListener('change', function() {
  document.getElementById('nego-label').textContent = this.checked ? 'Harga bisa nego' : 'Harga fix';
});

function previewImage(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];

  // Validasi ukuran 5MB
  if (file.size > 5 * 1024 * 1024) {
    alert('Ukuran file maksimal 5MB.');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('upload-placeholder').style.display    = 'none';
    document.getElementById('upload-preview-wrap').style.display   = 'block';
    document.getElementById('upload-preview-img').src              = e.target.result;
    document.getElementById('upload-file-name').textContent        = `📎 ${file.name} (${(file.size/1024).toFixed(0)} KB)`;
    document.getElementById('upload-zone').style.padding           = '12px';
  };
  reader.readAsDataURL(file);
}

function removeImage() {
  document.getElementById('image').value                          = '';
  document.getElementById('upload-preview-img').src               = '';
  document.getElementById('upload-preview-wrap').style.display    = 'none';
  document.getElementById('upload-placeholder').style.display     = 'block';
  document.getElementById('upload-zone').style.padding            = '';
}

// Drag & Drop
const zone = document.getElementById('upload-zone');
zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.classList.remove('drag-over');
  const dt = e.dataTransfer;
  if (dt.files && dt.files[0]) {
    document.getElementById('image').files = dt.files; // won't work in all browsers
    previewImage({ files: dt.files });
  }
});

document.getElementById('sell-form').addEventListener('submit', function() {
  const btn = document.getElementById('submit-btn');
  btn.textContent = '⏳ Memposting…';
  btn.disabled = true;
});
</script>
</body>
</html>
