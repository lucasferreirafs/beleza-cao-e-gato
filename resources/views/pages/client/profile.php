<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>

<header class="page-heading">
    <div>
        <h1>Meu Perfil</h1>
        <p>Gerencie seus dados pessoais e preferências de conta.</p>
    </div>
</header>

<form class="panel pet-form" method="post" action="/cliente/perfil">
    <?= \App\Security\Csrf::field() ?>

    <div class="profile-head">
        <span class="avatar large"><?= $e(mb_substr($profile['name'] ?? 'U', 0, 1)) ?></span>
        <div>
            <h2><?= $e($profile['name'] ?? '') ?></h2>
            <p>Cliente desde <?= !empty($profile['created_at']) ? date('Y', strtotime($profile['created_at'])) : '-' ?></p>
        </div>
    </div>

    <div class="form-grid">
        <label>
            Nome completo
            <input name="name" value="<?= $e($profile['name'] ?? '') ?>" maxlength="120" required>
        </label>
        <label>
            E-mail
            <input value="<?= $e($profile['email'] ?? '') ?>" disabled>
        </label>
        <label>
            Telefone
            <input name="phone" value="<?= $e($profile['phone'] ?? '') ?>">
        </label>
        <label>
            CPF
            <input value="<?= $e($profile['cpf'] ?? '') ?>" disabled>
        </label>
        <label>
            Data de nascimento
            <input type="date" name="birth_date" value="<?= $e($profile['birth_date'] ?? '') ?>">
        </label>
    </div>

    <footer class="form-actions">
        <button class="button primary" type="submit">Salvar alterações</button>
    </footer>
</form>
