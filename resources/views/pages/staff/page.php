<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>

<header class="page-heading">
    <div>
        <h1><?= $e($title) ?></h1>
        <p>Acompanhe os dados da unidade dentro das suas permissões.</p>
    </div>
</header>

<?php if ($section === 'perfil'): ?>
    <section class="panel">
        <div class="profile-head">
            <span class="avatar large"><?= $e(mb_substr($user['name'], 0, 1)) ?></span>
            <div>
                <h2><?= $e($user['name']) ?></h2>
                <p><?= $e($user['email']) ?></p>
            </div>
        </div>
    </section>

<?php else: ?>
    <section class="panel">
        <div class="filters">
            <input placeholder="Filtrar nesta página..." data-table-filter>
        </div>

        <div class="table-wrap">
            <table data-filter-table>
                <thead>
                    <tr>
                        <th>DATA / IDENTIFICAÇÃO</th>
                        <th>PET</th>
                        <th>CLIENTE</th>
                        <th>SERVIÇO</th>
                        <th>STATUS / AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= $e($row['schedule'] ?? '-') ?></td>
                            <td><strong><?= $e($row['pet_name'] ?? '-') ?></strong></td>
                            <td><?= $e($row['client_name'] ?? '-') ?></td>
                            <td><?= $e($row['service_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= \App\Support\StatusBadge::class($row['status'] ?? null) ?>"><?= $e(isset($row['status']) ? \App\Support\AppointmentStatus::label($row['status']) : '-') ?></span>

                                <?php if (isset($row['id']) && in_array($section, ['agenda', 'atendimentos'], true)): ?>
                                    <form method="post" action="/staff/agendamentos/<?= (int) $row['id'] ?>/status">
                                        <?= \App\Security\Csrf::field() ?>
                                        <select name="status" aria-label="Novo status">
                                            <?php foreach (\App\Support\AppointmentStatus::optionsFor($row['status']) as $value => $label): ?>
                                                <option value="<?= $e($value) ?>" <?= ($row['status'] ?? null) === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button ghost">Salvar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$rows): ?>
            <div class="empty-state">
                <p>Nenhum registro disponível.</p>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
