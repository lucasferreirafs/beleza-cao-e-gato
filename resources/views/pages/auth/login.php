<main class="login-page">
    <section class="brand-panel">
        <div class="auth-brand">
            <span class="brand-mark">
                <i class="fa-solid fa-paw"></i>
            </span>
            <span>
                <strong>Beleza Cão&amp;Gato</strong>
                <small>SISTEMA DE GESTÃO</small>
            </span>
        </div>

        <div class="brand-copy">
            <span class="eyebrow">✨ GESTÃO INTELIGENTE E MODERNA</span>

            <h1>Controle total do seu pet shop em um só lugar.</h1>

            <p>
                Gerencie agendamentos, histórico de vacinas dos pets, controle de estoque de cosméticos e faturamento de forma profissional e amigável.
            </p>

            <div class="brand-metrics">
                <span>
                    <small>DISPONIBILIDADE</small>
                    <b>24h por dia</b>
                </span>
                <span>
                    <small>SEGURANÇA</small>
                    <b>Seus dados protegidos</b>
                </span>
            </div>
        </div>
    </section>

    <section class="login-form-panel">
        <form class="auth-card" method="post" action="/login" novalidate>
            <?= \App\Security\Csrf::field() ?>

            <h2>Bem-vindo de volta</h2>
            <p>Insira suas credenciais para gerenciar sua unidade.</p>

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

            <label>
                Endereço de e-mail
                <input type="email" name="email" autocomplete="username" placeholder="contato@petspa.com" required>
            </label>

            <label>
                Senha de acesso
                <span class="password-field">
                    <input type="password" name="password" autocomplete="current-password" placeholder="••••••••••••" required>
                    <button type="button" data-password-toggle aria-label="Mostrar senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </span>
            </label>

            <div class="form-inline">
                <label class="check">
                    <input type="checkbox" name="remember">
                    Lembrar de mim
                </label>
                <a href="/recuperar-senha">Esqueci minha senha</a>
            </div>

            <button class="button primary wide" type="submit">Entrar no Sistema</button>

            <p class="auth-foot">
                Não tem uma conta? <a href="/cadastro">Criar conta</a>
            </p>
        </form>
    </section>
</main>