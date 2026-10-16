<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 40px 20px 80px; max-width: 800px;">
  <!-- TOP BACK NAV -->
  <div style="margin-bottom: 24px;">
    <a href="/warranties" style="color: var(--cyan); text-decoration: none; font-size: 0.9rem; font-family: 'Orbitron';">
      <i class="fa-solid fa-arrow-left"></i> BACK TO WARRANTY REGISTRY
    </a>
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

  <!-- HARDWARE SUMMARY CARD -->
  <div class="account-card" style="padding: 24px; margin-bottom: 24px;">
    <div class="section-subtitle">// HARDWARE CLAIM APPLICATION</div>
    <h1 class="auth-title" style="font-size: 1.8rem; margin-bottom: 6px; color: var(--text);">
      SUBMIT WARRANTY CLAIM
    </h1>
    <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 20px;">
      File a technical service or replacement claim under YasIX Store hardware warranty coverage.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: rgba(3, 7, 18, 0.6); padding: 18px; border-radius: 8px;">
      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">HARDWARE ITEM</div>
        <div style="font-size: 1rem; font-weight: 700; color: var(--text);">
          <?= e($item['product_name']) ?>
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">ORDER REFERENCE</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--cyan);">
          #<?= e($item['order_number']) ?>
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">WARRANTY WINDOW</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: #00ff88;">
          <?= (int)$item['warranty_months'] ?> Months (Expires <?= e(date('M d, Y', strtotime($eligibility['end_date']))) ?>)
        </div>
      </div>
    </div>
  </div>

  <!-- POLICY DISCLOSURE -->
  <div class="account-card" style="padding: 20px; background: rgba(255, 215, 0, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); margin-bottom: 24px;">
    <div style="display: flex; gap: 14px; align-items: flex-start;">
      <div style="font-size: 1.5rem; color: #ffd700; margin-top: 2px;">
        <i class="fa-solid fa-circle-exclamation"></i>
      </div>
      <div style="font-size: 0.88rem; color: #ffe680; line-height: 1.5;">
        <strong>Important Warranty Disclosure:</strong> Submitting a claim does not constitute automatic authorization or immediate refund/replacement. All claims undergo technical evaluation by our diagnostic engineering team to verify factory defect criteria versus physical impact or liquid damage.
      </div>
    </div>
  </div>

  <!-- CLAIM SUBMISSION FORM -->
  <div class="account-card" style="padding: 30px;">
    <form action="/warranties/claim" method="POST">
      <?= \App\Helpers\CSRF::field() ?>
      <input type="hidden" name="order_item_id" value="<?= (int)$item['order_item_id'] ?>">

      <!-- Claim Reason Selection -->
      <div class="form-group" style="margin-bottom: 22px;">
        <label for="claim_reason" class="form-label" style="display: block; font-family: 'Orbitron'; font-size: 0.85rem; color: var(--cyan); margin-bottom: 8px;">
          <i class="fa-solid fa-triangle-exclamation"></i> MALFUNCTION CATEGORY / REASON <span style="color: #ff3377;">*</span>
        </label>
        <select id="claim_reason" name="claim_reason" class="cyber-input" style="width: 100%;" required>
          <option value="" disabled selected>-- Select primary hardware issue reason --</option>
          <?php foreach ($reasons as $reason): ?>
            <option value="<?= e($reason) ?>"><?= e($reason) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Detailed Issue Description -->
      <div class="form-group" style="margin-bottom: 22px;">
        <label for="issue_description" class="form-label" style="display: block; font-family: 'Orbitron'; font-size: 0.85rem; color: var(--cyan); margin-bottom: 8px;">
          <i class="fa-solid fa-file-lines"></i> DETAILED TELEMETRY / ISSUE DESCRIPTION <span style="color: #ff3377;">*</span>
        </label>
        <textarea 
          id="issue_description" 
          name="issue_description" 
          class="cyber-input" 
          rows="6" 
          style="width: 100%; resize: vertical; line-height: 1.5;" 
          placeholder="Please describe when the defect occurred, symptoms observed, diagnostic indicators (LED codes, overheating, complete failure), and operating conditions..."
          required
        ></textarea>
        <div style="font-size: 0.78rem; color: var(--muted); margin-top: 6px;">
          Minimum 10 characters required. Maximum 3,000 characters.
        </div>
      </div>

      <!-- Documented Note on Evidence Uploads -->
      <div style="background: rgba(3, 7, 18, 0.5); padding: 14px; border-radius: 6px; font-size: 0.85rem; color: var(--muted); margin-bottom: 24px; border-left: 3px solid var(--cyan);">
        <i class="fa-solid fa-camera"></i> 
        <strong>Supporting Evidence Note:</strong> Text-only telemetry is currently processed for security integrity. If photographs or benchmark logs are required during review, our engineering liaison will provide a secure end-to-end transmission link.
      </div>

      <!-- Action Buttons -->
      <div style="display: flex; gap: 14px; justify-content: flex-end; flex-wrap: wrap;">
        <a href="/warranties" class="btn-secondary" style="padding: 12px 24px;">
          CANCEL
        </a>
        <button type="submit" class="btn-primary" style="padding: 12px 28px;">
          SUBMIT FOR DIAGNOSTIC REVIEW <i class="fa-solid fa-paper-plane"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
