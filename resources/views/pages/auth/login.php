<main class="auth-split login-page" id="auth-main">

    <!-- ── Coluna esquerda: Painel de Informação ─────────────────────────── -->
    <section class="auth-info-panel" aria-label="Informações sobre o pet shop" id="auth-info-panel">

        <div class="auth-info-overlay" aria-hidden="true"></div>

        <div class="auth-info-content">

            <a href="/" class="auth-brand" id="auth-brand">
                <span class="auth-brand-mark lp-brand-mark" aria-hidden="true">
                    <i class="fa-solid fa-paw"></i>
                </span>
                <span class="auth-brand-text lp-brand-text">
                    <strong>Beleza Cão&amp;Gato</strong>
                    <small>PET SHOP &amp; CLÍNICA</small>
                </span>
            </a>

            <div class="auth-info-copy" id="auth-info-copy">
                <span class="auth-eyebrow" aria-label="Pet Shop e Clínica">
                    🐾 PET SHOP &amp; CLÍNICA
                </span>

                <h1>Cuidado, carinho e saúde para o seu pet.</h1>

                <p>
                    Agende banhos, tosas, consultas e muito mais — com facilidade e sem complicação.
                    Um time apaixonado por pets, pronto para cuidar do seu melhor amigo.
                </p>

                <div class="auth-info-highlights" id="auth-info-highlights">
                    <div class="auth-highlight-item">
                        <span class="auth-highlight-icon" aria-hidden="true">
                            <i class="fa-solid fa-scissors"></i>
                        </span>
                        <span class="auth-highlight-text">
                            <strong>Banho &amp; Tosa</strong>
                            <small>Serviços completos de estética pet</small>
                        </span>
                    </div>
                    <div class="auth-highlight-item">
                        <span class="auth-highlight-icon" aria-hidden="true">
                            <i class="fa-solid fa-stethoscope"></i>
                        </span>
                        <span class="auth-highlight-text">
                            <strong>Consultas &amp; Cuidados</strong>
                            <small>Acompanhamento da saúde do seu pet</small>
                        </span>
                    </div>
                    <div class="auth-highlight-item">
                        <span class="auth-highlight-icon" aria-hidden="true">
                            <i class="fa-solid fa-calendar-check"></i>
                        </span>
                        <span class="auth-highlight-text">
                            <strong>Agendamento fácil</strong>
                            <small>Reserve em segundos, a qualquer hora</small>
                        </span>
                    </div>
                    <div class="auth-highlight-item">
                        <span class="auth-highlight-icon" aria-hidden="true">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <span class="auth-highlight-text">
                            <strong>Seg a Sáb, 8h–18h</strong>
                            <small>Horários que cabem na sua rotina</small>
                        </span>
                    </div>
                </div>

                <div class="auth-rating-badge" id="auth-rating-badge" role="img" aria-label="Avaliação 4,7 estrelas">
                    <span class="auth-rating-stars" aria-hidden="true">★★★★★</span>
                    <span class="auth-rating-text">
                        <strong>4,7</strong> de avaliação média
                    </span>
                    <span class="auth-rating-count">+320 avaliações</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ── Coluna direita: Formulário (Glassmorphism) ──────────────────────── -->
    <section class="auth-form-panel" id="auth-form-panel">

        <!-- Branding mobile (visível apenas em telas pequenas) -->
        <div class="auth-mobile-brand" aria-hidden="true">
            <span class="auth-brand-mark lp-brand-mark">
                <i class="fa-solid fa-paw"></i>
            </span>
            <span class="auth-brand-text lp-brand-text">
                <strong>Beleza Cão&amp;Gato</strong>
                <small>PET SHOP &amp; CLÍNICA</small>
            </span>
        </div>

        <form class="auth-card" method="post" action="/login" novalidate id="login-form">
            <?= \App\Security\Csrf::field() ?>

            <div class="auth-card-header" id="auth-card-header">
                <h2>Bem-vindo de volta</h2>
                <p>Insira suas credenciais para gerenciar sua unidade.</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert success" role="status">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="auth-fields" id="auth-fields">

                <label class="auth-field-label" for="login-email">
                    <span>Endereço de e-mail</span>
                    <span class="auth-input-wrap">
                        <i class="fa-solid fa-envelope auth-input-icon" aria-hidden="true"></i>
                        <input
                            id="login-email"
                            type="email"
                            name="email"
                            autocomplete="username"
                            placeholder="contato@petspa.com"
                            required
                        >
                    </span>
                </label>

                <label class="auth-field-label" for="login-password">
                    <span>Senha de acesso</span>
                    <span class="auth-input-wrap auth-password-wrap">
                        <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
                        <input
                            id="login-password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="••••••••••••"
                            required
                        >
                        <button type="button" data-password-toggle class="auth-toggle-btn" aria-label="Mostrar senha">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </span>
                </label>

                <div class="auth-form-inline">
                    <label class="auth-check-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Lembrar de mim</span>
                    </label>
                    <a href="/recuperar-senha" class="auth-forgot-link">Esqueci minha senha</a>
                </div>

            </div>

            <div class="auth-actions" id="auth-actions">
                <button class="button primary wide auth-submit-btn" type="submit" id="login-submit">
                    <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i>
                    Acessar Conta
                </button>
                <p class="auth-foot">
                    Não tem uma conta? <a href="/cadastro">Criar conta</a>
                </p>
            </div>

        </form>
    </section>

</main>
