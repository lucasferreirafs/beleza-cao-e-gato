<?php
$query = http_build_query(array_filter(['q' => $search, 'date' => $date], static fn(string $value): bool => $value !== ''));
?>
<div class="staff-appointments-list">
    <header class="page-heading">
        <div><h1>Atendimentos</h1><p>Consulte os atendimentos atribuídos a você e abra um card para ver os serviços.</p></div>
    </header>
    <section class="panel" aria-label="Histórico de atendimentos">
        <form class="staff-appointment-filters" method="get" action="/staff/atendimentos" role="search">
            <label>Buscar
                <input type="search" name="q" value="<?= $e($search) ?>" maxlength="100" placeholder="Buscar por pet, tutor ou serviço...">
            </label>
            <label>Data
                <input type="date" name="date" value="<?= $e($date) ?>">
            </label>
            <button class="button primary" type="submit">Filtrar</button>
            <?php if ($query !== ''): ?><a class="button ghost" href="/staff/atendimentos">Limpar filtros</a><?php endif; ?>
        </form>
        <div class="staff-appointment-results" aria-live="polite">
            <?php if (!$rows): ?>
                <div class="empty-state"><p>Nenhum atendimento encontrado<?= $query !== '' ? ' para os filtros selecionados' : '' ?>.</p></div>
            <?php else: ?>
                <?php foreach ($rows as $row):
                    $scheduled = new \DateTimeImmutable($row['scheduled_at']);
                    $status = (string) $row['status'];
                ?>
                <details class="staff-appointment-item">
                    <summary>
                        <span class="staff-appointment-date"><small>Data / horário</small><strong><?= $scheduled->format('d/m/Y · H:i') ?></strong></span>
                        <span class="staff-appointment-pet"><small>Pet</small><strong><?= $e($row['pet_name']) ?></strong></span>
                        <span class="staff-appointment-tutor"><small>Tutor</small><strong><?= $e($row['client_name']) ?></strong></span>
                        <span class="staff-appointment-status"><small>Status</small><span class="badge <?= $e(\App\Support\StatusBadge::class($status)) ?>"><?= $e(\App\Support\AppointmentStatus::label($status)) ?></span></span>
                        <i class="fa-solid fa-angle-down staff-appointment-chevron staff-appointment-chevron-down" aria-hidden="true"></i>
                        <i class="fa-solid fa-angle-up staff-appointment-chevron staff-appointment-chevron-up" aria-hidden="true"></i>
                    </summary>
                    <div class="staff-appointment-expanded">
                        <div><strong>Serviços contratados</strong><ul>
                            <?php foreach ($row['services'] as $service): ?><li><?= $e($service['name']) ?></li><?php endforeach; ?>
                        </ul></div>
                        <a class="button primary" href="/staff/atendimentos/<?= $e($row['id']) ?><?= $query !== '' ? '?' . $e($query) : '' ?>">Ver detalhes do atendimento</a>
                    </div>
                </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<script src="/assets/js/staff-appointment-list.js?v=<?= (int) filemtime(dirname(__DIR__, 4) . '/public/assets/js/staff-appointment-list.js') ?>" defer></script>
