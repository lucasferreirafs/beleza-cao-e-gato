<?php $petUrl = $clientUrl . '/pets/' . $pet['id']; ?>
<article class="panel sc-pet-card">
    <span class="sc-pet-image">
        <?php if ($pet['photo_path']): ?><img src="<?= $e($petUrl . '/foto?v=' . rawurlencode($pet['photo_path'])) ?>" alt="" width="64" height="64" loading="lazy" data-avatar-image><?php endif; ?>
        <img src="/assets/images/pet.webp" alt="" width="64" height="64" loading="lazy" data-avatar-fallback <?= $pet['photo_path'] ? 'hidden' : '' ?>>
    </span>
    <div><h3><?= $e($pet['name']) ?></h3><p><?= $e($pet['species']) ?> · <?= $e($pet['breed']) ?></p></div>
    <a class="button secondary" href="<?= $e($petUrl . $suffix) ?>" aria-label="Ver perfil do pet <?= $e($pet['name']) ?>">Ver perfil do pet <i class="fa-solid fa-angle-right" aria-hidden="true"></i></a>
</article>
