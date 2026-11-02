document.addEventListener('DOMContentLoaded', function () {
    var main = document.querySelector('.tw-app-content main');
    if (!main) { return; }

    var selector = [
        '.matrix-header',
        '.matrix-form-card',
        '.matrix-cell',
        '.clb-head',
        '.clb-bar',
        '.clb-col',
        '.clb-panel',
        '.clb-team',
        '.clb-empty',
        '.pb-head',
        '.pb-prompts',
        '.pb-chat',
        '.tw-app-content main .bg-white.shadow',
        '.tw-app-content main .bg-white.shadow-sm'
    ].join(',');
    var targets = new Set(main.children);

    main.querySelectorAll(selector).forEach(function (element) {
        targets.add(element);
    });

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
        !('IntersectionObserver' in window)) {
        return;
    }

    document.body.classList.add('tw-motion-ready');

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) { return; }
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -32px 0px'
    });

    Array.from(targets).forEach(function (element, index) {
        if (!(element instanceof HTMLElement)) { return; }
        element.classList.add('tw-motion-target');
        element.style.setProperty('--tw-motion-delay', Math.min(index % 5, 4) * 55 + 'ms');
        observer.observe(element);
    });
});
