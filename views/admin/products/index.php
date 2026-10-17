<?php
/**
 * YasIX Store - Product Administration View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        PRODUCT <span style="color: var(--neon-cyan);">CATALOG CONTROL</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Manage store inventory, warranty snapshots, pricing models, and SKU statuses.
      </p>
    </div>
    <a href="/admin/products/create" class="btn-admin btn-admin-cyan">
      <i class="fa-solid fa-plus"></i> Deploy New Product
    </a>
  </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/products" class="admin-filter-bar">
  <div style="flex: 1; min-width: 220px;">
    <input 
      type="text" 
      name="search" 
      class="admin-form-control" 
      placeholder="Search SKU name, brand, or slug..." 
      value="<?= e($filters['search'] ?? '') ?>"
    >
  </div>

  <div style="min-width: 160px;">
    <select name="category_id" class="admin-form-control">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int)$cat['id'] ?>" <?= (!empty($filters['category_id']) && (int)$filters['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
          <?= e($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div style="min-width: 150px;">
    <select name="stock_status" class="admin-form-control">
      <option value="">All Stock Levels</option>
      <option value="in_stock" <?= ($filters['stock_status'] ?? '') === 'in_stock' ? 'selected' : '' ?>>In Stock (> 5)</option>
      <option value="low_stock" <?= ($filters['stock_status'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock (1-5)</option>
      <option value="out_of_stock" <?= ($filters['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock (0)</option>
    </select>
  </div>

  <div style="min-width: 140px;">
    <select name="status" class="admin-form-control">
      <option value="">All Statuses</option>
      <option value="AVAILABLE" <?= ($filters['status'] ?? '') === 'AVAILABLE' ? 'selected' : '' ?>>AVAILABLE</option>
      <option value="OUT_OF_STOCK" <?= ($filters['status'] ?? '') === 'OUT_OF_STOCK' ? 'selected' : '' ?>>OUT OF STOCK</option>
      <option value="INACTIVE" <?= ($filters['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
      <option value="DISCONTINUED" <?= ($filters['status'] ?? '') === 'DISCONTINUED' ? 'selected' : '' ?>>DISCONTINUED</option>
    </select>
  </div>

  <button type="submit" class="btn-admin btn-admin-ghost">
    <i class="fa-solid fa-filter"></i> Apply
  </button>

  <?php if (!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['stock_status']) || !empty($filters['status'])): ?>
    <a href="/admin/products" class="btn-admin btn-admin-ghost" style="color: var(--neon-crimson);">
      <i class="fa-solid fa-xmark"></i> Reset
    </a>
  <?php endif; ?>
</form>

<!-- Products Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <i class="fa-solid fa-boxes-stacked"></i> Master SKU Matrix
    </div>
    <span style="font-size: 0.85rem; color: var(--text-dim);">
      Showing <?= count($products) ?> of <?= (int)$pagination['total_products'] ?> items
    </span>
  </div>

  <?php if (empty($products)): ?>
    <div class="admin-empty-state">
      <i class="fa-solid fa-box-open"></i>
      <h3>No Products Found</h3>
      <p>No catalog records match the selected filter parameters.</p>
    </div>
  <?php else: ?>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Product Specification</th>
            <th>Category</th>
            <th>Selling Price</th>
            <th>Stock</th>
            <th>Warranty</th>
            <th>Condition</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $prod): ?>
            <tr>
              <td style="font-family: 'Share Tech Mono', monospace; font-size: 0.85rem; color: var(--text-dim);">
                #<?= (int)$prod['id'] ?>
              </td>
              <td>
                <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">
                  <?= e($prod['name']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-dim);">
                  Brand: <strong style="color: var(--neon-cyan);"><?= e($prod['brand']) ?></strong> &bull; Slug: <code><?= e($prod['slug']) ?></code>
                </div>
              </td>
              <td>
                <span style="font-size: 0.85rem; color: var(--text-muted);">
                  <?= e($prod['category_name'] ?? 'Unassigned') ?>
                </span>
              </td>
              <td style="font-weight: 700; color: #fff;">
                <?= formatCurrency($prod['price']) ?>
              </td>
              <td>
                <?php if ((int)$prod['stock_quantity'] === 0): ?>
                  <span class="status-pill status-out_of_stock">0 Out</span>
                <?php elseif ((int)$prod['stock_quantity'] <= 5): ?>
                  <span class="status-pill status-pending"><?= (int)$prod['stock_quantity'] ?> Low</span>
                <?php else: ?>
                  <span class="status-pill status-available"><?= (int)$prod['stock_quantity'] ?> Units</span>
                <?php endif; ?>
              </td>
              <td>
                <span style="font-size: 0.85rem; color: var(--text-dim);">
                  <i class="fa-solid fa-shield-halved" style="color: var(--neon-gold);"></i> <?= (int)$prod['warranty_months'] ?> Mo
                </span>
              </td>
              <td>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">
                  <?= e($prod['condition']) ?>
                </span>
              </td>
              <td>
                <span class="status-pill status-<?= strtolower($prod['status']) ?>">
                  <?= e($prod['status']) ?>
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <a href="/products/<?= urlencode($prod['slug']) ?>" target="_blank" class="btn-admin btn-admin-ghost btn-admin-sm" title="View Storefront">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                  </a>
                  <a href="/admin/products/<?= (int)$prod['id'] ?>/edit" class="btn-admin btn-admin-cyan btn-admin-sm" title="Edit Product">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                  </a>
                  <form action="/admin/products/<?= (int)$prod['id'] ?>/toggle-status" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to change the status of this product?');">
                    <?= \App\Helpers\CSRF::field() ?>
                    <?php if ($prod['status'] === 'INACTIVE' || $prod['status'] === 'DISCONTINUED'): ?>
                      <button type="submit" class="btn-admin btn-admin-sm" style="background: rgba(0, 255, 170, 0.15); color: var(--neon-emerald); border: 1px solid rgba(0, 255, 170, 0.3);" title="Reactivate Product">
                        <i class="fa-solid fa-power-off"></i>
                      </button>
                    <?php else: ?>
                      <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm" title="Deactivate Product (Soft Delete)">
                        <i class="fa-solid fa-ban"></i>
                      </button>
                    <?php endif; ?>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
      <div class="admin-pagination">
        <div style="font-size: 0.82rem; color: var(--text-dim);">
          Page <?= (int)$pagination['current_page'] ?> of <?= (int)$pagination['total_pages'] ?>
        </div>
        <div class="admin-pagination-links">
          <?php if ($pagination['has_prev']): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1])) ?>" class="page-pill">
              &laquo; Prev
            </a>
          <?php endif; ?>

          <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <?php if ($p === 1 || $p === $pagination['total_pages'] || abs($p - $pagination['current_page']) <= 2): ?>
              <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>" class="page-pill <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                <?= $p ?>
              </a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($pagination['has_next']): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1])) ?>" class="page-pill">
              Next &raquo;
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
