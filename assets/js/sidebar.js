function initializeSidebar() {
    const oldToggles = document.querySelectorAll('.submenu-toggle');
    oldToggles.forEach(toggle => {
        const newToggle = toggle.cloneNode(true);
        if (toggle.parentNode) {
            toggle.parentNode.replaceChild(newToggle, toggle);
        }
    });

    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();

            const parentLi = this.closest('.has-submenu');
            if (!parentLi) return;

            if (parentLi.classList.contains('open')) {
                parentLi.classList.remove('open');
            } else {
                document.querySelectorAll('.has-submenu.open').forEach(openMenu => {
                    openMenu.classList.remove('open');
                });

                setTimeout(() => {
                    parentLi.classList.add('open');
                }, 10);
            }
        });
    });

    highlightActiveMenu();
}

function highlightActiveMenu() {
    const currentPath = window.location.pathname;

    document.querySelectorAll('.sidebar a.active').forEach(link => {
        link.classList.remove('active');
    });

    const activeLink = document.querySelector(`.sidebar a[href="${currentPath}"]`);
    if (activeLink) {
        activeLink.classList.add('active');

        const parentSubmenu = activeLink.closest('.submenu');
        if (parentSubmenu) {
            const parentLi = parentSubmenu.closest('.has-submenu');
            if (parentLi) {
                document.querySelectorAll('.has-submenu.open').forEach(openMenu => {
                    openMenu.classList.remove('open');
                });

                parentLi.classList.add('open');
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', initializeSidebar);
document.addEventListener('turbo:load', initializeSidebar);
document.addEventListener('turbo:render', initializeSidebar);

if (typeof $ !== 'undefined') {
    $(document).on('ajaxComplete', function() {
        initializeSidebar();
    });
}
