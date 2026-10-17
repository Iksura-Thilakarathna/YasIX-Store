<?php
/**
 * YasIX Store - Warranty Claim Adjudication Terminal View
 */
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
        <a href="/admin/warranties" class="btn-admin btn-admin-ghost" style="padding: 6px 12px; font-size: 0.8rem;">
          <i class="fa-solid fa-arrow-left"></i> Back to Registry
        </a>
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0;">
          CLAIM <span style="color: var(--neon-purple);"><?= e($claim['claim_number']) ?></span>
        </h1>
      </div>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Lodged on <?= date('F j, Y - h:i A', strtotime($claim['created_at'])) ?> | Order Ref #<?= e($claim['order_number']) ?>
      </p>
    </div>

    <div>
      <span class="admin-badge" style="<?= $claim['status_meta']['badge_style'] ?? '' ?> padding: 8px 16px; font-size: 0.9rem;">
        <i class="fa-solid <?= $claim['status_meta']['icon'] ?? 'fa-circle-dot' ?>"></i>
        <?= e(str_replace('_', ' ', $claim['status'])) ?>
      </span>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
  <!-- Left Column: Hardware Manifest & Customer Claim Statement -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Hardware & Warranty Coverage Info -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-microchip"></i> Covered Hardware Manifest
        </div>
      </div>
      <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.85rem;">
          <div>
            <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Product SKU:</span>
            <strong style="color: #fff; font-size: 1rem;"><?= e($claim['product_name']) ?></strong>
          </div>
          <div>
            <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Serial Number:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: var(--neon-cyan);">
              <?= e($claim['serial_number'] ?? 'SN-' . strtoupper(substr(md5((string)$claim['id']), 0, 10))) ?>
            </span>
          </div>
          <div>
            <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Purchase Order:</span>
            <a href="/admin/orders/<?= urlencode($claim['order_number']) ?>" style="color: var(--neon-cyan); font-family: 'JetBrains Mono', monospace; text-decoration: none;">
              #<?= e($claim['order_number']) ?>
            </a>
          </div>
          <div>
            <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Doorstep Delivery Date:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: #fff;">
              <?= !empty($claim['delivered_at']) ? date('Y-m-d', strtotime($claim['delivered_at'])) : '—' ?>
            </span>
          </div>
          <div>
            <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Warranty Window:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: var(--neon-green);">
              <?= e($claim['start_date']) ?> to <?= e($claim['end_date']) ?> (<?= (int)$claim['warranty_months'] ?> Months)
            </span>
          </div>
          <div>
            <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Original Unit Price:</span>
            <span style="font-family: 'JetBrains Mono', monospace; color: #fff;">
              LKR <?= number_format((float)$claim['unit_price'], 2) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Statement -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-triangle-exclamation"></i> Customer Defect Statement
        </div>
      </div>
      <div style="padding: 20px;">
        <div style="margin-bottom: 16px;">
          <span style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-bottom: 4px;">Reported Issue Category:</span>
          <span class="admin-badge admin-badge-warning" style="font-size: 0.85rem;">
            <?= e($claim['claim_reason']) ?>
          </span>
        </div>
        <div>
          <span style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-bottom: 6px;">Customer Problem Description:</span>
          <div style="background: rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); border-radius: 6px; padding: 16px; color: #fff; font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;">
            <?= e($claim['issue_description']) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Current Adjudication Record (Customer-Facing & Internal) -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-gavel"></i> Adjudication Dossier
        </div>
      </div>
      <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
        <div>
          <span style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-bottom: 4px;">Public Decision Title:</span>
          <div style="color: #fff; font-weight: 600; font-size: 0.95rem;">
            <?= !empty($claim['admin_decision']) ? e($claim['admin_decision']) : '<em style="color: var(--text-muted);">No public decision logged yet.</em>' ?>
          </div>
        </div>

        <div>
          <span style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-bottom: 4px;">Customer-Facing Resolution & Instructions:</span>
          <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid var(--border-color); border-radius: 6px; padding: 12px; color: #fff; font-size: 0.85rem; line-height: 1.5; white-space: pre-wrap;">
            <?= !empty($claim['resolution_details']) ? e($claim['resolution_details']) : '<em style="color: var(--text-muted);">Awaiting adjudication by store administration team.</em>' ?>
          </div>
        </div>

        <!-- Confidential Internal Notes (Admin-only) -->
        <div style="border-top: 1px solid var(--border-color); padding-top: 16px;">
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <i class="fa-solid fa-user-shield" style="color: var(--neon-gold);"></i>
            <span style="color: var(--neon-gold); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
              Confidential Internal Audit Notes (Store Staff Only)
            </span>
          </div>
          <div style="background: rgba(255, 215, 0, 0.04); border: 1px solid rgba(255, 215, 0, 0.2); border-radius: 6px; padding: 12px; color: #ffd700; font-size: 0.85rem; line-height: 1.5; white-space: pre-wrap;">
            <?= !empty($claim['internal_notes']) ? e($claim['internal_notes']) : '<em style="color: rgba(255,215,0,0.5);">No internal staff notes recorded.</em>' ?>
          </div>
        </div>

        <?php if (!empty($claim['reviewer_name'])): ?>
          <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
            Last Reviewed by: <strong style="color: #fff;"><?= e($claim['reviewer_name']) ?></strong>
            <?php if (!empty($claim['resolved_at'])): ?>
              on <?= date('Y-m-d H:i', strtotime($claim['resolved_at'])) ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Adjudication Action Terminal & Customer Contact -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Adjudication Action Terminal -->
    <div class="admin-card" style="border-color: rgba(184, 41, 247, 0.3);">
      <div class="admin-card-header" style="background: rgba(184, 41, 247, 0.05);">
        <div class="admin-card-title" style="color: var(--neon-purple);">
          <i class="fa-solid fa-gavel"></i> Adjudicate Claim
        </div>
      </div>
      <div style="padding: 20px;">
        <?php if (empty($nextStatuses)): ?>
          <div style="text-align: center; padding: 12px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 6px;">
            <i class="fa-solid fa-lock" style="color: var(--text-muted); margin-bottom: 8px;"></i>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
              Claim has reached terminal state (<strong><?= e($claim['status']) ?></strong>). No further transitions permitted.
            </p>
          </div>
        <?php else: ?>
          <form method="POST" action="/admin/warranties/<?= (int)$claim['id'] ?>/decide">
            <?= csrf_field() ?>

            <div class="admin-form-group">
              <label class="admin-form-label">Transition Claim Status *</label>
              <select name="status" class="admin-form-control" required>
                <option value="">Select target state...</option>
                <?php foreach ($nextStatuses as $nextSt): ?>
                  <option value="<?= e($nextSt) ?>">
                    <?= e(str_replace('_', ' ', $nextSt)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Decision Summary (Customer-Facing)</label>
              <input 
                type="text" 
                name="admin_decision" 
                class="admin-form-control" 
                placeholder="e.g. Approved: Defective controller replaced"
                value="<?= e($claim['admin_decision'] ?? '') ?>"
              >
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Resolution Details (Customer-Facing)</label>
              <textarea 
                name="resolution_details" 
                class="admin-form-control" 
                rows="3" 
                placeholder="e.g. Replacement unit has been scheduled for dispatch via courier. Customer may keep the original packaging."
              ><?= e($claim['resolution_details'] ?? '') ?></textarea>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label" style="color: var(--neon-gold);">
                <i class="fa-solid fa-lock"></i> Confidential Internal Notes (Staff Only)
              </label>
              <textarea 
                name="internal_notes" 
                class="admin-form-control" 
                rows="3" 
                placeholder="e.g. Serial checked with overseas distributor; RMA credit requested from supplier."
              ><?= e($claim['internal_notes'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-admin btn-admin-cyan" style="width: 100%; justify-content: center;">
              <i class="fa-solid fa-stamp"></i> Record Adjudication Decision
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Customer Contact Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          <i class="fa-solid fa-user-gear"></i> Customer Contact
        </div>
      </div>
      <div style="padding: 20px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 12px;">
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Customer:</span>
          <strong style="color: #fff; font-size: 0.95rem;"><?= e($claim['customer_name']) ?></strong>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Email:</span>
          <a href="mailto:<?= e($claim['customer_email']) ?>" style="color: var(--neon-cyan); text-decoration: none;">
            <?= e($claim['customer_email']) ?>
          </a>
        </div>
        <div>
          <span style="color: var(--text-muted); display: block; margin-bottom: 2px;">Phone:</span>
          <span style="color: #fff; font-family: 'JetBrains Mono', monospace;">
            <?= e($claim['customer_phone'] ?? 'N/A') ?>
          </span>
        </div>
        <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
          <a href="/admin/customers/<?= (int)$claim['user_id'] ?>" class="btn-admin btn-admin-ghost" style="width: 100%; justify-content: center; font-size: 0.8rem;">
            <i class="fa-solid fa-id-card"></i> Open Customer Dossier
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
