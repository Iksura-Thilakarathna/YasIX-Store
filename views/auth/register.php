<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-wrapper">
  <div class="auth-card" style="max-width: 520px;">
    <div class="auth-header">
      <div style="font-size: 2.5rem; color: var(--violet); margin-bottom: 10px;">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h1 class="auth-title">Initialize Node</h1>
      <p class="auth-subtitle">Register a new customer profile on the YasIX Network</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-banner alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form action="/register" method="POST">
      <?= \App\Helpers\CSRF::field() ?>

      <div class="form-group">
        <label for="full_name" class="form-label"><i class="fa-solid fa-user"></i> FULL NAME</label>
        <input 
          type="text" 
          id="full_name" 
          name="full_name" 
          class="cyber-input" 
          placeholder="Cyber Citizen" 
          value="<?= e($old['full_name'] ?? '') ?>" 
          required
        >
        <?php if (!empty($errors['full_name'])): ?>
          <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($errors['full_name'][0]) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> EMAIL ADDRESS</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="cyber-input" 
          placeholder="citizen@yasix.com" 
          value="<?= e($old['email'] ?? '') ?>" 
          required
        >
        <?php if (!empty($errors['email'])): ?>
          <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($errors['email'][0]) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="phone" class="form-label"><i class="fa-solid fa-phone"></i> PHONE NUMBER (SRI LANKA)</label>
        <input 
          type="text" 
          id="phone" 
          name="phone" 
          class="cyber-input" 
          placeholder="+94 77 123 4567 or 0771234567" 
          value="<?= e($old['phone'] ?? '') ?>" 
          required
        >
        <?php if (!empty($errors['phone'])): ?>
          <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($errors['phone'][0]) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password" class="form-label"><i class="fa-solid fa-lock"></i> PASSWORD (MIN 8 CHARS)</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="cyber-input" 
          placeholder="••••••••••••" 
          required
        >
        <?php if (!empty($errors['password'])): ?>
          <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($errors['password'][0]) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="confirm_password" class="form-label"><i class="fa-solid fa-shield-halved"></i> CONFIRM PASSWORD</label>
        <input 
          type="password" 
          id="confirm_password" 
          name="confirm_password" 
          class="cyber-input" 
          placeholder="••••••••••••" 
          required
        >
        <?php if (!empty($errors['confirm_password'])): ?>
          <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($errors['confirm_password'][0]) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
        REGISTER ACCOUNT <i class="fa-solid fa-user-check"></i>
      </button>
    </form>

    <div class="auth-footer-text">
      Already have a node account? <a href="/login">Authenticate Here</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
