<?php
$suffix = $returnQuery !== '' ? '?' . $returnQuery : '';
$directoryUrl = !empty($adminPetDirectory) ? '/admin/pets' : '/staff/pets';
$pageUrl = static fn(int $number): string => $directoryUrl . '?' . http_build_query(['q' => $search, 'page' => $number]);
?>
<div class="staff-clients">
    <?php if (empty($embedAdminPetDirectory)): ?><header class="page-heading"><div><h1>Pets</h1><p>Consulte os pets cadastrados e seus tutores.</p></div></header><?php endif; ?>
    <section class="panel sc-directory" aria-label="Diretório de pets">
        <form class="sc-search" action="<?= $e($directoryUrl) ?>" method="get" role="search">
                <label for="pet-search">Buscar pets
                <input id="pet-search" type="search" name="q" maxlength="100" value="<?= $e($search) ?>" placeholder="<?= !empty($adminPetDirectory) ? 'Buscar por nome do pet, raça ou tutor...' : 'Nome do pet, raça ou tutor' ?>">
            </label>
            <button class="button primary" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Buscar</button>
            <?php if ($search !== ''): ?><a class="button secondary" href="<?= $e($directoryUrl) ?>">Limpar busca</a><?php endif; ?>
        </form>
        <p class="sc-result-count" role="status"><?= $total ?> <?= $total === 1 ? 'pet encontrado' : 'pets encontrados' ?></p>
        <?php if (!$pets): ?>
            <div class="sc-empty"><i class="fa-solid fa-paw" aria-hidden="true"></i><h2><?= $search !== '' ? 'Nenhum pet encontrado' : 'Nenhum pet cadastrado' ?></h2><p><?= $search !== '' ? 'Tente buscar por outro pet, raça ou tutor.' : 'Os pets cadastrados aparecerão aqui.' ?></p></div>
        <?php else: ?>
            <?php if (!empty($adminPetDirectory)): ?>
                <div class="admin-pet-list" aria-label="Pets cadastrados">
                    <?php foreach ($pets as $entry): ?>
                        <?php $detailsId = 'admin-pet-actions-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $entry['id']); ?>
                        <article class="admin-pet-card" data-admin-pet-card>
                            <div class="admin-pet-summary" role="button" tabindex="0"
                                aria-label="Mostrar ou ocultar ações de <?= $e($entry['pet_name']) ?>"
                                aria-expanded="false" aria-controls="<?= $e($detailsId) ?>" data-admin-pet-toggle>
                                <div class="admin-pet-field admin-pet-name"><span>Pet</span><strong><i class="fa-solid fa-paw" aria-hidden="true"></i> <?= $e($entry['pet_name']) ?></strong></div>
                                <div class="admin-pet-field"><span>Raça</span><span><?= $e(trim((string) $entry['breed']) ?: 'Não informada') ?></span></div>
                                <div class="admin-pet-field"><span>Cor do pelo</span><span><?= $e(trim((string) ($entry['coat_color'] ?? '')) ?: 'Não informada') ?></span></div>
                                <div class="admin-pet-field"><span>Tutor</span><span><?= $e($entry['owner_name']) ?></span></div>
                                <span class="admin-pet-chevron" aria-hidden="true"><i class="fa-solid fa-angle-down"></i></span>
                            </div>
                            <div class="admin-pet-actions" id="<?= $e($detailsId) ?>" data-admin-pet-actions hidden>
                                <a class="button secondary" href="<?= $e($directoryUrl . '/' . rawurlencode((string) $entry['id']) . $suffix) ?>" aria-label="Ver detalhes de <?= $e($entry['pet_name']) ?>">Ver detalhes <i class="fa-solid fa-angle-right" aria-hidden="true"></i></a>
                                <a class="button ghost" href="/admin/pets/<?= $e(rawurlencode((string) $entry['id'])) ?>/editar">Editar</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
            <table class="sc-table" role="table" aria-label="Pets cadastrados">
                <thead role="rowgroup"><tr role="row"><th scope="col">Pet</th><th scope="col">Raça</th><th scope="col">Tutor</th><th scope="col">Perfil</th></tr></thead>
                <tbody role="rowgroup">
                <?php foreach ($pets as $entry): ?>
                    <tr role="row">
                        <td role="cell" class="sc-name-cell"><span class="sc-initial" aria-hidden="true"><i class="fa-solid fa-paw"></i></span><div><strong><?= $e($entry['pet_name']) ?></strong></div></td>
                        <td role="cell"><span class="sc-mobile-label" aria-hidden="true">Raça</span><?= $e(trim((string) $entry['breed']) ?: 'Não informada') ?></td>
                        <td role="cell"><span class="sc-mobile-label" aria-hidden="true">Tutor</span><?= $e($entry['owner_name']) ?></td>
                        <td role="cell"><a class="button secondary" href="/staff/pets/<?= $e($entry['id']) ?><?= $e($suffix) ?>" aria-label="Ver perfil de <?= $e($entry['pet_name']) ?>">Ver perfil do pet <i class="fa-solid fa-angle-right" aria-hidden="true"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <nav class="sc-pagination" aria-label="Paginação de pets">
                <?php if ($page > 1): ?><a class="button secondary" href="<?= $e($pageUrl($page - 1)) ?>" rel="prev">Anterior</a><?php endif; ?>
                <span>Página <strong><?= $page ?></strong> de <?= $pages ?></span>
                <?php if ($page < $pages): ?><a class="button secondary" href="<?= $e($pageUrl($page + 1)) ?>" rel="next">Próxima</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</div>
