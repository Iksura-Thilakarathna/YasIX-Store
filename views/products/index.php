<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container catalog-wrapper" style="padding: 40px 20px 80px;">
  <!-- PAGE HEADER & SEARCH BANNER -->
  <div class="catalog-header">
    <div>
      <div class="section-subtitle">// HARDWARE MATRIX</div>
      <h1 class="auth-title" style="font-size: 2.2rem; margin-bottom: 8px;">STORE CATALOG</h1>
      <p style="color: var(--muted); max-width: 600px;">
        Browse next-generation liquid-cooled workstation rigs, neural audio headsets, and ultra-high refresh cyber gear.
      </p>
    </div>
  </div>

  <div class="catalog-layout">
    <!-- SIDEBAR FILTERS -->
    <aside class="catalog-sidebar">
      <div class="filter-card">
        <h3 class="filter-title">
          <i class="fa-solid fa-filter" style="color: var(--cyan);"></i> PARAMETER FILTERS
        </h3>

        <form action="/products" method="GET" id="catalog-filter-form">
          <!-- Preserve current sort order -->
          <input type="hidden" name="sort" value="<?= e($catalog['filters']['sort']) ?>">

          <!-- Search Keyword -->
          <div class="form-group">
            <label for="q" class="form-label"><i class="fa-solid fa-magnifying-glass"></i> KEYWORD SEARCH</label>
            <input 
              type="text" 
              id="q" 
              name="q" 
              class="cyber-input" 
              placeholder="e.g. RTX 5090, Headset, Intel..." 
              value="<?= e($catalog['filters']['search']) ?>"
            >
          </div>

          <!-- Category -->
          <div class="form-group">
            <label for="category" class="form-label"><i class="fa-solid fa-layer-group"></i> CATEGORY</label>
            <select id="category" name="category" class="cyber-input">
              <option value="">All Categories</option>
              <?php foreach ($catalog['categories'] as $cat): ?>
                <option value="<?= e($cat['slug']) ?>" <?= $catalog['filters']['category'] === $cat['slug'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Brand -->
          <div class="form-group">
            <label for="brand" class="form-label"><i class="fa-solid fa-industry"></i> BRAND</label>
            <select id="brand" name="brand" class="cyber-input">
              <option value="">All Brands</option>
              <?php foreach ($catalog['brands'] as $brand): ?>
                <option value="<?= e($brand) ?>" <?= strtolower($catalog['filters']['brand']) === strtolower($brand) ? 'selected' : '' ?>>
                  <?= e($brand) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Price Range -->
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-tags"></i> PRICE RANGE ($)</label>
            <div style="display: flex; gap: 10px;">
              <input 
                type="number" 
                name="min_price" 
                class="cyber-input" 
                placeholder="Min" 
                value="<?= e($catalog['filters']['min_price'] ?? '') ?>"
                min="0"
                step="10"
              >
              <input 
                type="number" 
                name="max_price" 
                class="cyber-input" 
                placeholder="Max" 
                value="<?= e($catalog['filters']['max_price'] ?? '') ?>"
                min="0"
                step="10"
              >
            </div>
          </div>

          <!-- Condition -->
          <div class="form-group">
            <label for="condition" class="form-label"><i class="fa-solid fa-certificate"></i> CONDITION</label>
            <select id="condition" name="condition" class="cyber-input">
              <option value="">All Conditions</option>
              <option value="NEW" <?= $catalog['filters']['condition'] === 'NEW' ? 'selected' : '' ?>>Brand New</option>
              <option value="REFURBISHED" <?= $catalog['filters']['condition'] === 'REFURBISHED' ? 'selected' : '' ?>>Refurbished</option>
              <option value="CUSTOM_MOD" <?= $catalog['filters']['condition'] === 'CUSTOM_MOD' ? 'selected' : '' ?>>Custom Mod</option>
            </select>
          </div>

          <!-- Warranty -->
          <div class="form-group">
            <label for="warranty" class="form-label"><i class="fa-solid fa-shield-halved"></i> MIN WARRANTY</label>
            <select id="warranty" name="warranty" class="cyber-input">
              <option value="">Any Duration</option>
              <option value="12" <?= (int)$catalog['filters']['warranty'] === 12 ? 'selected' : '' ?>>12+ Months (1 Yr)</option>
              <option value="24" <?= (int)$catalog['filters']['warranty'] === 24 ? 'selected' : '' ?>>24+ Months (2 Yrs)</option>
              <option value="36" <?= (int)$catalog['filters']['warranty'] === 36 ? 'selected' : '' ?>>36+ Months (3 Yrs)</option>
            </select>
          </div>

          <!-- In-Stock Checkbox -->
          <div class="form-group" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input 
              type="checkbox" 
              id="in_stock" 
              name="in_stock" 
              value="1" 
              style="width: 18px; height: 18px; accent-color: var(--cyan); cursor: pointer;"
              <?= !empty($catalog['filters']['in_stock']) ? 'checked' : '' ?>
            >
            <label for="in_stock" style="color: var(--text); font-size: 0.95rem; cursor: pointer; user-select: none;">
              IN-STOCK ONLY
            </label>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; padding: 10px;">
              APPLY <i class="fa-solid fa-sliders"></i>
            </button>
            <a href="/products" class="btn-secondary" style="justify-content: center; padding: 10px;">RESET</a>
          </div>
        </form>
      </div>
    </aside>

    <!-- MAIN CATALOG CONTENT -->
    <main class="catalog-main">
      <!-- CONTROL BAR: Results Count & Sorting -->
      <div class="catalog-controls">
        <div style="color: var(--muted); font-family: 'Orbitron'; font-size: 0.85rem;">
          SHOWING <span style="color: var(--cyan); font-weight: 700;"><?= count($catalog['products']) ?></span> OF <span style="color: var(--text); font-weight: 700;"><?= $catalog['pagination']['total_products'] ?></span> HARDWARE ITEMS
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
          <label for="sort-select" style="font-family: 'Orbitron'; font-size: 0.8rem; color: var(--cyan);">SORT BY:</label>
          <select id="sort-select" class="cyber-input" style="width: auto; padding: 8px 12px;" onchange="updateSort(this.value)">
            <option value="newest" <?= $catalog['filters']['sort'] === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
            <option value="price_asc" <?= $catalog['filters']['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $catalog['filters']['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name_asc" <?= $catalog['filters']['sort'] === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
          </select>
        </div>
      </div>

      <!-- PRODUCT GRID -->
      <?php if (!empty($catalog['products'])): ?>
        <div class="products-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
          <?php foreach ($catalog['products'] as $item): ?>
            <div class="product-card">
              <div class="product-tag">
                <?= e(strtoupper($item['brand'] ?? 'YasIX')) ?>
              </div>

              <div class="product-img-box" style="position: relative;">
                <i class="fa-solid <?= e($item['category_icon'] ?? 'fa-microchip') ?> product-icon"></i>
                <span class="badge-condition-pill" style="position: absolute; top: 10px; right: 10px;">
                  <?= e($item['condition'] === 'REFURBISHED' ? 'REFURBISHED' : ($item['condition'] === 'CUSTOM_MOD' ? 'CUSTOM MOD' : 'NEW')) ?>
                </span>
              </div>

              <h3 class="product-title" style="margin-top: 10px;">
                <a href="/products/<?= e($item['slug']) ?>" style="color: var(--text); text-decoration: none;">
                  <?= e($item['name']) ?>
                </a>
              </h3>

              <p class="product-desc" style="font-size: 0.85rem; height: 38px; overflow: hidden;">
                <?= e($item['description']) ?>
              </p>

              <div style="display: flex; gap: 8px; font-size: 0.75rem; margin: 10px 0;">
                <span style="color: var(--cyan); background: rgba(0, 245, 255, 0.1); padding: 2px 8px; border-radius: 4px;">
                  <i class="fa-solid fa-shield-halved"></i> <?= (int)$item['warranty_months'] ?>M Warranty
                </span>
                <?php if ($item['is_available']): ?>
                  <span style="color: #00ff88; background: rgba(0, 255, 136, 0.1); padding: 2px 8px; border-radius: 4px;">
                    <i class="fa-solid fa-circle-check"></i> In Stock (<?= (int)$item['stock_quantity'] ?>)
                  </span>
                <?php else: ?>
                  <span style="color: var(--neon-pink); background: rgba(255, 0, 85, 0.1); padding: 2px 8px; border-radius: 4px;">
                    <i class="fa-solid fa-circle-xmark"></i> Out of Stock
                  </span>
                <?php endif; ?>
              </div>

              <div class="product-footer" style="margin-top: 15px;">
                <div class="product-price"><?= formatCurrency((float)$item['price']) ?></div>
                <div style="display: flex; gap: 6px;">
                  <a href="/products/<?= e($item['slug']) ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                    VIEW <i class="fa-solid fa-arrow-right"></i>
                  </a>
                  <?php if ($item['is_available']): ?>
                    <form action="/cart/add" method="POST" style="margin: 0; display: inline;">
                      <?= \App\Helpers\CSRF::field() ?>
                      <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="quantity" value="1">
                      <button type="submit" class="btn-add-cart" style="padding: 6px 10px;" title="Add to Cart">
                        <i class="fa-solid fa-cart-plus"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <button class="btn-add-cart" disabled style="opacity: 0.4; cursor: not-allowed; padding: 6px 10px;" title="Out of Stock">
                      <i class="fa-solid fa-ban"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- PAGINATION BAR -->
        <?php if ($catalog['pagination']['total_pages'] > 1): ?>
          <div class="pagination-wrapper">
            <?php
              $queryParams = $_GET;
              $currentPage = $catalog['pagination']['current_page'];
              $totalPages = $catalog['pagination']['total_pages'];

              function buildPageUrl($page, $params) {
                  $params['page'] = $page;
                  return '/products?' . http_build_query($params);
              }
            ?>

            <?php if ($catalog['pagination']['has_prev']): ?>
              <a href="<?= buildPageUrl($currentPage - 1, $queryParams) ?>" class="page-link">
                <i class="fa-solid fa-chevron-left"></i> PREV
              </a>
            <?php else: ?>
              <span class="page-link disabled"><i class="fa-solid fa-chevron-left"></i> PREV</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="<?= buildPageUrl($i, $queryParams) ?>" class="page-link <?= $i === $currentPage ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if ($catalog['pagination']['has_next']): ?>
              <a href="<?= buildPageUrl($currentPage + 1, $queryParams) ?>" class="page-link">
                NEXT <i class="fa-solid fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <span class="page-link disabled">NEXT <i class="fa-solid fa-chevron-right"></i></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- EMPTY STATE -->
        <div class="auth-card" style="max-width: 100%; text-align: center; padding: 60px 20px;">
          <div style="font-size: 3.5rem; color: var(--neon-pink); margin-bottom: 16px;">
            <i class="fa-solid fa-ghost"></i>
          </div>
          <h2 style="font-family: 'Orbitron'; color: var(--text); font-size: 1.5rem; margin-bottom: 10px;">
            NO CYBER HARDWARE MATCHES YOUR PARAMETERS
          </h2>
          <p style="color: var(--muted); max-width: 480px; margin: 0 auto 24px;">
            We couldn't find any products matching your specific query or filter parameters. Try clearing some criteria.
          </p>
          <a href="/products" class="btn-primary" style="display: inline-flex; justify-content: center;">
            CLEAR ALL FILTERS <i class="fa-solid fa-arrows-rotate"></i>
          </a>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<script>
  function updateSort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
  }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
