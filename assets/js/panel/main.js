document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        const handleResize = function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
            }
        };

        window.addEventListener('resize', handleResize);
    }
});
