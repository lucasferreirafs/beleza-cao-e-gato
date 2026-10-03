(() => {
    'use strict';
    const list = document.querySelector('.staff-appointment-results');
    if (!list) return;
    list.addEventListener('toggle', event => {
        const opened = event.target;
        if (!(opened instanceof HTMLDetailsElement) || !opened.open) return;
        list.querySelectorAll('details[open]').forEach(card => {
            if (card !== opened) card.open = false;
        });
    }, true);
})();
