<?php
/**
 * YasIX Store - Category Administration View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        CATEGORY <span style="color: var(--neon-cyan);">TAXONOMY MANAGER</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Configure hardware classifications, slug routing, and active catalog filters.
      </p>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: flex-start;">
  <!-- Create Category Panel -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-folder-plus"></i> New Taxonomy Node
      </div>
    </div>

    <form action="/admin/categories/create" method="POST">
      <?= \App\Helpers\CSRF::field() ?>

      <div class="admin-form-group">
        <label for="catName">Category Name *</label>
        <input 
          type="text" 
          name="name" 
          id="catName" 
          class="admin-form-control" 
          placeholder="e.g. Quantum Storage Arrays" 
          required
        >
      </div>

      <div class="admin-form-group">
        <label for="catSlug">URL Slug (Leave blank to auto-generate)</label>
        <input 
          type="text" 
          name="slug" 
          id="catSlug" 
          class="admin-form-control" 
          placeholder="e.g. quantum-storage-arrays"
        >
      </div>

      <div class="admin-form-group">
        <label for="catIcon">Icon Class (Font Awesome)</label>
        <input 
          type="text" 
          name="icon_class" 
          id="catIcon" 
          class="admin-form-control" 
          placeholder="fa-solid fa-hard-drive" 
          value="fa-solid fa-folder"
        >
      </div>

      <div class="admin-form-group">
        <label for="catDesc">Description</label>
        <textarea 
          name="description" 
          id="catDesc" 
          class="admin-form-control" 
          rows="3" 
          placeholder="Brief overview of hardware in this category..."
        ></textarea>
      </div>

      <div class="admin-form-group">
        <label for="catStatus">Status</label>
        <select name="status" id="catStatus" class="admin-form-control">
          <option value="ACTIVE">ACTIVE</option>
          <option value="INACTIVE">INACTIVE</option>
        </select>
      </div>

      <button type="submit" class="btn-admin btn-admin-cyan" style="width: 100%; justify-content: center; margin-top: 10px;">
        <i class="fa-solid fa-plus"></i> Create Category
      </button>
    </form>
  </div>

  <!-- Category List -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-tags"></i> Registered Categories (<?= count($categories) ?>)
      </div>
    </div>

    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name & Details</th>
            <th>Slug</th>
            <th>SKU Count</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td style="font-family: 'Share Tech Mono', monospace; font-size: 0.85rem; color: var(--text-dim);">
                #<?= (int)$c['id'] ?>
              </td>
              <td>
                <div style="font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px;">
                  <i class="<?= e($c['icon_class'] ?? 'fa-solid fa-folder') ?>" style="color: var(--neon-cyan);"></i>
                  <?= e($c['name']) ?>
                </div>
                <?php if (!empty($c['description'])): ?>
                  <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 2px;">
                    <?= e(substr($c['description'], 0, 80)) . (strlen($c['description']) > 80 ? '...' : '') ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <code style="color: var(--neon-gold); font-size: 0.85rem;"><?= e($c['slug']) ?></code>
              </td>
              <td>
                <span class="status-pill status-available">
                  <?= (int)($c['product_count'] ?? 0) ?> SKUs
                </span>
              </td>
              <td>
                <span class="status-pill status-<?= strtolower($c['status'] ?? 'ACTIVE') ?>">
                  <?= e($c['status'] ?? 'ACTIVE') ?>
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <!-- Toggle Status -->
                  <form action="/admin/categories/<?= (int)$c['id'] ?>/toggle-status" method="POST" style="display:inline;" onsubmit="return confirm('Change status for category <?= e($c['name']) ?>?');">
                    <?= \App\Helpers\CSRF::field() ?>
                    <?php if (($c['status'] ?? 'ACTIVE') === 'ACTIVE'): ?>
                      <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm" title="Deactivate Category">
                        <i class="fa-solid fa-ban"></i>
                      </button>
                    <?php else: ?>
                      <button type="submit" class="btn-admin btn-admin-sm" style="background: rgba(0, 255, 170, 0.15); color: var(--neon-emerald); border: 1px solid rgba(0, 255, 170, 0.3);" title="Reactivate Category">
                        <i class="fa-solid fa-power-off"></i>
                      </button>
                    <?php endif; ?>
                  </form>

                  <!-- Safe Delete (Enabled only if 0 products) -->
                  <?php if ((int)($c['product_count'] ?? 0) === 0): ?>
                    <form action="/admin/categories/<?= (int)$c['id'] ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('Delete this empty category permanently?');">
                      <?= \App\Helpers\CSRF::field() ?>
                      <button type="submit" class="btn-admin btn-admin-ghost btn-admin-sm" style="color: var(--neon-crimson);" title="Delete Empty Category">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <button class="btn-admin btn-admin-ghost btn-admin-sm" style="opacity: 0.35; cursor: not-allowed;" title="Category has <?= (int)$c['product_count'] ?> products. Cannot delete." disabled>
                      <i class="fa-solid fa-lock"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
