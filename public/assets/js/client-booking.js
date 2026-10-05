document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.querySelector('[data-booking-form]');
    if (!bookingForm) return;

    const serviceChecks = [...bookingForm.querySelectorAll('input[name="service_ids[]"]')];
    serviceChecks.forEach(input => input.addEventListener('change', () => {
        input.closest('.booking-service-card')?.classList.toggle('selected', input.checked);
    }));
    bookingForm.addEventListener('submit', event => {
        if (serviceChecks.length && !serviceChecks.some(input => input.checked)) {
            event.preventDefault();
            serviceChecks[0].focus();
            const hint = bookingForm.querySelector('.booking-hint');
            if (hint) hint.textContent = 'Selecione pelo menos um serviço para continuar.';
        }
    });

    const dateInput = bookingForm.querySelector('[data-booking-date]');
    const timeSelect = bookingForm.querySelector('[data-booking-time]');
    const staffSection = bookingForm.querySelector('[data-staff-slot-status]');
    const staffMessage = bookingForm.querySelector('[data-staff-slot-message]');
    const slotMessage = bookingForm.querySelector('[data-slot-status]');
    const nextButton = bookingForm.querySelector('button[type="submit"]');
    let preferredStaff = staffSection?.dataset.selectedStaff || '';
    let availabilityPending = false;

    bookingForm.addEventListener('submit', event => {
        if (!dateInput) return;
        if (availabilityPending || timeSelect.disabled || !timeSelect.value ||
            !staffSection?.querySelector('input[name="staff_id"]:checked:not(:disabled)')) {
            event.preventDefault();
            if (staffMessage) staffMessage.textContent = 'Selecione data, horário e uma opção de profissional disponível para continuar.';
        }
    });

    let availabilityRevision = 0;
    const refreshAvailability = async (includeTime = true) => {
        if (!dateInput || !timeSelect) return;
        const endLoading = window.AppRequestLoading?.begin?.() ?? (() => {});
        const revision = ++availabilityRevision;
        availabilityPending = true;
        if (nextButton) nextButton.disabled = true;
        const previousTime = timeSelect.value;
        const query = new URLSearchParams({ date: dateInput.value });
        if (includeTime && previousTime) query.set('time', previousTime);
        timeSelect.disabled = true;
        if (slotMessage) slotMessage.textContent = 'Consultando disponibilidade…';

        try {
            const response = await fetch(bookingForm.dataset.availabilityUrl + '?' + query.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (revision !== availabilityRevision) return;
            if (!response.ok) throw new Error(result.error || 'Não foi possível consultar a agenda.');

            const slots = Array.isArray(result.slots) ? result.slots : [];
            timeSelect.replaceChildren(new Option('Selecione', ''));
            slots.forEach(slot => timeSelect.add(new Option(slot, slot)));
            if (previousTime && slots.includes(previousTime)) timeSelect.value = previousTime;
            else if (previousTime) {
                timeSelect.add(new Option(previousTime + ' · Indisponível', previousTime, true, true));
                timeSelect.options[timeSelect.options.length - 1].disabled = true;
            }
            timeSelect.disabled = false;
            if (slotMessage) slotMessage.textContent = slots.length ? '' : 'Nenhum horário com profissional livre nesta data.';

            if (staffSection) {
                staffSection.hidden = !timeSelect.value;
                const available = new Map((result.staff || []).map(item => [String(item.id), ['true', 't', '1'].includes(String(item.available).toLowerCase())]));
                const radios = [...staffSection.querySelectorAll('input[name="staff_id"]')];
                radios.forEach(radio => {
                    const free = radio.value === '' ? slots.includes(timeSelect.value) : available.get(radio.value) === true;
                    radio.disabled = !free;
                    radio.checked = free && radio.value === preferredStaff;
                    const card = radio.closest('.staff-card');
                    card?.classList.toggle('unavailable', !free);
                    card?.querySelector('.staff-unavailable-badge')?.toggleAttribute('hidden', free);
                    card?.classList.toggle('selected', radio.checked);
                });

                const chosen = staffSection.querySelector('input[name="staff_id"]:checked');
                const chosenSpecific = staffSection.querySelectorAll('input[name="staff_id"]');
                if (staffMessage) {
                    const prior = preferredStaff && available.get(preferredStaff) === false;
                    staffMessage.textContent = !timeSelect.value ? 'Selecione um horário para consultar os profissionais.' :
                        (prior ? 'O profissional escolhido está Indisponível neste horário. Escolha outra opção.' :
                            (!chosen && chosenSpecific.length ? 'Escolha um profissional livre ou selecione sem preferência.' : ''));
                }
            }

            availabilityPending = false;
            if (nextButton) nextButton.disabled = false;
        } catch (error) {
            if (revision !== availabilityRevision) return;
            timeSelect.replaceChildren(new Option('Disponibilidade indisponível', ''));
            timeSelect.disabled = true;
            availabilityPending = false;
            if (slotMessage) slotMessage.textContent = error.message || 'Erro ao consultar os horários. Tente novamente.';
        } finally {
            endLoading();
        }
    };

    if (dateInput && timeSelect) {
        dateInput.addEventListener('change', () => {
            timeSelect.value = '';
            refreshAvailability(false);
        });
        timeSelect.addEventListener('change', () => refreshAvailability(true));
        // Reuse PHP's availability result unless the browser restored different values.
        if (bookingForm.dataset.availabilityRendered !== 'true' ||
            dateInput.value !== bookingForm.dataset.renderedDate ||
            timeSelect.value !== bookingForm.dataset.renderedTime) {
            refreshAvailability(true);
        }
        window.addEventListener('pageshow', event => {
            if (event.persisted) refreshAvailability(true);
        });
    }

    bookingForm.querySelectorAll('input[name="staff_id"]').forEach(radio => {
        radio.addEventListener('change', () => {
            preferredStaff = radio.value;
            if (staffMessage) staffMessage.textContent = '';
            bookingForm.querySelectorAll('.staff-card').forEach(card => card.classList.toggle('selected', card.querySelector('input[name="staff_id"]')?.checked === true));
        });
    });
});
