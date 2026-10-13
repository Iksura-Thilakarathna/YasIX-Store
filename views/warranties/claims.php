<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container account-wrapper" style="padding-top: 40px; padding-bottom: 80px;">
  <!-- PAGE HEADER -->
  <div class="account-header">
    <div>
      <div class="section-subtitle">// SUPPORT RADAR // CLAIMS</div>
      <h1 class="auth-title" style="margin-bottom: 4px;">WARRANTY CLAIMS TRACKER</h1>
      <p style="color: var(--muted); font-size: 0.95rem;">
        Monitor the diagnostic progress, technical decisions, and replacement resolutions for your submitted hardware claims.
      </p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <a href="/warranties" class="btn-secondary" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="fa-solid fa-shield-halved"></i> WARRANTY COVERAGE
      </a>
      <a href="/account" class="btn-primary" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="fa-solid fa-user-gear"></i> USER TERMINAL
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

  <!-- CLAIMS LIST OR EMPTY STATE -->
  <?php if (empty($claims)): ?>
    <div class="account-card" style="text-align: center; padding: 60px 20px; border: 1px dashed var(--border);">
      <div style="font-size: 3.5rem; color: var(--muted); margin-bottom: 16px;">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>
      <h3 style="font-family: 'Orbitron'; font-size: 1.3rem; margin-bottom: 10px; color: var(--text);">
        NO WARRANTY CLAIMS IN MATRIX
      </h3>
      <p style="color: var(--muted); max-width: 480px; margin: 0 auto 24px; line-height: 1.6; font-size: 0.95rem;">
        You have not submitted any warranty claims. If you experience hardware malfunctions on eligible delivered items, you may submit a claim from the warranty registry.
      </p>
      <a href="/warranties" class="btn-primary" style="padding: 12px 28px;">
        VIEW WARRANTY REGISTRY <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
      <?php foreach ($claims as $claim): ?>
        <div class="account-card" style="padding: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 16px;">
            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron'; margin-bottom: 4px;">
                CLAIM REFERENCE
              </div>
              <a href="/warranties/claim/<?= urlencode($claim['claim_number']) ?>" style="font-family: 'Orbitron'; font-size: 1.25rem; font-weight: 800; color: var(--cyan); text-decoration: none;">
                <?= e($claim['claim_number']) ?>
              </a>
              <div style="font-size: 0.85rem; color: var(--muted); margin-top: 4px;">
                Submitted on <?= e($claim['formatted_date']) ?>
              </div>
            </div>

            <!-- Status Badge -->
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-family: 'Orbitron'; font-size: 0.8rem; font-weight: 700; <?= $claim['status_meta']['badge_style'] ?>">
                <i class="fa-solid <?= $claim['status_meta']['icon'] ?>"></i> <?= e($claim['status_meta']['label']) ?>
              </span>
            </div>
          </div>

          <!-- Claim Details Grid -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">HARDWARE PRODUCT</div>
              <div style="font-size: 0.95rem; font-weight: 700; color: var(--text);">
                <?= e($claim['product_name']) ?>
              </div>
              <div style="font-size: 0.8rem; color: var(--muted);">
                Order #<?= e($claim['order_number']) ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">REPORTED ISSUE REASON</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: #ffd700;">
                <?= e($claim['claim_reason']) ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">ADMIN DECISION</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
                <?= e($claim['admin_decision'] ?? 'Pending Review') ?>
              </div>
            </div>
          </div>

          <!-- Description Preview -->
          <div style="background: rgba(3, 7, 18, 0.5); padding: 14px; border-radius: 6px; font-size: 0.85rem; color: var(--muted); margin-bottom: 16px; line-height: 1.5;">
            <strong style="color: var(--text);">Issue Telemetry:</strong> <?= e(mb_strimwidth($claim['issue_description'], 0, 180, '...')) ?>
          </div>

          <!-- Actions -->
          <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 16px;">
            <a href="/warranties/claim/<?= urlencode($claim['claim_number']) ?>" class="btn-primary" style="padding: 8px 20px; font-size: 0.85rem;">
              <i class="fa-solid fa-file-waveform"></i> VIEW CLAIM DOSSIER <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
