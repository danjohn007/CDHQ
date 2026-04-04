    </main><!-- /main -->
</div><!-- /main content area -->
</div><!-- /flex container -->

<!-- Chart.js loaded in header -->
<script>
    // Mobile sidebar toggle
    const toggle  = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            if (overlay) overlay.classList.toggle('hidden');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }
    // Auto-hide flash messages after 4s
    setTimeout(() => {
        document.querySelectorAll('[data-flash]').forEach(el => el.remove());
    }, 4000);
</script>
</body>
</html>
