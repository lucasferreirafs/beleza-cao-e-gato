<main class="center-auth">
    <?php if (!$validToken): ?>
        <section class="auth-card" aria-labelledby="reset-title">
            <div class="auth-icon"><i class="fa-solid fa-link-slash" aria-hidden="true"></i></div>
            <h2 id="reset-title">Link indisponível</h2>
            <p>Este link é inválido, expirou ou já foi utilizado. Solicite uma nova redefinição de senha.</p>
            <?php if ($error): ?><div class="alert error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
            <a class="button primary wide" href="/recuperar-senha">Solicitar novo link</a>
            <a class="back-link" href="/login">Voltar para o login</a>
        </section>
    <?php else: ?>
        <form class="auth-card" method="post" action="/redefinir-senha">
            <?= \App\Security\Csrf::field() ?>
            <input type="hidden" name="selector" value="<?= htmlspecialchars($selector, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

            <div class="auth-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></div>
            <div class="mini-brand">
                <span class="brand-mark" aria-hidden="true"><i class="fa-solid fa-paw"></i></span>
                <span><strong>Beleza Cão&amp;Gato</strong><small>SISTEMA DE GESTÃO</small></span>
            </div>

            <h2>Crie uma nova senha</h2>
            <p>Use uma senha forte que você ainda não utiliza em outros serviços.</p>

            <?php if ($error): ?><div class="alert error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>

            <label for="reset-password">Nova senha
                <input id="reset-password" type="password" name="password" autocomplete="new-password" minlength="8" required data-password-strength-input aria-describedby="password-rules">
            </label>

            <div class="register-strength" data-password-strength hidden aria-live="polite">
                <div><span>Força da senha:</span><strong data-strength-label></strong></div>
                <span class="register-strength-track" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                <small id="password-rules">Use 8 ou mais caracteres, incluindo maiúscula, minúscula, número e símbolo.</small>
            </div>

            <label for="reset-password-confirmation">Confirmar senha
                <input id="reset-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>

            <button class="button primary wide" type="submit">Redefinir senha</button>
            <a class="back-link" href="/login">Voltar para o login</a>
        </form>
    <?php endif; ?>
</main>
