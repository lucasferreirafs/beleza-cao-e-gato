(() => {
    const selector = '[data-chart-point]';

    const getPoint = (target) => target instanceof Element ? target.closest(selector) : null;

    const showPoint = (point) => {
        const chartName = point.dataset.chartName;
        const panel = point.closest('.chart-panel');
        const tooltip = panel?.querySelector(`[data-chart-tooltip="${chartName}"]`);
        if (!panel || !tooltip) return;

        panel.querySelectorAll(`${selector}[data-chart-name="${chartName}"]`).forEach((candidate) => {
            candidate.classList.toggle('is-active', candidate.dataset.chartKey === point.dataset.chartKey);
        });

        tooltip.textContent = point.dataset.chartDetail || '';
        tooltip.hidden = false;
    };

    const hidePoint = (point) => {
        const chartName = point.dataset.chartName;
        const panel = point.closest('.chart-panel');
        const tooltip = panel?.querySelector(`[data-chart-tooltip="${chartName}"]`);
        if (!panel || !tooltip) return;

        const focusedPoint = panel.querySelector(`${selector}[data-chart-name="${chartName}"]:focus`);
        if (focusedPoint) {
            showPoint(focusedPoint);
            return;
        }

        panel.querySelectorAll(`${selector}[data-chart-name="${chartName}"]`).forEach((candidate) => {
            candidate.classList.remove('is-active');
        });
        tooltip.hidden = true;
        tooltip.textContent = '';
    };

    document.addEventListener('pointerover', (event) => {
        const point = getPoint(event.target);
        if (point) showPoint(point);
    });

    document.addEventListener('pointerout', (event) => {
        const point = getPoint(event.target);
        if (!point || (event.relatedTarget instanceof Node && point.contains(event.relatedTarget))) return;
        hidePoint(point);
    });

    document.addEventListener('focusin', (event) => {
        const point = getPoint(event.target);
        if (point) showPoint(point);
    });

    document.addEventListener('focusout', (event) => {
        const point = getPoint(event.target);
        if (!point || (event.relatedTarget instanceof Node && point.contains(event.relatedTarget))) return;
        hidePoint(point);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const focusedPoint = getPoint(document.activeElement);
        if (focusedPoint) {
            focusedPoint.blur();
            hidePoint(focusedPoint);
        }
    });
})();
