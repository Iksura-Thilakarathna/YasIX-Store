<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container account-wrapper" style="padding-top: 40px; padding-bottom: 80px;">
  <!-- PAGE HEADER -->
  <div class="account-header">
    <div>
      <div class="section-subtitle">// WARRANTY REGISTRY & COVERAGE</div>
      <h1 class="auth-title" style="margin-bottom: 4px;">MY HARDWARE WARRANTIES</h1>
      <p style="color: var(--muted); font-size: 0.95rem;">
        Monitor warranty lifecycles for delivered products, review expiration dates, and submit RMA replacement claims.
      </p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <a href="/warranties/claims" class="btn-secondary" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="fa-solid fa-list-check"></i> VIEW SUBMITTED CLAIMS
      </a>
      <a href="/orders" class="btn-primary" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="fa-solid fa-receipt"></i> ORDER HISTORY
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

  <!-- POLICY NOTICE BANNER -->
  <div class="account-card" style="padding: 18px 24px; background: rgba(0, 245, 255, 0.04); border: 1px solid rgba(0, 245, 255, 0.2); margin-bottom: 24px;">
    <div style="display: flex; gap: 14px; align-items: center;">
      <div style="font-size: 1.8rem; color: var(--cyan);">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <div style="font-size: 0.88rem; color: var(--muted); line-height: 1.5;">
        <strong style="color: var(--text);">Warranty Commencement Policy:</strong> Warranty duration (typically 3–6 months for eligible electronics) strictly begins on the actual courier <strong>doorstep delivery date</strong>. Submitting a claim initiates hardware diagnostic review under YasIX store policy.
      </div>
    </div>
  </div>

  <!-- WARRANTIES LIST OR EMPTY STATE -->
  <?php if (empty($warranties)): ?>
    <div class="account-card" style="text-align: center; padding: 60px 20px; border: 1px dashed var(--border);">
      <div style="font-size: 3.5rem; color: var(--muted); margin-bottom: 16px;">
        <i class="fa-solid fa-shield-cat"></i>
      </div>
      <h3 style="font-family: 'Orbitron'; font-size: 1.3rem; margin-bottom: 10px; color: var(--text);">
        NO HARDWARE PURCHASES RECORDED
      </h3>
      <p style="color: var(--muted); max-width: 480px; margin: 0 auto 24px; line-height: 1.6; font-size: 0.95rem;">
        You currently have no hardware items recorded in your coverage matrix. Once an order is delivered, eligible hardware warranties will automatically appear here.
      </p>
      <a href="/products" class="btn-primary" style="padding: 12px 28px;">
        BROWSE CYBER STORE <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
      <?php foreach ($warranties as $item): ?>
        <?php $eligibility = $item['eligibility']; ?>
        <div class="account-card" style="padding: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 16px;">
            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron'; margin-bottom: 4px;">
                PURCHASE FROM ORDER <a href="/orders/<?= urlencode($item['order_number']) ?>" style="color: var(--cyan); text-decoration: none; font-weight: 700;">#<?= e($item['order_number']) ?></a>
              </div>
              <h3 style="font-family: 'Orbitron'; font-size: 1.2rem; font-weight: 800; color: var(--text); margin: 0;">
                <?= e($item['product_name']) ?>
              </h3>
              <div style="font-size: 0.85rem; color: var(--muted); margin-top: 4px;">
                Ordered: <?= e(date('M d, Y', strtotime($item['order_date']))) ?> | 
                Unit Price: <span style="font-family: 'Orbitron'; color: var(--cyan);"><?= formatCurrency((float)$item['unit_price']) ?></span>
              </div>
            </div>

            <!-- Status Badge -->
            <div>
              <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-family: 'Orbitron'; font-size: 0.8rem; font-weight: 700; <?= $item['status_meta']['badge_style'] ?>">
                <i class="fa-solid <?= $item['status_meta']['icon'] ?>"></i> <?= e($item['status_meta']['label']) ?>
              </span>
            </div>
          </div>

          <!-- Coverage Details Grid -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">WARRANTY DURATION</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
                <?php if ((int)$item['warranty_months'] > 0): ?>
                  <?= (int)$item['warranty_months'] ?> Months (Purchase Snapshot)
                <?php else: ?>
                  <span style="color: var(--muted);">No Warranty Coverage</span>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">DELIVERY DATE (START)</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
                <?php if (!empty($item['delivered_at'])): ?>
                  <?= e(date('M d, Y', strtotime($item['delivered_at']))) ?>
                <?php else: ?>
                  <span style="color: #ffd700;"><i class="fa-solid fa-clock"></i> Pending Delivery</span>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">EXPIRY BOUNDARY</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
                <?php if (!empty($eligibility['end_date'])): ?>
                  <?= e(date('M d, Y', strtotime($eligibility['end_date']))) ?>
                  <?php if (!empty($eligibility['days_remaining'])): ?>
                    <span style="font-size: 0.8rem; color: #00ff88;">(<?= $eligibility['days_remaining'] ?> days left)</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color: var(--muted);">Unavailable</span>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">WARRANTY SERIAL</div>
              <div style="font-family: 'Orbitron'; font-size: 0.85rem; color: var(--cyan);">
                <?= e($item['serial_number'] ?? 'Pending registration') ?>
              </div>
            </div>
          </div>

          <!-- Status Message Explanation -->
          <div style="background: rgba(3, 7, 18, 0.5); padding: 12px 16px; border-radius: 6px; font-size: 0.85rem; color: var(--muted); margin-bottom: 16px;">
            <i class="fa-solid fa-circle-info" style="color: var(--cyan); margin-right: 6px;"></i>
            <?= e($eligibility['message']) ?>
          </div>

          <!-- Actions -->
          <div style="display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 16px;">
            <a href="/orders/<?= urlencode($item['order_number']) ?>" class="btn-secondary" style="padding: 8px 18px; font-size: 0.85rem;">
              <i class="fa-solid fa-receipt"></i> VIEW ORDER
            </a>

            <?php if (!empty($item['active_claim_number'])): ?>
              <a href="/warranties/claim/<?= urlencode($item['active_claim_number']) ?>" class="btn-primary" style="padding: 8px 18px; font-size: 0.85rem; background: rgba(184, 41, 247, 0.2); border-color: #b829f7; color: #d8b4fe;">
                <i class="fa-solid fa-clipboard-check"></i> VIEW ACTIVE CLAIM (<?= e($item['active_claim_number']) ?>)
              </a>
            <?php elseif ($eligibility['is_eligible']): ?>
              <a href="/warranties/claim/new/<?= (int)$item['order_item_id'] ?>" class="btn-primary" style="padding: 8px 18px; font-size: 0.85rem;">
                <i class="fa-solid fa-shield-halved"></i> SUBMIT CLAIM <i class="fa-solid fa-arrow-right"></i>
              </a>
            <?php else: ?>
              <button disabled class="btn-secondary" style="padding: 8px 18px; font-size: 0.85rem; opacity: 0.4; cursor: not-allowed;">
                <i class="fa-solid fa-ban"></i> INELIGIBLE FOR CLAIM
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
