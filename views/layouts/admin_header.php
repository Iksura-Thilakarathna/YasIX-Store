<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$adminName = \App\Helpers\Session::get('user_name', 'Administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'YasIX Store - Administration Matrix') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800;900&family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
  <div class="admin-bg-grid"></div>

  <!-- Admin Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand">
      <a href="/admin">
        <i class="fa-solid fa-server" style="color: var(--neon-cyan);"></i>
        <span>YAS</span>IX
      </a>
      <span class="badge-admin-tag"><i class="fa-solid fa-shield-halved"></i> COMMAND HUB</span>
    </div>

    <nav class="admin-nav">
      <div class="nav-section-title">Core Operations</div>
      <a href="/admin" class="<?= $currentPath === '/admin' ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge-high"></i> Dashboard
      </a>
      <a href="/admin/products" class="<?= str_starts_with($currentPath, '/admin/products') ? 'active' : '' ?>">
        <i class="fa-solid fa-boxes-stacked"></i> Products
      </a>
      <a href="/admin/categories" class="<?= str_starts_with($currentPath, '/admin/categories') ? 'active' : '' ?>">
        <i class="fa-solid fa-tags"></i> Categories
      </a>
      <a href="/admin/inventory" class="<?= str_starts_with($currentPath, '/admin/inventory') ? 'active' : '' ?>">
        <i class="fa-solid fa-warehouse"></i> Inventory
      </a>

      <div class="nav-section-title">Commerce & Clients</div>
      <a href="/admin/orders" class="<?= str_starts_with($currentPath, '/admin/orders') ? 'active' : '' ?>">
        <i class="fa-solid fa-receipt"></i> Orders
      </a>
      <a href="/admin/payments" class="<?= str_starts_with($currentPath, '/admin/payments') ? 'active' : '' ?>">
        <i class="fa-solid fa-money-bill-transfer"></i> Payments (COD)
      </a>
      <a href="/admin/customers" class="<?= str_starts_with($currentPath, '/admin/customers') ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i> Customers
      </a>
      <a href="/admin/warranties" class="<?= str_starts_with($currentPath, '/admin/warranties') ? 'active' : '' ?>">
        <i class="fa-solid fa-shield-virus"></i> Warranty Claims
      </a>

      <div class="nav-section-title">Intelligence</div>
      <a href="/admin/reports" class="<?= str_starts_with($currentPath, '/admin/reports') ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i> Reports & Export
      </a>
    </nav>

    <div class="admin-sidebar-footer">
      <a href="/" target="_blank" class="nav-btn" style="color: var(--neon-cyan); border: 1px solid rgba(0, 240, 255, 0.25); justify-content: center; font-size: 0.85rem;">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Storefront
      </a>
      <form action="/admin/logout" method="POST" style="margin: 0;">
        <?= \App\Helpers\CSRF::field() ?>
        <button type="submit" class="nav-btn" style="color: var(--neon-crimson); border: 1px solid rgba(255, 51, 102, 0.25); justify-content: center; font-size: 0.85rem;">
          <i class="fa-solid fa-right-from-bracket"></i> Disconnect
        </button>
      </form>
    </div>
  </aside>

  <!-- Main Content Area -->
  <div class="admin-main">
    <!-- Admin Top Header -->
    <header class="admin-header">
      <div class="admin-header-left">
        <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle Navigation">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="admin-header-title">
          <?= e($headerTitle ?? 'System Command Matrix') ?>
        </div>
      </div>

      <div class="admin-header-right">
        <div class="admin-status-indicator" style="font-size: 0.8rem; color: var(--neon-emerald); display: flex; align-items: center; gap: 6px;">
          <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--neon-emerald); box-shadow: 0 0 8px var(--neon-emerald);"></span>
          SYS: OPERATIONAL
        </div>

        <div class="admin-user-pill">
          <div class="user-avatar-circle">
            <i class="fa-solid fa-user-astronaut"></i>
          </div>
          <div class="user-info-text">
            <span class="user-name-tag"><?= e($adminName) ?></span>
            <span class="user-role-badge">ADMINISTRATOR</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Content Body -->
    <main class="admin-content">
      <?php if ($flashSuccess = \App\Helpers\Session::getFlash('success')): ?>
        <div class="admin-flash flash-success">
          <i class="fa-solid fa-circle-check"></i>
          <div><?= e($flashSuccess) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($flashError = \App\Helpers\Session::getFlash('error')): ?>
        <div class="admin-flash flash-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <div><?= e($flashError) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($flashWarning = \App\Helpers\Session::getFlash('warning')): ?>
        <div class="admin-flash flash-warning">
          <i class="fa-solid fa-circle-exclamation"></i>
          <div><?= e($flashWarning) ?></div>
        </div>
      <?php endif; ?>
