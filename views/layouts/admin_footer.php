    </main>

    <!-- Admin Footer -->
    <footer class="admin-footer">
      <div>
        YasIX Store &copy; <?= date('Y') ?> | Administrative Operations Center
      </div>
      <div style="font-family: 'Share Tech Mono', monospace; font-size: 0.78rem;">
        BUILD: MATRIX-v2.6.10 &bull; SECURE PROTOCOL ACTIVE
      </div>
    </footer>
  </div>

  <script>
    // Responsive sidebar toggle for mobile
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (toggleBtn && sidebar) {
      toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        sidebar.classList.toggle('open');
      });
      document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('open')) {
          sidebar.classList.remove('open');
        }
      });
    }
  </script>
</body>
</html>
