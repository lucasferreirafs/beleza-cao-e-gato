(() => {
    'use strict';

    const render = (container, { currentPage, totalPages, onChange, controlsLabel = 'Paginação', controlsId = '' }) => {
        if (!(container instanceof HTMLElement)) return;
        const page = Math.max(1, Math.min(Number(currentPage) || 1, Math.max(1, Number(totalPages) || 1)));
        const pages = Math.max(1, Number(totalPages) || 1);
        const createStep = (targetPage, direction) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pagination-component__step';
            button.setAttribute('aria-label', direction === 'previous' ? 'Página anterior' : 'Próxima página');
            if (controlsId) button.setAttribute('aria-controls', controlsId);
            button.disabled = direction === 'previous' ? page === 1 : page === pages;
            if (button.disabled) button.classList.add('is-disabled');
            if (direction === 'previous') {
                button.innerHTML = '<i class="fa-solid fa-angle-left" aria-hidden="true"></i><span>Anterior</span>';
            } else {
                button.innerHTML = '<span>Próxima</span><i class="fa-solid fa-angle-right" aria-hidden="true"></i>';
            }
            button.addEventListener('click', () => {
                onChange(targetPage);
                requestAnimationFrame(() => container.querySelector('[aria-current="page"]')?.focus());
            });
            return button;
        };

        container.replaceChildren();
        container.setAttribute('aria-label', controlsLabel);
        container.classList.add('pagination-component__controls');
        container.append(createStep(page - 1, 'previous'));

        const visiblePages = [...new Set([1, page - 1, page, page + 1, pages])]
            .filter(number => number >= 1 && number <= pages)
            .sort((a, b) => a - b);
        let lastPage = null;
        visiblePages.forEach(number => {
            if (lastPage !== null && number - lastPage > 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-component__ellipsis';
                ellipsis.setAttribute('aria-hidden', 'true');
                ellipsis.textContent = '…';
                container.append(ellipsis);
            }
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pagination-component__number';
            button.textContent = String(number);
            button.setAttribute('aria-label', `Página ${number}`);
            if (controlsId) button.setAttribute('aria-controls', controlsId);
            if (number === page) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => {
                onChange(number);
                requestAnimationFrame(() => container.querySelector('[aria-current="page"]')?.focus());
            });
            container.append(button);
            lastPage = number;
        });

        const indicator = document.createElement('span');
        indicator.className = 'pagination-component__page-indicator';
        indicator.setAttribute('role', 'status');
        indicator.setAttribute('aria-label', `Página ${page} de ${pages}`);
        indicator.textContent = `${page} / ${pages}`;
        container.append(indicator, createStep(page + 1, 'next'));
    };

    window.AppPagination = Object.freeze({ render });
})();
