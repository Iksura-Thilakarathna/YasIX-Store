<!-- HERO SECTION -->
<section class="hero" id="home">
  <div class="container">
    <div class="hero-grid">
      <div class="hero-content">
        <div class="hero-badge"><i class="fa-solid fa-bolt-lightning" style="color: var(--cyan);"></i> NEXT-GEN CYBER HARDWARE MATRIX v3.0</div>
        <h1 class="hero-title">UPGRADE TO THE <span class="highlight">FUTURE</span> OF COMPUTING</h1>
        <p class="hero-subtitle">
          Engineered for extreme gaming performance, quantum neural processing, and ultra low-latency cybernetic gear.
        </p>
        <div class="hero-buttons">
          <a href="/products" class="btn-primary">EXPLORE CATALOG <i class="fa-solid fa-rocket"></i></a>
          <a href="#deals" class="btn-secondary">VIEW FLASH DEALS <i class="fa-solid fa-fire-flame-curved" style="color: var(--neon-pink);"></i></a>
        </div>
      </div>

      <div class="hero-card">
        <div class="hero-card-tag">FLAGSHIP RIG</div>
        <div class="hero-card-img-wrapper">
          <div class="tech-hologram">
            <i class="fa-solid fa-desktop tech-icon"></i>
          </div>
        </div>
        <h3 class="hero-card-title"><?= e($flashDeal['name'] ?? 'YasIX Neural Rig RTX 5090') ?></h3>
        <p class="hero-card-specs"><?= e($flashDeal['description'] ?? '32GB GDDR7 | Liquid Nitrogen Cooled | 6.0 GHz Quantum Chipset') ?></p>
        <div class="hero-card-footer">
          <div class="hero-card-price"><?= formatCurrency((float)($flashDeal['price'] ?? 3499.00)) ?></div>
          <?php if (!empty($flashDeal['slug'])): ?>
            <a href="/products/<?= e($flashDeal['slug']) ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">VIEW RIG <i class="fa-solid fa-arrow-right"></i></a>
          <?php else: ?>
            <a href="/products" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">EXPLORE <i class="fa-solid fa-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- STATS BAR -->
    <div class="stats-bar">
      <div class="stat-item">
        <div class="stat-number">150K+</div>
        <div class="stat-label">Cyber Gamers Joined</div>
      </div>
      <div class="stat-item">
        <div class="stat-number">0.01ms</div>
        <div class="stat-label">Neural Response Latency</div>
      </div>
      <div class="stat-item">
        <div class="stat-number">24/7</div>
        <div class="stat-label">Quantum Drone Shipping</div>
      </div>
      <div class="stat-item">
        <div class="stat-number">99.9%</div>
        <div class="stat-label">Matrix Uptime Rate</div>
      </div>
    </div>
  </div>
</section>

<!-- STORE CATALOG SECTION -->
<section class="store-section" id="store">
  <div class="container">
    <div class="section-header">
      <div class="section-subtitle">// STORE CATALOG</div>
      <h2 class="section-title">FEATURED CYBER HARDWARE</h2>
    </div>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
      <button class="tab-btn active" data-category="all">ALL GEAR</button>
      <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $cat): ?>
          <button class="tab-btn" data-category="<?= e($cat['slug']) ?>"><?= strtoupper(e($cat['name'])) ?></button>
        <?php endforeach; ?>
      <?php else: ?>
        <button class="tab-btn" data-category="cyber-gpus">CYBER GPUS</button>
        <button class="tab-btn" data-category="neural-heuristics">NEURAL HEURISTICS</button>
        <button class="tab-btn" data-category="rgb-kits">RGB KITS</button>
        <button class="tab-btn" data-category="quantum-gear">QUANTUM GEAR</button>
      <?php endif; ?>
    </div>

    <!-- PRODUCT GRID -->
    <div class="products-grid">
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $item): ?>
          <div class="product-card" data-category="<?= e($item['category_slug'] ?? 'quantum-gear') ?>">
            <div class="product-tag"><?= e($item['brand'] ?? 'FEATURED') ?></div>
            <div class="product-img-box" style="position: relative;">
              <?php if (!empty($item['primary_image'])): ?>
                <img src="<?= e($item['primary_image']) ?>" alt="<?= e($item['name']) ?>" style="max-width: 100%; max-height: 140px; object-fit: contain;">
              <?php else: ?>
                <i class="fa-solid <?= e($item['category_icon'] ?? $item['icon'] ?? 'fa-microchip') ?> product-icon"></i>
              <?php endif; ?>
              <span class="badge-condition-pill" style="position: absolute; top: 10px; right: 10px; font-size: 0.7rem; padding: 2px 6px;">
                <?= e(($item['condition'] ?? 'NEW') === 'REFURBISHED' ? 'REFURB' : (($item['condition'] ?? '') === 'CUSTOM_MOD' ? 'MOD' : 'NEW')) ?>
              </span>
            </div>
            <h3 class="product-title">
              <a href="/products/<?= e($item['slug'] ?? '') ?>" style="color: var(--text); text-decoration: none;">
                <?= e($item['name']) ?>
              </a>
            </h3>
            <p class="product-desc"><?= e($item['description'] ?? '') ?></p>
            
            <?php
              $avgRating = isset($item['avg_rating']) && $item['avg_rating'] !== null ? round((float)$item['avg_rating']) : 0;
              $revCount = (int)($item['review_count'] ?? $item['reviews_count'] ?? 0);
            ?>
            <div class="product-rating">
              <?php if ($revCount > 0): ?>
                <span style="color: var(--gold);"><?= str_repeat('★', max(1, min(5, (int)$avgRating))) ?></span>
                <span>(<?= $revCount ?>)</span>
              <?php else: ?>
                <span style="color: var(--muted); font-size: 0.8rem;"><i class="fa-regular fa-star"></i> Unreviewed</span>
              <?php endif; ?>
              <span style="color: var(--cyan); margin-left: auto; font-size: 0.75rem;">
                <i class="fa-solid fa-shield-halved"></i> <?= (int)($item['warranty_months'] ?? 12) ?>M
              </span>
            </div>
            
            <div class="product-footer">
              <div class="product-price"><?= formatCurrency((float)$item['price']) ?></div>
              <div style="display: flex; gap: 6px;">
                <a href="/products/<?= e($item['slug'] ?? '') ?>" class="btn-secondary" style="padding: 6px 10px; font-size: 0.8rem;" title="View Details">
                  <i class="fa-solid fa-arrow-right"></i>
                </a>
                <?php if (!empty($item['is_available'])): ?>
                  <form action="/cart/add" method="POST" style="margin: 0; display: inline;">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn-add-cart" style="padding: 6px 12px; font-size: 0.8rem;" title="Add to Cart">
                      <i class="fa-solid fa-cart-plus"></i> ADD
                    </button>
                  </form>
                <?php else: ?>
                  <button class="btn-add-cart" disabled style="opacity: 0.4; cursor: not-allowed; padding: 6px 12px; font-size: 0.8rem;" title="Out of Stock">
                    <i class="fa-solid fa-ban"></i> OUT
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- FLASH SALE DEALS SECTION -->
<section class="deals-section" id="deals">
  <div class="container">
    <div class="deals-card">
      <div class="deals-info">
        <div class="deals-timer-header">
          <i class="fa-solid fa-bolt-lightning"></i> DAILY CYBER FLASH DROP // LIMITED RUN
        </div>
        <h2 class="section-title" style="font-size: 2.2rem; margin-bottom: 16px;">
          <?= e($flashDeal['name'] ?? 'YasIX Apex Mech-Rig 2026 Edition') ?>
        </h2>
        <p style="color: var(--muted); line-height: 1.6; margin-bottom: 24px;">
          <?= e($flashDeal['description'] ?? 'Custom handcrafted titanium workstation rig with integrated liquid nitrogen loop and dual RTX 5090 GPUs.') ?>
        </p>

        <div class="countdown-box">
          <div class="time-block">
            <div class="time-val" id="hours">04</div>
            <div class="time-unit">Hours</div>
          </div>
          <div class="time-block">
            <div class="time-val" id="minutes">35</div>
            <div class="time-unit">Mins</div>
          </div>
          <div class="time-block">
            <div class="time-val" id="seconds">12</div>
            <div class="time-unit">Secs</div>
          </div>
        </div>

        <div class="progress-bar-container">
          <div class="progress-label">
            <span>Stock Status: <?= !empty($flashDeal['is_available']) ? 'In Stock' : 'Reserved' ?></span>
            <span><?= (int)($flashDeal['stock_quantity'] ?? 0) ?> Units In Vault</span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width: <?= min(100, max(25, (int)($flashDeal['stock_quantity'] ?? 5) * 10)) ?>%;"></div>
          </div>
        </div>
      </div>

      <div style="text-align: center; background: rgba(0,0,0,0.3); padding: 32px; border-radius: 16px; border: 1px solid rgba(255,0,85,0.2);">
        <div style="margin-bottom: 20px;">
          <?php if (!empty($flashDeal['primary_image'])): ?>
            <img src="<?= e($flashDeal['primary_image']) ?>" alt="<?= e($flashDeal['name'] ?? 'Flash Deal') ?>" style="max-height: 150px; object-fit: contain;">
          <?php else: ?>
            <i class="fa-solid <?= e($flashDeal['category_icon'] ?? 'fa-gamepad') ?>" style="font-size: 5.5rem; color: var(--neon-pink); filter: drop-shadow(0 0 20px var(--neon-pink));"></i>
          <?php endif; ?>
        </div>
        <div style="font-family: 'Orbitron'; font-size: 2rem; color: var(--gold); margin-bottom: 8px;">
          <?= formatCurrency((float)($flashDeal['price'] ?? 2999.00)) ?>
        </div>
        <p style="color: var(--cyan); font-family: 'Orbitron'; font-size: 0.85rem; margin-bottom: 24px;">
          <i class="fa-solid fa-shield-halved"></i> <?= (int)($flashDeal['warranty_months'] ?? 12) ?>M OFFICIAL WARRANTY
        </p>
        <?php if (!empty($flashDeal['slug'])): ?>
          <a href="/products/<?= e($flashDeal['slug']) ?>" class="btn-primary" style="background: linear-gradient(135deg, var(--neon-pink), var(--violet)); box-shadow: 0 0 20px rgba(255,0,85,0.5); width: 100%; justify-content: center; text-decoration: none;">
            VIEW FULL SPECIFICATIONS <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php else: ?>
          <a href="/products" class="btn-primary" style="background: linear-gradient(135deg, var(--neon-pink), var(--violet)); box-shadow: 0 0 20px rgba(255,0,85,0.5); width: 100%; justify-content: center; text-decoration: none;">
            EXPLORE STORE <i class="fa-solid fa-rocket"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES SECTION -->
<section class="features-section" id="features">
  <div class="container">
    <div class="section-header">
      <div class="section-subtitle">// MATRIX ADVANTAGE</div>
      <h2 class="section-title">WHY CHOOSE YASIX STORE</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-box"><i class="fa-solid fa-truck-fast"></i></div>
        <h3 class="feature-title">Quantum Shipping Grid</h3>
        <p class="feature-desc">Autonomous drone dispatch delivering your cyber hardware worldwide with zero delay.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fa-solid fa-shield-halved"></i></div>
        <h3 class="feature-title">Crypto Hardware Guarantee</h3>
        <p class="feature-desc">Cryptographically signed authentic components guaranteed by immutable blockchain registry.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fa-solid fa-brain"></i></div>
        <h3 class="feature-title">24/7 Neural AI Support</h3>
        <p class="feature-desc">Instant diagnostic assistance from our neural AI support agents and live senior engineers.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fa-solid fa-tag"></i></div>
        <h3 class="feature-title">Zero Latency Price Match</h3>
        <p class="feature-desc">Automated matrix scan guarantees you receive the lowest price available anywhere online.</p>
      </div>
    </div>
  </div>
</section>

<!-- REVIEWS SECTION -->
<section class="reviews-section" id="reviews">
  <div class="container">
    <div class="section-header">
      <div class="section-subtitle">// COMMUNITY FEEDBACK</div>
      <h2 class="section-title">CYBER GAMER REVIEWS</h2>
    </div>

    <div class="reviews-grid">
      <div class="review-card">
        <div class="review-user">
          <div class="review-avatar">VX</div>
          <div>
            <div class="review-name">@CyberViperX</div>
            <div class="review-tag"><i class="fa-solid fa-circle-check"></i> Verified Buyer</div>
          </div>
        </div>
        <div class="review-stars">★★★★★</div>
        <p class="review-text">"The RTX 5090 Cyber-Quantum rig blew away my expectations. 240FPS in 8K ultra settings with zero thermal throttling!"</p>
      </div>

      <div class="review-card">
        <div class="review-user">
          <div class="review-avatar" style="background: linear-gradient(135deg, var(--neon-pink), var(--gold));">NM</div>
          <div>
            <div class="review-name">@NeoMatrix_Dev</div>
            <div class="review-tag"><i class="fa-solid fa-circle-check"></i> Verified Buyer</div>
          </div>
        </div>
        <div class="review-stars">★★★★★</div>
        <p class="review-text">"Neural Headset feedback sync is unreal. Audio immersion feels like direct mind-link connection. Instant recommendation!"</p>
      </div>

      <div class="review-card">
        <div class="review-user">
          <div class="review-avatar" style="background: linear-gradient(135deg, var(--blue), var(--cyan));">AT</div>
          <div>
            <div class="review-name">@AeroTechGamer</div>
            <div class="review-tag"><i class="fa-solid fa-circle-check"></i> Verified Buyer</div>
          </div>
        </div>
        <div class="review-stars">★★★★★</div>
        <p class="review-text">"Quantum drone shipping arrived at my door in less than 4 hours. YasIX Store is officially the ultimate hardware marketplace."</p>
      </div>
    </div>
  </div>
</section>

<!-- NEWSLETTER SECTION -->
<section class="newsletter-section" id="contact">
  <div class="container">
    <div class="newsletter-card">
      <div class="section-subtitle" style="color: var(--cyan);"><i class="fa-solid fa-tower-cell"></i> MATRIX NETWORK JOIN</div>
      <h2 class="section-title" style="margin-bottom: 12px;">SUBSCRIBE TO CYBER DROPS</h2>
      <p style="color: var(--muted); max-width: 560px; margin: 0 auto; line-height: 1.6;">
        Get instant access to secret hardware drops, exclusive discount codes, and beta access keys directly in your inbox.
      </p>

      <form class="newsletter-form" onsubmit="event.preventDefault(); alert('🚀 Access granted! Connected to Matrix Network.');">
        <input type="email" class="newsletter-input" placeholder="Enter cyber email address..." required>
        <button type="submit" class="btn-primary">CONNECT <i class="fa-solid fa-paper-plane"></i></button>
      </form>
    </div>
  </div>
</section>
