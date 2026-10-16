<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container account-wrapper" style="padding-top: 40px; padding-bottom: 80px;">
  <!-- PAGE HEADER -->
  <div class="account-header">
    <div>
      <div class="section-subtitle">// DISPATCH REGISTRY</div>
      <h1 class="auth-title" style="margin-bottom: 4px;">MY ORDER HISTORY</h1>
      <p style="color: var(--muted); font-size: 0.95rem;">
        Track past purchases, review purchase-time specifications, and monitor live courier delivery stages.
      </p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <a href="/account" class="btn-secondary" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="fa-solid fa-user-gear"></i> USER TERMINAL
      </a>
      <a href="/products" class="btn-primary" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="fa-solid fa-microchip"></i> BROWSE HARDWARE
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

  <!-- ORDER LIST OR EMPTY STATE -->
  <?php if (empty($orders)): ?>
    <div class="account-card" style="text-align: center; padding: 60px 20px; border: 1px dashed var(--border);">
      <div style="font-size: 3.5rem; color: var(--muted); margin-bottom: 16px;">
        <i class="fa-solid fa-box-open"></i>
      </div>
      <h3 style="font-family: 'Orbitron'; font-size: 1.3rem; margin-bottom: 10px; color: var(--text);">
        NO ORDERS RECORDED IN MATRIX
      </h3>
      <p style="color: var(--muted); max-width: 480px; margin: 0 auto 24px; line-height: 1.6; font-size: 0.95rem;">
        You haven't placed any orders yet. Discover our latest neural hardware, GPUs, and cyber peripherals.
      </p>
      <a href="/products" class="btn-primary" style="padding: 12px 28px;">
        EXPLORE STORE CATALOG <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
      <?php foreach ($orders as $order): ?>
        <div class="account-card" style="padding: 24px; transition: transform 0.2s, border-color 0.2s;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 16px;">
            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron'; letter-spacing: 1px; margin-bottom: 4px;">
                ORDER ID
              </div>
              <a href="/orders/<?= urlencode($order['order_number']) ?>" style="font-family: 'Orbitron'; font-size: 1.25rem; font-weight: 800; color: var(--cyan); text-decoration: none; letter-spacing: 0.5px;">
                <?= e($order['order_number']) ?>
              </a>
              <div style="font-size: 0.85rem; color: var(--muted); margin-top: 4px;">
                <i class="fa-regular fa-calendar-days"></i> Placed on <?= e($order['formatted_date']) ?>
              </div>
            </div>

            <!-- Status Badges -->
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
              <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-family: 'Orbitron'; font-size: 0.8rem; font-weight: 700; <?= $order['status_meta']['badge_style'] ?>">
                <i class="fa-solid <?= $order['status_meta']['icon'] ?>"></i> <?= e($order['status_meta']['label']) ?>
              </span>

              <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; <?= $order['payment_meta']['badge_style'] ?>">
                <i class="fa-solid <?= $order['payment_meta']['icon'] ?>"></i> <?= e($order['payment_meta']['label']) ?>
              </span>
            </div>
          </div>

          <!-- Order Summary Line -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">PAYMENT METHOD</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
                <i class="fa-solid fa-truck-ramp-box" style="color: var(--cyan); margin-right: 4px;"></i> 
                Cash on Delivery (COD)
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">HARDWARE UNITS</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
                <?= (int)$order['total_units'] ?> unit(s) across <?= (int)$order['total_items'] ?> item(s)
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">TOTAL AMOUNT</div>
              <div style="font-family: 'Orbitron'; font-size: 1.25rem; font-weight: 800; color: var(--cyan);">
                <?= e($order['formatted_total']) ?>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div style="display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 16px;">
            <a href="/orders/<?= urlencode($order['order_number']) ?>" class="btn-secondary" style="padding: 8px 18px; font-size: 0.85rem;">
              <i class="fa-solid fa-receipt"></i> VIEW SPECIFICATIONS
            </a>
            <a href="/orders/<?= urlencode($order['order_number']) ?>/track" class="btn-primary" style="padding: 8px 18px; font-size: 0.85rem;">
              <i class="fa-solid fa-radar"></i> TRACK DISPATCH <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- PAGINATION -->
      <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
          <?php if ($page > 1): ?>
            <a href="/orders?page=<?= $page - 1 ?>" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
              <i class="fa-solid fa-angle-left"></i> PREVIOUS
            </a>
          <?php endif; ?>

          <span style="display: inline-flex; align-items: center; padding: 8px 16px; font-family: 'Orbitron'; font-size: 0.85rem; color: var(--muted);">
            PAGE <?= $page ?> OF <?= $total_pages ?> (<?= $total ?> TOTAL ORDERS)
          </span>

          <?php if ($page < $total_pages): ?>
            <a href="/orders?page=<?= $page + 1 ?>" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
              NEXT <i class="fa-solid fa-angle-right"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
