<header class="page-heading">
    <div>
        <h1>Meus Agendamentos</h1>
        <p>Gerencie as reservas e histórico de estética de seus pets.</p>
    </div>
    <a class="button primary" href="/cliente/agendamento/servico">
        <i class="fa-solid fa-plus"></i>
        Novo Agendamento
    </a>
</header>
<section class="panel">
    <?php
        $cancelledStatuses = ['cancelled'];
        $completedStatuses = ['completed'];

        $isCancelledFn  = static fn(array $a): bool => in_array($a['status'], $cancelledStatuses, true);
        $isCompletedFn  = static fn(array $a): bool => in_array($a['status'], $completedStatuses, true);
        $isUpcomingFn   = static fn(array $a): bool => in_array($a['status'], ['pending', 'in_progress'], true);

        $upcoming  = array_filter($appointments, $isUpcomingFn);
        $completed = array_filter($appointments, $isCompletedFn);
        $cancelled = array_filter($appointments, $isCancelledFn);

        $pets = array_unique(array_column($appointments, 'pet_name'));
        $statuses = array_unique(array_map(fn($a) => \App\Support\AppointmentStatus::label($a['status']), $appointments));
        sort($pets);
        sort($statuses);
    ?>

    <div class="tabs" id="appointment-tabs">
        <button class="active" type="button" data-tab-btn="upcoming">
            Próximos 
            <b><?= count($upcoming) ?></b>
        </button>
        
        <button type="button" data-tab-btn="completed">
            Concluídos 
            <b><?= count($completed) ?></b>
        </button>

        <button type="button" data-tab-btn="cancelled">
            Cancelados
            <b><?= count($cancelled) ?></b>
        </button>
    </div>

    <div class="filters filters-container">
        <input type="text" data-table-filter class="filter-input" placeholder="Buscar por pet, serviço, funcionário ou status...">
        
        <select data-pet-filter class="filter-select">
            <option value="">Pet: Todos</option>
            <?php foreach ($pets as $pet): ?>
                <option value="<?= htmlspecialchars((string) $pet) ?>"><?= htmlspecialchars((string) $pet) ?></option>
            <?php endforeach; ?>
        </select>

        <select data-status-filter class="filter-select">
            <option value="">Status: Todos</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars((string) $status) ?>"><?= htmlspecialchars((string) $status) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="table-wrap">
        <table data-filter-table>
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
                <?php foreach ($appointments as $a): 
                    $isUpcoming  = $isUpcomingFn($a);
                    $isCompleted = $isCompletedFn($a);
                    $isCancelled = $isCancelledFn($a);
                    
                    $tab = 'other';
                    if ($isUpcoming) $tab = 'upcoming';
                    elseif ($isCompleted) $tab = 'completed';
                    elseif ($isCancelled) $tab = 'cancelled';
                    
                    $statusLabel = \App\Support\AppointmentStatus::label($a['status']);
                    $staffName = $a['staff_name'] ?? 'Sem preferência';
                ?>
                    <tr class="appointment-row" 
                        data-tab="<?= $tab ?>" 
                        data-pet="<?= htmlspecialchars((string) $a['pet_name']) ?>" 
                        data-status="<?= htmlspecialchars((string) $statusLabel) ?>">
                        <td><strong><?= htmlspecialchars($a['pet_name']) ?></strong></td>
                        <td><?= htmlspecialchars($a['service_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($a['scheduled_at'])) ?></td>
                        <td><?= date('H:i', strtotime($a['scheduled_at'])) ?></td>
                        <td><?= htmlspecialchars($staffName) ?></td>
                        <td>R$ <?= number_format((float) $a['price'], 2, ',', '.') ?></td>
                        <td><span
                                class="badge <?= \App\Support\StatusBadge::class($a['status']) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                        </td>
                        <td>
                            <?php if ($a['status'] === 'pending' && strtotime($a['scheduled_at']) > time()): ?>
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
            <a class="button primary" href="/cliente/agendamento/servico">Criar primeiro agendamento</a>
        </div>
    <?php endif; ?>

    <footer class="pagination hidden">
        <span class="pagination-count" data-pagination-info>
            Mostrando 0 agendamentos
        </span>

        <span class="pagination-nav" data-pagination-controls>
            <!-- Renderizado via JS -->
        </span>
    </footer>
</section>
