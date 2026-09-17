<main class="center-auth">
    <form class="auth-card" method="post" action="/redefinir-senha">
        <?= \App\Security\Csrf::field() ?>
        <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <div class="auth-icon">♙</div>

        <div class="mini-brand">
            <span class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-paw"></i>
            </span>
            Beleza Cão&amp;Gato
        </div>

        <h2>Crie uma nova senha</h2>
        <p>Use uma senha forte que você ainda não utiliza em outros serviços.</p>

        <?php if ($error): ?>
            <div class="alert danger">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <label>
            Nova senha
            <input type="password" name="password" autocomplete="new-password" required>
        </label>

        <label>
            Confirmar senha
            <input type="password" name="password_confirmation" autocomplete="new-password" required>
        </label>

        <button class="button primary wide">Salvar nova senha</button>

        <a class="back-link" href="/login">← Voltar para o login</a>
    </form>
</main>
