<header class="page-heading">
    <div>
        <h1>Meus Agendamentos</h1>
        <p>Gerencie as reservas e histórico de estética de seus pets.</p>
    </div>
    <a class="button primary" href="/cliente/agendamento/1">
        <i class="fa-solid fa-plus"></i>
        Novo Agendamento
    </a>
</header>
<section class="panel">
    <?php
        $upcoming = array_filter(
            $appointments,
            static fn(array $a): bool => strtotime($a['scheduled_at']) >= time() && !in_array($a['status'], ['cancelled', 'canceled'], true)
        );

        $completed = array_filter(
            $appointments,
            static fn(array $a): bool => in_array($a['status'], ['completed', 'done'], true)
        );

        $cancelled = array_filter(
            $appointments,
            static fn(array $a): bool => in_array($a['status'], ['cancelled', 'canceled'], true)
        );
    ?>

    <div class="tabs">
        <button class="active" type="button">
            Próximos 
            <b><?= count($upcoming) ?></b>
        </button>
        
        <button type="button">
            Concluídos 
            <b><?= count($completed) ?></b>
        </button>

        <button type="button">
            Cancelados
            <b><?= count($cancelled) ?></b>
        </button>
    </div>

    <div class="filters">
        <select>
            <option>Pet: Todos</option>
        </select>

        <select>
            <option>Serviço: Todos</option>
        </select>

        <select>
            <option>Data: Todos</option>
        </select>

        <select>
            <option>Status: Todos</option>
        </select></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>Serviço</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Funcionário</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['pet_name']) ?></strong></td>
                        <td><?= htmlspecialchars($a['service_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($a['scheduled_at'])) ?></td>
                        <td><?= date('H:i', strtotime($a['scheduled_at'])) ?></td>
                        <td><?= htmlspecialchars($a['staff_name'] ?? 'Sem preferência') ?></td>
                        <td>R$ <?= number_format((float) $a['price'], 2, ',', '.') ?></td>
                        <td><span
                                class="badge <?= \App\Support\StatusBadge::class($a['status']) ?>"><?= htmlspecialchars(\App\Support\AppointmentStatus::label($a['status'])) ?></span>
                        </td>
                        <td>
                            <?php if (strtotime($a['scheduled_at']) > time() && in_array($a['status'], ['pending', 'confirmed', 'scheduled'], true)): ?>
                                <form method="post" action="/cliente/agendamentos/<?= (int) $a['id'] ?>/cancelar" data-confirm="Cancelar este agendamento?">
                                    <?= \App\Security\Csrf::field() ?>
                                    <button class="button ghost danger">Cancelar</button>
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

    <?php if (!$appointments): ?>
        <div class="empty-state">
            <p>Você ainda não possui agendamentos.</p>
            <a class="button primary" href="/cliente/agendamento/1">Criar primeiro agendamento</a>
        </div>
    <?php endif; ?>

    <footer class="pagination">
        <span class="pagination-count">
            Mostrando <?= count($appointments) ?>
            agendamentos
        </span>

        <span class="pagination-nav">
            <button class="btn-pagination prev">
                <i class="fa-solid fa-angle-left"></i>
            </button>
            <button class="btn-pagination active">
                1
            </button>
            <button class="btn-pagination">
                2
            </button>
            <button class="btn-pagination next">
                <i class="fa-solid fa-angle-right"></i>
            </button>
        </span>
    </footer>
</section>