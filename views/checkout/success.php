<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 50px 20px 90px; max-width: 800px;">
  <div class="account-card" style="text-align: center; padding: 40px 30px; margin-bottom: 30px;">
    <!-- SUCCESS ICON -->
    <div style="font-size: 4rem; color: #00ff88; margin-bottom: 16px; filter: drop-shadow(0 0 20px rgba(0, 255, 136, 0.4));">
      <i class="fa-solid fa-circle-check"></i>
    </div>

    <div class="section-subtitle">// DISPATCH AUTHORIZED</div>
    <h1 class="auth-title" style="font-size: 2.2rem; margin-bottom: 10px; color: var(--text);">
      ORDER CONFIRMED & QUEUED
    </h1>
    <p style="color: var(--muted); font-size: 1rem; max-width: 540px; margin: 0 auto 20px; line-height: 1.6;">
      Thank you for your order, <strong style="color: var(--cyan);"><?= e($order['customer_name']) ?></strong>! Your cybernetic hardware items have been reserved and scheduled for courier dispatch.
    </p>

    <div style="display: inline-block; background: rgba(0, 245, 255, 0.08); border: 1px dashed var(--cyan); border-radius: 8px; padding: 12px 24px; font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan);">
      TRACKING / ORDER ID: <span style="font-weight: 900; letter-spacing: 1px;"><?= e($order['order_number']) ?></span>
    </div>
  </div>

  <!-- ORDER SUMMARY BREAKDOWN -->
  <div class="account-card" style="padding: 30px;">
    <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <i class="fa-solid fa-receipt"></i> ORDER SPECIFICATIONS & RECEIPT
    </h3>

    <!-- Order Items List -->
    <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
      <?php foreach ($order['items'] as $item): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 10px;">
          <div>
            <div style="font-weight: 700; color: var(--text); font-size: 1rem;"><?= e($item['product_name']) ?></div>
            <div style="font-size: 0.85rem; color: var(--muted);">
              Quantity: <?= (int)$item['quantity'] ?> × <?= formatCurrency((float)$item['unit_price']) ?> (Snapshot)
            </div>
          </div>
          <div style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan); font-size: 1.1rem;">
            <?= formatCurrency((float)$item['subtotal']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Charges Breakdown -->
    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; background: rgba(3, 7, 18, 0.6); padding: 16px; border-radius: 8px;">
      <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
        <span style="color: var(--muted);">Items Subtotal:</span>
        <span style="font-family: 'Orbitron'; font-weight: 700;"><?= formatCurrency((float)$order['total_amount'] - (float)$order['shipping_fee']) ?></span>
      </div>

      <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
        <span style="color: var(--muted);">Delivery Fee (<?= e($order['state'] ?? 'Domestic') ?>):</span>
        <span style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan);"><?= formatCurrency((float)$order['shipping_fee']) ?></span>
      </div>

      <div style="border-top: 1px solid var(--border); padding-top: 10px; display: flex; justify-content: space-between; align-items: baseline;">
        <span style="font-family: 'Orbitron'; font-weight: 800; font-size: 1.1rem;">FINAL TOTAL DUE:</span>
        <span style="font-family: 'Orbitron'; font-size: 1.6rem; font-weight: 900; color: var(--cyan); text-shadow: 0 0 10px var(--cyan-glow);">
          <?= formatCurrency((float)$order['total_amount']) ?>
        </span>
      </div>
    </div>

    <!-- Delivery & Payment Information -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
      <div>
        <h4 style="font-family: 'Orbitron'; font-size: 0.85rem; color: var(--cyan); margin-bottom: 6px;">DELIVERY ADDRESS</h4>
        <p style="color: var(--muted); font-size: 0.9rem; line-height: 1.5;">
          <?= e($order['customer_name']) ?><br>
          <?= e($order['address_line1']) ?><?= !empty($order['address_line2']) ? ', ' . e($order['address_line2']) : '' ?><br>
          <?= e($order['city']) ?>, <?= e($order['state']) ?><?= !empty($order['postal_code']) ? ' ' . e($order['postal_code']) : '' ?><br>
          Phone: <?= e($order['customer_phone']) ?>
        </p>
      </div>

      <div>
        <h4 style="font-family: 'Orbitron'; font-size: 0.85rem; color: var(--cyan); margin-bottom: 6px;">PAYMENT DETAILS</h4>
        <div style="font-size: 0.9rem; color: var(--text);">
          Method: <strong>CASH ON DELIVERY</strong><br>
          Status: <span style="color: #ffd700; font-weight: bold;">PENDING CONFIRMATION</span><br>
          Estimated Delivery: <strong style="color: #00ff88;">5–7 WORKING DAYS</strong>
        </div>
      </div>
    </div>

    <div style="background: rgba(255, 215, 0, 0.08); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; padding: 14px; margin-bottom: 24px; font-size: 0.85rem; color: #ffe680; line-height: 1.5;">
      <i class="fa-solid fa-circle-info"></i> Our courier dispatch division will contact you at <strong><?= e($order['customer_phone']) ?></strong> prior to dispatch. Please have the exact payment of <strong><?= formatCurrency((float)$order['total_amount']) ?></strong> ready upon delivery.
    </div>

    <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
      <a href="/products" class="btn-primary" style="padding: 12px 24px;">
        CONTINUE BROWSING <i class="fa-solid fa-bag-shopping"></i>
      </a>
      <a href="/account" class="btn-secondary" style="padding: 12px 24px;">
        VIEW CUSTOMER TERMINAL <i class="fa-solid fa-user-gear"></i>
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
