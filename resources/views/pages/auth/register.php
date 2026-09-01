<main class="register-page">
    <section class="register-brand-panel" aria-label="Apresentação do sistema">
        <div class="auth-brand register-brand">
            <b class="brand-mark">
                <i class="fa-solid fa-paw"></i>
            </b>

            <span><strong>Beleza Cão&amp;Gato</strong><small>SISTEMA DE GESTÃO</small></span>
        </div>

        <div class="register-brand-content">
            <span class="register-eyebrow">✨ GESTÃO INTELIGENTE E MODERNA</span>
            <div class="register-brand-copy">
                <h1>Cadastre seu estabelecimento e cresça.</h1>
                <p>Junte-se a centenas de estéticas pet e clínicas que otimizaram sua agenda, reduziram faltas de clientes e multiplicaram suas vendas.</p>
            </div>
            <div class="register-stats" aria-label="Resultados do estabelecimento">
                <span><small>ATENDIMENTOS HOJE</small><strong>48 Pets</strong></span>
                <span><small>FATURAMENTO MENSAL</small><strong>R$ 14.820</strong></span>
            </div>
        </div>
    </section>

    <section class="register-form-panel">
        <form class="register-card" autocomplete="on">
            <header class="register-card-header">
                <h2>Criar sua Conta</h2>
                <p>Comece seu teste grátis de 14 dias hoje mesmo.</p>
            </header>

            <div class="register-fields">
                <label class="register-field register-field-full">
                    <span>Nome completo</span>
                    <span class="register-input">
                        <img src="/assets/icons/user.svg" alt="">
                        <input type="text" name="name" autocomplete="name" placeholder="Nome Completo">
                    </span>
                </label>

                <div class="register-row">
                    <label class="register-field">
                        <span>E-mail profissional</span>
                        <span class="register-input">
                            <img src="/assets/icons/mail.svg" alt="">
                            <input type="email" name="email" autocomplete="email" placeholder="Informe seu e-mail">
                        </span>
                    </label>
                    <label class="register-field">
                        <span>Telefone / WhatsApp</span>
                        <span class="register-input">
                            <img src="/assets/icons/phone.svg" alt="">
                            <input type="tel" name="phone" autocomplete="tel" placeholder="(xx) xxxxx-xxxx">
                        </span>
                    </label>
                </div>

                <div class="register-row">
                    <label class="register-field">
                        <span>CPF</span>
                        <span class="register-input">
                            <img src="/assets/icons/file-badge.svg" alt="">
                            <input type="text" name="cpf" inputmode="numeric" placeholder="xxx.xxx.xxx-xx">
                        </span>
                    </label>
                    <label class="register-field">
                        <span>Data de nascimento</span>
                        <span class="register-input">
                            <img src="/assets/icons/calendar.svg" alt="">
                            <input type="text" name="birth_date" inputmode="numeric" placeholder="dd/mm/aaaa">
                        </span>
                    </label>
                </div>

                <div class="register-row">
                    <label class="register-field">
                        <span>Senha</span>
                        <span class="register-input register-password">
                            <img src="/assets/icons/lock.svg" alt="">
                            <input type="password" name="password" autocomplete="new-password" placeholder="••••••••••••">
                            <button type="button" data-password-toggle aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </span>
                    </label>
                    <label class="register-field">
                        <span>Confirmar senha</span>
                        <span class="register-input register-password">
                            <img src="/assets/icons/lock.svg" alt="">
                            <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="••••••••••••">
                            <button type="button" data-password-toggle aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </span>
                    </label>
                </div>

                <div class="register-strength" aria-label="Força da senha: forte">
                    <div><span>Força da senha:</span><strong>Forte ✨</strong></div>
                    <span class="register-strength-track"><i></i><i></i><i></i><i></i></span>
                    <small>Dica: Use 8+ caracteres, uma letra maiúscula, um número e um caractere especial.</small>
                </div>

                <label class="register-terms">
                    <input type="checkbox" name="terms" required checked>
                    <span>Aceito os <a href="#">termos de uso</a> e <a href="#">política de privacidade</a></span>
                </label>
            </div>

            <div class="register-actions">
                <button class="button primary wide" type="button">Criar minha conta grátis</button>
                <p>Já possui uma conta? <a href="/login">Fazer login</a></p>
            </div>
        </form>
    </section>
</main>
