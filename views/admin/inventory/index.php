<?php
/**
 * YasIX Store - Inventory & Stock Adjustment View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        WAREHOUSE <span style="color: var(--neon-cyan);">STOCK ALLOCATION</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Audit warehouse inventory levels, trigger explicit restocks, and review adjustment records.
      </p>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <!-- Left Column: Master SKU Stock Table with Quick Adjustment -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-boxes-stacked"></i> Real-Time Stock Ledger
      </div>
      <span style="font-size: 0.85rem; color: var(--text-dim);">
        Showing <?= count($products) ?> items
      </span>
    </div>

    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>SKU</th>
            <th>Current Stock</th>
            <th>Level Indicator</th>
            <th style="min-width: 240px;">Adjust Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $prod): ?>
            <tr>
              <td>
                <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">
                  <?= e($prod['name']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-dim);">
                  ID #<?= (int)$prod['id'] ?> &bull; <?= e($prod['brand']) ?> &bull; Unit: <?= formatCurrency($prod['price']) ?>
                </div>
              </td>
              <td style="font-family: 'Orbitron', sans-serif; font-size: 1.15rem; font-weight: 700; color: #fff;">
                <?= (int)$prod['stock_quantity'] ?>
              </td>
              <td>
                <?php if ((int)$prod['stock_quantity'] === 0): ?>
                  <span class="status-pill status-out_of_stock">
                    <i class="fa-solid fa-ban"></i> 0 DEPLETED
                  </span>
                <?php elseif ((int)$prod['stock_quantity'] <= 5): ?>
                  <span class="status-pill status-pending">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= (int)$prod['stock_quantity'] ?> LOW
                  </span>
                <?php else: ?>
                  <span class="status-pill status-available">
                    <i class="fa-solid fa-circle-check"></i> <?= (int)$prod['stock_quantity'] ?> NOMINAL
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <form action="/admin/inventory/adjust" method="POST" style="display: flex; gap: 6px; align-items: center;" onsubmit="return confirm('Apply inventory stock adjustment for this product?');">
                  <?= \App\Helpers\CSRF::field() ?>
                  <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                  
                  <input 
                    type="number" 
                    name="new_stock" 
                    min="0" 
                    class="admin-form-control" 
                    style="width: 75px; padding: 6px 8px; text-align: center; font-weight: 700;" 
                    value="<?= (int)$prod['stock_quantity'] ?>" 
                    required
                  >

                  <input 
                    type="text" 
                    name="reason" 
                    class="admin-form-control" 
                    style="flex: 1; padding: 6px 8px; font-size: 0.8rem;" 
                    placeholder="Audit reason..." 
                    required
                  >

                  <button type="submit" class="btn-admin btn-admin-cyan btn-admin-sm" title="Apply Stock Adjustment">
                    <i class="fa-solid fa-check"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right Column: Audit Logs Feed -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-clipboard-check"></i> Adjustment Audit Feed
      </div>
    </div>

    <?php if (empty($logs)): ?>
      <div class="admin-empty-state">
        <i class="fa-solid fa-file-invoice"></i>
        <h3>No Adjustment Logs</h3>
        <p>Stock corrections and audit reasons will appear here.</p>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 14px; max-height: 600px; overflow-y: auto; padding-right: 6px;">
        <?php foreach ($logs as $log): ?>
          <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--admin-border-subtle); border-radius: 8px; padding: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
              <strong style="color: #fff; font-size: 0.88rem;"><?= e($log['product_name']) ?></strong>
              <span style="font-family: 'Share Tech Mono', monospace; font-size: 0.85rem; font-weight: 700; color: <?= (int)$log['change_quantity'] >= 0 ? 'var(--neon-emerald)' : 'var(--neon-crimson)' ?>;">
                <?= (int)$log['change_quantity'] >= 0 ? '+' : '' ?><?= (int)$log['change_quantity'] ?> units
              </span>
            </div>
            
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 6px;">
              Balance: <?= (int)$log['previous_stock'] ?> &rarr; <strong style="color: #fff;"><?= (int)$log['new_stock'] ?></strong>
            </div>

            <div style="font-size: 0.82rem; color: var(--neon-gold); font-style: italic; margin-bottom: 6px;">
              &ldquo;<?= e($log['reason']) ?>&rdquo;
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-dim); border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 6px;">
              <span>By: <?= e($log['user_name'] ?? 'System Administrator') ?></span>
              <span><?= date('M d, H:i', strtotime($log['created_at'])) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
