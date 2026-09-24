/**
 * landing.js — Beleza Cao&Gato
 * Animacoes: Lenis Smooth Scroll + GSAP + ScrollTrigger
 * PIVOT: Pagina do estabelecimento fisico
 * Carregado EXCLUSIVAMENTE na landing page (/)
 */

(function () {
    "use strict";

    // 0. GUARD
    if (typeof gsap === "undefined" || typeof ScrollTrigger === "undefined") {
        console.warn("[landing.js] GSAP/ScrollTrigger nao encontrado.");
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    // 1. LENIS SMOOTH SCROLL
    var lenis = null;
    if (typeof Lenis !== "undefined") {
        lenis = new Lenis({
            duration: 1.2,
            easing: function(t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
            smooth: true,
            smoothTouch: false,
        });
        gsap.ticker.add(function(time) { lenis.raf(time * 1000); });
        gsap.ticker.lagSmoothing(0);

        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener("click", function(e) {
                var target = document.querySelector(anchor.getAttribute("href"));
                if (!target) return;
                e.preventDefault();
                lenis.scrollTo(target, { offset: -80, duration: 1.4 });
            });
        });
    }

    // 2. NAVBAR — transparente no hero, escuro ao rolar
    var header = document.getElementById("lp-header");
    if (header) {
        ScrollTrigger.create({
            start: "top -80px",
            onEnter: function() { header.classList.add("lp-header--scrolled"); },
            onLeaveBack: function() { header.classList.remove("lp-header--scrolled"); },
        });
    }

    // 3. HERO — entrada stagger (sobre video de fundo)
    var heroTl = gsap.timeline({ defaults: { ease: "power4.out" } });

    if (document.getElementById("lp-kicker")) {
        heroTl.from("#lp-kicker", { opacity: 0, y: 20, duration: 0.7, delay: 0.3 });
    }
    if (document.querySelector(".lp-hero-title")) {
        heroTl.from(".lp-hero-title", { opacity: 0, y: 48, duration: 1.0 }, "-=0.45");
    }
    if (document.getElementById("lp-hero-sub")) {
        heroTl.from("#lp-hero-sub", { opacity: 0, y: 32, duration: 0.85 }, "-=0.6");
    }
    if (document.querySelector(".lp-hero-actions")) {
        heroTl.from(".lp-hero-actions > *", { opacity: 0, y: 24, duration: 0.7, stagger: 0.14 }, "-=0.55");
    }
    if (document.querySelector(".lp-hero-trust")) {
        heroTl.from(".lp-hero-trust > span", { opacity: 0, y: 16, duration: 0.5, stagger: 0.1 }, "-=0.45");
    }

    // Scroll hint
    var scrollHint = document.querySelector(".lp-scroll-hint");
    if (scrollHint) {
        ScrollTrigger.create({
            start: "top -100px",
            onEnter: function() { gsap.to(scrollHint, { opacity: 0, y: -16, duration: 0.5 }); },
            onLeaveBack: function() { gsap.to(scrollHint, { opacity: 1, y: 0, duration: 0.5 }); },
        });
    }

    // 4. VIDEO DE FUNDO — leve zoom-out ao rolar (parallax)
    var bgVideo = document.querySelector(".lp-bg-video");
    if (bgVideo) {
        gsap.fromTo(bgVideo,
            { scale: 1.08 },
            {
                scale: 1.0,
                ease: "none",
                scrollTrigger: {
                    trigger: ".lp-hero",
                    start: "top top",
                    end: "bottom top",
                    scrub: 1.5,
                }
            }
        );
    }

    // 5. BENTO GRID (Servicos) — 3D flip-up stagger
    var bentoSection = document.querySelector(".lp-features .lp-section-intro");
    if (bentoSection) {
        gsap.from(".lp-features .lp-section-intro > *", {
            scrollTrigger: { trigger: ".lp-features", start: "top 80%" },
            opacity: 0, y: 36, stagger: 0.14, duration: 0.8, ease: "power3.out",
        });
    }

    document.querySelectorAll("[data-bento]").forEach(function(card, i) {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: "top 88%",
                toggleActions: "play none none reverse",
            },
            opacity: 0,
            y: 60,
            rotateX: 7,
            scale: 0.97,
            duration: 0.85,
            delay: i * 0.09,
            ease: "power3.out",
            transformOrigin: "bottom center",
        });
    });

    // 6. GALERIA DE VIDEOS — autoplay no hover e ao entrar na tela
    document.querySelectorAll("[data-video-item]").forEach(function(item) {
        var video = item.querySelector(".lp-gallery-video");
        if (!video) return;

        // Autoplay ao entrar na viewport (ScrollTrigger)
        ScrollTrigger.create({
            trigger: item,
            start: "top 80%",
            onEnter: function() {
                video.play().catch(function() {});
            },
            onLeave: function() {
                video.pause();
            },
            onEnterBack: function() {
                video.play().catch(function() {});
            },
            onLeaveBack: function() {
                video.pause();
            },
        });

        // Animacao de entrada
        gsap.from(item, {
            scrollTrigger: {
                trigger: item,
                start: "top 90%",
                toggleActions: "play none none reverse",
            },
            opacity: 0,
            y: 40,
            scale: 0.97,
            duration: 0.9,
            ease: "power3.out",
        });
    });

    // Copy lateral da galeria
    var vidCopy = document.getElementById("lp-vid-copy");
    if (vidCopy) {
        gsap.from("#lp-vid-copy .lp-vid-copy-inner > *", {
            scrollTrigger: { trigger: vidCopy, start: "top 85%" },
            opacity: 0, x: 30, stagger: 0.12, duration: 0.75, ease: "power3.out",
        });
    }

    // 7. TRUST — Counters + valores
    gsap.from(".lp-trust-copy > *", {
        scrollTrigger: { trigger: ".lp-trust", start: "top 75%" },
        opacity: 0, y: 40, stagger: 0.14, duration: 0.85, ease: "power3.out",
    });

    gsap.from(".lp-trust-value", {
        scrollTrigger: { trigger: ".lp-trust-values", start: "top 82%" },
        opacity: 0, x: -24, stagger: 0.10, duration: 0.65, ease: "power3.out",
    });

    document.querySelectorAll(".lp-counter-num").forEach(function(counter) {
        var target = parseInt(counter.getAttribute("data-target"), 10);
        if (isNaN(target)) return;
        var obj = { val: 0 };
        gsap.to(obj, {
            val: target,
            duration: 2.2,
            ease: "power2.out",
            scrollTrigger: { trigger: counter, start: "top 85%", once: true },
            onUpdate: function() { counter.textContent = Math.round(obj.val).toLocaleString("pt-BR"); },
            onComplete: function() { counter.textContent = target.toLocaleString("pt-BR"); },
        });
    });

    gsap.from(".lp-counter-item", {
        scrollTrigger: { trigger: "#lp-counters", start: "top 80%" },
        opacity: 0, y: 30, stagger: 0.12, duration: 0.7, ease: "power3.out",
    });

    if (document.getElementById("lp-quote")) {
        gsap.from("#lp-quote", {
            scrollTrigger: { trigger: "#lp-quote", start: "top 85%" },
            opacity: 0, y: 40, duration: 0.9, ease: "power3.out",
        });
    }

    // 8. LOCALIZAÇÃO — entrada info + mapa
    var locationGrid = document.getElementById("lp-location-grid");
    if (locationGrid) {
        gsap.from(".lp-info-block", {
            scrollTrigger: { trigger: locationGrid, start: "top 82%" },
            opacity: 0, x: -32, stagger: 0.15, duration: 0.8, ease: "power3.out",
        });
        gsap.from("#lp-map-placeholder", {
            scrollTrigger: { trigger: locationGrid, start: "top 82%" },
            opacity: 0, x: 32, duration: 0.9, ease: "power3.out",
        });
    }

    // 9. CTA FINAL — entrada + efeito magnetico
    var ctaSection = document.getElementById("lp-cta-final");
    if (ctaSection) {
        gsap.from(".lp-cta-paws", {
            scrollTrigger: { trigger: ctaSection, start: "top 78%" },
            opacity: 0, scale: 0.5, rotation: -20, duration: 0.8, ease: "back.out(1.7)",
        });
        gsap.from(".lp-cta-content > *:not(.lp-cta-paws)", {
            scrollTrigger: { trigger: ctaSection, start: "top 78%" },
            opacity: 0, y: 40, stagger: 0.14, duration: 0.9, ease: "power4.out",
        });
    }

    // Efeito magnetico nos botoes CTA finais
    ["lp-final-cta", "lp-final-whatsapp"].forEach(function(id) {
        var btn = document.getElementById(id);
        if (!btn) return;
        btn.addEventListener("mousemove", function(e) {
            var r = btn.getBoundingClientRect();
            var x = e.clientX - r.left - r.width / 2;
            var y = e.clientY - r.top - r.height / 2;
            gsap.to(btn, { x: x * 0.22, y: y * 0.22, duration: 0.35, ease: "power2.out" });
        });
        btn.addEventListener("mouseleave", function() {
            gsap.to(btn, { x: 0, y: 0, duration: 0.55, ease: "elastic.out(1, 0.5)" });
        });
    });

    // 9.5 GALERIA DE VIDEOS - Play on hover
    document.querySelectorAll('[data-video-item]').forEach(function(item) {
        var video = item.querySelector('video');
        if (video) {
            item.addEventListener('mouseenter', function() {
                video.play().catch(function(e) { console.warn("Video play prevented:", e); });
            });
            item.addEventListener('mouseleave', function() {
                video.pause();
            });
        }
    });

    // 10. FOOTER
    gsap.from(".lp-footer-inner > *", {
        scrollTrigger: { trigger: ".lp-footer", start: "top 90%" },
        opacity: 0, y: 20, stagger: 0.10, duration: 0.65, ease: "power3.out",
    });

})();
