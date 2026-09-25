/**
 * auth-animations.js
 * Animações GSAP para as telas de Login e Cadastro.
 *
 * Respeita prefers-reduced-motion.
 * Funciona mesmo se o GSAP falhar no carregamento (graceful degradation).
 */
(function () {
    "use strict";

    // ── Graceful Degradation: se o GSAP não carregou, não faz nada ───────────
    if (typeof window.gsap === "undefined") {
        return;
    }

    // ── Respeita preferência do sistema: sem animações ────────────────────────
    const prefersReducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;

    if (prefersReducedMotion) {
        return;
    }

    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;

    // Registra plugins se disponíveis
    if (ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
    }

    // ── Elementos ─────────────────────────────────────────────────────────────
    const infoPanel   = document.getElementById("auth-info-panel");
    const brand       = document.getElementById("auth-brand");
    const infoCopy    = document.getElementById("auth-info-copy");
    const highlights  = document.getElementById("auth-info-highlights");
    const ratingBadge = document.getElementById("auth-rating-badge");
    const formPanel   = document.getElementById("auth-form-panel");
    const authCard    = formPanel?.querySelector(".auth-card");
    const cardHeader  = document.getElementById("auth-card-header");
    const authFields  = document.getElementById("auth-fields");
    const authActions = document.getElementById("auth-actions");

    // ── Timeline principal ────────────────────────────────────────────────────
    const tl = gsap.timeline({ defaults: { ease: "power3.out" } });

    // Estado inicial: visibilidade zerada (apenas o painel de informação)
    gsap.set(infoPanel, { opacity: 0 });

    if (authCard) {
        gsap.set(authCard, { opacity: 0, y: 30 });
    }

    // 1. Painel de informação: fade in
    tl.to(infoPanel, { opacity: 1, duration: 0.6 });

    // 2. Card do formulário: sobe do fundo
    if (authCard) {
        tl.to(authCard, { opacity: 1, y: 0, duration: 0.55 }, "-=0.25");
    }

    // 3. Header do card: surgir suavemente
    if (cardHeader) {
        gsap.set(cardHeader, { opacity: 0, y: 12 });
        tl.to(cardHeader, { opacity: 1, y: 0, duration: 0.4 }, "-=0.2");
    }

    // 4. Campos de formulário: stagger (entram um por um)
    if (authFields) {
        const fieldItems = authFields.querySelectorAll(
            ".auth-field-label, .register-row, .auth-form-inline, .register-strength, .auth-terms-label, .register-field-full"
        );
        if (fieldItems.length) {
            gsap.set(fieldItems, { opacity: 0, y: 10 });
            tl.to(
                fieldItems,
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.35,
                    stagger: 0.07,
                },
                "-=0.1"
            );
        }
    }

    // 5. Botão de ação: pop suave
    if (authActions) {
        gsap.set(authActions, { opacity: 0, scale: 0.97 });
        tl.to(
            authActions,
            { opacity: 1, scale: 1, duration: 0.35 },
            "-=0.05"
        );
    }

    // 6. Stagger do painel de informação (desktop only)
    const isDesktop = window.matchMedia("(min-width: 1024px)").matches;

    if (isDesktop && infoCopy) {
        const copyItems = infoCopy.querySelectorAll(
            ".auth-eyebrow, h1, p, .auth-highlight-item, .auth-rating-badge"
        );
        if (copyItems.length) {
            gsap.set(copyItems, { opacity: 0, y: 18 });
            tl.to(
                copyItems,
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.45,
                    stagger: 0.09,
                    ease: "power2.out",
                },
                0.3 // começa junto com as primeiras animações
            );
        }
    }

    // ── Parallax sutil na imagem de fundo (desktop only) ─────────────────────
    if (isDesktop && infoPanel) {
        const parallaxTarget = infoPanel.querySelector(".auth-info-panel::before");

        // Usamos o pseudo-element via o próprio painel, animando background-position
        let startY = 20; // posição inicial em %

        window.addEventListener(
            "scroll",
            function () {
                const scrolled = window.scrollY;
                const shift = startY + scrolled * 0.018;
                // Limita para não ultrapassar os limites razoáveis
                const clampedShift = Math.min(Math.max(shift, 10), 40);
                infoPanel.style.setProperty(
                    "--auth-parallax-y",
                    clampedShift + "%"
                );
            },
            { passive: true }
        );

        // Aplica a variável CSS ao painel
        infoPanel.style.setProperty("--auth-parallax-y", startY + "%");

        // Injeta a regra dinâmica de parallax (uma vez)
        const styleTag = document.createElement("style");
        styleTag.textContent = `
            .auth-info-panel::before {
                background-position: center var(--auth-parallax-y, 20%) !important;
            }
        `;
        document.head.appendChild(styleTag);
    }

    // ── Microinteração: botão submit ─────────────────────────────────────────
    const submitBtn = document.querySelector(".auth-submit-btn");
    if (submitBtn) {
        submitBtn.addEventListener("mouseenter", function () {
            gsap.to(this, {
                scale: 1.02,
                duration: 0.2,
                ease: "power2.out",
            });
        });

        submitBtn.addEventListener("mouseleave", function () {
            gsap.to(this, {
                scale: 1,
                duration: 0.18,
                ease: "power2.in",
            });
        });

        submitBtn.addEventListener("mousedown", function () {
            gsap.to(this, { scale: 0.97, duration: 0.1 });
        });

        submitBtn.addEventListener("mouseup", function () {
            gsap.to(this, { scale: 1.02, duration: 0.12 });
        });
    }

    // ── Focus: iluminar ícone do campo ────────────────────────────────────────
    document.querySelectorAll(".auth-input-wrap input").forEach(function (input) {
        const icon = input
            .closest(".auth-input-wrap")
            ?.querySelector(".auth-input-icon");
        if (!icon) return;

        input.addEventListener("focus", function () {
            gsap.to(icon, { opacity: 1, color: "#e8612d", duration: 0.2 });
        });

        input.addEventListener("blur", function () {
            gsap.to(icon, { opacity: 0.55, color: "#1a4350", duration: 0.2 });
        });
    });
})();
