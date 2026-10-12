<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 40px 20px 80px;">
  <!-- PAGE HEADER -->
  <div class="catalog-header">
    <div class="section-subtitle">// FINAL AUTHORIZATION</div>
    <h1 class="auth-title" style="font-size: 2.2rem; margin-bottom: 8px;">CHECKOUT TERMINAL</h1>
    <p style="color: var(--muted); max-width: 600px;">
      Enter your delivery coordinates and confirm Cash on Delivery dispatch across Sri Lanka.
    </p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert-banner alert-error" style="margin-bottom: 24px;">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 30px;" class="checkout-grid-layout">
    <!-- LEFT: SHIPPING FORM -->
    <div>
      <form action="/checkout/place-order" method="POST" id="checkout-form">
        <?= \App\Helpers\CSRF::field() ?>
        <input type="hidden" name="checkout_token" value="<?= e($checkout['checkout_token']) ?>">

        <!-- 1. CUSTOMER IDENTITY -->
        <div class="account-card" style="margin-bottom: 24px;">
          <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            <i class="fa-solid fa-user-shield"></i> 1. RECIPIENT INFORMATION
          </h3>

          <div class="form-group">
            <label for="full_name" class="form-label"><i class="fa-solid fa-user"></i> FULL NAME</label>
            <input 
              type="text" 
              id="full_name" 
              name="full_name" 
              class="cyber-input" 
              value="<?= e($checkout['user']->name ?? '') ?>" 
              required
            >
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label for="phone" class="form-label"><i class="fa-solid fa-phone"></i> CONTACT PHONE</label>
              <input 
                type="text" 
                id="phone" 
                name="phone" 
                class="cyber-input" 
                value="<?= e($checkout['user']->phone ?? '') ?>" 
                placeholder="+94 77 123 4567" 
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-envelope"></i> ACCOUNT EMAIL</label>
              <input 
                type="email" 
                class="cyber-input" 
                value="<?= e($checkout['user']->email ?? '') ?>" 
                readonly
              >
            </div>
          </div>
        </div>

        <!-- 2. DELIVERY COORDINATES -->
        <div class="account-card" style="margin-bottom: 24px;">
          <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            <i class="fa-solid fa-map-location-dot"></i> 2. DELIVERY ADDRESS & REGION
          </h3>

          <div class="form-group">
            <label for="address_line1" class="form-label"><i class="fa-solid fa-location-dot"></i> STREET ADDRESS</label>
            <input 
              type="text" 
              id="address_line1" 
              name="address_line1" 
              class="cyber-input" 
              placeholder="No. 42, Cyber Way, Neural Tower" 
              required
            >
          </div>

          <div class="form-group">
            <label for="address_line2" class="form-label"><i class="fa-solid fa-building"></i> APARTMENT / SUITE / LANDMARK (OPTIONAL)</label>
            <input 
              type="text" 
              id="address_line2" 
              name="address_line2" 
              class="cyber-input" 
              placeholder="Suite 7B"
            >
          </div>

          <div style="display: grid; grid-template-columns: 1.2fr 1.5fr 1fr; gap: 16px;">
            <div class="form-group">
              <label for="city" class="form-label"><i class="fa-solid fa-city"></i> CITY</label>
              <input 
                type="text" 
                id="city" 
                name="city" 
                class="cyber-input" 
                placeholder="Colombo" 
                required
              >
            </div>

            <div class="form-group">
              <label for="delivery_rule_id" class="form-label"><i class="fa-solid fa-truck"></i> DELIVERY REGION</label>
              <select 
                id="delivery_rule_id" 
                name="delivery_rule_id" 
                class="cyber-input" 
                onchange="updateRegionFee(this.value)" 
                required
              >
                <?php foreach ($checkout['delivery_rules'] as $rule): ?>
                  <option value="<?= $rule['id'] ?>" <?= $checkout['selected_rule_id'] == $rule['id'] ? 'selected' : '' ?>>
                    <?= e($rule['region_name']) ?> (Base <?= formatCurrency((float)$rule['base_fee']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="postal_code" class="form-label"><i class="fa-solid fa-mail-bulk"></i> POSTAL CODE</label>
              <input 
                type="text" 
                id="postal_code" 
                name="postal_code" 
                class="cyber-input" 
                placeholder="00500"
              >
            </div>
          </div>
        </div>

        <!-- 3. PAYMENT METHOD -->
        <div class="account-card" style="margin-bottom: 24px;">
          <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
            <i class="fa-solid fa-money-bill-wave"></i> 3. PAYMENT METHOD
          </h3>

          <div style="background: rgba(0, 245, 255, 0.08); border: 1px solid var(--cyan); border-radius: 8px; padding: 16px; display: flex; gap: 14px; align-items: flex-start;">
            <input type="radio" checked style="accent-color: var(--cyan); margin-top: 4px; width: 18px; height: 18px;">
            <div>
              <div style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan); font-size: 1rem;">
                CASH ON DELIVERY (COD)
              </div>
              <p style="color: var(--muted); font-size: 0.9rem; margin-top: 4px; line-height: 1.5;">
                Pay in cash when your overseas hardware package is delivered to your doorstep. No prepayment or credit card required.
              </p>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 1.05rem;">
          CONFIRM CASH ON DELIVERY ORDER <i class="fa-solid fa-shield-check"></i>
        </button>
      </form>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div>
      <div class="account-card" style="position: sticky; top: 90px;">
        <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
          <i class="fa-solid fa-receipt"></i> ORDER BREAKDOWN
        </h3>

        <!-- Cart Items Summary -->
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; max-height: 260px; overflow-y: auto; padding-right: 6px;">
          <?php foreach ($checkout['cart']['items'] as $item): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; border-bottom: 1px solid rgba(255, 255, 255, 0.04); padding-bottom: 8px;">
              <div>
                <div style="font-weight: 600; color: var(--text);"><?= e($item['product_name']) ?></div>
                <div style="font-size: 0.8rem; color: var(--muted);"><?= $item['quantity'] ?> × <?= $item['formatted_price'] ?></div>
              </div>
              <div style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan);">
                <?= e($item['formatted_line_total']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Financial Calculations -->
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
          <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
            <span style="color: var(--muted);">Items Subtotal:</span>
            <span style="font-family: 'Orbitron'; font-weight: 700;"><?= e($checkout['cart']['formatted_subtotal']) ?></span>
          </div>

          <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
            <span style="color: var(--muted);">
              Delivery Fee (<?= e($checkout['delivery_estimate']['region_name'] ?? 'Selected Region') ?>):
            </span>
            <span style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan);">
              <?= e($checkout['formatted_shipping_fee']) ?>
            </span>
          </div>

          <?php if (!empty($checkout['delivery_estimate'])): ?>
            <div style="font-size: 0.8rem; color: var(--muted); background: rgba(3, 7, 18, 0.6); padding: 8px 12px; border-radius: 6px;">
              <div>Chargeable Weight: <strong><?= $checkout['delivery_estimate']['chargeable_weight_kg'] ?> kg</strong></div>
              <div>Estimated Transit: <strong><?= $checkout['delivery_estimate']['estimated_days'] ?></strong></div>
            </div>
          <?php endif; ?>

          <div style="border-top: 1px solid var(--border); padding-top: 14px; display: flex; justify-content: space-between; align-items: baseline;">
            <span style="font-family: 'Orbitron'; font-weight: 800; font-size: 1.05rem;">TOTAL PAYABLE:</span>
            <span style="font-family: 'Orbitron'; font-size: 1.5rem; font-weight: 900; color: var(--cyan); text-shadow: 0 0 10px var(--cyan-glow);">
              <?= e($checkout['formatted_order_total']) ?>
            </span>
          </div>
        </div>

        <div style="font-size: 0.8rem; color: var(--muted); text-align: center; border-top: 1px solid var(--border); padding-top: 12px;">
          <i class="fa-solid fa-shield-halved" style="color: var(--cyan);"></i> 100% Guaranteed Official Hardware Warranty
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function updateRegionFee(ruleId) {
    const url = new URL(window.location.href);
    url.searchParams.set('rule_id', ruleId);
    window.location.href = url.toString();
  }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
