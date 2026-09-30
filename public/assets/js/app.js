document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector("[data-sidebar]");
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
    const mobileQuery = window.matchMedia('(max-width: 1000px)');
    const syncOverlayLock = () => document.body.classList.toggle('overlay-open',
        !!document.querySelector('dialog[open]:not([data-request-loading]), [data-sidebar].open'));

    const requestLoading = document.querySelector('[data-request-loading]');
    const pendingRequests = new Set();
    let navigationPending = false;
    const stopRequestLoading = () => {
        if (requestLoading?.open) requestLoading.close();
        document.body.classList.remove('request-loading-active');
    };
    const showRequestLoading = () => {
        if (requestLoading && !requestLoading.open) {
            requestLoading.showModal();
            document.body.classList.add('request-loading-active');
        }
    };
    const beginRequestLoading = () => {
        const token = Symbol('request');
        pendingRequests.add(token);
        showRequestLoading();
        return () => {
            pendingRequests.delete(token);
            if (!navigationPending && pendingRequests.size === 0) stopRequestLoading();
        };
    };
    const beginFormNavigation = () => {
        if (navigationPending) return false;
        navigationPending = true;
        showRequestLoading();
        return true;
    };
    requestLoading?.addEventListener('cancel', event => event.preventDefault());
    // On a history restore the old request no longer exists.
    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        navigationPending = false;
        pendingRequests.clear();
        stopRequestLoading();
    });
    document.addEventListener('submit', event => {
        if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
        if (!beginFormNavigation()) event.preventDefault();
    });
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;

        let destination;
        try { destination = new URL(link.href, window.location.href); }
        catch { return; }
        if (!['http:', 'https:'].includes(destination.protocol) || destination.origin !== window.location.origin) return;
        if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;
        if (!beginFormNavigation()) event.preventDefault();
    });
    const setSidebar = (open, restoreFocus = true) => {
        if (!sidebar) return;
        const visible = open && mobileQuery.matches;
        sidebar.classList.toggle('open', visible);
        sidebar.inert = mobileQuery.matches && !visible;
        sidebarToggle?.setAttribute('aria-expanded', String(visible));
        if (sidebarBackdrop) sidebarBackdrop.hidden = !visible;
        const workspace = document.querySelector('.workspace');
        if (workspace) workspace.inert = visible;
        if (visible) {
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            sidebar.querySelector('button, a')?.focus();
        } else {
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
            if (restoreFocus) sidebarToggle?.focus();
        }
        syncOverlayLock();
    };
    sidebarToggle?.addEventListener('click', () => setSidebar(!sidebar.classList.contains('open')));
    sidebarBackdrop?.addEventListener('click', () => setSidebar(false));
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => setSidebar(false));
    mobileQuery.addEventListener('change', () => setSidebar(false, false));
    setSidebar(false, false);
    sidebar?.addEventListener('keydown', (event) => {
        if (!sidebar.classList.contains('open')) return;
        if (event.key === 'Escape') { event.preventDefault(); setSidebar(false); }
        if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(el => el.getClientRects().length);
            const first = items[0], last = items.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });

    const profileMenu = document.querySelector('#profile-menu');
    const profileToggle = document.querySelector('[data-profile-toggle]');
    const positionProfileMenu = () => {
        if (!profileMenu?.open || !profileToggle) return;
        const rect = profileToggle.getBoundingClientRect();
        profileMenu.style.left = Math.max(12, Math.min(rect.right - profileMenu.offsetWidth, innerWidth - profileMenu.offsetWidth - 12)) + 'px';
        profileMenu.style.top = Math.max(12, Math.min(rect.bottom + 8, innerHeight - profileMenu.offsetHeight - 12)) + 'px';
    };
    profileToggle?.addEventListener('click', () => {
        setSidebar(false, false);
        profileMenu.showModal();
        profileToggle.setAttribute('aria-expanded', 'true');
        positionProfileMenu();
        syncOverlayLock();
    });
    document.querySelector('[data-profile-close]')?.addEventListener('click', () => profileMenu.close());
    profileMenu?.addEventListener('click', event => {
        if (event.target !== profileMenu) return;
        const r = profileMenu.getBoundingClientRect();
        if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) {
            event.preventDefault(); profileMenu.close();
        }
    });
    profileMenu?.addEventListener('close', () => {
        profileToggle?.setAttribute('aria-expanded', 'false');
        syncOverlayLock();
        profileToggle?.focus();
    });
    window.addEventListener('resize', positionProfileMenu);

    document.querySelectorAll('[data-avatar-image]').forEach(image => {
        const fallback = () => { image.hidden = true; const initial = image.parentElement.querySelector('[data-avatar-fallback]'); if (initial) initial.hidden = false; };
        image.addEventListener('error', fallback);
        if (image.hasAttribute('src') && image.complete && !image.naturalWidth) fallback();
    });
    const profileForm = document.querySelector('[data-profile-form]');
    if (profileForm) {
        const identityFields = [...profileForm.querySelectorAll('[data-edit-field]')].map(button => ({ button, input: document.getElementById(button.dataset.editField) }));
        const passwordLabel = profileForm.querySelector('[data-identity-password]');
        const password = passwordLabel.querySelector('input');
        const syncPassword = () => {
            const changed = identityFields.some(({ input }) => input.value !== input.defaultValue);
            passwordLabel.hidden = !changed;
            password.disabled = !changed;
            password.required = changed;
            if (!changed) password.value = '';
            profileForm.querySelector('[data-identity-help]').hidden = !identityFields.some(({ input }) => !input.readOnly);
        };
        identityFields.forEach(({ button, input }) => {
            button.addEventListener('click', () => {
                input.readOnly = !input.readOnly;
                if (input.readOnly) { input.value = input.defaultValue; setFieldError(input, ''); }
                button.setAttribute('aria-pressed', String(!input.readOnly));
                button.setAttribute('aria-label', (input.readOnly ? 'Editar ' : 'Cancelar edição de ') + (input.name === 'cpf' ? 'CPF' : 'e-mail'));
                syncPassword();
                if (!input.readOnly) input.focus();
            });
            input.addEventListener('input', syncPassword);
        });
        const photo = profileForm.querySelector('[data-profile-photo]');
        const preview = profileForm.querySelector('[data-profile-preview]');
        const savedSrc = preview.getAttribute('src');
        const photoError = profileForm.querySelector('#photo-error');
        let photoRevision = 0;
        const restorePreview = () => {
            if (savedSrc) { preview.src = savedSrc; preview.hidden = false; }
            else { preview.removeAttribute('src'); preview.hidden = true; }
            preview.parentElement.querySelector('[data-avatar-fallback]').hidden = !!savedSrc;
        };
        photo.addEventListener('change', () => {
            const revision = ++photoRevision;
            const file = photo.files[0];
            photoError.hidden = true;
            photoError.textContent = '';
            restorePreview();
            if (!file) return;
            const rejectPhoto = message => {
                if (revision !== photoRevision) return;
                photo.value = ''; photoError.textContent = message; photoError.hidden = false; restorePreview();
            };
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size === 0 || file.size > 5 * 1024 * 1024) {
                rejectPhoto('Escolha uma imagem JPG, PNG ou WebP de até 5 MB.'); return;
            }
            const reader = new FileReader();
            reader.onerror = () => rejectPhoto('Não foi possível ler a imagem.');
            reader.onload = () => {
                if (revision !== photoRevision) return;
                const probe = new Image();
                probe.onerror = () => rejectPhoto('A imagem não pôde ser aberta. Escolha outro arquivo.');
                probe.onload = () => {
                    if (revision !== photoRevision) return;
                    if (probe.width > 8192 || probe.height > 8192 || probe.width * probe.height > 25000000) {
                        rejectPhoto('A imagem excede o limite de dimensões.'); return;
                    }
                    preview.src = reader.result; preview.hidden = false;
                    preview.parentElement.querySelector('[data-avatar-fallback]').hidden = true;
                };
                probe.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
        profileForm.addEventListener('submit', event => {
            if (!profileForm.checkValidity()) { event.preventDefault(); profileForm.reportValidity(); return; }
            const submit = profileForm.querySelector('button[type="submit"]');
            submit.disabled = true;
            submit.textContent = 'Salvando…';
        });
        window.addEventListener('pageshow', () => {
            const submit = profileForm.querySelector('button[type="submit"]');
            submit.disabled = false; submit.textContent = 'Salvar alterações';
        });
    }

    const petForm = document.querySelector('[data-pet-form]');
    if (petForm) {
        const allergyToggle = petForm.querySelector('[data-allergies-toggle]');
        const allergyDetails = petForm.querySelector('#pet-allergy-details');
        const allergyDescription = petForm.querySelector('#pet-allergy-description');
        if (allergyToggle && allergyDetails && allergyDescription) {
            const syncAllergies = (animate = false) => {
                const expanded = allergyToggle.checked;
                allergyDetails.getAnimations?.().forEach(animation => animation.cancel());
                allergyToggle.setAttribute('aria-expanded', String(expanded));
                allergyDetails.hidden = !expanded;
                allergyDescription.disabled = !expanded;
                allergyDescription.required = expanded;
                if (!expanded) allergyDescription.value = '';
                if (expanded && animate && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    allergyDetails.animate?.([
                        { opacity: 0, transform: 'translateY(-6px)' },
                        { opacity: 1, transform: 'translateY(0)' }
                    ], { duration: 180, easing: 'ease-out' });
                }
            };
            allergyToggle.addEventListener('change', () => syncAllergies(true));
            petForm.addEventListener('reset', () => queueMicrotask(() => syncAllergies()));
            window.addEventListener('pageshow', () => syncAllergies());
            syncAllergies();
        }
        const photo = petForm.querySelector('#pet-photo');
        const preview = petForm.querySelector('[data-pet-photo-preview]');
        const placeholder = petForm.querySelector('[data-pet-photo-placeholder]');
        const error = petForm.querySelector('[data-pet-photo-error]');
        const originalPhoto = preview.getAttribute('src');
        let revision = 0;
        const restorePhoto = () => {
            if (originalPhoto) { preview.src = originalPhoto; preview.hidden = false; placeholder.hidden = true; }
            else { preview.removeAttribute('src'); preview.hidden = true; placeholder.hidden = false; }
        };
        photo.addEventListener('change', () => {
            const current = ++revision;
            const file = photo.files[0];
            error.hidden = true;
            error.textContent = '';
            restorePhoto();
            if (!file) return;
            const reject = message => {
                if (current !== revision) return;
                photo.value = '';
                error.textContent = message;
                error.hidden = false;
                restorePhoto();
            };
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size === 0 || file.size > 5 * 1024 * 1024) {
                reject('Selecione uma imagem JPG, PNG ou WebP de até 5 MB.');
                return;
            }
            const reader = new FileReader();
            reader.onerror = () => reject('Não foi possível ler esta imagem.');
            reader.onload = () => {
                if (current !== revision) return;
                const image = new Image();
                image.onerror = () => reject('O arquivo selecionado não é uma imagem válida.');
                image.onload = () => {
                    if (current !== revision) return;
                    if (image.width > 8192 || image.height > 8192 || image.width * image.height > 25000000) {
                        reject('A imagem excede as dimensões permitidas.');
                        return;
                    }
                    preview.src = reader.result;
                    preview.hidden = false;
                    placeholder.hidden = true;
                };
                image.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
        petForm.addEventListener('submit', event => {
            if (!petForm.checkValidity()) { event.preventDefault(); petForm.reportValidity(); return; }
            const submit = petForm.querySelector('button[type="submit"]');
            submit.disabled = true;
            submit.textContent = 'Salvando…';
        });
        const petSubmit = petForm.querySelector('button[type="submit"]');
        const petSubmitLabel = petSubmit?.textContent;
        window.addEventListener('pageshow', event => {
            if (!event.persisted || !petSubmit) return;
            petSubmit.disabled = false;
            petSubmit.textContent = petSubmitLabel;
        });
    }

    document.querySelectorAll("[data-password-toggle]").forEach((button) =>
        button.addEventListener("click", () => {
            const input = button.parentElement?.querySelector("input");
            if (!input) return;
            input.type = input.type === "password" ? "text" : "password";
            const icon = button.querySelector("i");
            if (icon) {
                icon.classList.toggle("fa-eye");
                icon.classList.toggle("fa-eye-slash");
            }
            button.setAttribute(
                "aria-label",
                input.type === "password" ? "Mostrar senha" : "Ocultar senha",
            );
        }),
    );

    // Custom Confirm Modal
    const createConfirmModal = () => {
        const modal = document.createElement("dialog");
        modal.className = "custom-modal-overlay hidden";
        modal.setAttribute('aria-labelledby', 'confirmation-title');
        modal.innerHTML = `
            <div class="custom-modal-box">
                <div class="custom-modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 id="confirmation-title" class="custom-modal-title">Confirmação</h3>
                <p class="custom-modal-message"></p>
                <div class="custom-modal-actions">
                    <button type="button" class="button secondary custom-modal-cancel">Cancelar</button>
                    <button type="button" class="button primary custom-modal-confirm">Confirmar</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    };

    let confirmModal = document.querySelector(".custom-modal-overlay");
    if (!confirmModal) {
        confirmModal = createConfirmModal();
    }

    const confirmMessage = confirmModal.querySelector(".custom-modal-message");
    const btnCancel = confirmModal.querySelector(".custom-modal-cancel");
    const btnConfirm = confirmModal.querySelector(".custom-modal-confirm");

    let currentFormToSubmit = null;

    const DANGER_KEYWORDS = ['cancelar', 'excluir', 'remover', 'deletar'];

    const closeConfirmModal = () => {
        confirmModal.close();
        confirmModal.classList.add("hidden");
        currentFormToSubmit = null;
        syncOverlayLock();
    };
    confirmModal.addEventListener('cancel', event => { event.preventDefault(); closeConfirmModal(); });

    btnCancel.addEventListener("click", closeConfirmModal);

    // Fechar modal clicando no overlay (fora da caixa)
    confirmModal.addEventListener("click", (e) => {
        if (e.target === confirmModal) closeConfirmModal();
    });

    // Fechar modal com a tecla Escape
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !confirmModal.classList.contains("hidden")) {
            closeConfirmModal();
        }
    });

    btnConfirm.addEventListener("click", () => {
        if (currentFormToSubmit) {
            const form = currentFormToSubmit;
            closeConfirmModal();
            if (beginFormNavigation()) HTMLFormElement.prototype.submit.call(form);
        }
    });

    document.querySelectorAll("form[data-confirm]").forEach((form) =>
        form.addEventListener("submit", (event) => {
            event.preventDefault();
            const message = form.dataset.confirm || "Tem certeza que deseja continuar?";
            confirmMessage.textContent = message;
            
            // Verifica se é uma ação destrutiva para pintar o botão de vermelho
            const msgLower = message.toLowerCase();
            const isDanger = form.querySelector('.danger') !== null
                || DANGER_KEYWORDS.some(kw => msgLower.includes(kw));
            if (isDanger) {
                btnConfirm.classList.add('custom-danger-btn');
            } else {
                btnConfirm.classList.remove('custom-danger-btn');
            }

            currentFormToSubmit = form;
            confirmModal.classList.remove("hidden");
            confirmModal.showModal();
            btnCancel.focus();
            syncOverlayLock();
        }),
    );

    document.querySelectorAll(".otp input").forEach((input, index, all) => {
        input.addEventListener("input", () => {
            input.value = input.value.replace(/\D/g, "").slice(0, 1);
            if (input.value) all[index + 1]?.focus();
        });

        input.addEventListener("keydown", (event) => {
            if (event.key === "Backspace" && !input.value) all[index - 1]?.focus();
        });
    });

    document.querySelectorAll('.selection-card input[type="radio"], .service-card input[type="radio"]').forEach((input) =>
        input.addEventListener("change", () => {
            const isService = input.closest(".service-card");
            const listClass = isService ? ".service-list" : ".selection-grid";
            const cardClass = isService ? ".service-card" : ".selection-card";

            input.closest(listClass)?.querySelectorAll(cardClass).forEach((card) => card.classList.remove("selected"));
            input.closest(cardClass)?.classList.add("selected");
        }),
    );

    const tableFilter = document.querySelector("[data-table-filter]");
    const statusFilter = document.querySelector("[data-status-filter]");
    const petFilter = document.querySelector("[data-pet-filter]");
    const tabs = document.querySelectorAll("[data-tab-btn]");
    const paginationControls = document.querySelector("[data-pagination-controls]");
    const paginationInfo = document.querySelector("[data-pagination-info]");
    const tableWrap = document.querySelector(".table-wrap");
    const paginationFooter = document.querySelector(".pagination");

    const DATA_ROW_SELECTOR = "[data-filter-table] tbody tr:not(#no-results-row)";

    let currentTab = document.querySelector("[data-tab-btn].active")?.dataset.tabBtn || 'all';
    let currentPage = 1;
    const itemsPerPage = 5;
    let lastFilteredRows = [];

    const filterRows = () => {
        const query = (tableFilter?.value || "").trim().toLocaleLowerCase("pt-BR");
        const status = statusFilter?.value || "";
        const pet = (petFilter?.value || "").trim();

        lastFilteredRows = [];

        document.querySelectorAll(DATA_ROW_SELECTOR).forEach((row) => {
            const rowTab = row.dataset.tab || 'all';

            // Ao selecionar um pet, exibe todos os agendamentos dele independente da aba atual
            const matchesTab = (pet !== "") || currentTab === 'all' || rowTab === currentTab;

            const matchesText = !query || row.textContent.toLocaleLowerCase("pt-BR").includes(query);
            const matchesStatus = !status || row.dataset.status === status;

            const rowPet = (row.getAttribute('data-pet') || "").trim();
            const matchesPet = !pet || rowPet.toLocaleLowerCase("pt-BR") === pet.toLocaleLowerCase("pt-BR");

            if (matchesTab && matchesText && matchesStatus && matchesPet) {
                lastFilteredRows.push(row);
                if (!paginationControls) row.hidden = false;
            } else {
                if (!paginationControls) row.hidden = true;
            }
        });

        if (paginationControls) {
            renderTable();
            renderPagination();
        }
    };

    function navigate(page) {
        currentPage = page;
        renderTable();
        renderPagination();
    }

    function renderTable() {
        const allDataRows = document.querySelectorAll(DATA_ROW_SELECTOR);
        allDataRows.forEach(row => row.style.display = 'none');

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageRows = lastFilteredRows.slice(startIndex, endIndex);

        pageRows.forEach(row => row.style.display = '');

        const totalFiltered = lastFilteredRows.length;
        const endDisplay = Math.min(endIndex, totalFiltered);

        let noResultsRow = document.querySelector("#no-results-row");

        if (allDataRows.length === 0) {
            if (tableWrap) tableWrap.style.display = 'none';
        } else if (totalFiltered === 0) {
            if (!noResultsRow) {
                const tbody = document.querySelector("[data-filter-table] tbody");
                if (tbody) {
                    noResultsRow = document.createElement("tr");
                    noResultsRow.id = "no-results-row";
                    noResultsRow.className = "table-no-results";
                    const colCount = document.querySelectorAll("[data-filter-table] thead th").length;
                    noResultsRow.innerHTML = `<td colspan="${colCount}">Nenhum agendamento encontrado para os filtros selecionados.</td>`;
                    tbody.appendChild(noResultsRow);
                }
            }
            if (noResultsRow) noResultsRow.style.display = '';
            if (tableWrap) tableWrap.style.display = '';
        } else {
            if (noResultsRow) noResultsRow.style.display = 'none';
            if (tableWrap) tableWrap.style.display = '';
        }

        if (paginationInfo) {
            if (totalFiltered === 0) {
                paginationInfo.textContent = 'Nenhum agendamento encontrado.';
            } else {
                paginationInfo.textContent = `Mostrando ${startIndex + 1} a ${endDisplay} de ${totalFiltered} agendamentos`;
            }
        }

        if (paginationFooter) {
            paginationFooter.style.display = totalFiltered > 0 ? 'flex' : 'none';
        }
    }

    function renderPagination() {
        const totalPages = Math.ceil(lastFilteredRows.length / itemsPerPage);
        paginationControls.innerHTML = '';

        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = `btn-pagination prev ${currentPage === 1 ? 'disabled' : ''}`;
        prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i>';
        prevBtn.type = 'button';
        prevBtn.onclick = () => navigate(currentPage - 1);
        if (currentPage === 1) prevBtn.disabled = true;
        paginationControls.appendChild(prevBtn);

        let startPage = Math.max(1, currentPage - 1);
        let endPage = startPage + 2;

        if (endPage > totalPages) {
            endPage = totalPages;
            startPage = Math.max(1, endPage - 2);
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn-pagination ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.type = 'button';
            pageBtn.onclick = () => navigate(i);
            paginationControls.appendChild(pageBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = `btn-pagination next ${currentPage === totalPages ? 'disabled' : ''}`;
        nextBtn.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
        nextBtn.type = 'button';
        nextBtn.onclick = () => navigate(currentPage + 1);
        if (currentPage === totalPages) nextBtn.disabled = true;
        paginationControls.appendChild(nextBtn);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentTab = tab.dataset.tabBtn;
            currentPage = 1;
            filterRows();
        });
    });

    tableFilter?.addEventListener("input", () => { currentPage = 1; filterRows(); });
    statusFilter?.addEventListener("change", () => { currentPage = 1; filterRows(); });
    petFilter?.addEventListener("change", () => { currentPage = 1; filterRows(); });

    if (paginationControls || tabs.length > 0) {
        filterRows();
    }

    // Password strength indicator
    const strengthPanel = document.querySelector("[data-password-strength]");
    const strengthLabel = document.querySelector("[data-strength-label]");
    const passwordInput = document.querySelector('input[name="password"][autocomplete="new-password"]');

    if (strengthPanel && strengthLabel && passwordInput) {
        const bars = strengthPanel.querySelectorAll(".register-strength-track i");
        let debounceTimer = null;

        passwordInput.addEventListener("input", () => {
            const value = passwordInput.value;

            if (!value) {
                strengthPanel.hidden = true;
                strengthPanel.removeAttribute("data-level");
                strengthLabel.textContent = "";
                bars.forEach((b) => b.classList.remove("active"));
                return;
            }

            strengthPanel.hidden = false;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const body = new FormData();
                body.append("password", value);
                body.append('_token', passwordInput.form?.querySelector('input[name="_token"]')?.value || '');

                fetch("/api/password-strength", { method: "POST", body })
                    .then((r) => r.json())
                    .then((data) => {
                        strengthPanel.setAttribute("data-level", data.level);
                        strengthPanel.setAttribute("aria-label", "Força da senha: " + data.label);
                        strengthLabel.textContent = data.label + " " + data.emoji;

                        bars.forEach((b, i) => {
                            b.classList.toggle("active", i < data.bars);
                        });
                    })
                    .catch(() => { });
            }, 250);
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function setFieldError(input, message) {
        input.setCustomValidity(message);
        const wrapper = input.parentElement.classList.contains("register-input") ? input.parentElement : input;

        if (wrapper.nextElementSibling && wrapper.nextElementSibling.classList.contains("field-error")) {
            wrapper.nextElementSibling.remove();
        }

        if (message) {
            const span = document.createElement("span");
            span.className = "field-error";
            span.textContent = message;
            wrapper.after(span);
        }
    }

    // ── Phone mask: (xx) xxxxx-xxxx ─────────────────────────────────────────
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener("input", () => {
            let v = phoneInput.value.replace(/\D/g, "").slice(0, 11);
            if (v.length > 10) {
                v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, "($1) $2-$3");
            } else if (v.length > 6) {
                v = v.replace(/^(\d{2})(\d{4})(\d*)$/, "($1) $2-$3");
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d*)$/, "($1) $2");
            } else if (v.length > 0) {
                v = v.replace(/^(\d*)$/, "($1");
            }
            phoneInput.value = v;
        });

        phoneInput.addEventListener("blur", () => {
            const digits = phoneInput.value.replace(/\D/g, "");
            if (digits && (digits.length < 10 || digits.length > 11)) {
                setFieldError(phoneInput, "Informe um telefone válido com DDD.");
            } else {
                setFieldError(phoneInput, "");
            }
        });
    }

    // ── CPF mask: xxx.xxx.xxx-xx ─────────────────────────────────────────────
    const cpfInput = document.querySelector('input[name="cpf"]');
    if (cpfInput) {
        cpfInput.addEventListener("input", () => {
            let v = cpfInput.value.replace(/\D/g, "").slice(0, 11);
            v = v
                .replace(/^(\d{3})(\d)/, "$1.$2")
                .replace(/^(\d{3}\.\d{3})(\d)/, "$1.$2")
                .replace(/^(\d{3}\.\d{3}\.\d{3})(\d)/, "$1-$2");
            cpfInput.value = v;
        });

        cpfInput.addEventListener("blur", () => {
            const digits = cpfInput.value.replace(/\D/g, "");
            if (!digits) { setFieldError(cpfInput, ""); return; }
            if (digits.length !== 11 || /^(\d)\1{10}$/.test(digits) || !validateCpf(digits)) {
                setFieldError(cpfInput, "Informe um CPF válido.");
            } else {
                setFieldError(cpfInput, "");
            }
        });

        function validateCpf(cpf) {
            let sum = 0;
            for (let i = 0; i < 9; i++) sum += parseInt(cpf[i]) * (10 - i);
            let rem = (sum * 10) % 11;
            if (rem === 10) rem = 0;
            if (rem !== parseInt(cpf[9])) return false;
            sum = 0;
            for (let i = 0; i < 10; i++) sum += parseInt(cpf[i]) * (11 - i);
            rem = (sum * 10) % 11;
            if (rem === 10) rem = 0;
            return rem === parseInt(cpf[10]);
        }
    }

    // ── Birth date mask: dd/mm/aaaa ───────────────────────────────────────────
    const birthInput = document.querySelector('input[name="birth_date"]');
    if (birthInput && birthInput.type !== 'date') {
        birthInput.addEventListener("input", () => {
            let v = birthInput.value.replace(/\D/g, "").slice(0, 8);
            v = v
                .replace(/^(\d{2})(\d)/, "$1/$2")
                .replace(/^(\d{2}\/\d{2})(\d)/, "$1/$2");
            birthInput.value = v;
        });

        birthInput.addEventListener("blur", () => {
            const v = birthInput.value;
            if (!v) { setFieldError(birthInput, ""); return; }

            const [day, month, year] = v.split("/").map(Number);
            const date = new Date(year, month - 1, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Normalize today to start of day for comparison

            const isValidFormat =
                date.getDate() === day &&
                date.getMonth() === month - 1 &&
                date.getFullYear() === year &&
                year >= 1900;

            if (!isValidFormat) {
                setFieldError(birthInput, "Informe uma data de nascimento válida.");
            } else if (date > today) {
                setFieldError(birthInput, "A data informada não pode ser no futuro.");
            } else {
                const age = today.getFullYear() - year -
                    (today < new Date(today.getFullYear(), month - 1, day) ? 1 : 0);
                if (age < 18 && !birthInput.closest(".pet-form")) {
                    setFieldError(birthInput, "É necessário ter pelo menos 18 anos.");
                } else {
                    setFieldError(birthInput, "");
                }
            }
        });
    }

    // ── Email validation on blur ──────────────────────────────────────────────
    const emailInput = document.querySelector('input[name="email"][autocomplete="email"]');
    if (emailInput) {
        emailInput.addEventListener("blur", () => {
            const v = emailInput.value.trim();
            if (!v) { setFieldError(emailInput, ""); return; }
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) && v.length <= 190;
            setFieldError(emailInput, valid ? "" : "Informe um e-mail válido.");
        });

        emailInput.addEventListener("input", () => {
            if (emailInput.validity.customError) setFieldError(emailInput, "");
        });
    }

    const bookingForm = document.querySelector('[data-booking-form]');
    if (bookingForm) {
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
                if (staffMessage) staffMessage.textContent = 'Selecione um horário e um profissional disponível para continuar.';
            }
        });
        let availabilityRevision = 0;
        const refreshAvailability = async (includeTime = true) => {
            if (!dateInput || !timeSelect) return;
            const endLoading = beginRequestLoading();
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
            refreshAvailability(true);
        }
        bookingForm.querySelectorAll('input[name="staff_id"]').forEach(radio => {
            radio.addEventListener('change', () => {
                preferredStaff = radio.value;
                if (staffMessage) staffMessage.textContent = '';
                bookingForm.querySelectorAll('.staff-card').forEach(card => card.classList.toggle('selected', card.querySelector('input[name="staff_id"]')?.checked === true));
            });
        });
    }
});
