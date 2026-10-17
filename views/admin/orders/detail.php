<?php
/**
 * YasIX Store - Admin Order Detail & Fulfilment Terminal View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
        <a href="/admin/orders" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
          <i class="fa-solid fa-arrow-left"></i> Back to Stream
        </a>
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0;">
          ORDER <span style="color: var(--neon-cyan);">#<?= e($order['order_number']) ?></span>
        </h1>
      </div>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Placed on <?= date('F j, Y - h:i A', strtotime($order['created_at'])) ?> | Internal Order Ref: ID #<?= (int)$order['id'] ?>
      </p>
    </div>

    <div style="display: flex; gap: 12px; align-items: center;">
      <span class="admin-badge" style="<?= $order['status_meta']['badge_style'] ?? '' ?> padding: 8px 16px; font-size: 0.9rem;">
        <i class="fa-solid <?= $order['status_meta']['icon'] ?? 'fa-circle-dot' ?>"></i>
        <?= e(str_replace('_', ' ', $order['status'])) ?>
      </span>
      <span class="admin-badge" style="<?= $order['payment_meta']['badge_style'] ?? '' ?> padding: 8px 16px; font-size: 0.9rem;">
        <i class="fa-solid <?= $order['payment_meta']['icon'] ?? 'fa-coins' ?>"></i>
        <?= e($order['payment_status']) ?>
      </span>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
  <!-- Left Column: Items, Snapshot, and Status History -->
  <div style="display: flex; flexDirection: column; gap: 24px;">
    <!-- Items Purchased -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-microchip"></i> Hardware Manifest (Snapshot)
        </div>
      </div>
      <div style="overflow-x: auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product SKU</th>
              <th>Warranty</th>
              <th style="text-align: right;">Unit Price</th>
              <th style="text-align: center;">Qty</th>
              <th style="text-align: right;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($order['items'] as $item): ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: #fff;"><?= e($item['product_name']) ?></div>
                  <?php if (!empty($item['category_name'])): ?>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                      <i class="<?= e($item['category_icon'] ?? 'fa-solid fa-tag') ?>"></i> <?= e($item['category_name']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((int)$item['warranty_months'] > 0): ?>
                    <span class="admin-badge admin-badge-cyan" style="font-size: 0.75rem;">
                      <i class="fa-solid fa-shield-halved"></i> <?= (int)$item['warranty_months'] ?> Months
                    </span>
                  <?php else: ?>
                    <span class="admin-badge admin-badge-muted" style="font-size: 0.75rem;">None</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: var(--text-muted);">
                  LKR <?= number_format((float)$item['unit_price'], 2) ?>
                </td>
                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff;">
                  <?= (int)$item['quantity'] ?>
                </td>
                <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: var(--neon-cyan);">
                  LKR <?= number_format((float)$item['subtotal'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Financial Totals -->
      <div style="padding: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
        <div style="width: 320px; display: flex; flex-direction: column; gap: 8px;">
          <?php $computedSubtotal = (float)$order['total_amount'] - (float)($order['shipping_fee'] ?? 0); ?>
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-muted);">
            <span>Order Subtotal:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: #fff;">
              LKR <?= number_format($computedSubtotal, 2) ?>
            </span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-muted);">
            <span>Delivery Fee:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: #fff;">
              LKR <?= number_format((float)($order['shipping_fee'] ?? 0), 2) ?>
            </span>
          </div>
          <div style="border-top: 1px dashed var(--border-color); padding-top: 8px; display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; color: #fff;">
            <span style="font-family: 'Orbitron', sans-serif;">FINAL TOTAL:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: var(--neon-cyan);">
              LKR <?= number_format((float)$order['total_amount'], 2) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Status History Audit Timeline -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-clock-rotate-left"></i> Lifecycle Audit Trail
        </div>
      </div>
      <div style="padding: 20px;">
        <?php if (empty($order['status_history'])): ?>
          <p style="color: var(--text-muted); font-size: 0.85rem;">No historical status transitions logged yet.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 16px; position: relative; border-left: 2px solid var(--border-color); padding-left: 20px; margin-left: 10px;">
            <?php foreach ($order['status_history'] as $history): ?>
              <div style="position: relative;">
                <div style="position: absolute; left: -27px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--neon-cyan); box-shadow: 0 0 8px var(--neon-cyan);"></div>
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                  <span style="font-weight: 700; font-size: 0.9rem; color: #fff;">
                    <?= e(str_replace('_', ' ', $history['previous_status'] ?? '')) ?> 
                    <i class="fa-solid fa-arrow-right" style="font-size: 0.75rem; color: var(--neon-cyan); margin: 0 4px;"></i> 
                    <?= e(str_replace('_', ' ', $history['new_status'] ?? '')) ?>
                  </span>
                  <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: var(--text-muted);">
                    <?= date('Y-m-d H:i:s', strtotime($history['created_at'])) ?>
                  </span>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                  <?= e($history['reason'] ?? 'Status advanced by administrator') ?>
                </div>
                <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.4); margin-top: 2px;">
                  Actor: <strong style="color: #fff;"><?= e($history['actor_name'] ?? $history['actor_role'] ?? 'System') ?></strong> (<?= e($history['actor_role'] ?? 'SYSTEM') ?>)
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Status Transitions & Customer Info -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Fulfilment Control Panel -->
    <div class="admin-card" style="border-color: rgba(0, 245, 255, 0.3);">
      <div class="admin-card-header" style="background: rgba(0, 245, 255, 0.05);">
        <div class="admin-card-title" style="color: var(--neon-cyan);">
          <i class="fa-solid fa-sliders"></i> Fulfilment Transition
        </div>
      </div>
      <div style="padding: 20px;">
        <?php if (empty($nextStatuses)): ?>
          <div style="text-align: center; padding: 12px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 6px;">
            <i class="fa-solid fa-lock" style="color: var(--text-muted); margin-bottom: 8px;"></i>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Order has reached terminal state (<strong><?= e($order['status']) ?></strong>). No further lifecycle transitions permitted.
            </p>
          </div>
        <?php else: ?>
          <form method="POST" action="/admin/orders/<?= urlencode($order['order_number']) ?>/status">
            <?= csrf_field() ?>

            <div class="admin-form-group">
              <label class="admin-form-label">Advance Fulfilment State *</label>
              <select name="status" class="admin-form-control" required>
                <option value="">Select next stage...</option>
                <?php foreach ($nextStatuses as $nextSt): ?>
                  <option value="<?= e($nextSt) ?>">
                    <?= e(str_replace('_', ' ', $nextSt)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span style="font-size: 0.75rem; color: var(--text-muted);">
                State machine enforces strict forward progression.
              </span>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Audit Log Reason / Notes</label>
              <textarea name="reason" class="admin-form-control" rows="2" placeholder="e.g. Dispatched with CityPak Courier tracking #CP-98124..."></textarea>
            </div>

            <button type="submit" class="btn-admin btn-admin-cyan" style="width: 100%; justify-content: center;">
              <i class="fa-solid fa-check"></i> Execute Status Update
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- COD Payment Settlement Terminal -->
    <div class="admin-card" style="border-color: rgba(255, 215, 0, 0.3);">
      <div class="admin-card-header" style="background: rgba(255, 215, 0, 0.05);">
        <div class="admin-card-title" style="color: var(--neon-gold);">
          <i class="fa-solid fa-cash-register"></i> COD Payment Settlement
        </div>
      </div>
      <div style="padding: 20px;">
        <div style="margin-bottom: 16px; font-size: 0.85rem;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="color: var(--text-muted);">Method:</span>
            <span style="color: #fff; font-weight: 600;"><?= e($order['payment_method']) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span style="color: var(--text-muted);">Payment Record:</span>
            <span style="color: #fff; font-weight: 600;"><?= e($order['payment_record_status'] ?? $order['payment_status']) ?></span>
          </div>
          <?php if (!empty($order['collected_at'])): ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
              <span style="color: var(--text-muted);">Collected At:</span>
              <span style="color: var(--neon-green); font-family: 'JetBrains Mono', monospace;">
                <?= date('Y-m-d H:i', strtotime($order['collected_at'])) ?>
              </span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Verified By Admin:</span>
              <span style="color: #fff;"><?= e($order['collected_by_admin'] ?? 'Administrator') ?></span>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($order['payment_status'] === 'PAID'): ?>
          <div style="text-align: center; padding: 12px; background: rgba(0, 255, 136, 0.05); border: 1px solid rgba(0, 255, 136, 0.3); border-radius: 6px; color: var(--neon-green); font-size: 0.85rem;">
            <i class="fa-solid fa-circle-check"></i> Cash on Delivery settled in full.
          </div>
        <?php else: ?>
          <form method="POST" action="/admin/orders/<?= urlencode($order['order_number']) ?>/collect-payment">
            <?= csrf_field() ?>
            <input type="hidden" name="amount_collected" value="<?= (float)$order['total_amount'] ?>">

            <div class="admin-form-group">
              <label class="admin-form-label">Cash Collected (LKR)</label>
              <input type="text" class="admin-form-control" value="LKR <?= number_format((float)$order['total_amount'], 2) ?>" disabled style="background: rgba(0,0,0,0.5);">
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Settlement Audit Notes</label>
              <textarea name="notes" class="admin-form-control" rows="2" placeholder="e.g. Courier deposit verified against bank slip #10842"></textarea>
            </div>

            <button type="submit" class="btn-admin btn-admin-gold" style="width: 100%; justify-content: center;">
              <i class="fa-solid fa-money-bill-wave"></i> Confirm COD Collection
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Customer & Delivery Snapshot -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-user-shield"></i> Customer Profile
        </div>
      </div>
      <div style="padding: 20px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 12px;">
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Customer Name:</span>
          <strong style="color: #fff; font-size: 0.95rem;"><?= e($order['customer_name'] ?? 'N/A') ?></strong>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Email Address:</span>
          <a href="mailto:<?= e($order['customer_email'] ?? '') ?>" style="color: var(--neon-cyan); text-decoration: none;">
            <?= e($order['customer_email'] ?? 'N/A') ?>
          </a>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Contact Phone (Snapshot):</span>
          <span style="color: #fff; font-family: 'JetBrains Mono', monospace;">
            <?= e($order['shipping_phone'] ?? $order['customer_phone'] ?? 'N/A') ?>
          </span>
        </div>
        <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
          <span style="color: var(--text-muted); display: block; margin-bottom: 4px;">Delivery Destination (Snapshot):</span>
          <div style="color: #fff; line-height: 1.4;">
            <?= e($order['shipping_address_line1'] ?? $order['address_line1'] ?? '') ?><br>
            <?php if (!empty($order['shipping_address_line2'] ?? $order['address_line2'])): ?>
              <?= e($order['shipping_address_line2'] ?? $order['address_line2']) ?><br>
            <?php endif; ?>
            <?= e($order['shipping_city'] ?? $order['city'] ?? '') ?>, <?= e($order['shipping_postal_code'] ?? $order['postal_code'] ?? '') ?><br>
            <?= e($order['shipping_country'] ?? $order['country'] ?? 'Sri Lanka') ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
