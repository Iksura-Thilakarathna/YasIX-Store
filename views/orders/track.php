<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container" style="padding: 40px 20px 80px; max-width: 900px;">
  <!-- BREADCRUMBS & TOP NAV -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <a href="/orders/<?= urlencode($order['order_number']) ?>" style="color: var(--cyan); text-decoration: none; font-size: 0.9rem; font-family: 'Orbitron';">
      <i class="fa-solid fa-arrow-left"></i> BACK TO ORDER RECEIPT
    </a>
    <div style="display: flex; gap: 10px;">
      <a href="/orders" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
        <i class="fa-solid fa-list"></i> ALL ORDERS
      </a>
    </div>
  </div>

  <!-- ORDER SUMMARY HEADER -->
  <div class="account-card" style="padding: 28px; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 18px; margin-bottom: 18px;">
      <div>
        <div class="section-subtitle">// REAL-TIME COURIER RADAR</div>
        <h1 class="auth-title" style="font-size: 1.8rem; margin-bottom: 6px; color: var(--text);">
          TRACKING ORDER #<?= e($order['order_number']) ?>
        </h1>
        <p style="color: var(--muted); font-size: 0.9rem; margin: 0;">
          Courier Dispatch Service: <strong style="color: var(--cyan);">YasIX Express Logistics</strong> | Standard Domestic Route (Sri Lanka)
        </p>
      </div>

      <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-family: 'Orbitron'; font-size: 0.85rem; font-weight: 700; <?= $order['status_meta']['badge_style'] ?>">
          <i class="fa-solid <?= $order['status_meta']['icon'] ?>"></i> <?= e($order['status_meta']['label']) ?>
        </span>
      </div>
    </div>

    <!-- ESTIMATE BAR -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: rgba(3, 7, 18, 0.6); padding: 16px; border-radius: 8px;">
      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">ESTIMATED TRANSIT WINDOW</div>
        <div style="font-size: 1rem; font-weight: 700; color: #00ff88;">
          5–7 Working Days
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">DELIVERY TARGET DATES</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
          <?= e($order['estimated_delivery_start']) ?> – <?= e($order['estimated_delivery_end']) ?>
        </div>
      </div>

      <div>
        <div style="font-size: 0.75rem; color: var(--muted); font-family: 'Orbitron';">DESTINATION DISTRICT</div>
        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text);">
          <?= e($order['city']) ?>, <?= e($order['state']) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- CANCELLATION / TERMINAL NOTICE (IF APPLICABLE) -->
  <?php if ($tracking['is_cancelled']): ?>
    <div class="account-card" style="padding: 24px; border: 1px solid rgba(255, 0, 85, 0.5); background: rgba(255, 0, 85, 0.08); margin-bottom: 24px;">
      <div style="display: flex; gap: 16px; align-items: center;">
        <div style="font-size: 2.2rem; color: #ff3377;">
          <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div>
          <h3 style="font-family: 'Orbitron'; color: #ff3377; font-size: 1.1rem; margin-bottom: 4px;">
            ORDER HAS BEEN CANCELLED
          </h3>
          <p style="color: var(--muted); font-size: 0.9rem; margin: 0; line-height: 1.5;">
            This order was cancelled prior to fulfilment dispatch. Hardware reservations have been released back into matrix inventory, and no payment is due.
          </p>
        </div>
      </div>
    </div>
  <?php elseif ($tracking['is_delivery_failed']): ?>
    <div class="account-card" style="padding: 24px; border: 1px solid rgba(239, 68, 68, 0.5); background: rgba(239, 68, 68, 0.08); margin-bottom: 24px;">
      <div style="display: flex; gap: 16px; align-items: center;">
        <div style="font-size: 2.2rem; color: #ef4444;">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
          <h3 style="font-family: 'Orbitron'; color: #ef4444; font-size: 1.1rem; margin-bottom: 4px;">
            DELIVERY ATTEMPT FAILED
          </h3>
          <p style="color: var(--muted); font-size: 0.9rem; margin: 0; line-height: 1.5;">
            The courier was unable to complete doorstep delivery. Our logistics dispatch team will contact you at <strong><?= e($order['customer_phone']) ?></strong> to reschedule.
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- PROGRESS TIMELINE BAR -->
  <div class="account-card" style="padding: 30px; margin-bottom: 24px;">
    <h3 style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--cyan); margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
      <i class="fa-solid fa-timeline"></i> DISPATCH MILESTONE LIFECYCLE
    </h3>

    <!-- Progress percentage bar -->
    <?php if (!$tracking['is_cancelled'] && !$tracking['is_delivery_failed']): ?>
      <div style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-family: 'Orbitron'; color: var(--muted); margin-bottom: 8px;">
          <span>FULFILMENT RADAR</span>
          <span style="color: var(--cyan); font-weight: 700;"><?= $tracking['progress_percent'] ?>% COMPLETED</span>
        </div>
        <div style="height: 6px; width: 100%; background: rgba(255, 255, 255, 0.08); border-radius: 3px; overflow: hidden;">
          <div style="height: 100%; width: <?= $tracking['progress_percent'] ?>%; background: linear-gradient(90deg, var(--cyan), #00ff88); transition: width 0.5s ease; box-shadow: 0 0 10px var(--cyan-glow);"></div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Milestones List -->
    <div style="display: flex; flex-direction: column; gap: 20px; position: relative;">
      <?php foreach ($tracking['milestones'] as $idx => $milestone): ?>
        <div style="display: flex; gap: 18px; align-items: flex-start; position: relative;">
          <!-- Node Dot & Icon -->
          <div style="
            width: 44px; 
            height: 44px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-shrink: 0;
            font-size: 1.1rem;
            z-index: 2;
            <?php if ($milestone['state'] === 'completed'): ?>
              background: rgba(0, 255, 136, 0.15); 
              border: 2px solid #00ff88; 
              color: #00ff88;
              box-shadow: 0 0 12px rgba(0, 255, 136, 0.3);
            <?php elseif ($milestone['state'] === 'current'): ?>
              background: rgba(0, 245, 255, 0.2); 
              border: 2px solid var(--cyan); 
              color: var(--cyan);
              box-shadow: 0 0 15px var(--cyan-glow);
            <?php elseif ($milestone['state'] === 'cancelled'): ?>
              background: rgba(255, 255, 255, 0.05); 
              border: 2px solid rgba(255, 255, 255, 0.1); 
              color: var(--muted);
            <?php else: ?>
              background: rgba(255, 255, 255, 0.05); 
              border: 2px solid rgba(255, 255, 255, 0.15); 
              color: var(--muted);
            <?php endif; ?>
          ">
            <?php if ($milestone['state'] === 'completed'): ?>
              <i class="fa-solid fa-check"></i>
            <?php else: ?>
              <i class="<?= $milestone['icon'] ?>"></i>
            <?php endif; ?>
          </div>

          <!-- Node Description -->
          <div style="flex-grow: 1; padding-top: 4px;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
              <h4 style="
                font-family: 'Orbitron'; 
                font-size: 1rem; 
                margin: 0;
                <?php if ($milestone['state'] === 'completed'): ?>
                  color: #00ff88;
                <?php elseif ($milestone['state'] === 'current'): ?>
                  color: var(--cyan);
                <?php else: ?>
                  color: var(--muted);
                <?php endif; ?>
              ">
                <?= e($milestone['title']) ?>
              </h4>

              <?php if ($milestone['is_current']): ?>
                <span style="font-family: 'Orbitron'; font-size: 0.7rem; font-weight: 800; background: var(--cyan); color: #000; padding: 2px 8px; border-radius: 4px; letter-spacing: 0.5px;">
                  CURRENT PHASE
                </span>
              <?php endif; ?>
            </div>

            <p style="
              font-size: 0.85rem; 
              margin: 4px 0 0; 
              line-height: 1.5;
              color: <?= ($milestone['state'] === 'upcoming' || $milestone['state'] === 'cancelled') ? 'rgba(255,255,255,0.3)' : 'var(--muted)' ?>;
            ">
              <?= e($milestone['description']) ?>
            </p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- AUDIT LOG HISTORY -->
  <?php if (!empty($tracking['status_history'])): ?>
    <div class="account-card" style="padding: 26px; margin-bottom: 24px;">
      <h3 style="font-family: 'Orbitron'; font-size: 1rem; color: var(--cyan); margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <i class="fa-solid fa-list-check"></i> LOGISTICS AUDIT TRAIL
      </h3>

      <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($tracking['status_history'] as $hist): ?>
          <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 10px; font-size: 0.85rem; flex-wrap: wrap; gap: 8px;">
            <div>
              <span style="font-family: 'Orbitron'; font-weight: 700; color: var(--cyan);">
                <?= e($hist['new_status']) ?>
              </span>
              <?php if (!empty($hist['reason'])): ?>
                <span style="color: var(--muted); margin-left: 6px;">— <?= e($hist['reason']) ?></span>
              <?php endif; ?>
              <div style="font-size: 0.75rem; color: var(--muted); margin-top: 2px;">
                Authorized by: <?= e($hist['actor_name'] ?? $hist['actor_role']) ?>
              </div>
            </div>

            <div style="font-size: 0.8rem; color: var(--muted); font-family: 'Orbitron';">
              <?= e(date('M d, Y - h:i A', strtotime($hist['created_at']))) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- BOTTOM BACK NAVIGATION -->
  <div style="display: flex; justify-content: center; gap: 14px;">
    <a href="/orders/<?= urlencode($order['order_number']) ?>" class="btn-primary" style="padding: 12px 24px;">
      <i class="fa-solid fa-receipt"></i> ORDER RECEIPT & SPECS
    </a>
    <a href="/orders" class="btn-secondary" style="padding: 12px 24px;">
      <i class="fa-solid fa-list"></i> ALL ORDERS
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
