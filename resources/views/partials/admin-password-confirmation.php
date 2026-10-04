<dialog class="admin-password-dialog" data-admin-password-dialog aria-labelledby="admin-password-title" aria-describedby="admin-password-message">
    <form method="dialog" class="admin-password-dialog__surface">
        <button type="button" class="admin-password-dialog__close" data-admin-password-cancel aria-label="Fechar confirmação">×</button>
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        <h2 id="admin-password-title" data-admin-password-title>Confirme sua senha</h2>
        <p id="admin-password-message" data-admin-password-message>Para continuar, confirme sua senha de administrador.</p>
        <label for="admin-password-input">Senha do administrador</label>
        <input id="admin-password-input" type="password" autocomplete="current-password" required data-admin-password-input>
        <div class="admin-password-dialog__actions">
            <button type="button" class="button ghost" data-admin-password-cancel>Cancelar</button>
            <button type="submit" class="button primary" data-admin-password-submit>Confirmar</button>
        </div>
    </form>
</dialog>
