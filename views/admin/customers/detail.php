<?php
/**
 * YasIX Store - Customer Account Terminal View
 */
$adminSession = \App\Helpers\Session::get('user');
$currentAdminId = (int)($adminSession['id'] ?? 0);
$isSelf = ((int)$customer['id'] === $currentAdminId);
$isAdminAccount = (strtoupper($customer['role_name'] ?? '') === 'ADMIN');
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
        <a href="/admin/customers" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
          <i class="fa-solid fa-arrow-left"></i> Back to Directory
        </a>
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0;">
          CUSTOMER <span style="color: var(--neon-cyan);"><?= e($customer['name']) ?></span>
        </h1>
      </div>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Registered Account UID #<?= (int)$customer['id'] ?> | Joined <?= date('F j, Y', strtotime($customer['created_at'])) ?>
      </p>
    </div>

    <div>
      <?php if ($customer['status'] === 'ACTIVE'): ?>
        <span class="admin-badge admin-badge-success" style="padding: 8px 16px; font-size: 0.9rem;">
          <i class="fa-solid fa-circle-check"></i> ACTIVE ACCOUNT
        </span>
      <?php else: ?>
        <span class="admin-badge admin-badge-danger" style="padding: 8px 16px; font-size: 0.9rem;">
          <i class="fa-solid fa-ban"></i> <?= e($customer['status']) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Metrics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Total Purchases</div>
    <div style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff;">
      <?= (int)($customer['total_orders'] ?? count($customer['orders'] ?? [])) ?>
    </div>
  </div>

  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Total Spend (Lifetime)</div>
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 800; color: var(--neon-green);">
      LKR <?= number_format((float)($customer['total_spent'] ?? 0), 2) ?>
    </div>
  </div>

  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Warranty RMA Claims</div>
    <div style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--neon-purple);">
      <?= count($customer['claims'] ?? []) ?>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
  <!-- Left Column: Order History & Warranty Claims -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Order History -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-truck-ramp-box"></i> Order Purchase History
        </div>
      </div>
      <?php if (empty($customer['orders'])): ?>
        <div style="padding: 32px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
          This customer has not placed any orders yet.
        </div>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Fulfilment</th>
                <th style="text-align: right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customer['orders'] as $ord): ?>
                <tr>
                  <td>
                    <a href="/admin/orders/<?= urlencode($ord['order_number']) ?>" style="color: var(--neon-cyan); font-family: 'JetBrains Mono', monospace; font-weight: 700; text-decoration: none;">
                      #<?= e($ord['order_number']) ?>
                    </a>
                  </td>
                  <td style="color: var(--text-muted); font-size: 0.85rem; font-family: 'JetBrains Mono', monospace;">
                    <?= date('Y-m-d', strtotime($ord['created_at'])) ?>
                  </td>
                  <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff;">
                    LKR <?= number_format((float)$ord['total_amount'], 2) ?>
                  </td>
                  <td>
                    <span class="admin-badge <?= ($ord['payment_status'] === 'PAID') ? 'admin-badge-success' : 'admin-badge-warning' ?>">
                      <?= e($ord['payment_status']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="admin-badge admin-badge-cyan">
                      <?= e(str_replace('_', ' ', $ord['status'])) ?>
                    </span>
                  </td>
                  <td style="text-align: right;">
                    <a href="/admin/orders/<?= urlencode($ord['order_number']) ?>" class="btn-admin btn-admin-ghost" style="padding: 4px 10px; font-size: 0.75rem;">
                      <i class="fa-solid fa-eye"></i> View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Warranty Claims -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-shield-halved"></i> Warranty Claims History
        </div>
      </div>
      <?php if (empty($customer['claims'])): ?>
        <div style="padding: 32px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
          No warranty claims logged for this customer.
        </div>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Claim #</th>
                <th>Order Ref</th>
                <th>Product Hardware</th>
                <th>Reason</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customer['claims'] as $cl): ?>
                <tr>
                  <td>
                    <a href="/admin/warranties/<?= (int)$cl['id'] ?>" style="color: var(--neon-purple); font-family: 'JetBrains Mono', monospace; font-weight: 700; text-decoration: none;">
                      <?= e($cl['claim_number']) ?>
                    </a>
                  </td>
                  <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;">
                    #<?= e($cl['order_number']) ?>
                  </td>
                  <td style="color: #fff; font-weight: 600;">
                    <?= e($cl['product_name']) ?>
                  </td>
                  <td style="font-size: 0.85rem; color: var(--text-muted);">
                    <?= e($cl['claim_reason']) ?>
                  </td>
                  <td>
                    <span class="admin-badge admin-badge-cyan">
                      <?= e($cl['status']) ?>
                    </span>
                  </td>
                  <td style="text-align: right;">
                    <a href="/admin/warranties/<?= (int)$cl['id'] ?>" class="btn-admin btn-admin-ghost" style="padding: 4px 10px; font-size: 0.75rem;">
                      <i class="fa-solid fa-eye"></i> View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Profile & Security Controls -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Account Information -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-address-card"></i> Account Dossier
        </div>
      </div>
      <div style="padding: 20px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 12px;">
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Full Name:</span>
          <strong style="color: #fff; font-size: 1rem;"><?= e($customer['name']) ?></strong>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Email Address:</span>
          <a href="mailto:<?= e($customer['email']) ?>" style="color: var(--neon-cyan); text-decoration: none;">
            <?= e($customer['email']) ?>
          </a>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Contact Phone:</span>
          <span style="color: #fff; font-family: 'JetBrains Mono', monospace;">
            <?= e($customer['phone'] ?? 'Unspecified') ?>
          </span>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Security Role:</span>
          <span class="admin-badge <?= $isAdminAccount ? 'admin-badge-purple' : 'admin-badge-muted' ?>">
            <?= e($customer['role_name'] ?? 'CUSTOMER') ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Security & Status Management -->
    <div class="admin-card" style="border-color: rgba(255, 0, 85, 0.3);">
      <div class="admin-card-header" style="background: rgba(255, 0, 85, 0.05);">
        <div class="admin-card-title" style="color: var(--neon-crimson);">
          <i class="fa-solid fa-user-lock"></i> Account Access Security
        </div>
      </div>
      <div style="padding: 20px;">
        <?php if ($isSelf): ?>
          <div style="padding: 12px; background: rgba(255, 0, 85, 0.08); border: 1px solid rgba(255, 0, 85, 0.3); border-radius: 6px; font-size: 0.85rem; color: #ff80a0;">
            <i class="fa-solid fa-shield-halved"></i> <strong>Protection Active:</strong> You cannot suspend or deactivate your own active administrative session.
          </div>
        <?php elseif ($isAdminAccount): ?>
          <div style="padding: 12px; background: rgba(184, 41, 247, 0.08); border: 1px solid rgba(184, 41, 247, 0.3); border-radius: 6px; font-size: 0.85rem; color: #d8b4fe;">
            <i class="fa-solid fa-lock"></i> <strong>Admin Privilege Shield:</strong> Administrative accounts cannot be modified or suspended through the customer management terminal.
          </div>
        <?php else: ?>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 16px;">
            Suspending this account immediately prevents the customer from logging into the store or placing future orders.
          </p>

          <form method="POST" action="/admin/customers/<?= (int)$customer['id'] ?>/toggle-status">
            <?= csrf_field() ?>

            <?php if ($customer['status'] === 'ACTIVE'): ?>
              <input type="hidden" name="status" value="SUSPENDED">
              <button type="submit" class="btn-admin btn-admin-danger" style="width: 100%; justify-content: center;" onclick="return confirm('Suspend customer account for <?= e($customer['name']) ?>?');">
                <i class="fa-solid fa-ban"></i> Suspend Customer Account
              </button>
            <?php else: ?>
              <input type="hidden" name="status" value="ACTIVE">
              <button type="submit" class="btn-admin btn-admin-cyan" style="width: 100%; justify-content: center;" onclick="return confirm('Reactivate customer account for <?= e($customer['name']) ?>?');">
                <i class="fa-solid fa-unlock"></i> Reactivate Customer Account
              </button>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
