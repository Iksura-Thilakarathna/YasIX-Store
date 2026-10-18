<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Administrator Portal - YasIX Store') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800;900&family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    .admin-login-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      position: relative;
      z-index: 10;
    }
    .admin-login-card {
      width: 100%;
      max-width: 440px;
      background: rgba(11, 19, 34, 0.9);
      border: 1px solid rgba(0, 240, 255, 0.3);
      box-shadow: 0 0 35px rgba(0, 240, 255, 0.15), inset 0 0 20px rgba(0, 0, 0, 0.5);
      border-radius: 12px;
      padding: 36px 32px;
      backdrop-filter: blur(16px);
      position: relative;
      overflow: hidden;
    }
    .admin-login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--neon-cyan), var(--neon-gold));
    }
    .admin-login-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .admin-login-logo {
      font-family: 'Orbitron', sans-serif;
      font-size: 1.8rem;
      font-weight: 900;
      color: #fff;
      letter-spacing: 2px;
      margin-bottom: 8px;
    }
    .admin-login-logo span {
      color: var(--neon-cyan);
      text-shadow: 0 0 10px rgba(0, 240, 255, 0.6);
    }
    .admin-login-subtitle {
      font-size: 0.82rem;
      color: var(--neon-gold);
      text-transform: uppercase;
      letter-spacing: 2px;
      font-weight: 700;
    }
  </style>
</head>
<body class="admin-body">
  <div class="admin-bg-grid"></div>

  <div class="admin-login-wrapper">
    <div class="admin-login-card">
      <div class="admin-login-header">
        <div class="admin-login-logo">
          <i class="fa-solid fa-server" style="color: var(--neon-cyan); font-size: 1.6rem; margin-right: 6px;"></i>
          YAS<span>IX</span>
        </div>
        <div class="admin-login-subtitle">
          <i class="fa-solid fa-lock"></i> Command Clearance Required
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="admin-flash flash-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <div><?= e($error) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="admin-flash flash-success">
          <i class="fa-solid fa-circle-check"></i>
          <div><?= e($success) ?></div>
        </div>
      <?php endif; ?>

      <form action="/admin/login" method="POST" autocomplete="off">
        <?= \App\Helpers\CSRF::field() ?>

        <div class="admin-form-group">
          <label for="adminEmail"><i class="fa-solid fa-envelope"></i> Administrator Email</label>
          <input 
            type="email" 
            name="email" 
            id="adminEmail" 
            class="admin-form-control" 
            placeholder="admin@yasix.com" 
            value="<?= e($old['email'] ?? '') ?>" 
            required 
            autofocus
          >
        </div>

        <div class="admin-form-group">
          <label for="adminPass"><i class="fa-solid fa-key"></i> Clearance Key (Password)</label>
          <input 
            type="password" 
            name="password" 
            id="adminPass" 
            class="admin-form-control" 
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" 
            required
          >
        </div>

        <div style="margin-top: 28px;">
          <button type="submit" class="btn-admin btn-admin-cyan" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
            <i class="fa-solid fa-shield-halved"></i> Authenticate Clearance
          </button>
        </div>
      </form>

      <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--admin-border-subtle); display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-dim);">
        <a href="/" style="color: var(--neon-cyan); text-decoration: none;">
          <i class="fa-solid fa-arrow-left"></i> Return to Storefront
        </a>
        <span>SECURE NODE v2.6</span>
      </div>
    </div>
  </div>
</body>
</html>
