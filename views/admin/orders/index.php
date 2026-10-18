<?php
/**
 * YasIX Store - Order Fulfilment Matrix View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        ORDER <span style="color: var(--neon-cyan);">FULFILMENT MATRIX</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Monitor order lifecycles, progress delivery stages, verify customer addresses, and track COD settlements.
      </p>
    </div>
    <div>
      <a href="/admin/payments" class="btn-admin btn-admin-gold">
        <i class="fa-solid fa-coins"></i> COD Payment Ledger
      </a>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/orders" class="admin-filter-bar">
  <div style="flex: 1; min-width: 200px;">
    <input 
      type="text" 
      name="search" 
      class="admin-form-control" 
      placeholder="Search order #, customer name..." 
      value="<?= e($filters['search'] ?? '') ?>"
    >
  </div>

  <div style="min-width: 160px;">
    <select name="order_status" class="admin-form-control">
      <option value="">All Fulfilment Stages</option>
      <option value="PENDING" <?= ($filters['order_status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
      <option value="CONFIRMED" <?= ($filters['order_status'] ?? '') === 'CONFIRMED' ? 'selected' : '' ?>>CONFIRMED</option>
      <option value="PROCESSING" <?= ($filters['order_status'] ?? '') === 'PROCESSING' ? 'selected' : '' ?>>PROCESSING</option>
      <option value="SHIPPED" <?= ($filters['order_status'] ?? '') === 'SHIPPED' ? 'selected' : '' ?>>SHIPPED</option>
      <option value="OUT_FOR_DELIVERY" <?= ($filters['order_status'] ?? '') === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>OUT FOR DELIVERY</option>
      <option value="DELIVERED" <?= ($filters['order_status'] ?? '') === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED</option>
      <option value="CANCELLED" <?= ($filters['order_status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED</option>
      <option value="DELIVERY_FAILED" <?= ($filters['order_status'] ?? '') === 'DELIVERY_FAILED' ? 'selected' : '' ?>>DELIVERY FAILED</option>
      <option value="RETURNED" <?= ($filters['order_status'] ?? '') === 'RETURNED' ? 'selected' : '' ?>>RETURNED</option>
    </select>
  </div>

  <div style="min-width: 150px;">
    <select name="payment_status" class="admin-form-control">
      <option value="">All Payment States</option>
      <option value="PENDING" <?= ($filters['payment_status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING (COD Due)</option>
      <option value="PAID" <?= ($filters['payment_status'] ?? '') === 'PAID' ? 'selected' : '' ?>>PAID (Collected)</option>
      <option value="FAILED" <?= ($filters['payment_status'] ?? '') === 'FAILED' ? 'selected' : '' ?>>FAILED</option>
    </select>
  </div>

  <div style="min-width: 130px;">
    <input 
      type="date" 
      name="date_from" 
      class="admin-form-control" 
      value="<?= e($filters['date_from'] ?? '') ?>" 
      title="Date From"
    >
  </div>

  <div style="min-width: 130px;">
    <input 
      type="date" 
      name="date_to" 
      class="admin-form-control" 
      value="<?= e($filters['date_to'] ?? '') ?>" 
      title="Date To"
    >
  </div>

  <button type="submit" class="btn-admin btn-admin-ghost">
    <i class="fa-solid fa-filter"></i> Filter
  </button>

  <?php if (!empty($filters['search']) || !empty($filters['order_status']) || !empty($filters['payment_status']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
    <a href="/admin/orders" class="btn-admin btn-admin-ghost" style="color: var(--neon-crimson);">
      <i class="fa-solid fa-xmark"></i> Reset
    </a>
  <?php endif; ?>
</form>

<!-- Orders Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <i class="fa-solid fa-truck-ramp-box"></i> Order Stream
    </div>
    <span style="font-size: 0.85rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
      Total: <?= number_format($pagination['total_orders'] ?? 0) ?> orders
    </span>
  </div>

  <?php if (empty($orders)): ?>
    <div style="text-align: center; padding: 48px 24px; color: var(--text-muted);">
      <i class="fa-solid fa-box-open" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 16px;"></i>
      <h3 style="font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #fff; margin-bottom: 8px;">No Orders Match Query</h3>
      <p style="font-size: 0.9rem;">Adjust your filter parameters or search terms to inspect other order logs.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Date & Time</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total Amount</th>
            <th>Payment</th>
            <th>Fulfilment Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <?php
              $statusBadgeClass = match($order['status']) {
                'DELIVERED' => 'admin-badge-success',
                'SHIPPED', 'OUT_FOR_DELIVERY' => 'admin-badge-cyan',
                'PROCESSING', 'CONFIRMED' => 'admin-badge-purple',
                'PENDING' => 'admin-badge-warning',
                'CANCELLED', 'DELIVERY_FAILED' => 'admin-badge-danger',
                default => 'admin-badge-muted'
              };

              $paymentBadgeClass = match($order['payment_status']) {
                'PAID' => 'admin-badge-success',
                'PENDING' => 'admin-badge-warning',
                'FAILED' => 'admin-badge-danger',
                default => 'admin-badge-muted'
              };
            ?>
            <tr>
              <td>
                <a href="/admin/orders/<?= urlencode($order['order_number']) ?>" style="color: var(--neon-cyan); font-family: 'JetBrains Mono', monospace; font-weight: 700; text-decoration: none;">
                  #<?= e($order['order_number']) ?>
                </a>
              </td>
              <td style="color: var(--text-muted); font-size: 0.85rem; font-family: 'JetBrains Mono', monospace;">
                <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight: 600; color: #fff;"><?= e($order['customer_name'] ?? 'Guest Customer') ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($order['customer_email'] ?? '') ?></div>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; color: var(--text-muted);">
                <?= (int)($order['item_count'] ?? 1) ?> item(s)
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff;">
                LKR <?= number_format((float)$order['total_amount'], 2) ?>
              </td>
              <td>
                <span class="admin-badge <?= $paymentBadgeClass ?>">
                  <?= e($order['payment_status']) ?>
                </span>
              </td>
              <td>
                <span class="admin-badge <?= $statusBadgeClass ?>">
                  <?= e(str_replace('_', ' ', $order['status'])) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="/admin/orders/<?= urlencode($order['order_number']) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
                  <i class="fa-solid fa-terminal"></i> Terminal
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 12px;">
        <span style="font-size: 0.85rem; color: var(--text-muted);">
          Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?> (<?= $pagination['total_orders'] ?> records)
        </span>
        <div style="display: flex; gap: 8px;">
          <?php
            $queryParams = $_GET;
          ?>
          <?php if ($pagination['has_prev']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] - 1; ?>
            <a href="/admin/orders?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              <i class="fa-solid fa-chevron-left"></i> Previous
            </a>
          <?php endif; ?>

          <?php if ($pagination['has_next']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] + 1; ?>
            <a href="/admin/orders?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              Next <i class="fa-solid fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
