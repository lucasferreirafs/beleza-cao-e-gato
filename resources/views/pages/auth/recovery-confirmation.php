<main class="center-auth">
    <section class="auth-card recovery-confirmation" aria-labelledby="recovery-confirmation-title">
        <div class="auth-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></div>

        <div class="mini-brand">
            <span class="brand-mark" aria-hidden="true"><i class="fa-solid fa-paw"></i></span>
            <span><strong>Beleza Cão&amp;Gato</strong><small>SISTEMA DE GESTÃO</small></span>
        </div>

        <h2 id="recovery-confirmation-title">Confira seu e-mail</h2>
        <p>Se houver uma conta associada a este endereço, enviaremos as instruções para:</p>
        <p class="recovery-confirmation-email"><strong><?= htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></p>
        <p class="recovery-confirmation-help">O link de redefinição expira em 30 minutos e só pode ser usado uma vez. Verifique também sua pasta de spam.</p>

        <?php if (!empty($notice)): ?>
            <p class="recovery-confirmation-notice" role="status"><?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($devUrl) && \App\Core\Env::get('APP_ENV') === 'development' && \App\Core\Env::bool('APP_DEBUG')): ?>
            <p class="dev-hint">Ambiente de desenvolvimento: <a href="<?= htmlspecialchars($devUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">abrir link de redefinição</a>.</p>
        <?php endif; ?>

        <form method="post" action="/recuperar-senha/reenviar" class="recovery-confirmation-actions">
            <?= \App\Security\Csrf::field() ?>
            <button class="button primary wide" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Reenviar e-mail</button>
        </form>
        <a class="back-link" href="/login"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> Voltar para o login</a>
    </section>
</main>
