(() => {
    'use strict';
    const pagination = document.querySelector('[data-history-pagination]');
    if (!pagination) return;
    const rows = [...document.querySelectorAll('#appointment-history-table tbody tr')];
    const mobile = window.matchMedia('(max-width: 680px)');
    const pageSize = 2;
    const totalPages = Math.ceil(rows.length / pageSize);
    const pages = pagination.querySelector('[data-history-pages]');
    const status = pagination.querySelector('[data-history-page-status]');
    let currentPage = 1;

    const render = () => {
        const start = (currentPage - 1) * pageSize;
        rows.forEach((row, index) => {
            row.hidden = mobile.matches && (index < start || index >= start + pageSize);
        });
        pagination.hidden = !mobile.matches || totalPages <= 1;
        status.textContent = rows.length
            ? `Mostrando ${start + 1}–${Math.min(start + pageSize, rows.length)} de ${rows.length} ${rows.length === 1 ? 'agendamento' : 'agendamentos'} · Página ${currentPage} de ${totalPages}`
            : '';
        window.AppPagination?.render(pages, {
            currentPage,
            totalPages,
            controlsLabel: 'Paginação do histórico recente',
            controlsId: 'appointment-history-table',
            onChange: goTo,
        });
    };
    const goTo = (page) => {
        currentPage = Math.max(1, Math.min(totalPages, page));
        render();
    };
    mobile.addEventListener('change', () => { currentPage = 1; render(); });
    render();
})();
