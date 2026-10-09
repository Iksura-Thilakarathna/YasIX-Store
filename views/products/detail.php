<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container product-detail-wrapper" style="padding: 40px 20px 80px;">
  <!-- BREADCRUMBS -->
  <nav class="breadcrumb-nav" style="margin-bottom: 30px; font-family: 'Orbitron'; font-size: 0.85rem; color: var(--muted);">
    <a href="/" style="color: var(--muted); text-decoration: none;">HOME</a> / 
    <a href="/products" style="color: var(--muted); text-decoration: none;">CATALOG</a> / 
    <a href="/products?category=<?= e($product['category_slug']) ?>" style="color: var(--cyan); text-decoration: none;"><?= strtoupper(e($product['category_name'])) ?></a> / 
    <span style="color: var(--text);"><?= e($product['name']) ?></span>
  </nav>

  <!-- PRODUCT DETAIL MAIN GRID -->
  <div class="product-detail-grid">
    <!-- LEFT: Image / Icon Display Box -->
    <div class="product-media-card">
      <div class="product-media-box">
        <i class="fa-solid <?= e($product['category_icon'] ?? 'fa-microchip') ?> media-icon"></i>
        <div class="media-badges">
          <span class="badge-tag"><?= e(strtoupper($product['brand'])) ?></span>
          <span class="badge-condition-pill">
            <?= e($product['condition'] === 'REFURBISHED' ? 'REFURBISHED' : ($product['condition'] === 'CUSTOM_MOD' ? 'CUSTOM MOD' : 'BRAND NEW')) ?>
          </span>
        </div>
      </div>

      <!-- Thumbnail Carousel Placeholder -->
      <div class="thumbnail-list">
        <div class="thumb-item active"><i class="fa-solid <?= e($product['category_icon'] ?? 'fa-microchip') ?>"></i></div>
        <div class="thumb-item"><i class="fa-solid fa-bolt"></i></div>
        <div class="thumb-item"><i class="fa-solid fa-atom"></i></div>
      </div>
    </div>

    <!-- RIGHT: Product Info & Purchase Form -->
    <div class="product-info-card">
      <div class="section-subtitle">// <?= e(strtoupper($product['category_name'])) ?></div>
      <h1 class="auth-title" style="font-size: 2.2rem; text-align: left; margin-bottom: 12px; color: var(--text);">
        <?= e($product['name']) ?>
      </h1>

      <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
        <div class="product-rating" style="margin: 0;">
          ★★★★★ <span style="color: var(--muted); font-size: 0.85rem;">(<?= (int)($product['review_count'] ?? 42) ?> Customer Reviews)</span>
        </div>
        <span style="color: var(--border);">|</span>
        <span style="color: var(--cyan); font-family: 'Orbitron'; font-size: 0.85rem;">
          <i class="fa-solid fa-shield-halved"></i> <?= (int)$product['warranty_months'] ?> Months Warranty
        </span>
      </div>

      <!-- Price & Stock Banner -->
      <div class="price-banner">
        <div class="detail-price"><?= formatCurrency((float)$product['price']) ?></div>
        <?php if ($product['is_available']): ?>
          <div class="stock-badge badge-in-stock">
            <i class="fa-solid fa-circle-check"></i> IN STOCK (<?= (int)$product['stock_quantity'] ?> AVAILABLE)
          </div>
        <?php else: ?>
          <div class="stock-badge badge-out-stock">
            <i class="fa-solid fa-circle-xmark"></i> OUT OF STOCK
          </div>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <div class="detail-description">
        <h3 style="font-family: 'Orbitron'; font-size: 1rem; color: var(--cyan); margin-bottom: 8px;">HARDWARE SPECIFICATIONS SUMMARY</h3>
        <p style="color: var(--text); line-height: 1.7; font-size: 1.05rem;">
          <?= e($product['description']) ?>
        </p>
      </div>

      <!-- Detailed Specifications Table -->
      <div class="specs-box">
        <h4 style="font-family: 'Orbitron'; font-size: 0.9rem; color: var(--muted); margin-bottom: 12px;">TECHNICAL PARAMETERS</h4>
        <div class="specs-grid">
          <div class="spec-row">
            <span class="spec-label">Brand / Manufacturer</span>
            <span class="spec-val"><?= e($product['brand']) ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Condition</span>
            <span class="spec-val"><?= e($product['condition'] === 'REFURBISHED' ? 'Refurbished' : ($product['condition'] === 'CUSTOM_MOD' ? 'Custom Mod' : 'Brand New')) ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Warranty Coverage</span>
            <span class="spec-val"><?= (int)$product['warranty_months'] ?> Months (Matrix Official)</span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Net Unit Weight</span>
            <span class="spec-val"><?= number_format((float)$product['weight'], 2) ?> kg</span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Package Dimensions</span>
            <span class="spec-val"><?= (float)$product['package_length'] ?> × <?= (float)$product['package_width'] ?> × <?= (float)$product['package_height'] ?> cm</span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Product SKU Code</span>
            <span class="spec-val" style="font-family: monospace; color: var(--cyan);"><?= e($product['slug']) ?></span>
          </div>
        </div>
      </div>

      <!-- Purchase & Cart Action Form -->
      <div class="action-box">
        <?php if ($product['is_available']): ?>
          <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <label for="quantity-input" style="font-family: 'Orbitron'; font-size: 0.8rem; color: var(--cyan);">QTY:</label>
              <input 
                type="number" 
                id="quantity-input" 
                class="cyber-input" 
                value="1" 
                min="1" 
                max="<?= (int)$product['stock_quantity'] ?>"
                style="width: 70px; text-align: center; font-weight: bold;"
              >
            </div>

            <button 
              class="btn-primary" 
              style="flex: 1; justify-content: center; padding: 14px 24px;"
              onclick="addQuantityToCart('<?= e($product['name']) ?>', <?= (float)$product['price'] ?>)"
            >
              ADD TO CART <i class="fa-solid fa-cart-shopping"></i>
            </button>
          </div>
        <?php else: ?>
          <div class="alert-banner alert-error" style="margin: 0;">
            <i class="fa-solid fa-bell"></i>
            <span>This product is currently out of stock. Pre-orders will open when stock arrives.</span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RELATED PRODUCTS SECTION -->
  <?php if (!empty($product['related_products'])): ?>
    <div style="margin-top: 80px;">
      <div class="section-header" style="text-align: left; margin-bottom: 30px;">
        <div class="section-subtitle">// COMPATIBLE HARDWARE</div>
        <h2 class="section-title" style="font-size: 1.6rem;">RELATED MATRIX RIGS & GEAR</h2>
      </div>

      <div class="products-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
        <?php foreach ($product['related_products'] as $rel): ?>
          <div class="product-card">
            <div class="product-tag"><?= e(strtoupper($rel['brand'] ?? 'YasIX')) ?></div>
            <div class="product-img-box">
              <i class="fa-solid fa-microchip product-icon"></i>
            </div>
            <h3 class="product-title">
              <a href="/products/<?= e($rel['slug']) ?>" style="color: var(--text); text-decoration: none;">
                <?= e($rel['name']) ?>
              </a>
            </h3>
            <p class="product-desc" style="font-size: 0.85rem; height: 36px; overflow: hidden;"><?= e($rel['description']) ?></p>
            <div class="product-footer">
              <div class="product-price"><?= formatCurrency((float)$rel['price']) ?></div>
              <a href="/products/<?= e($rel['slug']) ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                VIEW <i class="fa-solid fa-arrow-right"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  function addQuantityToCart(name, price) {
    const qtyInput = document.getElementById('quantity-input');
    const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
    for (let i = 0; i < qty; i++) {
      addToCart(name, price);
    }
  }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
