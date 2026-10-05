(() => {
    const root = document.querySelector('[data-admin-appointments]');
    if (!root) return;

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
})();
