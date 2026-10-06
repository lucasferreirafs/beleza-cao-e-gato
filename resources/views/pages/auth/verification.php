<main class="center-auth">
    <section class="auth-card">
        <div class="auth-icon">✉</div>

        <div class="mini-brand">
            <span class="brand-mark" aria-hidden="true">
                <i class="fa-solid fa-paw"></i>
            </span>
            Beleza Cão&amp;Gato
        </div>

        <h2>Verifique seu e-mail</h2>
        <p>Informe abaixo o código de verificação de 6 dígitos enviado ao seu e-mail. Se ainda não o recebeu, solicite um novo código.</p>

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

        <?php if (!empty($devCode)): ?>
            <p class="dev-hint">
                Código de desenvolvimento:
                <strong><?= htmlspecialchars($devCode, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        <?php endif; ?>

        <form class="form-verification" method="post" action="/verificar-email">
            <?= \App\Security\Csrf::field() ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>">

            <div class="otp">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input name="code[]" inputmode="numeric" pattern="[0-9]" maxlength="1" required aria-label="Dígito <?= $i + 1 ?>">
                <?php endfor; ?>
            </div>

            <button class="button primary wide">Confirmar código</button>
        </form>

        <form method="post" action="/verificar-email/reenviar" class="inline-form">
            <?= \App\Security\Csrf::field() ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>">
            <p class="auth-foot">
                Não recebeu?
                <button class="link-button" type="submit">Reenviar código</button>
            </p>
        </form>

        <a class="back-link" href="/cadastro">← Alterar endereço de e-mail</a>
    </section>
</main>
