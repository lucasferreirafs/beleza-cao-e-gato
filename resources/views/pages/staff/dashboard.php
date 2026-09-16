<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$firstName = explode(' ', trim((string) ($user['name'] ?? 'Profissional')))[0];
?>

<header class="page-heading">
    <div>
        <h1>Olá, <?= $e($firstName) ?> 👋</h1>
        <p>Aqui está o resumo dos seus atendimentos e agenda de hoje.</p>
    </div>
</header>

<section class="metric-grid four">
    <article class="metric-card featured">
        <small>ATENDIMENTOS HOJE</small>
        <strong><?= $counts['total'] ?></strong>
        <p>Agenda atribuída</p>
    </article>

    <article class="metric-card">
        <small>CONCLUÍDOS</small>
        <strong><?= $counts['completed'] ?></strong>
        <p>Finalizados hoje</p>
    </article>

    <article class="metric-card">
        <small>PENDENTES</small>
        <strong><?= $counts['pending'] ?></strong>
        <p>Aguardando ação</p>
    </article>

    <article class="metric-card">
        <small>CANCELADOS</small>
        <strong><?= $counts['cancelled'] ?></strong>
        <p>No dia de hoje</p>
    </article>
</section>

<section class="panel">
    <header>
        <h2>Minha Agenda — Hoje</h2>
        <a href="/staff/agenda">Ver Agenda Completa →</a>
    </header>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Horário</th>
                    <th>Pet</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $row): ?>
                    <tr>
                        <td><?= date('H:i', strtotime($row['scheduled_at'])) ?></td>
                        <td><strong><?= $e($row['pet_name']) ?></strong></td>
                        <td><?= $e($row['client_name']) ?></td>
                        <td><?= $e($row['service_name']) ?></td>
                        <td><span class="badge info"><?= $e($row['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$appointments): ?>
        <div class="empty-state">
            <p>Nenhum atendimento atribuído para hoje.</p>
        </div>
    <?php endif; ?>
</section>
