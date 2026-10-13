(() => {
    const demotionDialog = document.querySelector('[data-staff-demotion-dialog]');
    if (demotionDialog) {
        demotionDialog.showModal();
        demotionDialog.querySelector('button[autofocus]')?.focus();
    }

    document.addEventListener('click', (event) => {
        const card = event.target.closest('[data-admin-pet-card]');
        if (!card || event.target.closest('[data-admin-pet-actions] a, [data-admin-pet-actions] button')) return;
        const toggle = card.querySelector('[data-admin-pet-toggle]');
        if (toggle) setAdminPetCardExpanded(card, toggle);
    });
    document.addEventListener('keydown', (event) => {
        const toggle = event.target.closest('[data-admin-pet-toggle]');
        if (!toggle || !['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        const card = toggle.closest('[data-admin-pet-card]');
        if (card) setAdminPetCardExpanded(card, toggle);
    });
    function setAdminPetCardExpanded(card, toggle) {
        const panelId = toggle.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        if (!panel) return;
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
        card.classList.toggle('is-expanded', !expanded);
    }

    document.addEventListener('click', (event) => {
        const card = event.target.closest('[data-admin-client-card]');
        if (!card || event.target.closest('.admin-client-actions a, .admin-client-actions button, .admin-client-actions input, .admin-client-actions select, .admin-client-actions textarea')) return;
        const toggle = card.querySelector('[data-admin-client-toggle]');
        if (!toggle) return;
        setClientCardExpanded(toggle);
    });
    document.addEventListener('keydown', (event) => {
        const toggle = event.target.closest('[data-admin-client-toggle]');
        if (!toggle || !['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        setClientCardExpanded(toggle);
    });
    function setClientCardExpanded(toggle) {
        const panelId = toggle.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        if (!panel) return;
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
        toggle.closest('.admin-client-card')?.classList.toggle('is-expanded', !expanded);
    }

    const dialog = document.querySelector('[data-admin-password-dialog]');
    if (!dialog) return;
    const input = dialog.querySelector('[data-admin-password-input]');
    const title = dialog.querySelector('[data-admin-password-title]');
    const message = dialog.querySelector('[data-admin-password-message]');
    let pendingForm = null;
    let pendingSubmitter = null;
    let previousFocus = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-admin-password-required]') || form.dataset.passwordConfirmed === 'true') return;
        event.preventDefault();
        if (!form.reportValidity()) return;
        pendingForm = form;
        pendingSubmitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        previousFocus = pendingSubmitter || document.activeElement;
        title.textContent = form.dataset.passwordTitle || 'Confirme sua senha';
        message.textContent = form.dataset.passwordMessage || 'Para continuar, confirme sua senha de administrador.';
        input.value = '';
        dialog.showModal();
        requestAnimationFrame(() => input.focus());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target.closest('[data-admin-password-cancel]')) dialog.close();
    });
    dialog.addEventListener('close', () => {
        input.value = '';
        if (pendingForm && pendingForm.dataset.passwordConfirmed !== 'true' && previousFocus instanceof HTMLElement) previousFocus.focus();
        pendingForm = null;
        pendingSubmitter = null;
        previousFocus = null;
    });
    dialog.querySelector('form').addEventListener('submit', (event) => {
        event.preventDefault();
        if (!pendingForm || !input.reportValidity()) return;
        let hidden = pendingForm.querySelector('[data-admin-password-value]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'admin_password';
            hidden.dataset.adminPasswordValue = '';
            pendingForm.append(hidden);
        }
        hidden.value = input.value;
        hidden.disabled = false;
        pendingForm.dataset.passwordConfirmed = 'true';
        dialog.close();
        if (pendingSubmitter && pendingSubmitter.form === pendingForm) pendingForm.requestSubmit(pendingSubmitter);
        else pendingForm.requestSubmit();
    });
})();
