<section style="padding: 120px 0; text-align: center;">
  <div class="container">
    <div style="font-size: 6rem; color: #ff3366; filter: drop-shadow(0 0 25px rgba(255, 51, 102, 0.6)); margin-bottom: 16px;">
      <i class="fa-solid fa-shield-halved"></i> 403
    </div>
    <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2.2rem; margin-bottom: 16px; color: #fff;">SECURITY PROTOCOL: ACCESS RESTRICTED</h1>
    <p style="color: #94a3b8; max-width: 540px; margin: 0 auto 32px; line-height: 1.6; font-size: 1.1rem;">
      <?= e($error ?? 'You lack the administrative authorization clearances required to access this sector of the matrix.') ?>
    </p>
    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
      <a href="/" class="btn-primary" style="padding: 12px 28px; background: #00f0ff; color: #050b14; font-weight: 700; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-house"></i> RETURN TO STOREFRONT
      </a>
      <?php if (\App\Helpers\Session::has('user_id')): ?>
        <a href="/account" style="padding: 12px 28px; background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.2); font-weight: 600; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-user"></i> MY ACCOUNT
        </a>
      <?php else: ?>
        <a href="/admin/login" style="padding: 12px 28px; background: rgba(255, 183, 0, 0.15); color: #ffb700; border: 1px solid rgba(255, 183, 0, 0.4); font-weight: 600; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-lock"></i> ADMIN ACCESS PORTAL
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>
