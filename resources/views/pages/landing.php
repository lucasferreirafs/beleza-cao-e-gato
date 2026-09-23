<main class="landing-page" id="landing-root">

    <!-- ══════════════════════════════════════════
         HEADER — Glassmorphism Navbar
    ══════════════════════════════════════════ -->
    <header class="lp-header" id="lp-header">
        <a class="lp-brand" href="/" aria-label="Beleza Cão e Gato, página inicial">
            <span class="lp-brand-mark" aria-hidden="true">
                <i class="fa-solid fa-paw"></i>
            </span>
            <span class="lp-brand-text">
                <strong>Beleza Cão&amp;Gato</strong>
                <small>PET SHOP &amp; CLÍNICA</small>
            </span>
        </a>

        <nav class="lp-nav" aria-label="Navegação principal">
            <a href="#servicos" class="lp-nav-link">Serviços</a>
            <a href="#tour" class="lp-nav-link">Nossa Casa</a>
            <a href="#confianca" class="lp-nav-link">Sobre nós</a>
            <a href="#localizacao" class="lp-nav-link">Localização</a>
        </nav>

        <a class="lp-login-btn" href="/login" id="lp-login-btn">
            Área do Cliente
            <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
        </a>
    </header>

    <!-- ══════════════════════════════════════════
         HERO — Vídeo de Fundo Full-Screen
    ══════════════════════════════════════════ -->
    <section class="lp-hero lp-hero-video" aria-labelledby="lp-hero-title">

        <!-- Vídeo de fundo -->
        <div class="lp-hero-video-bg" aria-hidden="true">
            <video class="lp-bg-video" src="/assets/video/video_2.mp4" autoplay muted loop playsinline
                preload="auto"></video>
            <div class="lp-video-overlay"></div>
        </div>

        <!-- Orbs sutis sobre o overlay -->
        <div class="lp-orb lp-orb-1" aria-hidden="true"></div>
        <div class="lp-orb lp-orb-2" aria-hidden="true"></div>

        <div class="lp-hero-content lp-hero-content-centered">
            <div class="lp-hero-copy lp-hero-copy-light">
                <div class="lp-hero-badge" id="lp-kicker">
                    <span class="lp-hero-badge-stars">★★★★½</span>
                    <span class="lp-hero-badge-text">4,7 no Google · 89 avaliações</span>
                </div>

                <h1 class="lp-hero-title lp-hero-title-light" id="lp-hero-title">
                    Bem-vindo ao<br>
                    <span class="lp-hero-highlight-white">Beleza Cão&amp;Gato</span>
                </h1>

                <p class="lp-hero-sub lp-hero-sub-light" id="lp-hero-sub">
                    Conforto, estética e saúde para o seu filho de quatro patas.<br>
                    Um time apaixonado pelo que faz, cuidando com carinho de cada detalhe.
                </p>

                <div class="lp-hero-actions" id="lp-hero-actions">
                    <div>
                        <a class="lp-btn-primary lp-btn-xl" href="#servicos" id="lp-cta-main">
                            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                            Agende um Horário
                        </a>
                        <a class="lp-btn-ghost lp-btn-ghost-light lp-btn-ghost-xl" href="#tour" id="lp-cta-ghost">
                            Conheça o espaço
                        </a>
                    </div>

                </div>

                <div class="lp-hero-trust" id="lp-hero-trust">
                    <span><i class="fa-solid fa-star"></i> Avaliação 4,9</span>
                    <span><i class="fa-solid fa-shield-heart"></i> Equipe especializada</span>
                    <span><i class="fa-solid fa-clock"></i> Seg–Sáb 8h às 19h</span>
                </div>
            </div>
        </div>

        <div class="lp-scroll-hint" aria-hidden="true">
            <span>Role para explorar</span>
            <div class="lp-scroll-mouse">
                <div class="lp-scroll-wheel"></div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════
         SERVIÇOS — Bento Grid
    ══════════════════════════════════════════ -->
    <section class="lp-section lp-features" id="servicos" aria-labelledby="lp-features-title">
        <div class="lp-section-intro">
            <span class="lp-section-tag">NOSSOS SERVIÇOS</span>
            <h2 class="lp-section-title" id="lp-features-title">
                Tudo que seu pet<br>merece em um só lugar.
            </h2>
            <p class="lp-section-sub">Estrutura completa, profissionais dedicados e muito amor em cada atendimento.</p>
        </div>

        <div class="lp-bento-grid" id="lp-bento-grid">

            <!-- Card 1 — Banho e Tosa (grande) -->
            <article class="lp-bento-card lp-bento-lg" id="lp-bento-1" data-bento>
                <div class="lp-bento-icon lp-icon-orange">
                    <i class="fa-solid fa-shower"></i>
                </div>
                <h3>Banho e Tosa Premium</h3>
                <p>Profissionais dedicados e estrutura de ponta. Do banho completo à tosa especializada, cada pet recebe
                    atenção individualizada e muito carinho.</p>
                <div class="lp-bento-visual lp-service-visual lp-visual-bath">
                    <div class="lp-service-tags">
                        <span><i class="fa-solid fa-check"></i> Banho completo</span>
                        <span><i class="fa-solid fa-check"></i> Tosa na tesoura</span>
                        <span><i class="fa-solid fa-check"></i> Tosa higiênica</span>
                        <span><i class="fa-solid fa-check"></i> Hidratação capilar</span>
                        <span><i class="fa-solid fa-check"></i> Perfumação</span>
                        <span><i class="fa-solid fa-check"></i> Limpeza de ouvidos</span>
                    </div>
                </div>
                <a class="lp-bento-cta" href="#localizacao">
                    Agendar <i class="fa-solid fa-arrow-right-long"></i>
                </a>
                <div class="lp-bento-tag">Estética</div>
            </article>

            <!-- Card 2 — Clínica -->
            <article class="lp-bento-card lp-bento-sm" id="lp-bento-2" data-bento>
                <div class="lp-bento-icon lp-icon-teal">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
                <h3>Atendimento Clínico</h3>
                <p>Saúde em primeiro lugar. Consultas, exames de ultrassom, acompanhamento vacinal e orientação
                    preventiva com profissionais qualificados.</p>
                <div class="lp-clinic-preview">
                    <div class="lp-clinic-item">
                        <i class="fa-solid fa-syringe lp-icon-teal-sm"></i>
                        <span>Vacinas e vermífugos</span>
                    </div>
                    <div class="lp-clinic-item">
                        <i class="fa-solid fa-wave-square lp-icon-teal-sm"></i>
                        <span>Ultrassom e exames</span>
                    </div>
                    <div class="lp-clinic-item">
                        <i class="fa-solid fa-heart-pulse lp-icon-teal-sm"></i>
                        <span>Consultas clínicas</span>
                    </div>
                </div>
                <div class="lp-bento-tag">Saúde</div>
            </article>

            <!-- Card 3 — Pet Shop -->
            <article class="lp-bento-card lp-bento-sm" id="lp-bento-3" data-bento>
                <div class="lp-bento-icon lp-icon-orange">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h3>Pet Shop Completo</h3>
                <p>Os melhores produtos para o seu pet: rações premium, acessórios, brinquedos, higiene e muito mais.
                    Tudo selecionado com cuidado.</p>
                <div class="lp-store-preview">
                    <div class="lp-store-badge">🦴 Rações premium</div>
                    <div class="lp-store-badge">🪮 Acessórios</div>
                    <div class="lp-store-badge">🧴 Higiene</div>
                    <div class="lp-store-badge">🎾 Brinquedos</div>
                </div>
                <div class="lp-bento-tag">Loja</div>
            </article>

            <!-- Card 4 — Agendamento Online (wide) -->
            <article class="lp-bento-card lp-bento-wide lp-bento-accent" id="lp-bento-4" data-bento>
                <div class="lp-bento-icon lp-icon-white">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="lp-bento-wide-content">
                    <div class="lp-bento-wide-copy">
                        <h3>Agendamento Inteligente</h3>
                        <p>Nosso sistema próprio para você marcar serviços com facilidade. Acesse de qualquer lugar,
                            escolha o horário ideal e acompanhe o atendimento do seu pet em tempo real.</p>
                        <a class="lp-btn-primary lp-btn-sm-white" href="/login">
                            Agendar online <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    </div>
                    <div class="lp-bento-wide-visual">
                        <div class="lp-mini-booking">
                            <div class="lp-booking-header">
                                <i class="fa-solid fa-calendar-days"></i>
                                <strong>Novo agendamento</strong>
                            </div>
                            <div class="lp-booking-row">
                                <span>Serviço</span>
                                <b>Banho e Tosa · Mel</b>
                            </div>
                            <div class="lp-booking-row">
                                <span>Data</span>
                                <b>Qua, 24 Set · 10:00</b>
                            </div>
                            <div class="lp-booking-row">
                                <span>Status</span>
                                <em class="lp-badge-confirmed">✓ Confirmado</em>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lp-bento-tag lp-tag-light-inv">Sistema</div>
            </article>
        </div>
    </section>

    <!-- ══════════════════════════════════════════
         TOUR — Galeria de Vídeos Bastidores
    ══════════════════════════════════════════ -->
    <section class="lp-section lp-tour" id="tour" aria-labelledby="lp-tour-title">
        <div class="lp-section-intro lp-tour-intro">
            <span class="lp-section-tag">NOSSA CASA</span>
            <h2 class="lp-section-title" id="lp-tour-title">
                Conheça os bastidores<br>do cuidado
            </h2>
            <p class="lp-section-sub">
                Cada pet é tratado com muito amor e dedicação desde o primeiro dia.<br>
                Sinta-se tranquilo — seu pet está em boas mãos.
            </p>
        </div>

        <!-- Grid de vídeos -->
        <div class="lp-video-gallery" id="lp-video-gallery">

            <!-- Vídeo 1 — grande -->
            <div class="lp-vid-item lp-vid-main" id="lp-vid-1" data-video-item>
                <video class="lp-gallery-video" src="/assets/video/video_1.mp4" muted loop playsinline
                    preload="metadata" aria-label="Bastidores do pet shop - atendimento"></video>
                <div class="lp-vid-overlay">
                    <div class="lp-vid-play-btn" aria-hidden="true">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="lp-vid-caption">
                        <strong>Atendimento de excelência</strong>
                        <span>Cada detalhe pensado para o seu pet</span>
                    </div>
                </div>
            </div>

            <!-- Vídeo 2 — menor -->
            <div class="lp-vid-item lp-vid-secondary" id="lp-vid-2" data-video-item>
                <video class="lp-gallery-video" src="/assets/video/video_3.mp4" muted loop playsinline
                    preload="metadata" aria-label="Bastidores do pet shop - tosa e cuidado"></video>
                <div class="lp-vid-overlay">
                    <div class="lp-vid-play-btn" aria-hidden="true">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="lp-vid-caption">
                        <strong>Tosa e Estética</strong>
                        <span>Mãos experientes, resultado incrível</span>
                    </div>
                </div>
            </div>

            <!-- Card de copy lateral -->
            <div class="lp-vid-copy" id="lp-vid-copy">
                <div class="lp-vid-copy-inner">
                    <div class="lp-vid-stat">
                        <b>+1.000</b>
                        <small>Pets felizes atendidos</small>
                    </div>
                    <div class="lp-vid-divider"></div>
                    <div class="lp-vid-stat">
                        <b>6+</b>
                        <small>Anos de experiência</small>
                    </div>
                    <div class="lp-vid-divider"></div>
                    <div class="lp-vid-stat">
                        <b>4,7 ★</b>
                        <small>Nota no Google (89 avaliações)</small>
                    </div>
                    <div class="lp-vid-text">
                        <p>Profissionais capacitados, equipamentos modernos e um ambiente pensado para o bem-estar de
                            cada pet.</p>
                    </div>
                    <a class="lp-btn-primary" href="#localizacao">
                        Visitar o espaço <i class="fa-solid fa-arrow-right-long"></i>
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════════════════════
         CONFIANÇA — Contadores + Localização
    ══════════════════════════════════════════ -->
    <section class="lp-section lp-trust" id="confianca" aria-labelledby="lp-trust-title">
        <div class="lp-trust-bg-shapes" aria-hidden="true">
            <div class="lp-trust-shape lp-ts-1"></div>
            <div class="lp-trust-shape lp-ts-2"></div>
        </div>

        <div class="lp-trust-content">
            <div class="lp-trust-copy">
                <span class="lp-section-tag lp-tag-light">POR QUE NOS ESCOLHER</span>
                <h2 class="lp-trust-title" id="lp-trust-title">
                    Um lugar onde<br>seu pet é família
                </h2>
                <p class="lp-trust-sub">
                    Mais do que um pet shop — somos parceiros na saúde e felicidade do seu companheiro de quatro patas.
                </p>
                <div class="lp-trust-values">
                    <div class="lp-trust-value">
                        <i class="fa-solid fa-heart"></i>
                        <span>Atendimento humanizado e carinhoso</span>
                    </div>
                    <div class="lp-trust-value">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Ambiente seguro e higienizado</span>
                    </div>
                    <div class="lp-trust-value">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Profissionais treinados e certificados</span>
                    </div>
                    <div class="lp-trust-value">
                        <i class="fa-solid fa-leaf"></i>
                        <span>Produtos seguros e de qualidade</span>
                    </div>
                </div>
            </div>

            <div class="lp-counters" id="lp-counters">
                <div class="lp-counter-item" id="lp-counter-1">
                    <span class="lp-counter-num" data-target="1000">0</span><span class="lp-counter-sfx">+</span>
                    <small>Pets felizes e bem cuidados</small>
                </div>
                <div class="lp-counter-item" id="lp-counter-2">
                    <span class="lp-counter-num" data-target="6">0</span><span class="lp-counter-sfx">+</span>
                    <small>Anos de experiência</small>
                </div>
                <div class="lp-counter-item" id="lp-counter-3">
                    <span class="lp-counter-num" data-target="98">0</span><span class="lp-counter-sfx">%</span>
                    <small>Tutores satisfeitos</small>
                </div>
                <div class="lp-counter-item" id="lp-counter-4">
                    <span class="lp-counter-num lp-counter-decimal" data-target-decimal="4.7">4,7</span><span
                        class="lp-counter-sfx">★★★★★</span>
                    <small>Nota no Google (89 avaliações)</small>
                </div>
            </div>
        </div>

        <!-- Depoimento -->
        <div class="lp-trust-testimonial">
            <blockquote class="lp-quote" id="lp-quote">
                <p>"Levo minha Luna toda semana e ela ama! A equipe é super carinhosa e o espaço é impecável. Não troco
                    por nada."</p>
                <footer>
                    <strong>Mariana Costa</strong>
                    <small>Tutora da Luna · Golden Retriever</small>
                </footer>
            </blockquote>
        </div>
    </section>

    <!-- ══════════════════════════════════════════
         LOCALIZAÇÃO E HORÁRIOS
    ══════════════════════════════════════════ -->
    <section class="lp-section lp-location" id="localizacao" aria-labelledby="lp-location-title">
        <div class="lp-section-intro">
            <span class="lp-section-tag">ONDE ESTAMOS</span>
            <h2 class="lp-section-title" id="lp-location-title">
                Venha nos visitar
            </h2>
        </div>

        <div class="lp-location-grid" id="lp-location-grid">

            <div class="lp-location-info" id="lp-location-info">
                <div class="lp-info-block">
                    <div class="lp-info-icon">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <strong>Endereço</strong>
                        <p>R. José de Holanda, 459 – Torre<br>Recife – PE, CEP 50710-140</p>
                    </div>
                </div>

                <div class="lp-info-block">
                    <div class="lp-info-icon lp-info-icon-teal">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <strong>Horários de Funcionamento</strong>
                        <div class="lp-hours">
                            <div class="lp-hour-row">
                                <span>Segunda a Sexta</span>
                                <b>7h30 às 18h</b>
                            </div>
                            <div class="lp-hour-row">
                                <span>Sábado</span>
                                <b>7h30 às 17h</b>
                            </div>
                            <div class="lp-hour-row lp-closed">
                                <span>Domingo</span>
                                <b>Fechado</b>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-info-block">
                    <div class="lp-info-icon lp-info-icon-green">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div>
                        <strong>Fale conosco</strong>
                        <p style="font-size:13px;color:rgba(24,34,31,.6);margin:0 0 8px">(81) 99213-9997</p>
                        <a class="lp-whatsapp-btn" href="https://wa.me/5581992139997" target="_blank"
                            rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp"></i>
                            Chamar no WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <div class="lp-map-placeholder" id="lp-map-placeholder" style="padding:0; overflow:hidden;">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d246.9095384541112!2d-34.90850347964714!3d-8.044972976381853!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x7ab19024fefbc95%3A0x9daba5286a99fe66!2sBeleza%20C%C3%A3o%20e%20Gato%20-%20Torre!5e0!3m2!1spt-BR!2sbr!4v1790048493835!5m2!1spt-BR!2sbr"
                    width="100%" height="100%" style="border:0; min-height:360px;" allowfullscreen="" loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════════════════════
         CTA FINAL + FOOTER
    ══════════════════════════════════════════ -->
    <section class="lp-cta-final" id="lp-cta-final" aria-labelledby="lp-cta-title">
        <div class="lp-cta-orb" aria-hidden="true"></div>
        <div class="lp-cta-content">
            <div class="lp-cta-paws" aria-hidden="true">🐾</div>
            <span class="lp-section-tag">PRONTO PARA COMEÇAR?</span>
            <h2 class="lp-cta-title" id="lp-cta-title">
                Venha conhecer<br>nossa nova casa!
            </h2>
            <p class="lp-cta-sub">
                Agende online ou venha pessoalmente. Seu pet vai adorar!
            </p>
            <div class="lp-cta-actions">
                <a class="lp-btn-primary lp-btn-xl" href="/login" id="lp-final-cta">
                    <span>Agendar pelo Sistema</span>
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                </a>
                <a class="lp-whatsapp-btn lp-whatsapp-btn-lg" href="https://wa.me/5581992139997" target="_blank"
                    rel="noopener noreferrer" id="lp-final-whatsapp">
                    <i class="fa-brands fa-whatsapp"></i>
                    WhatsApp
                </a>
            </div>
        </div>
    </section>

    <footer class="lp-footer" role="contentinfo">
        <div class="lp-footer-inner">
            <a class="lp-brand" href="/" aria-label="Beleza Cão e Gato">
                <span class="lp-brand-mark" aria-hidden="true">
                    <i class="fa-solid fa-paw"></i>
                </span>
                <span class="lp-brand-text">
                    <strong>Beleza Cão&amp;Gato</strong>
                    <small>PET SHOP &amp; CLÍNICA</small>
                </span>
            </a>

            <nav class="lp-footer-nav" aria-label="Links do rodapé">
                <a href="#servicos">Serviços</a>
                <a href="#tour">Nossa Casa</a>
                <a href="#confianca">Sobre nós</a>
                <a href="#localizacao">Localização</a>
                <a href="/login">Área do Cliente</a>
            </nav>

            <p class="lp-footer-copy">
                &copy; <?= date('Y') ?> Beleza Cão&amp;Gato · Feito com amor para os pets.
            </p>
        </div>
    </footer>

    <!-- CDN LIBS — apenas na landing page -->
    <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/bundled/lenis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="/assets/js/landing.js?v=<?= time() ?>"></script>

</main>