<?php $e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<header class="page-heading">
    <div>
        <h1>Meus Pets</h1>
        <p>Gerencie e acompanhe a saúde e estética de seus companheiros.</p>
    </div>
    <a class="button primary" href="/cliente/pets/novo">
        <i class="fa-solid fa-plus"></i>
        Adicionar Pet
    </a>
</header>
<div class="stack">
    <?php
    foreach ($pets as $pet): ?>
        <article class="panel pet-card pet-card--managed">
            <form class="pet-card-delete" method="post" action="/cliente/pets/<?= $e($pet['id']) ?>" data-confirm="Excluir este pet?">
                <?= \App\Security\Csrf::field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="pet-delete-icon" aria-label="Excluir <?= htmlspecialchars($pet['name'], ENT_QUOTES, 'UTF-8') ?>" title="Excluir pet"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </form>
            <header>
                <img src="<?= htmlspecialchars(\App\Support\PetHelper::photo($pet), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($pet['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                <span>
                    <strong>
                        <?= htmlspecialchars($pet['name']) ?>
                    </strong>
                    <small>
                        <?= htmlspecialchars($pet['species']) ?>
                    </small>
                </span>
            </header>

            <dl class="pet-details">
                <div>
                    <dt>Raça</dt>
                    <dd><?= htmlspecialchars($pet['breed']) ?></dd>
                </div>
                <div>
                    <dt>Peso</dt>
                    <dd><?= htmlspecialchars((string) $pet['weight']) ?> kg</dd>
                </div>
                <div>
                    <dt>Sexo</dt>
                    <dd><?= htmlspecialchars($pet['sex']) ?></dd>
                </div>
            </dl>

            <footer>

                <a class="button muted" href="/cliente/pets/<?= $e($pet['id']) ?>">
                    <i class="fa-solid fa-eye"></i>
                    Ver Perfil
                </a>
                <a class="button secondary" href="/cliente/pets/<?= $e($pet['id']) ?>/editar">
                    <i class="fa-solid fa-pen"></i>
                    Editar
                </a>

            </footer>
        </article>
    <?php endforeach; ?>
</div>
<?php $pagination = ['path' => '/cliente/pets', 'current' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => $perPage, 'item_label' => 'pets', 'item_label_singular' => 'pet', 'aria_label' => 'Paginação dos meus pets']; ?>
<?php require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
