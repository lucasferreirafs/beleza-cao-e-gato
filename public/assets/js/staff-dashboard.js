(() => {
    'use strict';
    const countdown = document.querySelector('[data-staff-countdown]');
    if (!countdown) return;
    const scheduled = Number(countdown.dataset.scheduled);
    const serverNow = Number(countdown.dataset.serverNow);
    if (!Number.isFinite(scheduled) || !Number.isFinite(serverNow)) return;
    const started = performance.now();
    const format = (minutes) => minutes < 60 ? `${minutes} min`
        : `${Math.floor(minutes / 60)} h${minutes % 60 ? ` ${minutes % 60} min` : ''}`;
    const update = () => {
        if (countdown.dataset.started === 'true') return;
        const seconds = scheduled - (serverNow + (performance.now() - started) / 1000);
        if (seconds < -1800) {
            countdown.textContent = 'Prazo de início encerrado';
            countdown.parentElement.querySelector('small').textContent = 'Consulte o status atualizado';
            countdown.parentElement.querySelector('[data-staff-refresh]').hidden = false;
            return;
        }
        countdown.textContent = seconds > 0 ? `Em ${format(Math.ceil(seconds / 60))}`
            : seconds > -60 ? 'Horário do atendimento' : `Atrasado há ${format(Math.floor(-seconds / 60))}`;
    };
    update();
    window.setInterval(update, 15000);
    window.addEventListener('pageshow', update);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) update(); });
})();
