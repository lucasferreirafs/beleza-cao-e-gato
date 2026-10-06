<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<header class="page-heading">
    <div>
        <h1>Meu Perfil</h1>
        <p>Gerencie seus dados pessoais e sua foto de perfil.</p>
    </div>
    <a class="button account-password-link" href="/conta/alterar-senha"><i class="fa-solid fa-key" aria-hidden="true"></i> Alterar senha</a>
</header>
<form class="panel pet-form account-form" method="post" enctype="multipart/form-data" action="<?= $e($profileUrl) ?>" data-profile-form>
    <?= \App\Security\Csrf::field() ?>
    <input type="hidden" name="profile_version" value="<?= $e($profile['updated_at']) ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= \App\Services\SupabaseStorageService::MAX_BYTES ?>">
    <div class="profile-head">
        <span class="avatar large profile-photo-preview">
            <img <?= !empty($profile['avatar_path']) ? 'src="/conta/foto?v=' . $e(rawurlencode($profile['avatar_path'])) . '"' : 'hidden' ?> alt="Foto de perfil" data-profile-preview data-avatar-image>
            <span data-avatar-fallback <?= !empty($profile['avatar_path']) ? 'hidden' : '' ?>><?= $e(mb_substr($profile['name'], 0, 1)) ?></span>
        </span>
        <div class="profile-head-info">
            <h2><?= $e($profile['name']) ?></h2>
            <p>Na plataforma desde <?= date('Y', strtotime($profile['created_at'])) ?></p>
        </div>
        <div class="profile-photo-actions">
            <label class="button teal profile-photo-button" for="profile-photo">
                <i class="fa-solid fa-camera" aria-hidden="true"></i> Alterar foto
                <input class="profile-photo-input" id="profile-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-profile-photo aria-describedby="photo-help photo-error">
            </label>
            <small id="photo-help">JPG, PNG ou WebP, até 5 MB.<br>A foto será atualizada ao salvar.</small>
            <p id="photo-error" class="field-error" role="alert" hidden></p>
        </div>
    </div>
    <div class="form-grid">
        <label for="profile-name">Nome completo<input id="profile-name" name="name" value="<?= $e($profile['name']) ?>" maxlength="120" autocomplete="name" required></label>
        <div class="editable-field">
            <label for="profile-email">E-mail</label>
            <div class="editable-input"><input id="profile-email" type="email" name="email" value="<?= $e($profile['email']) ?>" autocomplete="email" maxlength="190" required readonly>
                <button type="button" data-edit-field="profile-email" aria-label="Editar e-mail" aria-controls="profile-email" aria-pressed="false"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            </div>
        </div>
        <label for="profile-phone">Telefone<input id="profile-phone" name="phone" type="tel" value="<?= $e($profile['phone'] ?? '') ?>" autocomplete="tel" maxlength="20"></label>
        <div class="editable-field">
            <label for="profile-cpf">CPF</label>
            <div class="editable-input"><input id="profile-cpf" name="cpf" value="<?= $e($profile['cpf'] ?? '') ?>" inputmode="numeric" maxlength="14" readonly>
                <button type="button" data-edit-field="profile-cpf" aria-label="Editar CPF" aria-controls="profile-cpf" aria-pressed="false"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            </div>
        </div>
        <label for="profile-birth">Data de nascimento<input id="profile-birth" type="date" name="birth_date" value="<?= $e($profile['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>" autocomplete="bday"></label>
        <label for="profile-password" data-identity-password hidden>Confirme sua senha atual
            <input id="profile-password" type="password" name="current_password" autocomplete="current-password" disabled>
        </label>
    </div>
    <p class="identity-help" data-identity-help hidden>Alterações de CPF e e-mail exigem sua senha atual. Ao trocar o e-mail, confirme o novo endereço para entrar novamente.</p>
    <p data-profile-feedback class="field-error" role="status"></p>
    <footer class="form-actions"><button class="button primary" type="submit">Salvar alterações</button></footer>
</form>
