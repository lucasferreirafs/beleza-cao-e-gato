<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<?php if ($section === 'agenda') { require __DIR__ . '/agenda.php'; return; } ?>
<?php if ($section === 'atendimentos') { require __DIR__ . '/appointments.php'; return; } ?>
<?php if ($section === 'clientes') {
    require __DIR__ . '/' . match ($clientView ?? 'list') {
        'profile' => 'client-profile.php', 'pet' => 'client-pet-profile.php', default => 'clients.php',
    };
    return;
} ?>
<?php if ($section === 'pets') {
    require __DIR__ . '/' . (($petView ?? 'list') === 'profile' ? 'client-pet-profile.php' : 'staff-pets.php');
    return;
} ?>

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
