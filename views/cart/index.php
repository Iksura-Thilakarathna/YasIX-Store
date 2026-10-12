<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 40px 20px 80px;">
  <!-- PAGE HEADER -->
  <div class="catalog-header">
    <div class="section-subtitle">// DEPLOYMENT QUEUE</div>
    <h1 class="auth-title" style="font-size: 2.2rem; margin-bottom: 8px;">SHOPPING CART</h1>
    <p style="color: var(--muted); max-width: 600px;">
      Review your selected quantum gear, neural rigs, and cybernetic accessories before proceeding to checkout.
    </p>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert-banner alert-success" style="margin-bottom: 24px;">
      <i class="fa-solid fa-circle-check"></i>
      <span><?= e($success) ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert-banner alert-error" style="margin-bottom: 24px;">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($cart['is_empty']): ?>
    <!-- EMPTY CART STATE -->
    <div class="auth-card" style="max-width: 100%; text-align: center; padding: 70px 20px;">
      <div style="font-size: 4rem; color: var(--cyan); margin-bottom: 16px;">
        <i class="fa-solid fa-cart-arrow-down"></i>
      </div>
      <h2 style="font-family: 'Orbitron'; color: var(--text); font-size: 1.6rem; margin-bottom: 10px;">
        YOUR CYBER CART IS EMPTY
      </h2>
      <p style="color: var(--muted); max-width: 480px; margin: 0 auto 24px; line-height: 1.6;">
        No quantum hardware or neural rigs have been queued for deployment. Browse our cutting-edge catalog to equip your workstation.
      </p>
      <a href="/products" class="btn-primary" style="display: inline-flex; justify-content: center; padding: 12px 28px;">
        EXPLORE STORE CATALOG <i class="fa-solid fa-rocket"></i>
      </a>
    </div>

  <?php else: ?>
    <!-- ACTIVE CART CONTENT -->
    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 30px;" class="cart-grid-layout">
      <!-- LEFT: CART ITEMS LIST -->
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php foreach ($cart['items'] as $item): ?>
          <div class="account-card" style="display: flex; gap: 20px; align-items: center; padding: 20px; flex-wrap: wrap;">
            <!-- Product Icon / Image -->
            <div style="width: 80px; height: 80px; background: rgba(3, 7, 18, 0.8); border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--cyan); flex-shrink: 0;">
              <i class="fa-solid <?= e($item['category_icon']) ?>"></i>
            </div>

            <!-- Product Info -->
            <div style="flex: 1; min-width: 200px;">
              <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                <span class="badge-tag" style="font-size: 0.65rem;"><?= e(strtoupper($item['brand'])) ?></span>
                <span class="badge-condition-pill" style="font-size: 0.65rem;">
                  <?= e($item['condition'] === 'REFURBISHED' ? 'REFURBISHED' : ($item['condition'] === 'CUSTOM_MOD' ? 'CUSTOM MOD' : 'NEW')) ?>
                </span>
              </div>
              <h3 style="font-family: 'Orbitron'; font-size: 1.05rem; margin-bottom: 6px;">
                <a href="/products/<?= e($item['product_slug']) ?>" style="color: var(--text); text-decoration: none;">
                  <?= e($item['product_name']) ?>
                </a>
              </h3>
              <div style="color: var(--cyan); font-family: 'Orbitron'; font-size: 0.95rem; font-weight: 700;">
                <?= e($item['formatted_price']) ?>
              </div>
              <div style="font-size: 0.8rem; color: var(--muted); margin-top: 4px;">
                <i class="fa-solid fa-shield-halved" style="color: var(--cyan);"></i> <?= $item['warranty_months'] ?>M Warranty |
                Weight: <?= number_format($item['weight'], 2) ?> kg
              </div>

              <?php if (!$item['is_available']): ?>
                <div style="color: var(--neon-pink); font-size: 0.8rem; font-weight: bold; margin-top: 4px;">
                  <i class="fa-solid fa-circle-exclamation"></i> Out of stock or unavailable
                </div>
              <?php endif; ?>
            </div>

            <!-- Quantity Form -->
            <div style="display: flex; align-items: center; gap: 8px;">
              <form action="/cart/update" method="POST" style="display: flex; align-items: center; gap: 6px; margin: 0;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <input 
                  type="number" 
                  name="quantity" 
                  value="<?= $item['quantity'] ?>" 
                  min="1" 
                  max="<?= $item['stock_quantity'] ?>"
                  class="cyber-input" 
                  style="width: 65px; text-align: center; padding: 6px; font-weight: bold;"
                >
                <button type="submit" class="btn-secondary" style="padding: 6px 10px; font-size: 0.75rem;" title="Update Quantity">
                  <i class="fa-solid fa-rotate"></i>
                </button>
              </form>
            </div>

            <!-- Line Total -->
            <div style="min-width: 110px; text-align: right;">
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">LINE TOTAL</div>
              <div style="font-family: 'Orbitron'; font-size: 1.15rem; font-weight: 800; color: var(--cyan);">
                <?= e($item['formatted_line_total']) ?>
              </div>
            </div>

            <!-- Remove Button -->
            <div>
              <form action="/cart/remove" method="POST" style="margin: 0;">
                <?= \App\Helpers\CSRF::field() ?>
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <button type="submit" style="background: none; border: none; color: var(--neon-pink); font-size: 1.1rem; cursor: pointer; padding: 6px;" title="Remove Item">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- CART ACTIONS -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; flex-wrap: wrap; gap: 12px;">
          <a href="/products" class="btn-secondary" style="padding: 8px 18px;">
            <i class="fa-solid fa-arrow-left"></i> CONTINUE SHOPPING
          </a>

          <form action="/cart/clear" method="POST" style="margin: 0;">
            <?= \App\Helpers\CSRF::field() ?>
            <button type="submit" class="btn-logout-link" onclick="return confirm('Clear all items from your cart?');">
              <i class="fa-solid fa-trash-arrow-up"></i> CLEAR ENTIRE CART
            </button>
          </form>
        </div>
      </div>

      <!-- RIGHT: ORDER SUMMARY CARD -->
      <div>
        <div class="account-card" style="position: sticky; top: 90px;">
          <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <i class="fa-solid fa-receipt"></i> ORDER SUMMARY
          </h3>

          <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
              <span style="color: var(--muted);">Queued Units:</span>
              <span style="font-weight: 700; color: var(--text);"><?= $cart['item_count'] ?> items</span>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
              <span style="color: var(--muted);">Cart Subtotal:</span>
              <span style="font-family: 'Orbitron'; font-size: 1.1rem; font-weight: 700; color: var(--text);">
                <?= e($cart['formatted_subtotal']) ?>
              </span>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
              <span style="color: var(--muted);">Estimated Delivery:</span>
              <span style="color: var(--cyan); font-size: 0.85rem; font-family: 'Orbitron';">Calculated at Checkout</span>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 14px; display: flex; justify-content: space-between; align-items: baseline;">
              <span style="font-family: 'Orbitron'; font-weight: 700;">ESTIMATED TOTAL:</span>
              <span style="font-family: 'Orbitron'; font-size: 1.4rem; font-weight: 900; color: var(--cyan); text-shadow: 0 0 10px var(--cyan-glow);">
                <?= e($cart['formatted_subtotal']) ?>
              </span>
            </div>
          </div>

          <div style="background: rgba(0, 245, 255, 0.05); border: 1px solid rgba(0, 245, 255, 0.2); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 0.8rem; color: var(--muted); line-height: 1.5;">
            <div style="color: var(--cyan); font-weight: 700; margin-bottom: 4px;">
              <i class="fa-solid fa-truck-fast"></i> CASH ON DELIVERY (SRI LANKA)
            </div>
            Standard domestic courier delivery within 5–7 working days. Payment collected at your doorstep.
          </div>

          <?php if ($cart['has_unavailable']): ?>
            <div class="alert-banner alert-error" style="font-size: 0.85rem; margin-bottom: 16px;">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>One or more items in your cart are currently out of stock. Please remove them to proceed.</span>
            </div>
            <button class="btn-primary" disabled style="width: 100%; justify-content: center; opacity: 0.5; cursor: not-allowed;">
              PROCEED TO CHECKOUT <i class="fa-solid fa-lock"></i>
            </button>
          <?php else: ?>
            <a href="/checkout" class="btn-primary" style="width: 100%; justify-content: center; text-decoration: none; padding: 14px;">
              PROCEED TO CHECKOUT <i class="fa-solid fa-arrow-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
