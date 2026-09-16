<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>

<header class="page-heading">
    <div>
        <h1>Perfil de <?= $e($pet['name']) ?></h1>
        <p>Informações e cuidados registrados para o pet.</p>
    </div>
    <a class="button secondary" href="/cliente/pets/<?= (int) $pet['id'] ?>/editar">✎ Editar</a>
</header>

<section class="panel">
    <div class="profile-head">
        <span class="avatar large"><?= $e(mb_substr($pet['name'], 0, 1)) ?></span>
        <div>
            <h2><?= $e($pet['name']) ?></h2>
            <p><?= $e($pet['species']) ?> • <?= $e($pet['breed']) ?></p>
        </div>
    </div>

    <dl class="pet-details">
        <div>
            <dt>Sexo</dt>
            <dd><?= $e($pet['sex']) ?></dd>
        </div>
        <div>
            <dt>Nascimento</dt>
            <dd><?= $pet['birth_date'] ? date('d/m/Y', strtotime($pet['birth_date'])) : 'Não informado' ?></dd>
        </div>
        <div>
            <dt>Peso</dt>
            <dd><?= $pet['weight'] !== null ? $e($pet['weight']) . ' kg' : 'Não informado' ?></dd>
        </div>
        <div>
            <dt>Castrado</dt>
            <dd><?= $pet['neutered'] ? 'Sim' : 'Não' ?></dd>
        </div>
        <div>
            <dt>Comportamento</dt>
            <dd><?= $e($pet['behavior'] ?: 'Não informado') ?></dd>
        </div>
        <div>
            <dt>Alergias</dt>
            <dd><?= $e($pet['allergies'] ?: 'Nenhuma informada') ?></dd>
        </div>
    </dl>

    <?php if ($pet['special_needs'] || $pet['notes']): ?>
        <div class="summary-box">
            <strong>Cuidados importantes</strong>
            <p><?= $e($pet['special_needs'] ?: $pet['notes']) ?></p>
        </div>
    <?php endif; ?>
</section>
