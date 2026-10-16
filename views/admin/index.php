<?php
/**
 * YasIX Store - Admin Analytics Dashboard
 */
?>

<div style="margin-bottom: 28px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 6px;">
        MISSION CONTROL <span style="color: var(--neon-cyan);">DASHBOARD</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">
        Real-time telemetry, store transaction metrics, inventory alerts, and COD settlement logs.
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="/admin/products/create" class="btn-admin btn-admin-cyan">
        <i class="fa-solid fa-plus"></i> New Product
      </a>
      <a href="/admin/reports" class="btn-admin btn-admin-ghost">
        <i class="fa-solid fa-file-export"></i> Export Reports
      </a>
    </div>
  </div>
</div>

<!-- Revenue Distinction Panel -->
<div class="revenue-breakdown-panel">
  <div class="rev-card" style="border-left: 3px solid var(--neon-emerald);">
    <div class="rev-label">
      <i class="fa-solid fa-money-bill-wave" style="color: var(--neon-emerald);"></i>
      Actual Cash Collected
    </div>
    <div class="rev-amount" style="color: var(--neon-emerald);">
      <?= formatCurrency($stats['collected_cash_amount'] ?? 0) ?>
    </div>
    <div class="rev-desc">
      Physical cash confirmed received from completed COD order deliveries (<?= (int)$stats['collected_cod_count'] ?> receipts).
    </div>
  </div>

  <div class="rev-card" style="border-left: 3px solid var(--neon-cyan);">
    <div class="rev-label">
      <i class="fa-solid fa-truck-fast" style="color: var(--neon-cyan);"></i>
      Delivered Sales Volume
    </div>
    <div class="rev-amount" style="color: var(--neon-cyan);">
      <?= formatCurrency($stats['delivered_revenue'] ?? 0) ?>
    </div>
    <div class="rev-desc">
      Total value of packages successfully handed over to customers (<?= (int)$stats['delivered_orders'] ?> orders).
    </div>
  </div>

  <div class="rev-card" style="border-left: 3px solid var(--neon-gold);">
    <div class="rev-label">
      <i class="fa-solid fa-hand-holding-dollar" style="color: var(--neon-gold);"></i>
      Pending COD In Transit
    </div>
    <div class="rev-amount" style="color: var(--neon-gold);">
      <?= formatCurrency($stats['pending_cod_amount'] ?? 0) ?>
    </div>
    <div class="rev-desc">
      Outstanding cash balance to be collected by courier partners upon delivery (<?= (int)$stats['pending_cod_count'] ?> pending).
    </div>
  </div>

  <div class="rev-card" style="border-left: 3px solid var(--neon-purple);">
    <div class="rev-label">
      <i class="fa-solid fa-chart-pie" style="color: var(--neon-purple);"></i>
      Gross Placed Orders Value
    </div>
    <div class="rev-amount" style="color: #fff;">
      <?= formatCurrency($stats['total_order_value'] ?? 0) ?>
    </div>
    <div class="rev-desc">
      Combined gross booking value across all valid non-cancelled orders in system history.
    </div>
  </div>
</div>

<!-- Primary KPI Summary Cards -->
<div class="stats-grid">
  <!-- Total Orders -->
  <div class="stat-card" style="--stat-accent: var(--neon-cyan);">
    <div class="stat-card-header">
      <span class="stat-title">Total Orders</span>
      <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
    </div>
    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
    <div class="stat-subtext">All-time store orders placed</div>
  </div>

  <!-- Awaiting Confirmation -->
  <div class="stat-card" style="--stat-accent: var(--neon-gold);">
    <div class="stat-card-header">
      <span class="stat-title">Awaiting Confirmation</span>
      <div class="stat-icon"><i class="fa-solid fa-hourglass-start"></i></div>
    </div>
    <div class="stat-value" style="color: var(--neon-gold);"><?= number_format($stats['pending_orders']) ?></div>
    <div class="stat-subtext">Orders pending initial verification</div>
  </div>

  <!-- In Processing -->
  <div class="stat-card" style="--stat-accent: #38bdf8;">
    <div class="stat-card-header">
      <span class="stat-title">In Fulfillment</span>
      <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
    </div>
    <div class="stat-value"><?= number_format($stats['processing_orders']) ?></div>
    <div class="stat-subtext">Confirmed or currently packing</div>
  </div>

  <!-- Delivered -->
  <div class="stat-card" style="--stat-accent: var(--neon-emerald);">
    <div class="stat-card-header">
      <span class="stat-title">Delivered Orders</span>
      <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
    </div>
    <div class="stat-value" style="color: var(--neon-emerald);"><?= number_format($stats['delivered_orders']) ?></div>
    <div class="stat-subtext">Successfully fulfilled packages</div>
  </div>

  <!-- Registered Customers -->
  <div class="stat-card" style="--stat-accent: #a855f7;">
    <div class="stat-card-header">
      <span class="stat-title">Registered Clients</span>
      <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
    </div>
    <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
    <div class="stat-subtext">Verified customer accounts</div>
  </div>

  <!-- Active Catalog Products -->
  <div class="stat-card" style="--stat-accent: var(--neon-cyan);">
    <div class="stat-card-header">
      <span class="stat-title">Active Products</span>
      <div class="stat-icon"><i class="fa-solid fa-microchip"></i></div>
    </div>
    <div class="stat-value"><?= number_format($stats['active_products']) ?></div>
    <div class="stat-subtext">Live catalog listings (<?= number_format($stats['total_inventory_units']) ?> units)</div>
  </div>

  <!-- Low Stock Alert -->
  <div class="stat-card" style="--stat-accent: #f97316;">
    <div class="stat-card-header">
      <span class="stat-title">Low Stock Items</span>
      <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    </div>
    <div class="stat-value" style="color: #f97316;"><?= number_format($stats['low_stock_products']) ?></div>
    <div class="stat-subtext">Products with &le; 5 units remaining</div>
  </div>

  <!-- Out of Stock -->
  <div class="stat-card" style="--stat-accent: var(--neon-crimson);">
    <div class="stat-card-header">
      <span class="stat-title">Out of Stock</span>
      <div class="stat-icon"><i class="fa-solid fa-ban"></i></div>
    </div>
    <div class="stat-value" style="color: var(--neon-crimson);"><?= number_format($stats['out_of_stock_products']) ?></div>
    <div class="stat-subtext">Depleted inventory listings</div>
  </div>

  <!-- Open RMA Claims -->
  <div class="stat-card" style="--stat-accent: #eab308;">
    <div class="stat-card-header">
      <span class="stat-title">Open RMA Claims</span>
      <div class="stat-icon"><i class="fa-solid fa-shield-virus"></i></div>
    </div>
    <div class="stat-value" style="color: #eab308;"><?= number_format($stats['open_claims']) ?></div>
    <div class="stat-subtext">Warranty requests awaiting review</div>
  </div>
</div>

<!-- Two-Column Operational Grids -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; margin-bottom: 24px;">
  <!-- Recent Orders Table -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-clock-rotate-left"></i> Recent Orders Telemetry
      </div>
      <a href="/admin/orders" class="btn-admin btn-admin-ghost btn-admin-sm">View All Orders</a>
    </div>

    <?php if (empty($recent_orders)): ?>
      <div class="admin-empty-state">
        <i class="fa-solid fa-inbox"></i>
        <h3>No Order History Yet</h3>
        <p>Customer orders will stream into this feed automatically.</p>
      </div>
    <?php else: ?>
      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_orders as $ord): ?>
              <tr>
                <td>
                  <strong style="color: var(--neon-cyan); font-family: 'Share Tech Mono', monospace;">
                    <?= e($ord['order_number']) ?>
                  </strong>
                  <div style="font-size: 0.72rem; color: var(--text-dim);">
                    <?= date('Y-m-d H:i', strtotime($ord['created_at'])) ?>
                  </div>
                </td>
                <td>
                  <div style="font-weight: 600;"><?= e($ord['customer_name']) ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-dim);"><?= e($ord['customer_email']) ?></div>
                </td>
                <td style="font-weight: 700;">
                  <?= formatCurrency($ord['total_amount']) ?>
                </td>
                <td>
                  <span class="status-pill status-<?= strtolower($ord['status']) ?>">
                    <?= e($ord['status']) ?>
                  </span>
                </td>
                <td>
                  <span class="status-pill status-<?= strtolower($ord['payment_status']) ?>">
                    <?= e($ord['payment_status']) ?>
                  </span>
                </td>
                <td>
                  <a href="/admin/orders/<?= urlencode($ord['order_number']) ?>" class="btn-admin btn-admin-ghost btn-admin-sm">
                    <i class="fa-solid fa-eye"></i> View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Low Stock Warnings -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title" style="color: #f97316;">
        <i class="fa-solid fa-triangle-exclamation" style="color: #f97316;"></i> Low Stock Attention Grid
      </div>
      <a href="/admin/inventory" class="btn-admin btn-admin-ghost btn-admin-sm">Inventory Manager</a>
    </div>

    <?php if (empty($low_stock_items)): ?>
      <div class="admin-empty-state">
        <i class="fa-solid fa-boxes-packing" style="color: var(--neon-emerald);"></i>
        <h3 style="color: var(--neon-emerald);">Inventory Levels Nominal</h3>
        <p>No products are currently under the 5-unit restock threshold.</p>
      </div>
    <?php else: ?>
      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Category</th>
              <th>Unit Price</th>
              <th>Stock</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($low_stock_items as $prod): ?>
              <tr>
                <td>
                  <div style="font-weight: 700; color: #fff;"><?= e($prod['name']) ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-dim);"><?= e($prod['brand']) ?></div>
                </td>
                <td style="color: var(--text-muted); font-size: 0.85rem;">
                  <?= e($prod['category_name'] ?? 'Unassigned') ?>
                </td>
                <td style="font-weight: 600;">
                  <?= formatCurrency($prod['price']) ?>
                </td>
                <td>
                  <?php if ((int)$prod['stock_quantity'] === 0): ?>
                    <span class="status-pill status-out_of_stock">
                      <i class="fa-solid fa-ban"></i> 0 DEPLETED
                    </span>
                  <?php else: ?>
                    <span class="status-pill status-pending">
                      <i class="fa-solid fa-triangle-exclamation"></i> <?= (int)$prod['stock_quantity'] ?> LEFT
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="/admin/inventory" class="btn-admin btn-admin-cyan btn-admin-sm">
                    <i class="fa-solid fa-plus-minus"></i> Restock
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Secondary Row: Top Selling Products & Status Distribution -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px;">
  <!-- Best Selling Products -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-fire" style="color: var(--neon-gold);"></i> Best-Selling Hardware
      </div>
      <span style="font-size: 0.78rem; color: var(--text-dim);">By units delivered/ordered</span>
    </div>

    <?php if (empty($top_selling)): ?>
      <div class="admin-empty-state">
        <i class="fa-solid fa-chart-column"></i>
        <h3>No Sales Data Recorded</h3>
        <p>Top performers will calculate once orders are fulfilled.</p>
      </div>
    <?php else: ?>
      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product Name</th>
              <th style="text-align: right;">Units Sold</th>
              <th style="text-align: right;">Total Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_selling as $item): ?>
              <tr>
                <td style="font-weight: 600; color: #fff;">
                  <?= e($item['product_name']) ?>
                </td>
                <td style="text-align: right; font-weight: 700; color: var(--neon-cyan);">
                  <?= (int)$item['total_units_sold'] ?>
                </td>
                <td style="text-align: right; font-weight: 700; color: var(--neon-emerald);">
                  <?= formatCurrency($item['total_revenue']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Orders by Status Breakdown -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-diagram-project"></i> Orders by Status Lifecycle
      </div>
      <span style="font-size: 0.78rem; color: var(--text-dim);">Historical distribution</span>
    </div>

    <?php if (empty($orders_by_status)): ?>
      <div class="admin-empty-state">
        <i class="fa-solid fa-chart-pie"></i>
        <h3>No Status Logs</h3>
        <p>Status breakdown will appear when orders are created.</p>
      </div>
    <?php else: ?>
      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Status</th>
              <th style="text-align: right;">Orders Count</th>
              <th style="text-align: right;">Gross Value</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders_by_status as $row): ?>
              <tr>
                <td>
                  <span class="status-pill status-<?= strtolower($row['status']) ?>">
                    <?= e($row['status']) ?>
                  </span>
                </td>
                <td style="text-align: right; font-weight: 700; color: #fff;">
                  <?= number_format($row['count']) ?>
                </td>
                <td style="text-align: right; font-weight: 600; color: var(--text-muted);">
                  <?= formatCurrency($row['total_amount']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
