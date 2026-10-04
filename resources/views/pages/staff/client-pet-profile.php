<?php
use App\Support\PetHelper;
$suffix = $returnQuery !== '' ? '?' . $returnQuery : '';
$clientUrl = $ownerProfileUrl ?? '/staff/clientes/' . $client['id'];
$petUrl = $petProfileUrl ?? (!empty($fromPetDirectory) ? '/staff/pets/' . $pet['id'] : $clientUrl . '/pets/' . $pet['id']);
$petPhotoUrl = $petUrl . '/foto';
$backUrl = $backUrl ?? (!empty($fromPetDirectory) ? '/staff/pets' : $clientUrl);
$neutered = in_array($pet['neutered'], [true, 1, '1', 't'], true);
$hasAllergies = trim((string) $pet['allergies']) !== '';
$hasNeeds = trim((string) $pet['special_needs']) !== '';
?>
<div class="staff-clients">
    <a class="sc-back" href="<?= $e($backUrl . $suffix) ?>"><i class="fa-solid fa-angle-left" aria-hidden="true"></i> <?= !empty($fromPetDirectory) ? 'Voltar à lista de pets' : 'Voltar ao perfil do tutor' ?></a>
    <header class="panel sc-profile-header">
        <span class="sc-avatar">
            <?php if ($pet['photo_path']): ?><img src="<?= $e($petPhotoUrl . '?v=' . rawurlencode($pet['photo_path'])) ?>" alt="" width="80" height="80" data-avatar-image><?php endif; ?>
            <img src="/assets/images/pet.webp" alt="" width="80" height="80" data-avatar-fallback <?= $pet['photo_path'] ? 'hidden' : '' ?>>
        </span>
        <div><p class="sc-eyebrow">Perfil do pet</p><h1><?= $e($pet['name']) ?></h1><p><?= $e($pet['species']) ?> · <?= $e($pet['breed']) ?></p><p>Tutor: <a href="<?= $e($clientUrl . $suffix) ?>"><?= $e($client['name']) ?></a></p></div>
        <?php if (!empty($adminPetProfile)): ?><div class="admin-pet-profile-actions"><a class="button ghost" href="/admin/pets/<?= $e(rawurlencode((string) $pet['id'])) ?>/editar">Editar pet</a><form method="post" action="/admin/pets/<?= $e(rawurlencode((string) $pet['id'])) ?>/excluir" data-admin-password-required data-password-title="Excluir pet" data-password-message="Confirme sua senha. Pets com histórico de agendamentos não podem ser excluídos."><?= \App\Security\Csrf::field() ?><input type="password" name="admin_password" hidden disabled data-admin-password-value><noscript><label>Senha do administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript><button class="button danger" type="submit">Excluir pet</button></form></div><?php endif; ?>
    </header>
    <div class="sc-profile-grid sc-pet-profile-grid">
        <section class="panel sc-facts" aria-labelledby="pet-basics"><h2 id="pet-basics">Informações do pet</h2><dl>
            <?php foreach (['Espécie' => $pet['species'], 'Raça' => $pet['breed'], 'Sexo' => $pet['sex'], 'Nascimento' => PetHelper::formatDate($pet['birth_date']), 'Idade' => PetHelper::age($pet['birth_date']) ?? 'Não informada', 'Peso' => PetHelper::formatWeight($pet['weight']), 'Cor do pelo' => trim((string) $pet['coat_color']) ?: 'Não informada', 'Castrado' => $neutered ? 'Sim' : 'Não'] as $label => $value): ?>
                <div><dt><?= $e($label) ?></dt><dd><?= $e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl></section>
        <section class="panel sc-facts sc-care" aria-labelledby="pet-care"><h2 id="pet-care">Saúde e cuidados</h2><dl>
            <div class="<?= $hasAllergies ? 'sc-care-alert' : '' ?>"><dt><?php if ($hasAllergies): ?><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> <?php endif; ?>Alergias</dt><dd><?= $e($hasAllergies ? $pet['allergies'] : 'Nenhuma registrada') ?></dd></div>
            <div class="<?= $hasNeeds ? 'sc-care-alert' : '' ?>"><dt><?php if ($hasNeeds): ?><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> <?php endif; ?>Necessidades especiais</dt><dd><?= $e($hasNeeds ? $pet['special_needs'] : 'Nenhuma registrada') ?></dd></div>
            <div><dt>Comportamento</dt><dd><?= $e(trim((string) $pet['behavior']) ?: 'Não informado') ?></dd></div>
            <div><dt>Observações</dt><dd><?= $e(trim((string) $pet['notes']) ?: 'Nenhuma observação registrada') ?></dd></div>
        </dl></section>
    </div>
</div>
