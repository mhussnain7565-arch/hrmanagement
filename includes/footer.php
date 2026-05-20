</div> </div> </main>
    
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">V 1.0</div>
        <strong><?= $settings['footer_text'] ?></strong>
    </footer>
</div> <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/adminlte.min.js"></script>
<script>
    // Theme Toggle Logic with Persistence
    const toggleButton = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const htmlElement = document.documentElement;

    // 1. Function to update Icon
    function updateIcon(theme) {
        if (theme === 'dark') {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-fill');
        } else {
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill');
        }
    }

    // 2. Initialize Icon on Load
    const currentTheme = localStorage.getItem('theme') || 'light';
    updateIcon(currentTheme);

    // 3. Handle Click
    toggleButton.addEventListener('click', () => {
        const current = htmlElement.getAttribute('data-bs-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        
        // Apply
        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme); // SAVE IT
        updateIcon(newTheme);
    });

    // 4. Sidebar Persistence State Handler (NEW)
    const sidebarToggle = document.querySelector('[data-lte-toggle="sidebar"]');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setTimeout(() => {
                const isCollapsed = document.body.classList.contains('sidebar-collapse');
                localStorage.setItem('sidebar-state', isCollapsed ? 'collapsed' : 'expanded');
            }, 300); // Wait for transition
        });
    }
</script>
</body>
</html>