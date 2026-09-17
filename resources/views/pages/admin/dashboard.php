<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>

<header class="page-heading">
    <div>
        <h1>Visão Geral do Admin 🐾</h1>
        <p>Métricas operacionais consolidadas da Beleza Cão&amp;Gato.</p>
    </div>
</header>

<section class="metric-grid four">
    <article class="metric-card featured">
        <small>CLIENTES</small>
        <strong><?= (int) ($counts['clients'] ?? 0) ?></strong>
        <p>Contas cadastradas</p>
    </article>

    <article class="metric-card">
        <small>PETS CADASTRADOS</small>
        <strong><?= (int) ($counts['pets'] ?? 0) ?></strong>
        <p>Registros ativos</p>
    </article>

    <article class="metric-card">
        <small>FUNCIONÁRIOS</small>
        <strong><?= (int) ($counts['staff'] ?? 0) ?></strong>
        <p>Profissionais ativos</p>
    </article>

    <article class="metric-card">
        <small>AGENDAMENTOS HOJE</small>
        <strong><?= (int) ($counts['appointments_today'] ?? 0) ?></strong>
        <p>Todos os estados</p>
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
                        <td><span class="badge info"><?= $e($row['status']) ?></span></td>
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
