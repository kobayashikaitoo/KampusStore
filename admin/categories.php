<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/admin.php';
require_once __DIR__ . '/../config/db.php';

requireAdmin();

$db  = getDB();
$msg = $_SESSION['admin_msg'] ?? null;
$err = $_SESSION['admin_err'] ?? null;
unset($_SESSION['admin_msg'], $_SESSION['admin_err']);

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if (!$name || !$slug) {
                $_SESSION['admin_err'] = 'Nama dan slug wajib diisi.';
                header('Location: ' . BASE_URL . 'admin/categories.php#add');
                exit;
            }

            // Check duplicate slug
            $check = $db->prepare('SELECT id FROM categories WHERE slug = ?');
            $check->execute([$slug]);
            if ($check->fetch()) {
                $_SESSION['admin_err'] = 'Slug sudah ada. Gunakan slug yang berbeda.';
                header('Location: ' . BASE_URL . 'admin/categories.php#add');
                exit;
            }

            // Get max order_index
            $maxOrder = $db->query('SELECT MAX(order_index) as max_order FROM categories')->fetchColumn() ?? 0;

            $stmt = $db->prepare('
                INSERT INTO categories (name, slug, description, order_index, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$name, $slug, $desc, $maxOrder + 1]);

            adminLog('ADD_CATEGORY', 'categories', $db->lastInsertId());
            $_SESSION['admin_msg'] = 'Kategori berhasil ditambahkan.';
            break;

        case 'edit':
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if (!$catId || !$name || !$slug) {
                $_SESSION['admin_err'] = 'Data tidak valid.';
                header('Location: ' . BASE_URL . 'admin/categories.php');
                exit;
            }

            // Check duplicate slug (excluding current)
            $check = $db->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
            $check->execute([$slug, $catId]);
            if ($check->fetch()) {
                $_SESSION['admin_err'] = 'Slug sudah ada. Gunakan slug yang berbeda.';
                header('Location: ' . BASE_URL . 'admin/categories.php');
                exit;
            }

            $stmt = $db->prepare('
                UPDATE categories
                SET name = ?, slug = ?, description = ?
                WHERE id = ?
            ');
            $stmt->execute([$name, $slug, $desc, $catId]);

            adminLog('EDIT_CATEGORY', 'categories', $catId);
            $_SESSION['admin_msg'] = 'Kategori berhasil diperbarui.';
            break;

        case 'delete':
            $catId = (int)($_POST['category_id'] ?? 0);

            if (!$catId) {
                $_SESSION['admin_err'] = 'Kategori tidak ditemukan.';
                header('Location: ' . BASE_URL . 'admin/categories.php');
                exit;
            }

            // Check if category has products
            $check = $db->prepare('SELECT COUNT(*) as cnt FROM products WHERE category_id = ?');
            $check->execute([$catId]);
            $result = $check->fetch();
            if ($result['cnt'] > 0) {
                $_SESSION['admin_err'] = 'Tidak dapat menghapus kategori yang masih memiliki ' . $result['cnt'] . ' produk. Pindahkan atau hapus produk terlebih dahulu.';
                header('Location: ' . BASE_URL . 'admin/categories.php');
                exit;
            }

            $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$catId]);
            adminLog('DELETE_CATEGORY', 'categories', $catId);
            $_SESSION['admin_msg'] = 'Kategori berhasil dihapus.';
            break;

        case 'reorder':
            $categories = $_POST['categories'] ?? [];
            foreach ($categories as $idx => $catId) {
                $catId = (int)$catId;
                if ($catId > 0) {
                    $db->prepare('UPDATE categories SET order_index = ? WHERE id = ?')
                        ->execute([$idx + 1, $catId]);
                }
            }
            adminLog('REORDER_CATEGORIES', 'categories', 0);
            $_SESSION['admin_msg'] = 'Urutan kategori berhasil diperbarui.';
            break;
    }

    header('Location: ' . BASE_URL . 'admin/categories.php');
    exit;
}

// ── Query categories ────────────────────────────────────────
$categories = $db->query('SELECT * FROM categories ORDER BY order_index ASC')->fetchAll();

$pageTitle = 'Kelola Kategori';
require_once __DIR__ . '/layout_header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Add Category Section -->
<div class="admin-card" id="add">
  <div class="admin-card-header">
    <span class="admin-card-title"><i class="fas fa-plus"></i> Tambah Kategori Baru</span>
  </div>
  <div style="padding:20px;border-bottom:1px solid var(--hairline)">
    <form method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end">
      <div>
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">Nama Kategori *</label>
        <input type="text" name="name" required placeholder="cth: Laptop" 
               style="width:100%;padding:10px 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px;transition:border-color .2s"
               onfocus="this.style.borderColor='var(--primary)'" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor=''"/>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">Slug *</label>
        <input type="text" name="slug" required placeholder="cth: laptop" 
               style="width:100%;padding:10px 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px;transition:border-color .2s"
               pattern="[a-z0-9-]+" title="Hanya huruf kecil, angka, dan tanda hubung"
               onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor=''"/>
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">Deskripsi (opsional)</label>
        <input type="text" name="description" placeholder="cth: Laptop dan komputer" 
               style="width:100%;padding:10px 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px;transition:border-color .2s"
               onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor=''"/>
      </div>
      <div></div>
      <div></div>
      <button type="submit" name="action" value="add" class="btn-primary-sm" style="justify-self:start">
        <i class="fas fa-plus"></i> Tambah Kategori
      </button>
    </form>
  </div>
</div>

<!-- Categories List -->
<div class="admin-card">
  <div class="admin-card-header">
    <span class="admin-card-title"><i class="fas fa-layer-group"></i> Daftar Kategori (<?= count($categories) ?>)</span>
  </div>
  
  <?php if (empty($categories)): ?>
    <div style="padding:60px 20px;text-align:center;color:var(--muted)">
      <div style="font-size:48px;margin-bottom:12px"><i class="fas fa-inbox"></i></div>
      <p>Belum ada kategori. Buat kategori pertama Anda di atas.</p>
    </div>
  <?php else: ?>
    <form method="POST" id="reorder-form">
      <input type="hidden" name="action" value="reorder"/>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:40px">Order</th>
              <th>Nama</th>
              <th>Slug</th>
              <th>Deskripsi</th>
              <th>Produk</th>
              <th style="width:200px">Aksi</th>
            </tr>
          </thead>
          <tbody id="categories-tbody">
            <?php foreach ($categories as $cat):
              $prodCount = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
              $prodCount->execute([$cat['id']]);
              $count = $prodCount->fetchColumn();
            ?>
              <tr draggable="true" class="category-row" data-id="<?= $cat['id'] ?>" style="cursor:grab">
                <td style="text-align:center;color:var(--muted)">
                  <i class="fas fa-grip-vertical" style="opacity:0.5"></i>
                  <input type="hidden" name="categories[]" value="<?= $cat['id'] ?>"/>
                </td>
                <td>
                  <strong><?= htmlspecialchars($cat['name']) ?></strong>
                </td>
                <td>
                  <code style="background:var(--surface);padding:4px 8px;border-radius:6px;font-size:12px">
                    <?= htmlspecialchars($cat['slug']) ?>
                  </code>
                </td>
                <td>
                  <span style="color:var(--body);font-size:13px">
                    <?= htmlspecialchars($cat['description'] ?? '-') ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge-active" style="background:var(--primary-light);color:var(--primary)">
                    <?= $count ?> produk
                  </span>
                </td>
                <td>
                  <button type="button" class="btn-action btn-view" onclick="editCategory(<?= $cat['id'] ?>, <?= htmlspecialchars(json_encode($cat)) ?>)" title="Edit">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <?php if ($count == 0): ?>
                    <button type="button" class="btn-action btn-delete" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')" title="Hapus">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  <?php else: ?>
                    <span title="Tidak bisa dihapus, masih ada <?= $count ?> produk" style="opacity:0.5;cursor:not-allowed;display:inline-block;padding:5px 12px;color:var(--muted);font-size:12px">
                      <i class="fas fa-lock"></i> Terkunci
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="padding:16px;border-top:1px solid var(--hairline);text-align:right">
        <button type="submit" class="btn-primary-sm">
          <i class="fas fa-save"></i> Simpan Urutan
        </button>
      </div>
    </form>
  <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:20px">
  <div style="background:white;border-radius:16px;padding:28px;max-width:500px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.3)">
    <h2 style="font-size:18px;font-weight:700;color:var(--ink);margin-bottom:20px">
      <i class="fas fa-edit"></i> Edit Kategori
    </h2>
    <form method="POST">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="category_id" id="edit-cat-id"/>
      
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">Nama Kategori</label>
        <input type="text" id="edit-name" name="name" required
               style="width:100%;padding:10px 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px"/>
      </div>

      <div style="margin-bottom:16px">
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">Slug</label>
        <input type="text" id="edit-slug" name="slug" required
               style="width:100%;padding:10px 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px"
               pattern="[a-z0-9-]+" title="Hanya huruf kecil, angka, dan tanda hubung"/>
      </div>

      <div style="margin-bottom:20px">
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px">Deskripsi</label>
        <input type="text" id="edit-desc" name="description"
               style="width:100%;padding:10px 12px;border:1.5px solid var(--hairline);border-radius:10px;font-family:inherit;font-size:14px"/>
      </div>

      <div style="display:flex;gap:10px">
        <button type="submit" class="btn-primary-sm" style="flex:1">
          <i class="fas fa-save"></i> Simpan Perubahan
        </button>
        <button type="button" onclick="closeEditModal()" class="btn-primary-sm" style="flex:1;background:var(--surface);color:var(--ink);border:1.5px solid var(--hairline)">
          Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px">
  <div style="background:white;border-radius:16px;padding:28px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.3)">
    <h2 style="font-size:18px;font-weight:700;color:var(--ink);margin-bottom:8px">
      <i class="fas fa-trash" style="color:#ef4444"></i> Hapus Kategori?
    </h2>
    <p id="delete-msg" style="font-size:14px;color:var(--body);margin-bottom:20px">
      Kategori <strong id="delete-name"></strong> akan dihapus secara permanen.
    </p>
    <div style="display:flex;gap:10px">
      <form method="POST" style="flex:1">
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="category_id" id="delete-cat-id"/>
        <button type="submit" class="btn-action btn-delete" style="width:100%;padding:10px;justify-content:center;border:none">
          <i class="fas fa-trash"></i> Ya, Hapus
        </button>
      </form>
      <button type="button" onclick="closeDeleteModal()" class="btn-primary-sm" style="flex:1;background:var(--surface);color:var(--ink);border:1.5px solid var(--hairline)">
        Batal
      </button>
    </div>
  </div>
</div>

<script>
// ── Edit Modal ────────────────────────────────────────────────
function editCategory(id, cat) {
  document.getElementById('edit-cat-id').value = id;
  document.getElementById('edit-name').value = cat.name;
  document.getElementById('edit-slug').value = cat.slug;
  document.getElementById('edit-desc').value = cat.description || '';
  document.getElementById('edit-modal').style.display = 'flex';
}
function closeEditModal() {
  document.getElementById('edit-modal').style.display = 'none';
}

// ── Delete Modal ───────────────────────────────────────────────
function deleteCategory(id, name) {
  document.getElementById('delete-cat-id').value = id;
  document.getElementById('delete-name').textContent = name;
  document.getElementById('delete-modal').style.display = 'flex';
}
function closeDeleteModal() {
  document.getElementById('delete-modal').style.display = 'none';
}

// ── Click outside to close modals ─────────────────────────────
document.getElementById('edit-modal')?.addEventListener('click', (e) => {
  if (e.target.id === 'edit-modal') closeEditModal();
});
document.getElementById('delete-modal')?.addEventListener('click', (e) => {
  if (e.target.id === 'delete-modal') closeDeleteModal();
});

// ── Drag and drop reordering ──────────────────────────────────
let draggedRow = null;
document.querySelectorAll('.category-row').forEach(row => {
  row.addEventListener('dragstart', () => { draggedRow = row; row.style.opacity = '0.5'; });
  row.addEventListener('dragend', () => { draggedRow = null; row.style.opacity = '1'; });
  row.addEventListener('dragover', (e) => {
    e.preventDefault();
    if (draggedRow && draggedRow !== row) {
      const tbody = document.getElementById('categories-tbody');
      const allRows = [...tbody.querySelectorAll('.category-row')];
      const draggedIdx = allRows.indexOf(draggedRow);
      const thisIdx = allRows.indexOf(row);
      if (draggedIdx < thisIdx) {
        row.parentNode.insertBefore(draggedRow, row.nextSibling);
      } else {
        row.parentNode.insertBefore(draggedRow, row);
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
