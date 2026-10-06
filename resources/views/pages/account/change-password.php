<?php $e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
<header class="page-heading account-password-heading">
    <div>
        <h1>Alterar senha</h1>
        <p>Confirme sua senha atual e escolha uma nova senha forte.</p>
    </div>
</header>

<section class="panel account-password-panel" aria-labelledby="account-password-title">
    <h2 id="account-password-title">Atualize suas credenciais</h2>
    <form method="post" action="/conta/alterar-senha" class="account-password-form" data-password-change-form>
        <?= \App\Security\Csrf::field() ?>

        <label for="current-password">Senha atual
            <span class="account-password-input-wrap">
                <input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
                <button type="button" class="account-password-toggle" data-password-toggle aria-label="Mostrar senha atual" aria-controls="current-password">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </span>
        </label>

        <label for="new-password">Nova senha
            <span class="account-password-input-wrap">
                <input id="new-password" type="password" name="new_password" autocomplete="new-password" minlength="8" required aria-describedby="password-rules" data-password-strength-input>
                <button type="button" class="account-password-toggle" data-password-toggle aria-label="Mostrar nova senha" aria-controls="new-password">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </span>
        </label>
        <div class="register-strength" data-password-strength hidden aria-live="polite">
            <div><span>Força da senha:</span><strong data-strength-label></strong></div>
            <span class="register-strength-track" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
            <small id="password-rules">Use 8 ou mais caracteres, incluindo maiúscula, minúscula, número e símbolo.</small>
        </div>

        <label for="confirm-password">Confirme a nova senha
            <span class="account-password-input-wrap">
                <input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                <button type="button" class="account-password-toggle" data-password-toggle aria-label="Mostrar confirmação da senha" aria-controls="confirm-password">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </span>
        </label>

        <footer class="account-password-actions">
            <a class="button ghost" href="<?= $e(\App\Support\Navigation::home($user['role'] ?? 'client')) ?>/perfil">Cancelar</a>
            <button class="button primary" type="submit">Salvar nova senha</button>
        </footer>
    </form>
</section>
