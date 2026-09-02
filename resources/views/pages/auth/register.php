<main class="register-page">
    <section class="register-brand-panel" aria-label="Apresentação do sistema">
        <div class="auth-brand">
            <div class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-dog"></i>
                <span class="brand-mark-sep"></span>
                <i class="fa-solid fa-cat"></i>
            </div>
            <span class="brand-text">
                <strong>Beleza <span class="brand-name-accent">Cão&amp;Gato</span></strong>
                <small>SISTEMA DE GESTÃO</small>
            </span>
        </div>

        <div class="register-brand-content">
            <span class="register-eyebrow">✨ JUNTE-SE A QUEM JÁ CRESCEU</span>

            <div class="register-brand-copy">
                <h1>Seu pet shop organizado do jeito certo.</h1>
                <p>Crie sua conta grátis e comece a usar hoje. Sem cartão de crédito, sem burocracia — só resultado.</p>
            </div>

            <div class="register-stats" aria-label="Diferenciais do sistema">
                <span><small>ESTABELECIMENTOS ATIVOS</small><strong>+500 pet shops</strong></span>
                <span><small>SUPORTE</small><strong>Seg. a Sáb. • 8h–18h</strong></span>
            </div>
        </div>
    </section>

    <section class="register-form-panel">
        <form class="register-card" method="post" action="/cadastro" autocomplete="on" novalidate>
            <header class="register-card-header">
                <h2>Criar sua Conta</h2>
                <p>Comece seu teste grátis de 14 dias hoje mesmo.</p>
            </header>

            <div class="register-fields">
                <div class="register-field register-field-full">
                    <label for="reg-name">Nome completo</label>
                    <div class="register-input">
                        <img src="/assets/icons/user.svg" alt="" aria-hidden="true">
                        <input id="reg-name" type="text" name="name" autocomplete="name" placeholder="Nome Completo" required>
                    </div>
                </div>

                <div class="register-row">
                    <div class="register-field">
                        <label for="reg-email">E-mail profissional</label>
                        <div class="register-input">
                            <img src="/assets/icons/mail.svg" alt="" aria-hidden="true">
                            <input id="reg-email" type="email" name="email" autocomplete="email" placeholder="Informe seu e-mail" required>
                        </div>
                    </div>

                    <div class="register-field">
                        <label for="reg-phone">Telefone / WhatsApp</label>
                        <div class="register-input">
                            <img src="/assets/icons/phone.svg" alt="" aria-hidden="true">
                            <input id="reg-phone" type="tel" name="phone" autocomplete="tel" placeholder="(xx) xxxxx-xxxx">
                        </div>
                    </div>
                </div>

                <div class="register-row">
                    <div class="register-field">
                        <label for="reg-cpf">CPF</label>
                        <div class="register-input">
                            <img src="/assets/icons/file-badge.svg" alt="" aria-hidden="true">
                            <input id="reg-cpf" type="text" name="cpf" inputmode="numeric" placeholder="xxx.xxx.xxx-xx" required>
                        </div>
                    </div>

                    <div class="register-field">
                        <label for="reg-birth">Data de nascimento</label>
                        <div class="register-input">
                            <img src="/assets/icons/calendar.svg" alt="" aria-hidden="true">
                            <input id="reg-birth" type="text" name="birth_date" inputmode="numeric" placeholder="dd/mm/aaaa">
                        </div>
                    </div>
                </div>

                <div class="register-row">
                    <div class="register-field">
                        <label for="reg-password">Senha</label>
                        <div class="register-input register-password">
                            <img src="/assets/icons/lock.svg" alt="" aria-hidden="true">
                            <input id="reg-password" type="password" name="password" autocomplete="new-password" placeholder="••••••••••••" required>
                            <button type="button" data-password-toggle aria-label="Mostrar senha" aria-controls="reg-password">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="register-field">
                        <label for="reg-password-confirm">Confirmar senha</label>
                        <div class="register-input register-password">
                            <img src="/assets/icons/lock.svg" alt="" aria-hidden="true">
                            <input id="reg-password-confirm" type="password" name="password_confirmation" autocomplete="new-password" placeholder="••••••••••••" required>
                            <button type="button" data-password-toggle aria-label="Mostrar confirmação de senha" aria-controls="reg-password-confirm">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="register-strength" aria-live="polite" aria-label="Indicador de força da senha">
                    <div>
                        <span>Força da senha:</span>
                        <strong>Forte ✨</strong>
                    </div>
                    <span class="register-strength-track" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <small>Dica: Use 8+ caracteres, uma letra maiúscula, um número e um caractere especial.</small>
                </div>

                <label class="register-terms">
                    <input type="checkbox" name="terms" required>
                    <span>Aceito os <a href="#">termos de uso</a> e <a href="#">política de privacidade</a></span>
                </label>
            </div>

            <div class="register-actions">
                <button class="button primary wide" type="submit">Criar minha conta grátis</button>
                <p>Já possui uma conta? <a href="/login">Fazer login</a></p>
            </div>
        </form>
    </section>
</main>
