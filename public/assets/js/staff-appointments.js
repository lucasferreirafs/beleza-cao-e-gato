(() => {
    'use strict';
    const schedule = document.querySelector('[data-staff-appointments]');
    const summary = document.querySelector('[data-staff-pet-summary]');
    const statusNotice = document.querySelector('[data-staff-status-notice]');
    const showValue = (selector, value, suffix = '') => {
        summary.querySelector(selector).textContent = value ? `${value}${suffix}` : 'Não informado';
    };

    document.querySelectorAll('[data-pet-details]').forEach(button => {
        button.addEventListener('click', () => {
            summary.querySelector('[data-summary-name]').textContent = button.dataset.name;
            showValue('[data-summary-weight]', button.dataset.weight, ' kg');
            showValue('[data-summary-coat]', button.dataset.coat);
            for (const key of ['allergies', 'needs', 'behavior', 'notes']) {
                const row = summary.querySelector(`[data-summary-${key}-row]`);
                const value = button.dataset[key]?.trim() ?? '';
                row.hidden = value === '';
                summary.querySelector(`[data-summary-${key}]`).textContent = value;
            }
            summary.showModal();
        });
    });

    summary?.querySelector('[data-summary-close]')?.addEventListener('click', () => summary.close());
    summary?.addEventListener('click', event => {
        if (event.target !== summary) return;
        const rect = summary.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) summary.close();
    });

    const setMetric = (name, value) => {
        const element = document.querySelector(`[data-staff-metric="${name}"]`);
        if (element) element.textContent = String(Math.max(0, value));
    };

    schedule?.addEventListener('submit', async event => {
        const form = event.target.closest('[data-staff-status-form]');
        if (!form) return;
        event.preventDefault();
        const row = form.closest('[data-staff-appointment-row]');
        const button = form.querySelector('button[type="submit"]');
        const feedback = row.querySelector('[data-row-feedback]');
        const priorStatus = row.dataset.status;
        feedback.hidden = true;
        button.disabled = true;
        form.setAttribute('aria-busy', 'true');
        document.dispatchEvent(new Event('request-loading:show'));
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new URLSearchParams(new FormData(form)),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || 'Não foi possível atualizar este atendimento.');
            row.dataset.status = result.status;
            const badge = row.querySelector('.badge');
            badge.textContent = result.label;
            badge.className = `badge ${result.status === 'in_progress' ? 'status-progress' : 'status-success'}`;
            if (priorStatus === 'pending' && result.status === 'in_progress') {
                document.querySelector('[data-request-loading]')?.setAttribute('data-suspended', 'true');
                statusNotice?.showModal();
                window.setTimeout(() => {
                    if (statusNotice?.open) statusNotice.close();
                    window.location.reload();
                }, 1400);
            } else if (priorStatus === 'in_progress' && result.status === 'completed') {
                const completedMetric = document.querySelector('[data-staff-metric="completed"]');
                if (completedMetric) setMetric('completed', Number(completedMetric.textContent) + 1);
                row.classList.add('is-completing');
                window.setTimeout(() => {
                    const wasPendingRow = row.closest('tbody') !== null;
                    row.remove();
                    if (wasPendingRow && !schedule.querySelector('tbody [data-staff-appointment-row]')) {
                        document.querySelector('[data-staff-empty]')?.removeAttribute('hidden');
                    }
                    if (!schedule.querySelector('.staff-in-progress-item')) document.querySelector('[data-staff-in-progress]')?.remove();
                }, 200);
            }
        } catch (error) {
            document.dispatchEvent(new Event('request-loading:hide'));
            feedback.textContent = error.message || 'Não foi possível atualizar o atendimento. Tente novamente.';
            feedback.hidden = false;
        } finally {
            if (row.isConnected) {
                button.disabled = false;
                form.removeAttribute('aria-busy');
            }
        }
    });
})();
