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

  document.querySelectorAll("form[data-confirm]").forEach((form) =>
    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm || "Confirmar ação?"))
        event.preventDefault();
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

  document.querySelectorAll(".selection-card input").forEach((input) =>
    input.addEventListener("change", () => {
      input.closest(".selection-grid")?.querySelectorAll(".selection-card").forEach((card) => card.classList.remove("selected"));
      input.closest(".selection-card")?.classList.add("selected");
    }),
  );

  const tableFilter = document.querySelector("[data-table-filter]");
  const statusFilter = document.querySelector("[data-status-filter]");
  const filterRows = () => {
    const query = (tableFilter?.value || "").trim().toLocaleLowerCase("pt-BR");
    const status = statusFilter?.value || "";
    document.querySelectorAll("[data-filter-table] tbody tr").forEach((row) => {
      const matchesText = !query || row.textContent.toLocaleLowerCase("pt-BR").includes(query);
      const matchesStatus = !status || row.dataset.status === status;
      row.hidden = !(matchesText && matchesStatus);
    });
  };
  tableFilter?.addEventListener("input", filterRows);
  statusFilter?.addEventListener("change", filterRows);

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
          .catch(() => {});
      }, 250);
    });
  }

  // ── Helpers ──────────────────────────────────────────────────────────────
  function setFieldError(input, message) {
    input.setCustomValidity(message);
    const existing = input.parentElement?.querySelector(".field-error");
    if (existing) existing.remove();
    if (message) {
      const span = document.createElement("span");
      span.className = "field-error";
      span.textContent = message;
      input.parentElement?.after(span);
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
      const isValid =
        date.getDate() === day &&
        date.getMonth() === month - 1 &&
        date.getFullYear() === year &&
        year >= 1900 &&
        date <= today;

      if (!isValid) {
        setFieldError(birthInput, "Informe uma data de nascimento válida.");
      } else {
        const age = today.getFullYear() - year -
          (today < new Date(today.getFullYear(), month - 1, day) ? 1 : 0);
        if (age < 18) {
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
