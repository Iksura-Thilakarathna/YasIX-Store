<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div style="font-size: 2.5rem; color: var(--cyan); margin-bottom: 10px;">
        <i class="fa-solid fa-user-shield"></i>
      </div>
      <h1 class="auth-title">System Access</h1>
      <p class="auth-subtitle">Authenticate your terminal credentials to access YasIX Matrix</p>
    </div>

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

    <?php if (!empty($errors) && is_array($errors)): ?>
      <div class="alert-banner alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
          <?php foreach ($errors as $fieldErrors): ?>
            <?php foreach ((array)$fieldErrors as $err): ?>
              <div><?= e($err) ?></div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <form action="/login" method="POST">
      <?= \App\Helpers\CSRF::field() ?>

      <div class="form-group">
        <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> EMAIL ADDRESS</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="cyber-input" 
          placeholder="operator@yasix.com" 
          value="<?= e($old['email'] ?? '') ?>" 
          required 
          autofocus
        >
      </div>

      <div class="form-group">
        <label for="password" class="form-label"><i class="fa-solid fa-key"></i> PASSWORD</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="cyber-input" 
          placeholder="••••••••••••" 
          required
        >
      </div>

      <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
        AUTHENTICATE <i class="fa-solid fa-right-to-bracket"></i>
      </button>
    </form>

    <div class="auth-footer-text">
      Need a new access node? <a href="/register">Create an Account</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
