<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>

<header class="page-heading">
    <div>
        <h1>Visão Geral do Admin 🐾</h1>
        <p>Métricas operacionais consolidadas da Beleza Cão&amp;Gato.</p>
    </div>
</header>

<section class="metric-grid four">
    <article class="metric-card featured">
        <div>
            <small>TOTAL DE CLIENTES</small>
            <strong><?= (int) ($counts['clients'] ?? 0) ?> Clientes</strong>
            <p>Contas cadastradas no sistema</p>
        </div>

        <span>
            <i class="fa-solid fa-users"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>PETS CADASTRADOS</small>
            <strong><?= (int) ($counts['pets'] ?? 0) ?> Pets</strong>
            <p>Registros ativos</p>
        </div>

        <span>
            <i class="fa-solid fa-paw"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>FUNCIONÁRIOS</small>
            <strong><?= (int) ($counts['staff'] ?? 0) ?> Funcionário</strong>
            <p>Profissionais ativos</p>
        </div>

        <span>
            <i class="fa-solid fa-users-line"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>AGENDAMENTOS HOJE</small>
            <strong><?= (int) ($counts['appointments_today'] ?? 0) ?> Agendamentos</strong>
            <p>Todos os agendamentos do dia</p>
        </div>

        <span>
            <i class="fa-regular fa-calendar-days"></i>
        </span>
    </article>
</section>

<div class="charts-grid">
    <section class="panel">
        <h2>Serviços Mais Utilizados</h2>

        <?php
        $max = max(1, ...array_map(static fn(array $row): int => (int) $row['total'], $serviceStats));
        foreach ($serviceStats as $i => $row):
        ?>
            <div class="progress">
                <span>
                    <b><?= $i + 1 ?>. <?= $e($row['name']) ?></b>
                    <?= (int) $row['total'] ?> agendamentos
                </span>
                <i style="--progress:<?= round(((int) $row['total'] / $max) * 100) ?>%"></i>
            </div>
        <?php endforeach; ?>

        <?php if (!$serviceStats): ?>
            <div class="empty-state">Sem dados de serviços.</div>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <h2>Resumo operacional</h2>

        <div class="summary-box">
            <small>Cadastros monitorados</small>
            <strong><?= (int) ($counts['clients'] ?? 0) + (int) ($counts['pets'] ?? 0) ?></strong>
        </div>

        <div class="summary-box">
            <small>Equipe ativa</small>
            <strong><?= (int) ($counts['staff'] ?? 0) ?></strong>
        </div>
    </aside>
</div>

<section class="panel">
    <h2>Agendamentos Recentes</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Pet</th>
                    <th>Serviço</th>
                    <th>Funcionário</th>
                    <th>Data</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><strong><?= $e($row['client_name']) ?></strong></td>
                        <td><?= $e($row['pet_name']) ?></td>
                        <td><?= $e($row['service_name']) ?></td>
                        <td><?= $e($row['staff_name'] ?? 'A definir') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['scheduled_at'])) ?></td>
                        <td><span class="badge <?= \App\Support\StatusBadge::class($row['status']) ?>"><?= $e(\App\Support\AppointmentStatus::label($row['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$recent): ?>
        <div class="empty-state">
            <p>Nenhum agendamento cadastrado.</p>
        </div>
    <?php endif; ?>
</section>