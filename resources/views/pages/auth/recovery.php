<main class="center-auth">
    <form class="auth-card" method="post" action="/recuperar-senha">
        <?= \App\Security\Csrf::field() ?>

        <div class="auth-icon">
            <img src="/assets/icons/lock-recovery.svg">
        </div>

        <div class="mini-brand">
            <span class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-paw"></i>
            </span>
            <span>
                <strong>Beleza Cão&amp;Gato</strong>
                <small>SISTEMA DE GESTÃO</small>
            </span>
        </div>

        <h2 class="text-center">Esqueceu sua senha?</h2>
        <p class="text-center">Insira o e-mail cadastrado e enviaremos um link com instruções para redefinição.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <label>
            Endereço de e-mail cadastrado
            <input type="email" name="email" autocomplete="email" placeholder="exemplo@beleza.com" required>
        </label>

        <button class="button primary wide" type="submit">Enviar instruções</button>

        <a class="back-link" href="/login">
            <i class="fa-solid fa-arrow-left-long"></i> 
            Voltar para o login
        </a>
    </form>
</main>
