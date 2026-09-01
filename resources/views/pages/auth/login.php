<main class="login-page">
    <section class="brand-panel">
        <div class="auth-brand">
            <b class="brand-mark">
                <i class="fa-solid fa-paw"></i>
            </b>

            <span>
                <strong>Beleza Cão&amp;Gato</strong>
                <small>SISTEMA DE GESTÃO</small>
            </span>
        </div>

        <div class="brand-copy">
            <span class="brand-badge">✨ GESTÃO INTELIGENTE E MODERNA</span>

            <h1>
                Controle total do seu pet shop em um só lugar.
            </h1>

            <p>
                Gerencie agendamentos, histórico de vacinas dos pets, controle de estoque
                de cosméticos e faturamento de forma profissional e amigável.
            </p>

            <div class="brand-metrics">
                <span>
                    <small>Atendimentos Hoje</small>
                    <b>48 Pets</b>
                </span>

                <span>
                    <small>Faturamento Mensal</small>
                    <b>R$ 14.820</b>
                </span>
            </div>
        </div>
    </section>

    <section class="login-form-panel">
        <form method="post" action="/login" class="auth-card">
            <h2>Bem-vindo de volta</h2>
            <p>Insira suas credencias para gerenciar sua unidade.</p>

            <label>
                Endereço de e-mail
                <input type="email" name="email" placeholder="contato@gmail.com" required>
            </label>

            <label>
                Senha de acesso
                <span class="password-field">
                    <input type="password" name="password" placeholder="••••••••••••" autocomplete="current-password"
                        required>
                    <button type="button" data-password-toggle aria-label="Mostra senha">
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

            <button class="button primary wide">Entrar no Sistema</button>

            <p class="auth-foot">Não tem conta? <a href="#">Cadastre-se</a></p>
        </form>
    </section>
</main>