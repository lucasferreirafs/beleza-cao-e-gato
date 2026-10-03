<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$singular = match ($section) {
    'clientes' => 'Cliente',
    'pets' => 'Pet',
    'funcionarios' => 'Funcionário',
    'servicos' => 'Serviço',
    'agendamentos' => 'Agendamento',
    default => null,
};
?>

<header class="page-heading">
    <div>
        <h1><?= $e($title) ?></h1>
        <p>Consulte cadastros, vínculos e estados persistidos no banco.</p>
    </div>

    <?php if (in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true)): ?>
        <a class="button primary" href="/admin/<?= $e($section) ?>/novo">
            ＋ Novo <?= $e($singular) ?>
        </a>
    <?php endif; ?>
</header>

<?php if ($section === 'relatorios'): ?>
    <div class="metric-grid three">
        <article class="metric-card featured">
            <small>RELATÓRIOS</small>
            <strong>Dados ao vivo</strong>
            <p>Use os módulos de clientes, serviços e agendamentos para auditoria operacional.</p>
        </article>
    </div>

<?php elseif ($section === 'configuracoes'): ?>
    <section class="panel">
        <h2>Configurações</h2>
        <p>Parâmetros sensíveis são administrados por variáveis de ambiente e não são expostos nesta tela.</p>
    </section>

<?php else: ?>
    <section class="panel">
        <div class="filters">
            <input type="search" placeholder="Filtrar nesta página..." data-table-filter>
            <select data-status-filter>
                <option value="">Todos os status</option>
                <option value="active">Ativo</option>
                <option value="blocked">Bloqueado</option>
                <option value="pending">Pendente</option>
            </select>
        </div>

        <div class="table-wrap">
            <table data-filter-table>
                <thead>
                    <tr>
                        <th><?= strtoupper($e($singular ?? 'ITEM')) ?></th>
                        <th>CONTATO / DETALHE</th>
                        <th>VÍNCULOS</th>
                        <th>STATUS</th>
                        <th>AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr data-status="<?= $e($row['status']) ?>">
                            <td><strong><?= $e($row['label']) ?></strong></td>
                            <td><?= $e($row['detail']) ?></td>
                            <td><?= $e($row['links']) ?></td>
                            <td>
                                <span class="badge <?= \App\Support\StatusBadge::class($row['status']) ?>">
                                    <?= $e($section === 'agendamentos'
                                        ? \App\Support\AppointmentStatus::label($row['status'])
                                        : $row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (in_array($section, ['clientes', 'funcionarios'], true)): ?>
                                    <a class="button ghost"
                                        href="/admin/<?= $e($section) ?>/<?= $e($row['id']) ?>/editar">Editar</a>
                                    <form method="post" action="/admin/<?= $e($section) ?>/<?= $e($row['id']) ?>/status">
                                        <?= \App\Security\Csrf::field() ?>
                                        <input type="hidden" name="status"
                                            value="<?= $row['status'] === 'active' ? 'blocked' : 'active' ?>">
                                        <button
                                            class="button ghost"><?= $row['status'] === 'active' ? 'Bloquear' : 'Ativar' ?></button>
                                    </form>

                                <?php elseif ($section === 'servicos'): ?>
                                    <a class="button ghost" href="/admin/servicos/<?= $e($row['id']) ?>/editar">Editar</a>
                                    <form method="post" action="/admin/servicos/<?= $e($row['id']) ?>/status">
                                        <?= \App\Security\Csrf::field() ?>
                                        <input type="hidden" name="status"
                                            value="<?= $row['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button
                                            class="button ghost"><?= $row['status'] === 'active' ? 'Desativar' : 'Ativar' ?></button>
                                    </form>

                                <?php elseif ($section === 'pets'): ?>
                                    <a class="button ghost" href="/admin/pets/<?= $e($row['id']) ?>/editar">Editar</a>

                                <?php elseif ($section === 'agendamentos'): ?>
                                    <form method="post" action="/admin/agendamentos/<?= $e($row['id']) ?>/status">
                                        <?= \App\Security\Csrf::field() ?>
                                        <select name="status" aria-label="Novo status">
                                            <?php foreach (\App\Support\AppointmentStatus::optionsFor($row['status']) as $value => $label): ?>
                                                <option value="<?= $e($value) ?>" <?= $row['status'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button ghost">Salvar</button>
                                    </form>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$rows): ?>
            <div class="empty-state">
                <p>Nenhum registro encontrado.</p>
            </div>
        <?php endif; ?>

        <footer class="pagination">
            <span>Mostrando <?= count($rows) ?> registros</span>
        </footer>
    </section>
<?php endif; ?>
