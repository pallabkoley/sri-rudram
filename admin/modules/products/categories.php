<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Product Categories';
$adminPageSub   = 'Manage Siddha Store categories';

/* Handle add / edit / delete / toggle inline */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));
    $action = (string) post('action', '');

    if ($action === 'save') {
        $cid  = (int) post('id', 0);
        $name = trim((string) post('name', ''));
        $slug = trim((string) post('slug', ''));
        $sort = (int) post('sort_order', 0);

        if ($name === '') {
            flash_set('error', 'Category name is required.');
        } else {
            if ($slug === '') $slug = function_exists('slugify') ? slugify($name) : trim(strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)),'-');

            try {
                if ($cid > 0) {
                    /* Check duplicate */
                    $c = $pdo->prepare('SELECT id FROM product_categories WHERE slug = ? AND id <> ?');
                    $c->execute([$slug, $cid]);
                    if ($c->fetch()) {
                        flash_set('error', 'Slug already in use.');
                    } else {
                        $pdo->prepare('UPDATE product_categories SET name = ?, slug = ?, sort_order = ? WHERE id = ?')
                            ->execute([$name, $slug, $sort, $cid]);
                        flash_set('success', 'Category updated.');
                    }
                } else {
                    $c = $pdo->prepare('SELECT id FROM product_categories WHERE slug = ?');
                    $c->execute([$slug]);
                    if ($c->fetch()) {
                        flash_set('error', 'Slug already in use.');
                    } else {
                        $pdo->prepare('INSERT INTO product_categories (name, slug, sort_order) VALUES (?,?,?)')
                            ->execute([$name, $slug, $sort]);
                        flash_set('success', 'Category created.');
                    }
                }
            } catch (PDOException $e) {
                error_log('[prod_categories.save] ' . $e->getMessage());
                flash_set('error', 'Could not save.');
            }
        }
    } elseif ($action === 'delete') {
        $cid = (int) post('id', 0);
        try {
            $c = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $c->execute([$cid]);
            $refs = (int) $c->fetchColumn();
            if ($refs > 0) {
                flash_set('info', "Category has {$refs} product(s) — cannot delete. Reassign them first.");
            } else {
                $pdo->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$cid]);
                flash_set('success', 'Category deleted.');
            }
        } catch (PDOException $e) {
            flash_set('error', 'Delete failed.');
        }
    }
    redirect('admin/modules/products/categories.php');
}

/* Load categories with product counts */
$categories = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count
     FROM product_categories c
     ORDER BY c.sort_order ASC, c.name ASC'
)->fetchAll();

/* Edit mode */
$editing = null;
$editId  = (int) get('edit', 0);
if ($editId > 0) {
    $e = $pdo->prepare('SELECT * FROM product_categories WHERE id = ?');
    $e->execute([$editId]);
    $editing = $e->fetch();
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:24px" class="cat-grid">
  <style>
  @media (max-width:900px){ .cat-grid{grid-template-columns:1fr !important} }
  </style>

  <!-- LEFT: List -->
  <div class="pd-card" style="background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden">
    <div style="padding:16px 22px;border-bottom:1px solid #E5E7EB;font-weight:600;color:#1F2937">
      All Categories
    </div>
    <table class="pd-table" style="width:100%;border-collapse:separate;border-spacing:0">
      <thead>
        <tr>
          <th style="background:#351B2F;color:#F7EBDD;text-align:left;padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">ID</th>
          <th style="background:#351B2F;color:#F7EBDD;text-align:left;padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">Name</th>
          <th style="background:#351B2F;color:#F7EBDD;text-align:left;padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">Slug</th>
          <th style="background:#351B2F;color:#F7EBDD;text-align:left;padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">Products</th>
          <th style="background:#351B2F;color:#F7EBDD;text-align:left;padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">Order</th>
          <th style="background:#351B2F;color:#F7EBDD;text-align:left;padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;width:180px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td style="padding:12px 16px;border-bottom:1px solid #E5E7EB;color:#6B7280;font-weight:600">#<?= (int) $c['id'] ?></td>
            <td style="padding:12px 16px;border-bottom:1px solid #E5E7EB;font-weight:600"><?= e($c['name']) ?></td>
            <td style="padding:12px 16px;border-bottom:1px solid #E5E7EB;color:#6B7280;font-family:monospace;font-size:12px"><?= e($c['slug']) ?></td>
            <td style="padding:12px 16px;border-bottom:1px solid #E5E7EB;font-weight:600"><?= (int) $c['product_count'] ?></td>
            <td style="padding:12px 16px;border-bottom:1px solid #E5E7EB;color:#6B7280"><?= (int) $c['sort_order'] ?></td>
            <td style="padding:12px 16px;border-bottom:1px solid #E5E7EB">
              <div style="display:flex;gap:6px">
                <a href="<?= url('admin/modules/products/categories.php?edit=' . (int) $c['id']) ?>"
                   style="padding:6px 12px;font-size:12px;font-weight:600;border-radius:7px;background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE;text-decoration:none">
                  Edit
                </a>
                <form method="post" style="margin:0"
                      onsubmit="return confirm('Delete this category?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                  <button style="padding:6px 12px;font-size:12px;font-weight:600;border-radius:7px;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;cursor:pointer">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
          <tr><td colspan="6" style="padding:30px;text-align:center;color:#6B7280">No categories yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- RIGHT: Form -->
  <div style="background:#fff;border-radius:14px;border:1px solid #E5E7EB;padding:22px;height:fit-content">
    <h3 style="margin:0 0 18px;font-family:var(--font-serif, Georgia, serif);font-size:20px;color:#351B2F">
      <?= $editing ? 'Edit Category' : 'Add New Category' ?>
    </h3>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">

      <label class="f-label">Name <span class="req">*</span></label>
      <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" class="f-input" required>

      <div style="height:12px"></div>

      <label class="f-label">Slug</label>
      <input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>"
             class="f-input" placeholder="Auto-generate if empty">

      <div style="height:12px"></div>

      <label class="f-label">Sort Order</label>
      <input type="number" name="sort_order" value="<?= (int) ($editing['sort_order'] ?? 0) ?>" class="f-input">

      <div style="display:flex;gap:10px;margin-top:22px">
        <button type="submit" class="form-btn form-btn-save" style="flex:1">
          <i class="bi bi-check2-circle"></i>
          <?= $editing ? 'Update' : 'Create' ?>
        </button>
        <?php if ($editing): ?>
          <a href="<?= url('admin/modules/products/categories.php') ?>" class="form-btn form-btn-cancel">
            Cancel
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>