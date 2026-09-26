<main class="auth-split register-page" id="auth-main">

    <!-- ── Coluna esquerda: Painel de Informação ─────────────────────────── -->
    <section class="auth-info-panel" aria-label="Apresentação do pet shop" id="auth-info-panel">

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

                <p>Agende banhos, tosas, consultas e muito mais — tudo num só lugar, com facilidade e sem complicação.</p>

                <div class="auth-info-highlights" id="auth-info-highlights">
                    <div class="auth-highlight-item">
                        <span class="auth-highlight-icon" aria-hidden="true">
                            <i class="fa-solid fa-scissors"></i>
                        </span>
                        <span class="auth-highlight-text">
                            <strong>Banho &amp; Tosa</strong>
                            <small>Estética completa com carinho e cuidado</small>
                        </span>
                    </div>
                    <div class="auth-highlight-item">
                        <span class="auth-highlight-icon" aria-hidden="true">
                            <i class="fa-solid fa-stethoscope"></i>
                        </span>
                        <span class="auth-highlight-text">
                            <strong>Consultas &amp; Vacinas</strong>
                            <small>Saúde em dia para o seu pet</small>
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
    <section class="auth-form-panel auth-form-panel--register" id="auth-form-panel">

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

        <form class="auth-card auth-card--register" method="post" action="/cadastro" autocomplete="on" id="register-form">
            <?= \App\Security\Csrf::field() ?>

            <div class="auth-card-header" id="auth-card-header">
                <h2>Criar sua Conta</h2>
                <p>Crie sua conta e agende os cuidados do seu pet com facilidade.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="register-fields" id="auth-fields">

                <label class="auth-field-label register-field-full" for="reg-name">
                    <span>Nome completo</span>
                    <span class="auth-input-wrap">
                        <i class="fa-solid fa-user auth-input-icon" aria-hidden="true"></i>
                        <input
                            id="reg-name"
                            type="text"
                            name="name"
                            autocomplete="name"
                            placeholder="Nome sobrenome"
                            minlength="2"
                            maxlength="120"
                            value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </span>
                </label>

                <div class="register-row">
                    <label class="auth-field-label register-field" for="reg-email">
                        <span>E-mail</span>
                        <span class="auth-input-wrap">
                            <i class="fa-solid fa-envelope auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="reg-email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                placeholder="example@email.com"
                                maxlength="190"
                                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </span>
                    </label>
                    <label class="auth-field-label register-field" for="reg-phone">
                        <span>Telefone / WhatsApp</span>
                        <span class="auth-input-wrap">
                            <i class="fa-solid fa-phone auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="reg-phone"
                                type="tel"
                                name="phone"
                                autocomplete="tel"
                                placeholder="(xx) xxxxx-xxxx"
                                value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </span>
                    </label>
                </div>

                <div class="register-row">
                    <label class="auth-field-label register-field" for="reg-cpf">
                        <span>CPF</span>
                        <span class="auth-input-wrap">
                            <i class="fa-solid fa-id-card auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="reg-cpf"
                                type="text"
                                name="cpf"
                                inputmode="numeric"
                                placeholder="xxx.xxx.xxx-xx"
                                maxlength="14"
                                value="<?= htmlspecialchars($old['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </span>
                    </label>
                    <label class="auth-field-label register-field" for="reg-birth">
                        <span>Data de nascimento</span>
                        <span class="auth-input-wrap">
                            <i class="fa-solid fa-calendar auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="reg-birth"
                                type="text"
                                name="birth_date"
                                inputmode="numeric"
                                placeholder="dd/mm/aaaa"
                                value="<?= htmlspecialchars($old['birth_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </span>
                    </label>
                </div>

                <div class="register-row">
                    <label class="auth-field-label register-field" for="reg-password">
                        <span>Senha</span>
                        <span class="auth-input-wrap auth-password-wrap">
                            <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="reg-password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="••••••••••••"
                                minlength="8"
                            >
                            <button type="button" data-password-toggle class="auth-toggle-btn" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </span>
                    </label>
                    <label class="auth-field-label register-field" for="reg-password-confirm">
                        <span>Confirmar senha</span>
                        <span class="auth-input-wrap auth-password-wrap">
                            <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="reg-password-confirm"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="••••••••••••"
                                minlength="8"
                            >
                            <button type="button" data-password-toggle class="auth-toggle-btn" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </span>
                    </label>
                </div>

                <div class="register-strength" data-password-strength hidden>
                    <div><span>Força da senha:</span><strong data-strength-label></strong></div>
                    <span class="register-strength-track"><i></i><i></i><i></i><i></i></span>
                    <small>Dica: Use 8+ caracteres, uma letra maiúscula, um número e um caractere especial.</small>
                </div>

                <label class="register-terms auth-terms-label">
                    <input type="checkbox" name="terms" value="1" required>
                    <span>Aceito os <a href="/termos">termos de uso</a> e li as informações de <a href="/termos#dados-pessoais">privacidade</a></span>
                </label>
                <input type="hidden" name="terms_version" value="<?= \App\Services\TermsService::VERSION ?>">

            </div>

            <div class="auth-actions register-actions" id="auth-actions">
                <button class="button primary wide auth-submit-btn" type="submit" id="register-submit">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Criar minha conta
                </button>
                <p class="auth-foot">Já possui uma conta? <a href="/login">Fazer login</a></p>
            </div>

        </form>
    </section>

</main>
