<?php
/**
 * YasIX Store - Customer Directory View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        CUSTOMER <span style="color: var(--neon-cyan);">DIRECTORY</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Monitor registered customer accounts, view order histories, and enforce account security policies.
      </p>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<form method="GET" action="/admin/customers" class="admin-filter-bar">
  <div style="flex: 1; min-width: 220px;">
    <input 
      type="text" 
      name="search" 
      class="admin-form-control" 
      placeholder="Search customer name, email, phone..." 
      value="<?= e($filters['search'] ?? '') ?>"
    >
  </div>

  <div style="min-width: 160px;">
    <select name="status" class="admin-form-control">
      <option value="">All Account Statuses</option>
      <option value="ACTIVE" <?= ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
      <option value="SUSPENDED" <?= ($filters['status'] ?? '') === 'SUSPENDED' ? 'selected' : '' ?>>SUSPENDED</option>
      <option value="INACTIVE" <?= ($filters['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
    </select>
  </div>

  <button type="submit" class="btn-admin btn-admin-ghost">
    <i class="fa-solid fa-filter"></i> Apply
  </button>

  <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
    <a href="/admin/customers" class="btn-admin btn-admin-ghost" style="color: var(--neon-crimson);">
      <i class="fa-solid fa-xmark"></i> Reset
    </a>
  <?php endif; ?>
</form>

<!-- Customers Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <i class="fa-solid fa-users"></i> Registered Accounts
    </div>
    <span style="font-size: 0.85rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
      Total: <?= number_format($pagination['total_customers'] ?? 0) ?> accounts
    </span>
  </div>

  <?php if (empty($customers)): ?>
    <div style="text-align: center; padding: 48px 24px; color: var(--text-muted);">
      <i class="fa-solid fa-user-slash" style="font-size: 3rem; color: rgba(255,255,255,0.1); margin-bottom: 16px;"></i>
      <h3 style="font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #fff; margin-bottom: 8px;">No Customers Found</h3>
      <p style="font-size: 0.9rem;">Try adjusting your search criteria.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>UID</th>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Joined</th>
            <th>Total Orders</th>
            <th>Total Spend</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
            <?php
              $statusBadgeClass = match($c['status']) {
                'ACTIVE' => 'admin-badge-success',
                'SUSPENDED' => 'admin-badge-danger',
                default => 'admin-badge-muted'
              };
            ?>
            <tr>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                #<?= (int)$c['id'] ?>
              </td>
              <td>
                <a href="/admin/customers/<?= (int)$c['id'] ?>" style="color: #fff; font-weight: 600; text-decoration: none;">
                  <?= e($c['name']) ?>
                </a>
              </td>
              <td style="color: var(--neon-cyan); font-size: 0.85rem;">
                <?= e($c['email']) ?>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                <?= e($c['phone'] ?? '—') ?>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                <?= date('Y-m-d', strtotime($c['created_at'])) ?>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; color: var(--text-muted);">
                <?= (int)($c['total_orders'] ?? 0) ?>
              </td>
              <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff;">
                LKR <?= number_format((float)($c['total_spent'] ?? 0), 2) ?>
              </td>
              <td>
                <span class="admin-badge <?= $statusBadgeClass ?>">
                  <?= e($c['status']) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="/admin/customers/<?= (int)$c['id'] ?>" class="btn-admin btn-admin-ghost" style="padding: 4px 10px; font-size: 0.75rem;">
                    <i class="fa-solid fa-id-card"></i> Profile
                  </a>
                  <form method="POST" action="/admin/customers/<?= (int)$c['id'] ?>/toggle-status" style="display: inline-block;">
                    <?= csrf_field() ?>
                    <?php if ($c['status'] === 'ACTIVE'): ?>
                      <input type="hidden" name="status" value="SUSPENDED">
                      <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 10px; font-size: 0.75rem;" onclick="return confirm('Suspend customer account for <?= e($c['name']) ?>? They will be locked out of store logins.');">
                        <i class="fa-solid fa-ban"></i> Suspend
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="status" value="ACTIVE">
                      <button type="submit" class="btn-admin btn-admin-cyan" style="padding: 4px 10px; font-size: 0.75rem;" onclick="return confirm('Reactivate customer account for <?= e($c['name']) ?>?');">
                        <i class="fa-solid fa-unlock"></i> Reactivate
                      </button>
                    <?php endif; ?>
                  </form>
                </div>
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
          Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?> (<?= $pagination['total_customers'] ?> accounts)
        </span>
        <div style="display: flex; gap: 8px;">
          <?php
            $queryParams = $_GET;
          ?>
          <?php if ($pagination['has_prev']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] - 1; ?>
            <a href="/admin/customers?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              <i class="fa-solid fa-chevron-left"></i> Previous
            </a>
          <?php endif; ?>

          <?php if ($pagination['has_next']): ?>
            <?php $queryParams['page'] = $pagination['current_page'] + 1; ?>
            <a href="/admin/customers?<?= http_build_query($queryParams) ?>" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
              Next <i class="fa-solid fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
