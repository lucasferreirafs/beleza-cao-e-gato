<?php
$pet = $appointment;
$petAge = \App\Support\PetHelper::age($pet['pet_birth_date'] ?? null);
$petPhoto = !empty($pet['pet_photo_path'])
    ? '/staff/atendimentos/' . (int) $pet['id'] . '/foto?v=' . rawurlencode((string) $pet['pet_photo_path'])
    : '/assets/images/pet.webp';
$value = static fn(mixed $v): string => trim((string) $v) !== '' ? (string) $v : 'Não informado';
?>
<section class="staff-attendance-panel staff-attendance-pet" aria-labelledby="staff-attendance-pet-heading">
    <h2 id="staff-attendance-pet-heading">Informações do Pet</h2>
    <div class="staff-attendance-pet-identity">
        <img src="<?= $e($petPhoto) ?>" alt="Foto de <?= $e($pet['pet_name']) ?>" width="72" height="72" loading="lazy">
        <div><div class="staff-attendance-pet-name"><h3><?= $e($pet['pet_name']) ?></h3><span class="staff-attendance-badge <?= $e(\App\Support\StatusBadge::class($pet['status'])) ?>"><?= $e(\App\Support\AppointmentStatus::label($pet['status'])) ?></span></div>
            <p><?= $e($value($pet['pet_breed'] ?? '')) ?><?= $petAge ? ' · ' . $e($petAge) : '' ?></p></div>
    </div>
    <dl class="staff-attendance-pet-facts">
        <div><dt>Sexo</dt><dd><?= $e($value($pet['pet_sex'] ?? '')) ?></dd></div>
        <div><dt>Peso</dt><dd><?= $e(\App\Support\PetHelper::formatWeight($pet['pet_weight'] ?? null)) ?></dd></div>
        <div class="<?= trim((string) ($pet['pet_allergies'] ?? '')) !== '' ? 'staff-attendance-alert' : '' ?>"><dt>Alergias</dt><dd><?= $e(trim((string) ($pet['pet_allergies'] ?? '')) ?: 'Nenhuma registrada') ?></dd></div>
        <div class="<?= trim((string) ($pet['pet_special_needs'] ?? '')) !== '' ? 'staff-attendance-alert' : '' ?>"><dt>Necessidades especiais</dt><dd><?= $e(trim((string) ($pet['pet_special_needs'] ?? '')) ?: 'Nenhuma registrada') ?></dd></div>
        <div><dt>Comportamento</dt><dd><?= $e($value($pet['pet_behavior'] ?? '')) ?></dd></div>
        <div><dt>Última tosa concluída</dt><dd><?= $lastGrooming ? $e((new \DateTimeImmutable($lastGrooming))->format('d/m/Y')) : 'Não registrada' ?></dd></div>
        <?php if (trim((string) ($pet['pet_notes'] ?? '')) !== ''): ?><div class="staff-attendance-wide"><dt>Cuidados do pet</dt><dd><?= $e($pet['pet_notes']) ?></dd></div><?php endif; ?>
    </dl>
</section>
