(() => {
    const root = document.querySelector('[data-admin-appointments]');
    if (!root) return;

    const searchInput = root.querySelector('[data-admin-appointment-search]');
    const dateInput = root.querySelector('[data-admin-appointment-date]');
    const statusInput = root.querySelector('[data-admin-appointment-status]');
    const cards = [...root.querySelectorAll('[data-admin-appointment-card]')];
    const emptyState = root.querySelector('[data-admin-appointments-empty]');
    const count = root.querySelector('[data-admin-appointments-count]');

    const filterCards = () => {
        const query = (searchInput?.value || '').trim().toLocaleLowerCase('pt-BR');
        const date = dateInput?.value || '';
        const status = statusInput?.value || '';
        let visible = 0;

        cards.forEach((card) => {
            const matchesText = !query || (card.dataset.search || '').toLocaleLowerCase('pt-BR').includes(query);
            const matchesDate = !date || card.dataset.date === date;
            const matchesStatus = !status || card.dataset.status === status;
            const matches = matchesText && matchesDate && matchesStatus;

            card.hidden = !matches;
            if (matches) visible++;
        });

        if (count) {
            count.textContent = visible === cards.length
                ? `${cards.length} ${cards.length === 1 ? 'agendamento' : 'agendamentos'}`
                : `${visible} de ${cards.length} agendamentos`;
        }

        if (emptyState) {
            emptyState.hidden = visible > 0;
            emptyState.textContent = cards.length === 0
                ? 'Nenhum agendamento cadastrado.'
                : 'Nenhum agendamento encontrado para os filtros selecionados.';
        }
    };

    searchInput?.addEventListener('input', filterCards);
    dateInput?.addEventListener('change', filterCards);
    statusInput?.addEventListener('change', filterCards);

    root.addEventListener('click', (event) => {
        const toggle = event.target instanceof Element
            ? event.target.closest('[data-appointment-details-toggle]')
            : null;
        if (!toggle || !root.contains(toggle)) return;

        const card = toggle.closest('[data-admin-appointment-card]');
        const details = card?.querySelector('[data-appointment-details]');
        if (!card || !details) return;

        const expanded = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(expanded));
        details.hidden = !expanded;
        card.classList.toggle('is-expanded', expanded);
        const label = toggle.querySelector('span');
        if (label) label.textContent = expanded ? 'Ocultar detalhes do agendamento' : 'Ver detalhes do agendamento';
    });

    filterCards();
})();
