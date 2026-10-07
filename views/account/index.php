<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container account-wrapper">
  <div class="account-header">
    <div>
      <h1 class="auth-title" style="margin-bottom: 4px;">USER TERMINAL // DASHBOARD</h1>
      <p style="color: var(--muted);">Welcome back, <span style="color: var(--cyan); font-weight: 700;"><?= e($user->name) ?></span></p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <span class="badge-role <?= $user->isAdmin() ? 'badge-admin' : 'badge-customer' ?>">
        <i class="fa-solid <?= $user->isAdmin() ? 'fa-user-shield' : 'fa-id-badge' ?>"></i> <?= e($user->role_name ?? 'CUSTOMER') ?>
      </span>
      <form action="/logout" method="POST" style="margin: 0;">
        <?= \App\Helpers\CSRF::field() ?>
        <button type="submit" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; background: rgba(255, 0, 85, 0.2); border-color: var(--neon-pink); color: #ff3377;">
          LOGOUT <i class="fa-solid fa-right-from-bracket"></i>
        </button>
      </form>
    </div>
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

  <div class="account-grid">
    <!-- Left Column: User Credentials Overview -->
    <div class="account-card">
      <h3 style="font-family: 'Orbitron'; color: var(--cyan); margin-bottom: 20px; font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <i class="fa-solid fa-address-card"></i> NODE DETAILS
      </h3>

      <div style="display: flex; flex-direction: column; gap: 16px;">
        <div>
          <span style="color: var(--muted); font-size: 0.8rem; font-family: 'Orbitron';">FULL NAME</span>
          <div style="font-size: 1.05rem; font-weight: 600; color: var(--text);"><?= e($user->name) ?></div>
        </div>

        <div>
          <span style="color: var(--muted); font-size: 0.8rem; font-family: 'Orbitron';">EMAIL ADDRESS</span>
          <div style="font-size: 1rem; color: var(--text);"><?= e($user->email) ?></div>
        </div>

        <div>
          <span style="color: var(--muted); font-size: 0.8rem; font-family: 'Orbitron';">PHONE NUMBER</span>
          <div style="font-size: 1rem; color: var(--text);"><?= e($user->phone ?: 'Not configured') ?></div>
        </div>

        <div>
          <span style="color: var(--muted); font-size: 0.8rem; font-family: 'Orbitron';">ACCOUNT STATUS</span>
          <div>
            <span class="badge-active" style="font-weight: 700; font-size: 0.9rem;">
              <i class="fa-solid fa-circle-dot"></i> <?= e($user->status ?? 'ACTIVE') ?>
            </span>
          </div>
        </div>

        <div>
          <span style="color: var(--muted); font-size: 0.8rem; font-family: 'Orbitron';">MEMBER SINCE</span>
          <div style="font-size: 0.95rem; color: var(--muted);"><?= e(date('F j, Y', strtotime($user->created_at ?? 'now'))) ?></div>
        </div>
      </div>
    </div>

    <!-- Right Column: Profile Management Forms -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
      <!-- Edit Profile Card -->
      <div class="account-card">
        <h3 style="font-family: 'Orbitron'; color: var(--cyan); margin-bottom: 20px; font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <i class="fa-solid fa-user-pen"></i> UPDATE PROFILE DETAILS
        </h3>

        <form action="/account/profile" method="POST">
          <?= \App\Helpers\CSRF::field() ?>

          <div class="form-group">
            <label for="full_name" class="form-label"><i class="fa-solid fa-user"></i> FULL NAME</label>
            <input 
              type="text" 
              id="full_name" 
              name="full_name" 
              class="cyber-input" 
              value="<?= e($user->name) ?>" 
              required
            >
            <?php if (!empty($profile_errors['full_name'])): ?>
              <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($profile_errors['full_name'][0]) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="email_readonly" class="form-label"><i class="fa-solid fa-envelope"></i> EMAIL ADDRESS (READ-ONLY)</label>
            <input 
              type="email" 
              id="email_readonly" 
              class="cyber-input" 
              value="<?= e($user->email) ?>" 
              readonly
            >
          </div>

          <div class="form-group">
            <label for="phone" class="form-label"><i class="fa-solid fa-phone"></i> PHONE NUMBER</label>
            <input 
              type="text" 
              id="phone" 
              name="phone" 
              class="cyber-input" 
              value="<?= e($user->phone ?? '') ?>" 
              required
            >
            <?php if (!empty($profile_errors['phone'])): ?>
              <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($profile_errors['phone'][0]) ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn-primary" style="justify-content: center;">
            SAVE PROFILE CHANGES <i class="fa-solid fa-floppy-disk"></i>
          </button>
        </form>
      </div>

      <!-- Password Change Card -->
      <div class="account-card">
        <h3 style="font-family: 'Orbitron'; color: var(--violet); margin-bottom: 20px; font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <i class="fa-solid fa-key"></i> CHANGE SECURITY PASSWORD
        </h3>

        <form action="/account/password" method="POST">
          <?= \App\Helpers\CSRF::field() ?>

          <div class="form-group">
            <label for="current_password" class="form-label"><i class="fa-solid fa-lock"></i> CURRENT PASSWORD</label>
            <input 
              type="password" 
              id="current_password" 
              name="current_password" 
              class="cyber-input" 
              placeholder="••••••••••••" 
              required
            >
            <?php if (!empty($password_errors['current_password'])): ?>
              <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($password_errors['current_password'][0]) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="new_password" class="form-label"><i class="fa-solid fa-shield-halved"></i> NEW PASSWORD (MIN 8 CHARS)</label>
            <input 
              type="password" 
              id="new_password" 
              name="new_password" 
              class="cyber-input" 
              placeholder="••••••••••••" 
              required
            >
            <?php if (!empty($password_errors['new_password'])): ?>
              <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($password_errors['new_password'][0]) ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="confirm_password" class="form-label"><i class="fa-solid fa-check-double"></i> CONFIRM NEW PASSWORD</label>
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              class="cyber-input" 
              placeholder="••••••••••••" 
              required
            >
            <?php if (!empty($password_errors['confirm_password'])): ?>
              <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= e($password_errors['confirm_password'][0]) ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn-primary" style="justify-content: center; background: linear-gradient(135deg, var(--violet), var(--blue)); border-color: var(--violet);">
            UPDATE PASSWORD <i class="fa-solid fa-shield-virus"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
