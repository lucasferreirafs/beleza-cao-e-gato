<main class="register-page">
    <section class="register-brand-panel" aria-label="Apresentação do sistema">
        <div class="auth-brand register-brand">
            <span class="brand-mark" aria-hidden="true"><i class="fa-solid fa-paw"></i></span>
            <span><strong>Beleza Cão&amp;Gato</strong><small>SISTEMA DE GESTÃO</small></span>
        </div>

        <div class="register-brand-content">
            <span class="register-eyebrow">✨ GESTÃO INTELIGENTE E MODERNA</span>
            <div class="register-brand-copy">
                <h1>Cadastre-se e cuide do seu pet com carinho.</h1>
                <p>Junte-se a centenas de estéticas pet e clínicas que otimizaram sua agenda e reduziram faltas de clientes.</p>
            </div>
            <div class="register-stats" aria-label="Resultados do estabelecimento">
                <span><small>ATENDIMENTOS HOJE</small><strong>48 Pets</strong></span>
                <span><small>CLIENTES SATISFEITOS</small><strong>97%</strong></span>
            </div>
        </div>
    </section>

    <section class="register-form-panel">
        <form class="register-card" method="post" action="/cadastro" autocomplete="on">
            <?= \App\Security\Csrf::field() ?>
            <header class="register-card-header">
                <h2>Criar sua Conta</h2>
                <p>Crie sua conta e agende os cuidados do seu pet com facilidade.</p>
            </header>

            <?php if (!empty($error)): ?>
                <div class="alert error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="register-fields">
                <label class="register-field register-field-full">
                    <span>Nome completo</span>
                    <span class="register-input">
                        <img src="/assets/icons/user.svg" alt="">
                        <input type="text" name="name" autocomplete="name" placeholder="Nome sobrenome" minlength="2" maxlength="120" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </span>
                </label>

                <div class="register-row">
                    <label class="register-field">
                        <span>E-mail profissional</span>
                        <span class="register-input">
                            <img src="/assets/icons/mail.svg" alt="">
                            <input type="email" name="email" autocomplete="email" placeholder="example@email.com" maxlength="190" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </span>
                    </label>
                    <label class="register-field">
                        <span>Telefone / WhatsApp</span>
                        <span class="register-input">
                            <img src="/assets/icons/phone.svg" alt="">
                            <input type="tel" name="phone" autocomplete="tel" placeholder="(xx) xxxxx-xxxx" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </span>
                    </label>
                </div>

                <div class="register-row">
                    <label class="register-field">
                        <span>CPF</span>
                        <span class="register-input">
                            <img src="/assets/icons/file-badge.svg" alt="">
                            <input type="text" name="cpf" inputmode="numeric" placeholder="xxx.xxx.xxx-xx" maxlength="14" value="<?= htmlspecialchars($old['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </span>
                    </label>
                    <label class="register-field">
                        <span>Data de nascimento</span>
                        <span class="register-input">
                            <img src="/assets/icons/calendar.svg" alt="">
                            <input type="text" name="birth_date" inputmode="numeric" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars($old['birth_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </span>
                    </label>
                </div>

                <div class="register-row">
                    <label class="register-field">
                        <span>Senha</span>
                        <span class="register-input register-password">
                            <img src="/assets/icons/lock.svg" alt="">
                            <input type="password" name="password" autocomplete="new-password" placeholder="••••••••••••" minlength="8">
                            <button type="button" data-password-toggle aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </span>
                    </label>
                    <label class="register-field">
                        <span>Confirmar senha</span>
                        <span class="register-input register-password">
                            <img src="/assets/icons/lock.svg" alt="">
                            <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="••••••••••••" minlength="8">
                            <button type="button" data-password-toggle aria-label="Mostrar senha">
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

                <label class="register-terms">
                    <input type="checkbox" name="terms" value="1" required>
                    <span>Aceito os <a href="#">termos de uso</a> e <a href="#">política de privacidade</a></span>
                </label>
            </div>

            <div class="register-actions">
                <button class="button primary wide" type="submit">Criar minha conta</button>
                <p>Já possui uma conta? <a href="/login">Fazer login</a></p>
            </div>
        </form>
    </section>
</main>
