(() => {
    'use strict';
    const pagination = document.querySelector('[data-history-pagination]');
    if (!pagination) return;
    const rows = [...document.querySelectorAll('#appointment-history-table tbody tr')];
    const mobile = window.matchMedia('(max-width: 680px)');
    const pageSize = 2;
    const totalPages = Math.ceil(rows.length / pageSize);
    const previous = pagination.querySelector('[data-history-previous]');
    const next = pagination.querySelector('[data-history-next]');
    const pages = pagination.querySelector('[data-history-pages]');
    const status = pagination.querySelector('[data-history-page-status]');
    let currentPage = 1;

    const render = () => {
        const start = (currentPage - 1) * pageSize;
        rows.forEach((row, index) => {
            row.hidden = mobile.matches && (index < start || index >= start + pageSize);
        });
        pagination.hidden = !mobile.matches || totalPages <= 1;
        previous.disabled = currentPage === 1;
        next.disabled = currentPage === totalPages;
        [...pages.children].forEach((button, index) => {
            if (index + 1 === currentPage) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        status.textContent = rows.length
            ? `${start + 1}–${Math.min(start + pageSize, rows.length)} de ${rows.length} agendamentos · Página ${currentPage} de ${totalPages}`
            : '';
    };
    const goTo = (page) => {
        currentPage = Math.max(1, Math.min(totalPages, page));
        render();
        if (document.activeElement === previous && previous.disabled || document.activeElement === next && next.disabled) {
            pages.children[currentPage - 1]?.focus();
        }
    };
    for (let page = 1; page <= totalPages; page++) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = String(page);
        button.setAttribute('aria-label', `Página ${page}`);
        button.setAttribute('aria-controls', 'appointment-history-table');
        button.addEventListener('click', () => goTo(page));
        pages.append(button);
    }
    previous.addEventListener('click', () => goTo(currentPage - 1));
    next.addEventListener('click', () => goTo(currentPage + 1));
    mobile.addEventListener('change', () => { currentPage = 1; render(); });
    render();
})();
