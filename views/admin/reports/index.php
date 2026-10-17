<?php
/**
 * YasIX Store - Store Intelligence & Performance Reports View
 */
$summary = $report['summary'] ?? [];
$period = $filters['period'] ?? '30days';
?>

<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 4px;">
        PERFORMANCE <span style="color: var(--neon-cyan);">INTELLIGENCE MATRIX</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Analyze delivered revenue, COD settlement efficiency, product velocity, and generate sanitized data exports.
      </p>
    </div>
  </div>
</div>

<!-- Period Selection Filter Bar -->
<form method="GET" action="/admin/reports" class="admin-filter-bar" style="margin-bottom: 24px;">
  <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
    <a href="/admin/reports?period=today" class="btn-admin <?= $period === 'today' ? 'btn-admin-cyan' : 'btn-admin-ghost' ?>" style="padding: 6px 14px; font-size: 0.8rem;">
      Today
    </a>
    <a href="/admin/reports?period=7days" class="btn-admin <?= $period === '7days' ? 'btn-admin-cyan' : 'btn-admin-ghost' ?>" style="padding: 6px 14px; font-size: 0.8rem;">
      Last 7 Days
    </a>
    <a href="/admin/reports?period=30days" class="btn-admin <?= $period === '30days' ? 'btn-admin-cyan' : 'btn-admin-ghost' ?>" style="padding: 6px 14px; font-size: 0.8rem;">
      Last 30 Days
    </a>
    <a href="/admin/reports?period=ytd" class="btn-admin <?= $period === 'ytd' ? 'btn-admin-cyan' : 'btn-admin-ghost' ?>" style="padding: 6px 14px; font-size: 0.8rem;">
      Year to Date
    </a>
    <a href="/admin/reports?period=all" class="btn-admin <?= $period === 'all' ? 'btn-admin-cyan' : 'btn-admin-ghost' ?>" style="padding: 6px 14px; font-size: 0.8rem;">
      All Time
    </a>
  </div>

  <div style="display: flex; gap: 8px; align-items: center; margin-left: auto;">
    <input type="hidden" name="period" value="custom">
    <input 
      type="date" 
      name="date_from" 
      class="admin-form-control" 
      value="<?= e($filters['date_from'] ?? '') ?>" 
      title="Custom Range From"
      style="min-width: 130px;"
    >
    <span style="color: var(--text-muted); font-size: 0.85rem;">to</span>
    <input 
      type="date" 
      name="date_to" 
      class="admin-form-control" 
      value="<?= e($filters['date_to'] ?? '') ?>" 
      title="Custom Range To"
      style="min-width: 130px;"
    >
    <button type="submit" class="btn-admin btn-admin-ghost" style="padding: 6px 14px; font-size: 0.8rem;">
      <i class="fa-solid fa-calendar-check"></i> Filter
    </button>
  </div>
</form>

<!-- Performance Metric Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Delivered Sales Revenue</div>
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 800; color: var(--neon-cyan);">
      LKR <?= number_format((float)($summary['delivered_revenue'] ?? 0), 2) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
      Delivered: <?= (int)($summary['delivered_orders'] ?? 0) ?> orders
    </div>
  </div>

  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Collected COD Cash</div>
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 800; color: var(--neon-green);">
      LKR <?= number_format((float)($summary['collected_cod_cash'] ?? 0), 2) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
      Settled: <?= (int)($summary['completed_payments_count'] ?? 0) ?> payments
    </div>
  </div>

  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Total Period Orders</div>
    <div style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: #fff;">
      <?= (int)($summary['period_orders'] ?? 0) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
      Cancelled: <?= (int)($summary['cancelled_orders'] ?? 0) ?> orders
    </div>
  </div>

  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Gross Order Value (Pipeline)</div>
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.4rem; font-weight: 800; color: var(--neon-gold);">
      LKR <?= number_format((float)($summary['total_order_value'] ?? 0), 2) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
      Pending COD: LKR <?= number_format((float)($summary['pending_cod_cash'] ?? 0), 2) ?>
    </div>
  </div>

  <div class="admin-card" style="padding: 20px;">
    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Warranty RMA Volume</div>
    <div style="font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--neon-purple);">
      <?= (int)($summary['total_claims'] ?? 0) ?>
    </div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
      Approved: <?= (int)($summary['approved_claims'] ?? 0) ?> | Resolved: <?= (int)($summary['resolved_claims'] ?? 0) ?>
    </div>
  </div>
</div>

<!-- Safe Data Export Hub -->
<div class="admin-card" style="margin-bottom: 24px; border-color: rgba(0, 240, 255, 0.3);">
  <div class="admin-card-header" style="background: rgba(0, 240, 255, 0.05);">
    <div class="admin-card-title" style="color: var(--neon-cyan);">
      <i class="fa-solid fa-file-export"></i> Safe CSV Data Export Hub
    </div>
    <span style="font-size: 0.8rem; color: var(--neon-green); display: flex; align-items: center; gap: 6px;">
      <i class="fa-solid fa-shield-halved"></i> Formula Injection Protection Active
    </span>
  </div>
  <div style="padding: 20px;">
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 16px;">
      Export raw structured datasets directly into UTF-8 sanitized CSV files for offline analysis in Excel, LibreOffice, or BI software. All potentially unsafe spreadsheet command prefixes (<code style="color: var(--neon-gold);">=</code>, <code style="color: var(--neon-gold);">+</code>, <code style="color: var(--neon-gold);">-</code>, <code style="color: var(--neon-gold);">@</code>, tab) are automatically neutralized.
    </p>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
      <a href="/admin/reports/export?type=orders&<?= http_build_query($filters) ?>" class="btn-admin btn-admin-cyan">
        <i class="fa-solid fa-file-csv"></i> Export Orders (CSV)
      </a>
      <a href="/admin/reports/export?type=payments" class="btn-admin btn-admin-gold">
        <i class="fa-solid fa-file-invoice-dollar"></i> Export COD Payments (CSV)
      </a>
      <a href="/admin/reports/export?type=inventory" class="btn-admin btn-admin-ghost">
        <i class="fa-solid fa-boxes-stacked"></i> Export Inventory Matrix (CSV)
      </a>
      <a href="/admin/reports/export?type=warranties" class="btn-admin btn-admin-ghost" style="color: var(--neon-purple); border-color: rgba(184, 41, 247, 0.4);">
        <i class="fa-solid fa-shield-halved"></i> Export Warranty Claims (CSV)
      </a>
    </div>
  </div>
</div>

<!-- Detailed Performance Grids -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
  <!-- Fulfilment Status Distribution -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-chart-pie"></i> Fulfilment Stage Distribution
      </div>
    </div>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Status</th>
            <th style="text-align: center;">Order Volume</th>
            <th style="text-align: right;">Cumulative Value</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($report['status_distribution'])): ?>
            <tr>
              <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 24px;">No orders found in this period.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($report['status_distribution'] as $dist): ?>
              <tr>
                <td>
                  <span class="admin-badge admin-badge-cyan">
                    <?= e(str_replace('_', ' ', $dist['status'])) ?>
                  </span>
                </td>
                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff;">
                  <?= (int)$dist['count'] ?>
                </td>
                <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: var(--neon-cyan);">
                  LKR <?= number_format((float)$dist['total_amount'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Performing Hardware SKUs -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">
        <i class="fa-solid fa-ranking-star"></i> Top Velocity Hardware SKUs
      </div>
    </div>
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Product Hardware</th>
            <th style="text-align: center;">Units Sold</th>
            <th style="text-align: right;">Revenue (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($report['top_products'])): ?>
            <tr>
              <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 24px;">No product sales recorded in this period.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($report['top_products'] as $top): ?>
              <tr>
                <td style="color: #fff; font-weight: 600;">
                  <?= e($top['product_name']) ?>
                </td>
                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #fff;">
                  <?= (int)$top['total_units_sold'] ?>
                </td>
                <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: var(--neon-green);">
                  LKR <?= number_format((float)$top['total_revenue'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
