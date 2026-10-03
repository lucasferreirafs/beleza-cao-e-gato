(() => {
    'use strict';
    const form = document.querySelector('[data-attendance-status-form]');
    const notice = document.querySelector('[data-attendance-notice]');
    if (!form || !notice) return;
    let busy = false;
    notice.addEventListener('cancel', event => event.preventDefault());
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const button = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('[data-attendance-feedback]');
        button.disabled = true;
        feedback.hidden = true;
        form.setAttribute('aria-busy', 'true');
        document.dispatchEvent(new Event('request-loading:show'));
        try {
            const response = await fetch(form.action, { method: 'POST', body: new URLSearchParams(new FormData(form)),
                credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const result = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
            if (!response.ok || !result?.ok) throw new Error(result?.message || 'Não foi possível atualizar o atendimento.');
            document.querySelector('[data-request-loading]')?.setAttribute('data-suspended', 'true');
            notice.querySelector('[data-attendance-notice-title]').textContent = result.status === 'in_progress' ? 'Atendimento iniciado' : 'Atendimento concluído';
            notice.showModal();
            window.setTimeout(() => {
                notice.close();
                document.querySelector('[data-request-loading]')?.removeAttribute('data-suspended');
                window.location.reload();
            }, 1400);
        } catch (error) {
            document.dispatchEvent(new Event('request-loading:hide'));
            feedback.textContent = error.message || 'Não foi possível atualizar o atendimento.';
            feedback.hidden = false;
            button.disabled = false;
            form.removeAttribute('aria-busy');
            busy = false;
        }
    });
})();
