document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector("[data-sidebar]");
    document.querySelector("[data-sidebar-toggle]")
        ?.addEventListener("click", () => sidebar?.classList.toggle("open"));

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
        const modal = document.createElement("div");
        modal.className = "custom-modal-overlay hidden";
        modal.innerHTML = `
            <div class="custom-modal-box">
                <div class="custom-modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="custom-modal-title">Confirmação</h3>
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
        confirmModal.classList.add("hidden");
        currentFormToSubmit = null;
    };

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
            currentFormToSubmit.submit();
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

    document.querySelector("[data-booking-date]")?.addEventListener("change", (event) => {
        const url = new URL(window.location.href);
        url.searchParams.set("date", event.currentTarget.value);
        window.location.assign(url.toString());
    });

    document.querySelectorAll(".selection-card input, .service-card input").forEach((input) =>
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
    if (birthInput) {
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
});
