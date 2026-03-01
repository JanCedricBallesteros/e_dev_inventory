// Simple reveal-on-scroll utility
(function () {
    if (typeof window === 'undefined') return;
    const supportsIO = 'IntersectionObserver' in window;
    const els = document.querySelectorAll('.reveal-on-scroll');
    if (!els.length) return;

    function applyInView(el) {
        const delay = parseInt(el.dataset.revealDelay || 0, 10);
        if (delay) el.style.transitionDelay = (delay / 1000) + 's';
        el.classList.add('in-view');
    }

    function removeInView(el) {
        el.classList.remove('in-view');
        el.style.transitionDelay = '';
    }

    if (supportsIO) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) applyInView(entry.target);
                else removeInView(entry.target);
            });
        }, { threshold: 0.15 });

        els.forEach(el => io.observe(el));
    } else {
        // Fallback: reveal all
        els.forEach(el => applyInView(el));
    }
})();