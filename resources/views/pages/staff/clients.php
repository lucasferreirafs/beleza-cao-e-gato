<?php
$suffix = $returnQuery !== '' ? '?' . $returnQuery : '';
?>
<div class="staff-clients">
    <header class="page-heading"><div><h1>Clientes</h1><p>Contatos e cuidados dos pets, sempre à mão.</p></div></header>
    <section class="panel sc-directory" aria-label="Diretório de clientes">
        <form class="sc-search" action="/staff/clientes" method="get" role="search">
            <label for="client-search">Buscar clientes
                <input id="client-search" type="search" name="q" maxlength="100" value="<?= $e($search) ?>" placeholder="Nome, e-mail ou telefone">
            </label>
            <button class="button primary" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Buscar</button>
            <?php if ($search !== ''): ?><a class="button secondary" href="/staff/clientes">Limpar busca</a><?php endif; ?>
        </form>
        <p class="sc-result-count" role="status"><?= $total ?> <?= $total === 1 ? 'cliente encontrado' : 'clientes encontrados' ?></p>
        <?php if (!$clients): ?>
            <div class="sc-empty"><i class="fa-solid fa-users" aria-hidden="true"></i><h2><?= $search !== '' ? 'Nenhum cliente encontrado' : 'Nenhum cliente cadastrado' ?></h2><p><?= $search !== '' ? 'Tente outro nome, e-mail ou telefone.' : 'Os clientes cadastrados aparecerão aqui.' ?></p></div>
        <?php else: ?>
            <table class="sc-table" role="table" aria-label="Clientes do pet shop">
                <thead role="rowgroup"><tr role="row"><th scope="col">Cliente</th><th scope="col">E-mail</th><th scope="col">Telefone</th><th scope="col">Perfil</th></tr></thead>
                <tbody role="rowgroup">
                <?php foreach ($clients as $entry): ?>
                    <tr role="row">
                        <td role="cell" class="sc-name-cell"><span class="sc-initial" aria-hidden="true"><?= $e(mb_substr($entry['name'], 0, 1)) ?></span><div><strong><?= $e($entry['name']) ?></strong><?php if ($entry['status'] !== 'active'): ?><span class="sc-account-state"><?= $entry['status'] === 'blocked' ? 'Conta bloqueada' : 'Conta inativa' ?></span><?php endif; ?></div></td>
                        <td role="cell"><span class="sc-mobile-label" aria-hidden="true">E-mail</span><?= $e($entry['email']) ?></td>
                        <td role="cell"><span class="sc-mobile-label" aria-hidden="true">Telefone</span><?= $e(trim((string) $entry['phone']) ?: 'Não informado') ?></td>
                        <td role="cell"><a class="button secondary" href="/staff/clientes/<?= $e($entry['id']) ?><?= $e($suffix) ?>" aria-label="Ver perfil de <?= $e($entry['name']) ?>">Ver perfil <i class="fa-solid fa-angle-right" aria-hidden="true"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php $pagination = ['path' => '/staff/clientes', 'query' => ['q' => $search], 'current' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => $perPage, 'item_label' => 'clientes', 'item_label_singular' => 'cliente', 'aria_label' => 'Paginação de clientes']; ?>
        <?php require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
    </section>
</div>
