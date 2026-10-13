<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'YasIX Store - Next-Gen Cyber Hardware & Tech') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome Vector Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Cyber Styling -->
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <div class="bg-grid"></div>
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>

  <!-- Custom Cursor Elements -->
  <div class="cursor" id="cursor"></div>
  <div class="cursor-ring" id="cursor-ring"></div>

  <!-- Navigation -->
  <nav>
    <div class="container nav-container">
      <div class="nav-left">
        <a href="/" class="nav-logo">YAS<span>IX</span></a>
        <div class="nav-status">
          <span class="status-dot"></span>
          MATRIX: ONLINE 99.9%
        </div>
      </div>
      <ul class="nav-links">
        <li><a href="/">Home</a></li>
        <li><a href="/products">Store Catalog</a></li>
        <li><a href="/#deals">Flash Sale</a></li>
        <li><a href="/#features">Features</a></li>
        <li><a href="/#reviews">Reviews</a></li>
        <?php if (\App\Helpers\Session::has('user_id')): ?>
          <li><a href="/orders"><i class="fa-solid fa-receipt"></i> Orders</a></li>
          <li><a href="/warranties"><i class="fa-solid fa-shield-halved"></i> Warranties</a></li>
          <li><a href="/account" class="btn-signup-link"><i class="fa-solid fa-user-gear"></i> <?= e(\App\Helpers\Session::get('user_name', 'Account')) ?></a></li>
          <li>
            <form action="/logout" method="POST" style="display:inline;">
              <?= \App\Helpers\CSRF::field() ?>
              <button type="submit" class="btn-logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
            </form>
          </li>
        <?php else: ?>
          <li><a href="/login"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
          <li><a href="/register" class="btn-signup-link"><i class="fa-solid fa-user-plus"></i> Sign Up</a></li>
        <?php endif; ?>
      </ul>
      <div class="nav-actions">
        <?php
          $cartBadgeCount = 0;
          if (\App\Helpers\Session::has('user_id')) {
              try {
                  $cartBadgeCount = (new \App\Services\CartService())->getCartCount((int)\App\Helpers\Session::get('user_id'));
              } catch (\Throwable $e) {}
          }
        ?>
        <a href="/cart" class="btn-cart" id="open-cart-btn" style="text-decoration: none; display: inline-flex; align-items: center;">
          <i class="fa-solid fa-cart-shopping"></i> CART <span class="cart-badge" id="cart-count"><?= $cartBadgeCount ?></span>
        </a>
      </div>
    </div>
  </nav>

  <main>
