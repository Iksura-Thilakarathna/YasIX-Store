<?php
/**
 * YasIX Store - Warranty Claims Administration View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        WARRANTY & RMA <span style="color: var(--neon-purple);">ADJUDICATION</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Review customer warranty claims, investigate hardware defects, make adjudication decisions, and log confidential audit notes.
      </p>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/warranties" class="admin-filter-bar">
  <div style="flex: 1; min-width: 200px;">
    <input 
      type="text" 
      name="search" 
      class="admin-form-control" 
      placeholder="Search claim #, order #, product, customer..." 
      value="<?= e($filters['search'] ?? '') ?>"
    >
  </div>

  <div style="min-width: 170px;">
    <select name="status" class="admin-form-control">
      <option value="">All Claim Statuses</option>
      <option value="SUBMITTED" <?= ($filters['status'] ?? '') === 'SUBMITTED' ? 'selected' : '' ?>>SUBMITTED</option>
      <option value="UNDER_REVIEW" <?= ($filters['status'] ?? '') === 'UNDER_REVIEW' ? 'selected' : '' ?>>UNDER REVIEW</option>
      <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
      <option value="REJECTED" <?= ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
      <option value="RESOLVED" <?= ($filters['status'] ?? '') === 'RESOLVED' ? 'selected' : '' ?>>RESOLVED</option>
      <option value="CLOSED" <?= ($filters['status'] ?? '') === 'CLOSED' ? 'selected' : '' ?>>CLOSED</option>
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
    <i class="fa-solid fa-filter"></i> Apply
  </button>

  <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
    <a href="/admin/warranties" class="btn-admin btn-admin-ghost" style="color: var(--neon-crimson);">
      <i class="fa-solid fa-xmark"></i> Reset
    </a>
  <?php endif; ?>
</form>

<!-- Claims Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <i class="fa-solid fa-file-shield"></i> Warranty Claims Registry
    </div>
    <span style="font-size: 0.85rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
      Total: <?= number_format($pagination['total_claims'] ?? 0) ?> claims
    </span>
  </div>

  <?php if (empty($claims)): ?>
    <div style="text-align: center; padding: 48px 24px; color: var(--text-muted);">
      <i class="fa-solid fa-shield-cat" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 16px;"></i>
      <h3 style="font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #fff; margin-bottom: 8px;">No Warranty Claims Found</h3>
      <p style="font-size: 0.9rem;">Adjust search filters to inspect other claim submissions.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Claim #</th>
            <th>Submitted</th>
            <th>Customer</th>
            <th>Hardware Item</th>
            <th>Order Ref</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Reviewer</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($claims as $c): ?>
            <tr>
              <td>
                <a href="/admin/warranties/<?= (int)$c['id'] ?>" style="color: var(--neon-purple); font-family: 'JetBrains Mono', monospace; font-weight: 700; text-decoration: none;">
                  <?= e($c['claim_number']) ?>
                </a>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                <?= date('Y-m-d H:i', strtotime($c['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight: 600; color: #fff;"><?= e($c['customer_name']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($c['customer_email']) ?></div>
              </td>
              <td style="color: #fff; font-weight: 500;">
                <?= e($c['product_name']) ?>
              </td>
              <td>
                <a href="/admin/orders/<?= urlencode($c['order_number']) ?>" style="color: var(--neon-cyan); font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; text-decoration: none;">
                  #<?= e($c['order_number']) ?>
                </a>
              </td>
              <td style="font-size: 0.85rem; color: var(--text-muted);">
                <?= e($c['claim_reason']) ?>
              </td>
              <td>
                <span class="admin-badge" style="<?= $c['status_meta']['badge_style'] ?? '' ?> font-size: 0.8rem;">
                  <i class="fa-solid <?= $c['status_meta']['icon'] ?? 'fa-circle-dot' ?>"></i>
                  <?= e(str_replace('_', ' ', $c['status'])) ?>
                </span>
              </td>
              <td style="font-size: 0.85rem; color: var(--text-muted);">
                <?= e($c['reviewer_name'] ?? '—') ?>
              </td>
              <td style="text-align: right;">
                <a href="/admin/warranties/<?= (int)$c['id'] ?>" class="btn-admin btn-admin-ghost" style="padding: 4px 10px; font-size: 0.75rem;">
                  <i class="fa-solid fa-gavel"></i> Adjudicate
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
          Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?> (<?= $pagination['total_claims'] ?> records)
        </span>
        <div style="display: flex; gap: 8px;">
          <?php
            $queryParams = $_GET;
          ?>
          <?php if ($pagination['has_prev']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] - 1; ?>
            <a href="/admin/warranties?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              <i class="fa-solid fa-chevron-left"></i> Previous
            </a>
          <?php endif; ?>

          <?php if ($pagination['has_next']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] + 1; ?>
            <a href="/admin/warranties?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              Next <i class="fa-solid fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
