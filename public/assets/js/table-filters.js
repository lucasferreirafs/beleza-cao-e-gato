document.addEventListener('DOMContentLoaded', () => {
    const tableFilter = document.querySelector('[data-table-filter]');
    const statusFilter = document.querySelector('[data-status-filter]');
    const petFilter = document.querySelector('[data-pet-filter]');
    const tabs = document.querySelectorAll('[data-tab-btn]');
    const paginationControls = document.querySelector('[data-pagination-controls]');
    const paginationInfo = document.querySelector('[data-pagination-info]');
    const tableWrap = document.querySelector('.table-wrap');
    const paginationFooter = document.querySelector('.pagination');
    const dataRowSelector = '[data-filter-table] tbody tr:not(#no-results-row)';

    let currentTab = document.querySelector('[data-tab-btn].active')?.dataset.tabBtn || 'all';
    let currentPage = 1;
    const itemsPerPage = 5;
    let lastFilteredRows = [];

    const filterRows = () => {
        const query = (tableFilter?.value || '').trim().toLocaleLowerCase('pt-BR');
        const status = statusFilter?.value || '';
        const pet = (petFilter?.value || '').trim();
        lastFilteredRows = [];

        document.querySelectorAll(dataRowSelector).forEach(row => {
            const rowTab = row.dataset.tab || 'all';
            // Ao selecionar um pet, exibe todos os agendamentos dele independente da aba atual.
            const matchesTab = pet !== '' || currentTab === 'all' || rowTab === currentTab;
            const matchesText = !query || row.textContent.toLocaleLowerCase('pt-BR').includes(query);
            const matchesStatus = !status || row.dataset.status === status;
            const rowPet = (row.getAttribute('data-pet') || '').trim();
            const matchesPet = !pet || rowPet.toLocaleLowerCase('pt-BR') === pet.toLocaleLowerCase('pt-BR');

            if (matchesTab && matchesText && matchesStatus && matchesPet) {
                lastFilteredRows.push(row);
                if (!paginationControls) row.hidden = false;
            } else if (!paginationControls) {
                row.hidden = true;
            }
        });

        if (paginationControls) {
            renderTable();
            renderPagination();
        }
    };

    function navigate(page) {
        currentPage = page;
        renderTable();
        renderPagination();
    }

    function renderTable() {
        const allDataRows = document.querySelectorAll(dataRowSelector);
        allDataRows.forEach(row => { row.style.display = 'none'; });

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageRows = lastFilteredRows.slice(startIndex, endIndex);
        pageRows.forEach(row => { row.style.display = ''; });

        const totalFiltered = lastFilteredRows.length;
        const endDisplay = Math.min(endIndex, totalFiltered);
        let noResultsRow = document.querySelector('#no-results-row');

        if (allDataRows.length === 0) {
            if (tableWrap) tableWrap.style.display = 'none';
        } else if (totalFiltered === 0) {
            if (!noResultsRow) {
                const tbody = document.querySelector('[data-filter-table] tbody');
                if (tbody) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row';
                    noResultsRow.className = 'table-no-results';
                    const colCount = document.querySelectorAll('[data-filter-table] thead th').length;
                    noResultsRow.innerHTML = `<td colspan="${colCount}">Nenhum agendamento encontrado para os filtros selecionados.</td>`;
                    tbody.appendChild(noResultsRow);
                }
            }
            if (noResultsRow) noResultsRow.style.display = '';
            if (tableWrap) tableWrap.style.display = '';
        } else {
            if (noResultsRow) noResultsRow.style.display = 'none';
            if (tableWrap) tableWrap.style.display = '';
        }

        if (paginationInfo) {
            paginationInfo.textContent = totalFiltered === 0
                ? 'Nenhum agendamento encontrado.'
                : `Mostrando ${startIndex + 1} a ${endDisplay} de ${totalFiltered} agendamentos`;
        }
        if (paginationFooter) paginationFooter.style.display = totalFiltered > 0 ? 'flex' : 'none';
    }

    function renderPagination() {
        const totalPages = Math.ceil(lastFilteredRows.length / itemsPerPage);
        paginationControls.innerHTML = '';
        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = `btn-pagination prev ${currentPage === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i>';
        prevBtn.type = 'button';
        prevBtn.onclick = () => navigate(currentPage - 1);
        prevBtn.disabled = currentPage === 1;
        paginationControls.appendChild(prevBtn);

        let startPage = Math.max(1, currentPage - 1);
        let endPage = startPage + 2;
        if (endPage > totalPages) {
            endPage = totalPages;
            startPage = Math.max(1, endPage - 2);
        }

        for (let page = startPage; page <= endPage; page++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn-pagination ${page === currentPage ? 'active' : ''}`;
            pageBtn.textContent = page;
            pageBtn.type = 'button';
            pageBtn.onclick = () => navigate(page);
            paginationControls.appendChild(pageBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = `btn-pagination next ${currentPage === totalPages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
        nextBtn.type = 'button';
        nextBtn.onclick = () => navigate(currentPage + 1);
        nextBtn.disabled = currentPage === totalPages;
        paginationControls.appendChild(nextBtn);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            currentTab = tab.dataset.tabBtn;
            currentPage = 1;
            filterRows();
        });
    });

    tableFilter?.addEventListener('input', () => { currentPage = 1; filterRows(); });
    statusFilter?.addEventListener('change', () => { currentPage = 1; filterRows(); });
    petFilter?.addEventListener('change', () => { currentPage = 1; filterRows(); });

    if (paginationControls || tabs.length > 0) filterRows();
});
