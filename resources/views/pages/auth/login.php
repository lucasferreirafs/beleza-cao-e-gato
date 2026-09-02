<main class="login-page">
    <section class="brand-panel" aria-label="Apresentação do sistema">
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

        <div class="brand-copy">
            <span class="brand-badge">🔒 ACESSO SEGURO E CONFIÁVEL</span>

            <h1>Bem-vindo de volta ao seu sistema.</h1>

            <p>
                Tudo o que você precisa para gerenciar seu pet shop está aqui.
                Acesse e retome de onde parou.
            </p>

            <div class="brand-metrics" aria-label="Diferenciais do sistema">
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
        <form method="post" action="/login" class="auth-card" novalidate>
            <header class="login-card-header">
                <h2>Bem-vindo de volta</h2>
                <p>Insira suas credenciais para gerenciar sua unidade.</p>
            </header>

            <div class="auth-fields">
                <div class="auth-field">
                    <label for="login-email">Endereço de e-mail</label>
                    <div class="auth-input">
                        <input
                            id="login-email"
                            type="email"
                            name="email"
                            placeholder="contato@gmail.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="auth-field">
                    <label for="login-password">Senha de acesso</label>
                    <div class="auth-input auth-password">
                        <input
                            id="login-password"
                            type="password"
                            name="password"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" data-password-toggle aria-label="Mostrar senha" aria-controls="login-password">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-inline">
                <label class="check">
                    <input type="checkbox" name="remember">
                    Lembrar de mim
                </label>

                <a href="/recuperar-senha">Esqueci minha senha</a>
            </div>

            <button class="button primary wide" type="submit">Entrar no Sistema</button>

            <p class="auth-foot">Não tem conta? <a href="/cadastro">Cadastre-se</a></p>
        </form>
    </section>
</main>