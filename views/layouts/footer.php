  </main>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        <div>
          <a href="#" class="nav-logo" style="margin-bottom: 16px; display: inline-block;">YAS<span>IX</span> STORE</a>
          <p style="color: var(--muted); line-height: 1.6; max-width: 320px;">
            The world's leading storefront for next-generation cybernetic rigs, neural acoustics, and ultra-high speed quantum computer hardware.
          </p>
        </div>

        <div class="footer-col">
          <h4>NAVIGATION</h4>
          <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/products">Store Catalog</a></li>
            <li><a href="/#deals">Flash Sales</a></li>
            <li><a href="/#features">Features</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>SUPPORT</h4>
          <ul>
            <li><a href="/orders">Order Tracking</a></li>
            <li><a href="/warranties">Warranty Claims</a></li>
            <li><a href="/account">Customer Account</a></li>
            <li><a href="/admin">Admin Portal</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>CONNECT</h4>
          <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 16px;">Join our Discord & GitHub matrix node</p>
          <div class="social-links-grid">
            <a href="#" class="social-icon-link"><i class="fa-brands fa-discord"></i></a>
            <a href="#" class="social-icon-link"><i class="fa-brands fa-github"></i></a>
            <a href="#" class="social-icon-link"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="#" class="social-icon-link"><i class="fa-brands fa-youtube"></i></a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div>© 2026 YasIX Store. All rights reserved. Matrix Division.</div>
        <div style="color: var(--cyan); font-family: 'Orbitron'; font-size: 0.75rem;">
          SYSTEM: ONLINE <i class="fa-solid fa-bolt" style="color: var(--cyan);"></i> ASIA-SOUTH-1 | LATENCY: 0.04ms
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Custom Cursor Script
    const cursor = document.getElementById('cursor');
    const ring = document.getElementById('cursor-ring');
    let mouseX = 0, mouseY = 0;
    let ringX = 0, ringY = 0;

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      cursor.style.left = mouseX + 'px';
      cursor.style.top = mouseY + 'px';
    });

    function renderCursor() {
      ringX += (mouseX - ringX) * 0.15;
      ringY += (mouseY - ringY) * 0.15;
      ring.style.left = ringX + 'px';
      ring.style.top = ringY + 'px';
      requestAnimationFrame(renderCursor);
    }
    renderCursor();

    // Hover effect
    const interactiveElements = 'a, button, input, .product-card, .hero-card, .deals-card, .feature-card, .review-card, .tab-btn';
    document.addEventListener('mouseover', (e) => {
      if (e.target.closest(interactiveElements)) {
        document.body.classList.add('cursor-hover');
      }
    });
    document.addEventListener('mouseout', (e) => {
      if (e.target.closest(interactiveElements)) {
        document.body.classList.remove('cursor-hover');
      }
    });

    // Filter Tabs Script
    const tabBtns = document.querySelectorAll('.tab-btn');
    const productCards = document.querySelectorAll('.product-card');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const cat = btn.getAttribute('data-category');

        productCards.forEach(card => {
          if (cat === 'all' || card.getAttribute('data-category') === cat) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });

    // Countdown Timer Script
    let totalSeconds = 4 * 3600 + 35 * 60 + 12;
    const hoursEl = document.getElementById('hours');
    const minutesEl = document.getElementById('minutes');
    const secondsEl = document.getElementById('seconds');

    setInterval(() => {
      if (totalSeconds > 0) {
        totalSeconds--;
        const h = Math.floor(totalSeconds / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);
        const s = totalSeconds % 60;
        hoursEl.textContent = String(h).padStart(2, '0');
        minutesEl.textContent = String(m).padStart(2, '0');
        secondsEl.textContent = String(s).padStart(2, '0');
      }
    }, 1000);

    </script>
</body>
</html>
