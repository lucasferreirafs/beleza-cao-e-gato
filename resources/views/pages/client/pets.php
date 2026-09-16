<header class="page-heading">
    <div>
        <h1>Meus Pets</h1>
        <p>Gerencie e acompanhe a saúde e estética de seus companheiros.</p>
    </div><a class="button primary" href="/cliente/pets/novo">＋ Adicionar Pet</a>
</header>
<div class="stack">
    <?php $photos = ['Luna' => 'pet-luna.png', 'Mel' => 'pet-mel.png', 'Toddy' => 'pet-toddy.png'];
    foreach ($pets as $pet): ?>
        <article class="panel pet-card">
            <header><img src="/assets/images/<?= $photos[$pet['name']] ?? 'pet-luna.png' ?>"
                    alt="<?= htmlspecialchars($pet['name']) ?>"><span><strong><?= htmlspecialchars($pet['name']) ?></strong><small><?= htmlspecialchars($pet['species']) ?></small></span><span
                    class="badge success">Ativo</span></header>
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
            <footer><a class="button muted" href="/cliente/pets/<?= (int) $pet['id'] ?>">◉ Ver Perfil</a><a class="button secondary" href="/cliente/pets/<?= (int) $pet['id'] ?>/editar">✎ Editar</a>
                <form method="post" action="/cliente/pets/<?= (int) $pet['id'] ?>" data-confirm="Excluir este pet?">
                    <?= \App\Security\Csrf::field() ?><input type="hidden" name="_method" value="DELETE"><button
                        class="button ghost danger">Excluir</button>
                </form>
            </footer>
        </article><?php endforeach; ?>
</div>
