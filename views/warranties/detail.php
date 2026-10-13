<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 40px 20px 80px; max-width: 850px;">
  <!-- TOP BACK NAV -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <a href="/warranties/claims" style="color: var(--cyan); text-decoration: none; font-size: 0.9rem; font-family: 'Orbitron';">
      <i class="fa-solid fa-arrow-left"></i> BACK TO WARRANTY CLAIMS
    </a>
    <div style="display: flex; gap: 10px;">
      <a href="/warranties" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
        <i class="fa-solid fa-shield-halved"></i> COVERAGE REGISTRY
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

  <!-- CLAIM HEADER CARD -->
  <div class="account-card" style="padding: 28px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 18px; margin-bottom: 18px;">
      <div>
        <div class="section-subtitle">// TECHNICAL SERVICE DOSSIER</div>
        <h1 class="auth-title" style="font-size: 1.8rem; margin-bottom: 6px; color: var(--text);">
          CLAIM #<?= e($claim['claim_number']) ?>
        </h1>
        <p style="color: var(--muted); font-size: 0.9rem; margin: 0;">
          Filed on <span style="color: var(--text); font-weight: 600;"><?= e($claim['formatted_date']) ?></span>
        </p>
      </div>

      <div>
        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-family: 'Orbitron'; font-size: 0.85rem; font-weight: 700; <?= $claim['status_meta']['badge_style'] ?>">
          <i class="fa-solid <?= $claim['status_meta']['icon'] ?>"></i> <?= e($claim['status_meta']['label']) ?>
        </span>
      </div>
    </div>

    <!-- METRICS GRID -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: rgba(3, 7, 18, 0.6); padding: 16px; border-radius: 8px;">
      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">HARDWARE UNIT</div>
        <div style="font-size: 1rem; font-weight: 700; color: var(--text);">
          <?= e($claim['product_name']) ?>
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">ORDER REFERENCE</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--cyan);">
          <a href="/orders/<?= urlencode($claim['order_number']) ?>" style="color: var(--cyan); text-decoration: none;">
            #<?= e($claim['order_number']) ?>
          </a>
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">WARRANTY SERIAL</div>
        <div style="font-family: 'Orbitron'; font-size: 0.85rem; color: var(--text);">
          <?= e($claim['serial_number']) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ISSUE TELEMETRY & REASON -->
  <div class="account-card" style="padding: 28px; margin-bottom: 24px;">
    <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <i class="fa-solid fa-triangle-exclamation"></i> REPORTED ISSUE TELEMETRY
    </h3>

    <div style="margin-bottom: 18px;">
      <div style="font-size: 0.8rem; color: var(--muted); font-family: 'Orbitron'; margin-bottom: 4px;">PRIMARY MALFUNCTION CATEGORY</div>
      <div style="font-size: 1.1rem; font-weight: 700; color: #ffd700;">
        <i class="fa-solid fa-tag"></i> <?= e($claim['claim_reason']) ?>
      </div>
    </div>

    <div>
      <div style="font-size: 0.8rem; color: var(--muted); font-family: 'Orbitron'; margin-bottom: 8px;">CUSTOMER DEFECT NARRATIVE</div>
      <div style="background: rgba(3, 7, 18, 0.7); padding: 18px; border-radius: 8px; border: 1px solid var(--border); font-size: 0.95rem; line-height: 1.6; color: var(--text); white-space: pre-wrap;">
<?= e($claim['issue_description']) ?>
      </div>
    </div>
  </div>

  <!-- ADMINISTRATIVE DECISION & RESOLUTION -->
  <div class="account-card" style="padding: 28px; margin-bottom: 24px;">
    <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <i class="fa-solid fa-user-shield"></i> DIAGNOSTIC FINDINGS & RESOLUTION
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 18px;">
      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">DIAGNOSTIC DECISION</div>
        <div style="font-size: 1.05rem; font-weight: 700; color: var(--text);">
          <?= e($claim['admin_decision'] ?? 'Under Review') ?>
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">LAST UPDATED</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--muted);">
          <?= e($claim['formatted_updated_date'] ?? $claim['formatted_date']) ?>
        </div>
      </div>
    </div>

    <?php if (!empty($claim['resolution_details'])): ?>
      <div style="background: rgba(0, 255, 136, 0.05); border: 1px solid rgba(0, 255, 136, 0.3); border-radius: 8px; padding: 18px;">
        <div style="font-size: 0.8rem; color: #00ff88; font-family: 'Orbitron'; font-weight: 700; margin-bottom: 6px;">
          <i class="fa-solid fa-wrench"></i> RESOLUTION SPECIFICATION
        </div>
        <div style="color: var(--text); font-size: 0.95rem; line-height: 1.6;">
          <?= e($claim['resolution_details']) ?>
        </div>
      </div>
    <?php else: ?>
      <div style="background: rgba(3, 7, 18, 0.5); padding: 16px; border-radius: 8px; border: 1px dashed var(--border); font-size: 0.88rem; color: var(--muted); line-height: 1.5;">
        <i class="fa-solid fa-microscope" style="color: var(--cyan); margin-right: 6px;"></i>
        Hardware claim is queued in diagnostic pipeline. Once physical or telemetry analysis is completed by YasIX technicians, findings and replacement tracking will be updated here.
      </div>
    <?php endif; ?>
  </div>

  <!-- BOTTOM ACTIONS -->
  <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
    <a href="/warranties/claims" class="btn-primary" style="padding: 12px 24px;">
      <i class="fa-solid fa-list-check"></i> ALL CLAIMS
    </a>
    <a href="/warranties" class="btn-secondary" style="padding: 12px 24px;">
      <i class="fa-solid fa-shield-halved"></i> WARRANTY REGISTRY
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
