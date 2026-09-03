<main class="center-auth">
    <section class="auth-card">
        <div class="verification-icon">
            <i class="fa-regular fa-envelope-open"></i>
        </div>

        <div class="auth-brand logo">
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


        <div class="verification-text">
            <h2>Verifique seu e-mail</h2>
            <p>
                Enviamos um código de verificação de 6 dígitos para o endereço <b>example@petspa.com</b>.
                Insira-o abaixo para ativar sua conta.
            </p>
        </div>

        <div class="otp">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <input inputmode="numeric" maxlength="1" aria-label="Dígito <?= $i + 1 ?>">
            <?php endfor; ?>
        </div>

        <button class="button primary wide">Confirmar código</button>

        <p class="auth-foot">
            Não recebeu o código? <a href="#">Reenviar código</a>
        </p>

        <a class="back-link" href="/cadastro">
            <i class="fa-solid fa-arrow-left"></i>
            Alterar endereço de e-mail
        </a>
    </section>
</main>