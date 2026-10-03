(() => {
    'use strict';
    const agenda = document.querySelector('[data-staff-agenda]');
    if (!agenda) return;
    const notice = document.querySelector('[data-agenda-notice]');
    const key = `staff-agenda:${location.search}`;
    try {
        const expanded = JSON.parse(sessionStorage.getItem(key) || '[]');
        agenda.querySelectorAll('details[data-appointment]').forEach(card => { card.open = expanded.includes(card.dataset.appointment); });
        sessionStorage.removeItem(key);
    } catch { /* Storage may be unavailable in private browsing. */ }
    notice?.addEventListener('cancel', event => event.preventDefault());
    let busy = false;
    agenda.addEventListener('submit', async event => {
        const form = event.target.closest('[data-agenda-status-form]');
        if (!form) return;
        event.preventDefault();
        if (busy) return;
        busy = true;
        const button = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('[data-agenda-feedback]');
        button.disabled = true;
        feedback.hidden = true;
        form.setAttribute('aria-busy', 'true');
        document.dispatchEvent(new Event('request-loading:show'));
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new URLSearchParams(new FormData(form)), credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const result = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
            if (!response.ok || !result?.ok) throw new Error(result?.message || 'Não foi possível atualizar. Confira sua conexão e atualize a agenda.');
            try {
                sessionStorage.setItem(key, JSON.stringify([...agenda.querySelectorAll('details[open]')].map(card => card.dataset.appointment)));
            } catch { /* Preserve operation even if storage is blocked. */ }
            document.querySelector('[data-request-loading]')?.setAttribute('data-suspended', 'true');
            notice.querySelector('[data-agenda-notice-title]').textContent = result.status === 'in_progress' ? 'Atendimento iniciado' : 'Atendimento concluído';
            notice.showModal();
            window.setTimeout(() => {
                notice.close();
                document.querySelector('[data-request-loading]')?.removeAttribute('data-suspended');
                window.location.reload();
            }, 1400);
        } catch (error) {
            document.dispatchEvent(new Event('request-loading:hide'));
            feedback.textContent = error.message;
            feedback.hidden = false;
            form.querySelector('[data-agenda-refresh]').hidden = false;
            button.disabled = false;
            form.removeAttribute('aria-busy');
            busy = false;
        }
    });
})();
