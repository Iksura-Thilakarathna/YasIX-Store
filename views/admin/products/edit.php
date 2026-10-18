<?php
/**
 * YasIX Store - Edit Product SKU View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        MODIFY SKU <span style="color: var(--neon-cyan);">#<?= (int)$product['id'] ?></span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Update active specifications and pricing. Historical purchase records will remain immutably preserved.
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="/products/<?= urlencode($product['slug']) ?>" target="_blank" class="btn-admin btn-admin-ghost">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Storefront View
      </a>
      <a href="/admin/products" class="btn-admin btn-admin-ghost">
        <i class="fa-solid fa-arrow-left"></i> Catalog List
      </a>
    </div>
  </div>
</div>

<div class="admin-card">
  <form action="/admin/products/<?= (int)$product['id'] ?>/update" method="POST" enctype="multipart/form-data">
    <?= \App\Helpers\CSRF::field() ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
      <!-- Column 1: Core Identification -->
      <div>
        <h3 style="font-family: 'Orbitron', sans-serif; font-size: 1.05rem; color: var(--neon-cyan); margin-bottom: 16px; border-bottom: 1px solid var(--admin-border-subtle); padding-bottom: 8px;">
          <i class="fa-solid fa-tag"></i> Core Identification
        </h3>

        <div class="admin-form-group">
          <label for="pName">Product Name *</label>
          <input 
            type="text" 
            name="name" 
            id="pName" 
            class="admin-form-control" 
            value="<?= e($product['name']) ?>" 
            required
          >
        </div>

        <div class="admin-form-group">
          <label for="pSlug">URL Slug *</label>
          <input 
            type="text" 
            name="slug" 
            id="pSlug" 
            class="admin-form-control" 
            value="<?= e($product['slug']) ?>" 
            required
          >
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="admin-form-group">
            <label for="pBrand">Manufacturer Brand</label>
            <input 
              type="text" 
              name="brand" 
              id="pBrand" 
              class="admin-form-control" 
              value="<?= e($product['brand'] ?? 'YasIX') ?>"
            >
          </div>

          <div class="admin-form-group">
            <label for="pCategory">Category *</label>
            <select name="category_id" id="pCategory" class="admin-form-control" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="admin-form-group">
          <label for="pDesc">Hardware Description & Key Features</label>
          <textarea 
            name="description" 
            id="pDesc" 
            class="admin-form-control" 
            rows="5"
          ><?= e($product['description'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Column 2: Economics, Warranty & Logistics -->
      <div>
        <h3 style="font-family: 'Orbitron', sans-serif; font-size: 1.05rem; color: var(--neon-gold); margin-bottom: 16px; border-bottom: 1px solid var(--admin-border-subtle); padding-bottom: 8px;">
          <i class="fa-solid fa-coins"></i> Economics & Specifications
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="admin-form-group">
            <label for="pPrice">Selling Price (LKR) *</label>
            <input 
              type="number" 
              step="0.01" 
              min="0" 
              name="price" 
              id="pPrice" 
              class="admin-form-control" 
              value="<?= e($product['price']) ?>" 
              required
            >
          </div>

          <div class="admin-form-group">
            <label for="pStock">Available Stock Quantity *</label>
            <input 
              type="number" 
              step="1" 
              min="0" 
              name="stock_quantity" 
              id="pStock" 
              class="admin-form-control" 
              value="<?= (int)$product['stock_quantity'] ?>" 
              required
            >
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="admin-form-group">
            <label for="pWarranty">Warranty Duration (Months)</label>
            <select name="warranty_months" id="pWarranty" class="admin-form-control">
              <option value="0" <?= ((int)$product['warranty_months'] === 0) ? 'selected' : '' ?>>No Warranty (0 Mo)</option>
              <option value="3" <?= ((int)$product['warranty_months'] === 3) ? 'selected' : '' ?>>3 Months</option>
              <option value="6" <?= ((int)$product['warranty_months'] === 6) ? 'selected' : '' ?>>6 Months</option>
              <option value="12" <?= ((int)$product['warranty_months'] === 12) ? 'selected' : '' ?>>12 Months (1 Year)</option>
              <option value="24" <?= ((int)$product['warranty_months'] === 24) ? 'selected' : '' ?>>24 Months (2 Years)</option>
              <option value="36" <?= ((int)$product['warranty_months'] === 36) ? 'selected' : '' ?>>36 Months (3 Years)</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label for="pCondition">Condition</label>
            <select name="condition" id="pCondition" class="admin-form-control">
              <option value="NEW" <?= ($product['condition'] === 'NEW') ? 'selected' : '' ?>>NEW (Factory Sealed)</option>
              <option value="REFURBISHED" <?= ($product['condition'] === 'REFURBISHED') ? 'selected' : '' ?>>REFURBISHED (A-Grade)</option>
              <option value="CUSTOM_MOD" <?= ($product['condition'] === 'CUSTOM_MOD') ? 'selected' : '' ?>>CUSTOM MOD</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="admin-form-group">
            <label for="pStatus">Catalog Status</label>
            <select name="status" id="pStatus" class="admin-form-control">
              <option value="AVAILABLE" <?= ($product['status'] === 'AVAILABLE') ? 'selected' : '' ?>>AVAILABLE</option>
              <option value="OUT_OF_STOCK" <?= ($product['status'] === 'OUT_OF_STOCK') ? 'selected' : '' ?>>OUT OF STOCK</option>
              <option value="INACTIVE" <?= ($product['status'] === 'INACTIVE') ? 'selected' : '' ?>>INACTIVE</option>
              <option value="DISCONTINUED" <?= ($product['status'] === 'DISCONTINUED') ? 'selected' : '' ?>>DISCONTINUED</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label for="pWeight">Gross Weight (KG)</label>
            <input 
              type="number" 
              step="0.01" 
              min="0" 
              name="weight" 
              id="pWeight" 
              class="admin-form-control" 
              value="<?= e($product['weight'] ?? '0.00') ?>"
            >
          </div>
        </div>

        <div class="admin-form-group">
          <label><i class="fa-solid fa-box"></i> Package Dimensions (L &times; W &times; H in CM)</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
            <input type="number" step="0.1" min="0" name="package_length" class="admin-form-control" placeholder="L (cm)" value="<?= e($product['package_length'] ?? '0.0') ?>">
            <input type="number" step="0.1" min="0" name="package_width" class="admin-form-control" placeholder="W (cm)" value="<?= e($product['package_width'] ?? '0.0') ?>">
            <input type="number" step="0.1" min="0" name="package_height" class="admin-form-control" placeholder="H (cm)" value="<?= e($product['package_height'] ?? '0.0') ?>">
          </div>
        </div>

        <div class="admin-form-group">
          <label for="pImage"><i class="fa-solid fa-image"></i> Replace Product Image (JPG, PNG, WEBP max 5MB)</label>
          <input type="file" name="image" id="pImage" class="admin-form-control" accept="image/jpeg,image/png,image/webp">
          <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">Or specify image relative path:</div>
          <input type="text" name="image_path" class="admin-form-control" style="margin-top: 4px;" value="<?= e($product['primary_image'] ?? '') ?>">
        </div>

        <div style="margin-top: 14px;">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" name="featured" value="1" <?= !empty($product['featured']) ? 'checked' : '' ?> style="accent-color: var(--neon-cyan); width: 18px; height: 18px;">
            <span style="font-size: 0.9rem; font-weight: 700; color: #fff;">Feature on Storefront Homepage</span>
          </label>
        </div>
      </div>
    </div>

    <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--admin-border-subtle); display: flex; justify-content: flex-end; gap: 12px;">
      <a href="/admin/products" class="btn-admin btn-admin-ghost">Cancel</a>
      <button type="submit" class="btn-admin btn-admin-cyan">
        <i class="fa-solid fa-floppy-disk"></i> Save Modifications
      </button>
    </div>
  </form>
</div>
