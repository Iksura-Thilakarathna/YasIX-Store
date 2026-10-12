  </main>

  <!-- CART DRAWER -->
  <div class="cart-drawer" id="cart-drawer">
    <div class="cart-header">
      <h3 style="font-family: 'Orbitron'; color: var(--cyan);">YOUR CART GEAR</h3>
      <button class="modal-close" id="close-cart-btn"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="cart-items" id="cart-items-list">
      <!-- Added dynamically -->
    </div>
    <div style="border-top: 1px solid var(--border); padding-top: 16px;">
      <div style="display: flex; justify-content: space-between; font-family: 'Orbitron'; margin-bottom: 16px;">
        <span>TOTAL:</span>
        <span id="cart-total-price" style="color: var(--cyan);">$3,047.00</span>
      </div>
      <button class="btn-primary" style="width: 100%; justify-content: center;" onclick="alert('Proceeding to Cyber Quantum Checkout!')">CHECKOUT NOW <i class="fa-solid fa-lock"></i></button>
    </div>
  </div>

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
            <li><a href="#home">Home</a></li>
            <li><a href="#store">Catalog</a></li>
            <li><a href="#deals">Flash Sales</a></li>
            <li><a href="#features">Features</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>SUPPORT</h4>
          <ul>
            <li><a href="#">Neural AI Bot</a></li>
            <li><a href="#">Warranty Claim</a></li>
            <li><a href="#">Order Tracking</a></li>
            <li><a href="#">Matrix Status</a></li>
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

    // Cart System
    const cartItems = [
      { name: 'YasIX Synapse Headset', price: 399 },
      { name: 'RTX 5090 Cyber-Quantum', price: 2299 },
      { name: 'AeroBlade Cyber Keyboard', price: 349 }
    ];

    const cartDrawer = document.getElementById('cart-drawer');
    const openCartBtn = document.getElementById('open-cart-btn');
    const closeCartBtn = document.getElementById('close-cart-btn');
    const cartItemsList = document.getElementById('cart-items-list');
    const cartCountEl = document.getElementById('cart-count');
    const cartTotalEl = document.getElementById('cart-total-price');

    if (openCartBtn && closeCartBtn && cartDrawer) {
      openCartBtn.addEventListener('click', () => cartDrawer.classList.add('active'));
      closeCartBtn.addEventListener('click', () => cartDrawer.classList.remove('active'));
    }

    function renderCart() {
      if (!cartItemsList) return;
      cartItemsList.innerHTML = '';
      let total = 0;
      cartItems.forEach((item, index) => {
        total += item.price;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
          <div>
            <div style="font-weight: bold; font-size: 0.9rem;">${item.name}</div>
            <div style="color: var(--cyan); font-size: 0.85rem;">$${item.price}</div>
          </div>
          <button style="background:none; border:none; color:var(--neon-pink); cursor:pointer;" onclick="removeItem(${index})"><i class="fa-solid fa-xmark"></i></button>
        `;
        cartItemsList.appendChild(div);
      });
      if (cartCountEl) cartCountEl.textContent = cartItems.length;
      if (cartTotalEl) cartTotalEl.textContent = `$${total.toLocaleString()}.00`;
    }

    function addToCart(name, price) {
      cartItems.push({ name, price });
      renderCart();
      if (cartDrawer) cartDrawer.classList.add('active');
    }

    function removeItem(index) {
      cartItems.splice(index, 1);
      renderCart();
    }

    renderCart();
  </script>
</body>
</html>
