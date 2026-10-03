<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$firstName = explode(' ', trim((string) ($user['name'] ?? 'Profissional')))[0];
$periodLabel = $period === 'week' ? 'Esta semana' : 'Hoje';
$formatMinutes = static fn(int $minutes): string => $minutes < 60
    ? $minutes . ' min'
    : intdiv($minutes, 60) . ' h' . ($minutes % 60 ? ' ' . ($minutes % 60) . ' min' : '');
?>

<header class="page-heading">
    <div>
        <h1>Olá, <?= $e($firstName) ?> 👋</h1>
        <p>Seu próximo atendimento e os resultados da sua agenda.</p>
    </div>
</header>

<script src="/assets/js/staff-dashboard.js?v=<?= (int) filemtime(dirname(__DIR__, 4) . '/public/assets/js/staff-dashboard.js') ?>" defer></script>
<script src="/assets/js/staff-appointments.js?v=<?= (int) filemtime(dirname(__DIR__, 4) . '/public/assets/js/staff-appointments.js') ?>" defer></script>

<section class="staff-next" aria-labelledby="staff-next-title" <?= $nextAppointment ? 'data-appointment-id="' . $e($nextAppointment['id']) . '"' : '' ?>>
    <div class="staff-next-icon" aria-hidden="true"><i class="fa-solid fa-paw"></i></div>
    <div class="staff-next-content">
        <span class="staff-eyebrow" id="staff-next-title">Próximo atendimento</span>
        <?php if ($nextAppointment):
            $scheduled = new \DateTimeImmutable($nextAppointment['scheduled_at']);
            $scheduled = $scheduled->setTimezone($now->getTimezone());
            $seconds = $scheduled->getTimestamp() - $now->getTimestamp();
            $remaining = $seconds > 0 ? 'Em ' . $formatMinutes((int) ceil($seconds / 60))
                : ($seconds > -60 ? 'Horário do atendimento' : 'Atrasado há ' . $formatMinutes((int) floor(-$seconds / 60)));
        ?>
            <h2><?= $e($nextAppointment['pet_name']) ?></h2>
            <p class="staff-next-services"><?= $e($nextAppointment['service_name']) ?></p>
            <div class="staff-next-details">
                <span><i class="fa-regular fa-calendar" aria-hidden="true"></i>
                    <time datetime="<?= $e($scheduled->format('c')) ?>"><?= $e($scheduled->format('d/m/Y · H:i')) ?></time>
                </span>
                <span><i class="fa-regular fa-clock" aria-hidden="true"></i> Duração prevista: <?= $e($formatMinutes((int) $nextAppointment['duration_minutes'])) ?></span>
            </div>
        <?php else: ?>
            <h2>Nenhum atendimento pendente</h2>
            <p>Seus próximos agendamentos atribuídos aparecerão aqui.</p>
        <?php endif; ?>
    </div>
    <?php if ($nextAppointment): ?>
        <div class="staff-next-timing">
            <span class="staff-next-countdown" data-staff-countdown
                data-scheduled="<?= $scheduled->getTimestamp() ?>" data-server-now="<?= $now->getTimestamp() ?>"><?= $e($remaining) ?></span>
            <small>Aguardando início</small>
            <a href="/staff?period=<?= $e($period) ?>" data-staff-refresh hidden>Atualizar agenda</a>
        </div>
    <?php endif; ?>
</section>

<div class="staff-metrics-heading">
    <div>
        <h2 id="staff-metrics-title">Seus atendimentos</h2>
        <p><?= $e($periodLabel) ?> · <?= $e($periodStart->format('d/m/Y')) ?><?= $period === 'week' ? ' a ' . $e($periodEnd->modify('-1 day')->format('d/m/Y')) : '' ?></p>
    </div>
    <form method="get" action="/staff" class="staff-period-filter">
        <label for="staff-period">Período dos indicadores</label>
        <div>
            <select name="period" id="staff-period">
                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hoje</option>
                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Esta Semana</option>
            </select>
            <button type="submit" class="button primary">Aplicar</button>
        </div>
    </form>
</div>
<section class="metric-grid four" aria-labelledby="staff-metrics-title">
    <article class="metric-card featured">
        <div>
            <small>TOTAL DE AGENDAMENTOS</small>
            <strong><?= $counts['total'] ?></strong>
            <p><?= $e($periodLabel) ?> · Atribuídos a você</p>
        </div>

        <span>
            <i class="fa-solid fa-calendar-days"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>CONCLUÍDOS</small>
            <strong data-staff-metric="completed"><?= $counts['completed'] ?></strong>
            <p>Finalizados no período</p>
        </div>

        <span>
            <i class="fa-regular fa-circle-check"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>PENDENTES</small>
            <strong data-staff-metric="pending"><?= $counts['pending'] ?></strong>
            <p>Aguardando início</p>
        </div>

        <span>
            <i class="fa-regular fa-clock"></i>
        </span>
    </article>

    <article class="metric-card">
        <div>
            <small>CANCELADOS</small>
            <strong><?= $counts['cancelled'] ?></strong>
            <p>Cancelados no período</p>
        </div>

        <span>
            <i class="fa-solid fa-ban"></i>
        </span>
    </article>
</section>

<section class="panel" data-staff-appointments>
    <header>
        <h2>Minha Agenda — Hoje</h2>
        <a class="button primary staff-agenda-cta" href="/staff/agenda"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i> Ver Agenda Completa</a>
    </header>

    <div class="table-wrap staff-agenda-table-wrap">
        <table class="staff-agenda-table">
            <thead>
                <tr>
                    <th>Horário</th>
                    <th>Pet</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $row): ?>
                    <?php $allergies = trim((string) ($row['pet_allergies'] ?? '')); $needs = trim((string) ($row['pet_special_needs'] ?? '')); ?>
                    <tr data-staff-appointment-row data-id="<?= $e($row['id']) ?>" data-status="pending">
                        <td data-label="Horário"><?= date('H:i', strtotime($row['scheduled_at'])) ?></td>
                        <td data-label="Pet">
                            <div class="staff-agenda-pet">
                                <button type="button" class="staff-pet-details-button" data-pet-details
                                    data-name="<?= $e($row['pet_name']) ?>"
                                    data-weight="<?= $e($row['pet_weight'] ?? '') ?>"
                                    data-coat="<?= $e($row['pet_coat_color'] ?? '') ?>"
                                    data-allergies="<?= $e($allergies) ?>"
                                    data-needs="<?= $e($needs) ?>"
                                    data-behavior="<?= $e($row['pet_behavior'] ?? '') ?>"
                                    data-notes="<?= $e($row['pet_notes'] ?? '') ?>"
                                    aria-haspopup="dialog" aria-controls="staff-pet-summary">
                                    <strong><?= $e($row['pet_name']) ?></strong>
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i><span class="visually-hidden">Ver resumo do pet</span>
                                </button>
                                <?php if ($allergies !== '' || $needs !== ''): ?>
                                    <span class="staff-health-alert" title="Há alertas de saúde cadastrados; abra o resumo do pet."
                                        aria-label="Alerta de saúde cadastrado; consulte o resumo do pet.">
                                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                        <span class="visually-hidden">Alerta de saúde cadastrado</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Cliente"><?= $e($row['client_name']) ?></td>
                        <td data-label="Serviço"><?= $e($row['service_name']) ?></td>
                        <td data-label="Status"><span
                                class="badge <?= \App\Support\StatusBadge::class($row['status']) ?>"><?= $e(\App\Support\AppointmentStatus::label($row['status'])) ?></span>
                        </td>
                        <td class="staff-row-actions" data-label="Ação">
                            <form method="post" action="/staff/agendamentos/<?= $e($row['id']) ?>/status" data-staff-status-form>
                                <?= \App\Security\Csrf::field() ?>
                                <input type="hidden" name="period" value="<?= $e($period) ?>">
                                <input type="hidden" name="status" value="in_progress">
                                <button type="submit" class="button success"><i class="fa-solid fa-play" aria-hidden="true"></i> Iniciar atendimento</button>
                            </form>
                            <span class="staff-row-feedback" data-row-feedback role="status" hidden></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$appointments): ?>
        <div class="empty-state" data-staff-empty>
            <p>Nenhum atendimento pendente para hoje.</p>
        </div>
    <?php endif; ?>
</section>

<?php if ($inProgressAppointments): ?>
    <section class="panel staff-in-progress-panel" aria-labelledby="staff-in-progress-title" data-staff-in-progress>
        <header><h2 id="staff-in-progress-title">Em andamento</h2></header>
        <div class="staff-in-progress-list">
            <?php foreach ($inProgressAppointments as $row): ?>
                <?php $allergies = trim((string) ($row['pet_allergies'] ?? '')); $needs = trim((string) ($row['pet_special_needs'] ?? '')); ?>
                <article class="staff-in-progress-item" data-staff-appointment-row data-status="in_progress" data-id="<?= $e($row['id']) ?>">
                    <div class="staff-progress-overview">
                        <span class="staff-progress-time"><?= date('H:i', strtotime($row['scheduled_at'])) ?></span>
                        <button type="button" class="staff-pet-details-button" data-pet-details
                            data-name="<?= $e($row['pet_name']) ?>" data-weight="<?= $e($row['pet_weight'] ?? '') ?>"
                            data-coat="<?= $e($row['pet_coat_color'] ?? '') ?>" data-allergies="<?= $e($allergies) ?>"
                            data-needs="<?= $e($needs) ?>" data-behavior="<?= $e($row['pet_behavior'] ?? '') ?>"
                            data-notes="<?= $e($row['pet_notes'] ?? '') ?>" aria-haspopup="dialog" aria-controls="staff-pet-summary">
                            <strong><?= $e($row['pet_name']) ?></strong><i class="fa-regular fa-eye" aria-hidden="true"></i>
                            <span class="visually-hidden">Ver resumo do pet</span>
                        </button>
                        <?php if ($allergies !== '' || $needs !== ''): ?>
                            <span class="staff-health-alert" aria-label="Alerta de saúde cadastrado; consulte o resumo do pet." title="Alerta de saúde cadastrado">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="visually-hidden">Alerta de saúde cadastrado</span>
                            </span>
                        <?php endif; ?>
                        <span><?= $e($row['client_name']) ?></span>
                        <span><?= $e($row['service_name']) ?></span>
                        <span class="badge status-progress" data-status-badge>Em andamento</span>
                    </div>
                    <form method="post" action="/staff/agendamentos/<?= $e($row['id']) ?>/status" data-staff-status-form>
                        <?= \App\Security\Csrf::field() ?>
                        <input type="hidden" name="period" value="<?= $e($period) ?>">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="button primary"><i class="fa-solid fa-check" aria-hidden="true"></i> Concluir atendimento</button>
                    </form>
                    <span class="staff-row-feedback" data-row-feedback role="status" hidden></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<dialog class="staff-pet-summary" id="staff-pet-summary" aria-labelledby="staff-pet-summary-title" data-staff-pet-summary>
    <header>
        <div><span class="staff-eyebrow">Resumo do pet</span><h2 id="staff-pet-summary-title" data-summary-name></h2></div>
        <button type="button" class="staff-summary-close" data-summary-close aria-label="Fechar resumo">&times;</button>
    </header>
    <dl>
        <div><dt>Peso</dt><dd data-summary-weight></dd></div>
        <div><dt>Cor do pelo</dt><dd data-summary-coat></dd></div>
        <div data-summary-allergies-row><dt>Alergias</dt><dd data-summary-allergies></dd></div>
        <div data-summary-needs-row><dt>Necessidades e cuidados</dt><dd data-summary-needs></dd></div>
        <div data-summary-behavior-row><dt>Comportamento</dt><dd data-summary-behavior></dd></div>
        <div data-summary-notes-row><dt>Observações</dt><dd data-summary-notes></dd></div>
    </dl>
</dialog>

<dialog class="staff-status-notice" aria-labelledby="staff-status-notice-title" aria-describedby="staff-status-notice-message" role="status" data-staff-status-notice>
    <span class="staff-status-notice-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
    <h2 id="staff-status-notice-title">Atendimento iniciado</h2>
    <p id="staff-status-notice-message">Atualizando a agenda…</p>
</dialog>
