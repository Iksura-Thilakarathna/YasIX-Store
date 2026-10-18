<?php
/**
 * YasIX Store - COD Payment Settlement Matrix View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        COD PAYMENT <span style="color: var(--neon-gold);">SETTLEMENT MATRIX</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Reconcile cash collected by courier agents, audit remittance timestamps, and verify cash flow.
      </p>
    </div>
    <div>
      <a href="/admin/orders" class="btn-admin btn-admin-cyan">
        <i class="fa-solid fa-truck-ramp-box"></i> View Order Stream
      </a>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/payments" class="admin-filter-bar">
  <div style="flex: 1; min-width: 220px;">
    <input 
      type="text" 
      name="search" 
      class="admin-form-control" 
      placeholder="Search order #, customer name..." 
      value="<?= e($filters['search'] ?? '') ?>"
    >
  </div>

  <div style="min-width: 180px;">
    <select name="payment_status" class="admin-form-control">
      <option value="">All Settlement Statuses</option>
      <option value="PENDING" <?= ($filters['payment_status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING (Cash Due)</option>
      <option value="COMPLETED" <?= ($filters['payment_status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED (Collected)</option>
      <option value="FAILED" <?= ($filters['payment_status'] ?? '') === 'FAILED' ? 'selected' : '' ?>>FAILED (Uncollected)</option>
    </select>
  </div>

  <button type="submit" class="btn-admin btn-admin-ghost">
    <i class="fa-solid fa-filter"></i> Apply Filters
  </button>

  <?php if (!empty($filters['search']) || !empty($filters['payment_status'])): ?>
    <a href="/admin/payments" class="btn-admin btn-admin-ghost" style="color: var(--neon-crimson);">
      <i class="fa-solid fa-xmark"></i> Reset
    </a>
  <?php endif; ?>
</form>

<!-- Payments Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <i class="fa-solid fa-file-invoice-dollar"></i> COD Transactions
    </div>
    <span style="font-size: 0.85rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
      Total: <?= number_format($pagination['total_payments'] ?? 0) ?> records
    </span>
  </div>

  <?php if (empty($payments)): ?>
    <div style="text-align: center; padding: 48px 24px; color: var(--text-muted);">
      <i class="fa-solid fa-receipt" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 16px;"></i>
      <h3 style="font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #fff; margin-bottom: 8px;">No Payment Records Found</h3>
      <p style="font-size: 0.9rem;">Adjust search filters or check customer orders.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Payment Ref</th>
            <th>Order #</th>
            <th>Customer</th>
            <th>Amount Due</th>
            <th>Settlement State</th>
            <th>Collected At</th>
            <th>Admin Auditor</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $pay): ?>
            <?php
              $isPaid = ($pay['status'] === 'COMPLETED');
              $payBadgeClass = match($pay['status']) {
                'COMPLETED' => 'admin-badge-success',
                'PENDING' => 'admin-badge-warning',
                'FAILED' => 'admin-badge-danger',
                default => 'admin-badge-muted'
              };
            ?>
            <tr>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                #PAY-<?= (int)$pay['id'] ?>
              </td>
              <td>
                <a href="/admin/orders/<?= urlencode($pay['order_number']) ?>" style="color: var(--neon-cyan); font-family: 'JetBrains Mono', monospace; font-weight: 700; text-decoration: none;">
                  #<?= e($pay['order_number']) ?>
                </a>
              </td>
              <td>
                <div style="font-weight: 600; color: #fff;"><?= e($pay['customer_name']) ?></div>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: <?= $isPaid ? 'var(--neon-green)' : 'var(--neon-gold)' ?>;">
                LKR <?= number_format((float)$pay['amount'], 2) ?>
              </td>
              <td>
                <span class="admin-badge <?= $payBadgeClass ?>">
                  <?= e($pay['status']) ?>
                </span>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                <?= !empty($pay['collected_at']) ? date('Y-m-d H:i', strtotime($pay['collected_at'])) : '—' ?>
              </td>
              <td style="font-size: 0.85rem; color: var(--text-muted);">
                <?= e($pay['collected_by_admin'] ?? '—') ?>
              </td>
              <td style="text-align: right;">
                <?php if (!$isPaid): ?>
                  <form method="POST" action="/admin/orders/<?= urlencode($pay['order_number']) ?>/collect-payment" style="display: inline-block;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="amount_collected" value="<?= (float)$pay['amount'] ?>">
                    <input type="hidden" name="redirect_to" value="payments">
                    <button type="submit" class="btn-admin btn-admin-gold" style="padding: 4px 10px; font-size: 0.75rem;" onclick="return confirm('Confirm COD cash collection of LKR <?= number_format((float)$pay['amount'], 2) ?> for Order #<?= e($pay['order_number']) ?>?');">
                      <i class="fa-solid fa-coins"></i> Record Collection
                    </button>
                  </form>
                <?php else: ?>
                  <a href="/admin/orders/<?= urlencode($pay['order_number']) ?>" class="btn-admin btn-admin-ghost" style="padding: 4px 10px; font-size: 0.75rem;">
                    <i class="fa-solid fa-eye"></i> View Order
                  </a>
                <?php endif; ?>
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
          Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?> (<?= $pagination['total_payments'] ?> records)
        </span>
        <div style="display: flex; gap: 8px;">
          <?php
            $queryParams = $_GET;
          ?>
          <?php if ($pagination['has_prev']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] - 1; ?>
            <a href="/admin/payments?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              <i class="fa-solid fa-chevron-left"></i> Previous
            </a>
          <?php endif; ?>

          <?php if ($pagination['has_next']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] + 1; ?>
            <a href="/admin/payments?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              Next <i class="fa-solid fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
