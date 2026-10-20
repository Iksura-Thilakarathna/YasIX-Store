<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 40px 20px 80px; max-width: 900px;">
  <!-- BREADCRUMBS & TOP NAV -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <a href="/orders" style="color: var(--cyan); text-decoration: none; font-size: 0.9rem; font-family: 'Orbitron';">
      <i class="fa-solid fa-arrow-left"></i> BACK TO ORDER HISTORY
    </a>
    <div style="display: flex; gap: 10px;">
      <a href="/orders/<?= urlencode($order['order_number']) ?>/track" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">
        <i class="fa-solid fa-radar"></i> LIVE TRACKING TIMELINE
      </a>
    </div>
  </div>

  <!-- FLASH MESSAGES -->
  <?php if (!empty($success)): ?>
    <div class="alert-banner alert-success">
      <i class="fa-solid fa-circle-check"></i>
      <span><?= e($success) ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert-banner alert-error">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- HEADER CARD -->
  <div class="account-card" style="padding: 28px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
      <div>
        <div class="section-subtitle">// HISTORICAL SPECIFICATION RECEIPT</div>
        <h1 class="auth-title" style="font-size: 1.8rem; margin-bottom: 6px; color: var(--text);">
          ORDER #<?= e($order['order_number']) ?>
        </h1>
        <p style="color: var(--muted); font-size: 0.9rem; margin: 0;">
          Placed on <span style="color: var(--text); font-weight: 600;"><?= e($order['formatted_date']) ?></span>
        </p>
      </div>

      <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-family: 'Orbitron'; font-size: 0.85rem; font-weight: 700; <?= $order['status_meta']['badge_style'] ?>">
          <i class="fa-solid <?= $order['status_meta']['icon'] ?>"></i> <?= e($order['status_meta']['label']) ?>
        </span>

        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; <?= $order['payment_meta']['badge_style'] ?>">
          <i class="fa-solid <?= $order['payment_meta']['icon'] ?>"></i> <?= e($order['payment_meta']['label']) ?>
        </span>
      </div>
    </div>

    <!-- QUICK HIGHLIGHT BAR -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">PAYMENT METHOD</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
          Cash on Delivery (COD)
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">ESTIMATED DELIVERY</div>
        <div style="font-size: 0.95rem; font-weight: 700; color: #00ff88;">
          5–7 Working Days (<?= e($order['estimated_delivery_start']) ?> – <?= e($order['estimated_delivery_end']) ?>)
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">DELIVERY STATUS</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
          <?= !empty($order['delivered_at']) ? 'Delivered on ' . e($order['formatted_delivered_at']) : 'In Transit / Queued' ?>
        </div>
      </div>
    </div>
  </div>

  <!-- PURCHASED PRODUCTS SPECIFICATION (HISTORICAL SNAPSHOT) -->
  <div class="account-card" style="padding: 28px; margin-bottom: 24px;">
    <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <i class="fa-solid fa-microchip"></i> PURCHASED HARDWARE (HISTORICAL PRICE SNAPSHOT)
    </h3>

    <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
      <?php foreach ($order['items'] as $item): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 14px; flex-wrap: wrap; gap: 12px;">
          <div>
            <div style="font-weight: 700; color: var(--text); font-size: 1.05rem;">
              <?= e($item['product_name']) ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--muted); margin-top: 4px;">
              Quantity: <strong style="color: var(--text);"><?= (int)$item['quantity'] ?></strong> × 
              <span style="font-family: 'Orbitron'; color: var(--cyan);"><?= formatCurrency((float)$item['unit_price']) ?></span> 
              <span style="color: var(--muted); font-size: 0.75rem;">(Locked at purchase)</span>
            </div>
            <?php if ((int)$item['warranty_months'] > 0): ?>
              <div style="font-size: 0.8rem; color: #00ff88; margin-top: 4px;">
                <i class="fa-solid fa-shield-halved"></i> Warranty: <?= (int)$item['warranty_months'] ?> Months 
                <?php if (!empty($order['delivered_at'])): ?>
                  (Active - <a href="/warranties" style="color: #00ff88; text-decoration: underline;">View Coverage & Claims</a>)
                <?php else: ?>
                  (Starts upon delivery)
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div style="font-size: 0.8rem; color: var(--muted); margin-top: 4px;">
                <i class="fa-solid fa-shield-xmark"></i> Standard Non-Warranty Accessory
              </div>
            <?php endif; ?>
          </div>

          <div style="text-align: right;">
            <div style="font-family: 'Orbitron'; font-weight: 800; color: var(--cyan); font-size: 1.2rem;">
              <?= formatCurrency((float)$item['subtotal']) ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--muted);">Line Subtotal</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- FINANCIAL BREAKDOWN -->
    <div style="background: rgba(3, 7, 18, 0.7); border: 1px solid var(--border); border-radius: 8px; padding: 20px;">
      <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 10px;">
        <span style="color: var(--muted);">Items Subtotal:</span>
        <span style="font-family: 'Orbitron'; font-weight: 700; color: var(--text);"><?= e($order['formatted_subtotal']) ?></span>
      </div>

      <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 12px;">
        <span style="color: var(--muted);">Delivery Fee (<?= e($order['state'] ?? 'Domestic Hub') ?>):</span>
        <span style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan);"><?= e($order['formatted_shipping_fee']) ?></span>
      </div>

      <div style="border-top: 1px solid var(--border); padding-top: 14px; display: flex; justify-content: space-between; align-items: baseline;">
        <div>
          <span style="font-family: 'Orbitron'; font-weight: 800; font-size: 1.1rem; color: var(--text);">TOTAL CHARGEABLE:</span>
          <div style="font-size: 0.75rem; color: var(--muted);">Cash payment collected upon delivery</div>
        </div>
        <span style="font-family: 'Orbitron'; font-size: 1.7rem; font-weight: 900; color: var(--cyan); text-shadow: 0 0 10px var(--cyan-glow);">
          <?= e($order['formatted_total']) ?>
        </span>
      </div>
    </div>
  </div>

  <!-- ADDRESS & RECIPIENT SNAPSHOT -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Delivery Address Card -->
    <div class="account-card" style="padding: 24px;">
      <h4 style="font-family: 'Orbitron'; font-size: 0.95rem; color: var(--cyan); margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
        <i class="fa-solid fa-location-dot"></i> DELIVERY ADDRESS SNAPSHOT
      </h4>
      <div style="color: var(--text); font-size: 0.95rem; line-height: 1.6;">
        <strong><?= e($order['customer_name']) ?></strong><br>
        <?= e($order['address_line1']) ?><?= !empty($order['address_line2']) ? '<br>' . e($order['address_line2']) : '' ?><br>
        <?= e($order['city']) ?>, <?= e($order['state']) ?><?= !empty($order['postal_code']) ? ' - ' . e($order['postal_code']) : '' ?><br>
        <span style="color: var(--muted); font-size: 0.85rem;"><i class="fa-solid fa-flag"></i> <?= e($order['country'] ?? 'Sri Lanka') ?></span>
      </div>
    </div>

    <!-- Recipient Contact & Courier Directives -->
    <div class="account-card" style="padding: 24px;">
      <h4 style="font-family: 'Orbitron'; font-size: 0.95rem; color: var(--cyan); margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
        <i class="fa-solid fa-phone"></i> CONTACT & COD INSTRUCTIONS
      </h4>
      <div style="font-size: 0.9rem; color: var(--muted); line-height: 1.6;">
        <div>Primary Contact Phone: <strong style="color: var(--text);"><?= e($order['customer_phone']) ?></strong></div>
        <div>Customer Email: <span style="color: var(--text);"><?= e($order['customer_email']) ?></span></div>
        <div style="margin-top: 10px; background: rgba(0, 245, 255, 0.05); padding: 10px; border-radius: 6px; border: 1px solid rgba(0, 245, 255, 0.2);">
          <i class="fa-solid fa-shield-heart" style="color: var(--cyan);"></i> 
          Courier personnel will contact this mobile number prior to dispatching package.
        </div>
      </div>
    </div>
  </div>

  <!-- CUSTOMER CANCELLATION SECTION (IF ELIGIBLE) -->
  <?php if (!empty($order['is_cancellable'])): ?>
    <div class="account-card" style="padding: 24px; border: 1px solid rgba(255, 0, 85, 0.3); background: rgba(255, 0, 85, 0.04); margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
          <h4 style="font-family: 'Orbitron'; font-size: 0.95rem; color: #ff3377; margin-bottom: 6px;">
            <i class="fa-solid fa-ban"></i> ORDER CANCELLATION REQUEST
          </h4>
          <p style="color: var(--muted); font-size: 0.85rem; margin: 0; max-width: 550px;">
            This order is currently in <strong><?= e($order['status_meta']['label']) ?></strong> stage. You may cancel this order before warehouse fulfilment processing begins.
          </p>
        </div>
        <form action="/orders/<?= urlencode($order['order_number']) ?>/cancel" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Hardware stock will be released back to matrix inventory.');">
          <?= \App\Helpers\CSRF::field() ?>
          <input type="hidden" name="reason" value="Cancelled by customer from order details portal">
          <button type="submit" class="btn-primary" style="background: rgba(255, 0, 85, 0.2); border-color: var(--neon-pink); color: #ff3377; padding: 10px 20px; font-size: 0.85rem;">
            CANCEL ORDER <i class="fa-solid fa-xmark"></i>
          </button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- BOTTOM ACTIONS -->
  <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
    <a href="/orders/<?= urlencode($order['order_number']) ?>/track" class="btn-primary" style="padding: 12px 24px;">
      <i class="fa-solid fa-radar"></i> VIEW TRACKING TIMELINE
    </a>
    <a href="/orders" class="btn-secondary" style="padding: 12px 24px;">
      <i class="fa-solid fa-list"></i> BACK TO ALL ORDERS
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
