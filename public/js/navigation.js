// TIMEWISE navigation: mobile drawer + profile dropdown.
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('twSidebar');
    var overlay = document.getElementById('twOverlay');
    var openBtn = document.getElementById('twNavOpen');
    var closeBtn = document.getElementById('twNavClose');
    var profileBtn = document.getElementById('twProfileBtn');
    var menu = document.getElementById('twProfileMenu');

    // ---- Mobile drawer ----
    function openDrawer() {
        sidebar.classList.add('is-open');
        overlay.hidden = false;
        document.body.classList.add('nav-locked');
        if (openBtn) { openBtn.setAttribute('aria-expanded', 'true'); }
        if (closeBtn) { closeBtn.focus(); }
    }

    function closeDrawer() {
        sidebar.classList.remove('is-open');
        overlay.hidden = true;
        document.body.classList.remove('nav-locked');
        if (openBtn) { openBtn.setAttribute('aria-expanded', 'false'); }
    }

    if (openBtn) { openBtn.addEventListener('click', openDrawer); }
    if (closeBtn) { closeBtn.addEventListener('click', closeDrawer); }
    if (overlay) { overlay.addEventListener('click', closeDrawer); }

    // Close the drawer after choosing a real link
    sidebar.querySelectorAll('a.tw-nav-link').forEach(function (link) {
        link.addEventListener('click', closeDrawer);
    });

    // If the window grows to desktop size, reset the drawer state
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) { closeDrawer(); }
    });

    // ---- Profile dropdown ----
    function setMenu(open) {
        if (!menu || !profileBtn) { return; }
        menu.hidden = !open;
        profileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (profileBtn && menu) {
        profileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            setMenu(menu.hidden);
        });
        menu.addEventListener('click', function (e) { e.stopPropagation(); });
        document.addEventListener('click', function () { setMenu(false); });
    }

    // Escape closes whichever is open
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        if (menu && !menu.hidden) { setMenu(false); profileBtn.focus(); }
        if (sidebar.classList.contains('is-open')) { closeDrawer(); if (openBtn) { openBtn.focus(); } }
    });
});
