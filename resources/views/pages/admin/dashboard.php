<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<?php
$chartColors = ['#df4b17', '#194654', '#ffac16', '#8b9b97'];
$monthCount = count($monthlyStats);
$chartWidth = 700;
$chartTop = 20;
$chartBottom = 190;
$chartMax = max([1, ...array_map(static fn(array $month): int => (int) $month['total'], $monthlyStats)]);
$chartStep = $monthCount > 1 ? $chartWidth / ($monthCount - 1) : 0;
$chartPoints = [];
foreach ($monthlyStats as $index => $month) {
    $x = $index * $chartStep;
    $y = $chartBottom - ((int) $month['total'] / $chartMax) * ($chartBottom - $chartTop);
    $chartPoints[] = ['x' => $x, 'y' => $y, 'month' => $month];
}
$linePoints = implode(' ', array_map(
    static fn(array $point): string => number_format($point['x'], 2, '.', '') . ',' . number_format($point['y'], 2, '.', ''),
    $chartPoints
));
$donutCircumference = 2 * M_PI * 72;
$donutOffset = 0.0;
$today = new DateTimeImmutable('now', new DateTimeZone((string) \App\Core\Env::get('APP_TIMEZONE', 'America/Sao_Paulo')));
$formatDuration = static function (int $minutes): string {
    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;
    if ($hours === 0) return $remainingMinutes . ' min';
    return $hours . ' h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . ' min' : '');
};
?>

<header class="page-heading">
    <div>
        <h1>Visão Geral do Admin 🐾</h1>
        <p>Acompanhe a operação e os atendimentos de hoje.</p>
    </div>
</header>

<section class="admin-daily-overview" aria-labelledby="daily-overview-heading">
    <header class="admin-daily-heading">
        <div>
            <h2 id="daily-overview-heading">Resumo operacional</h2>
            <p><?= $e($today->format('d/m/Y')) ?> · <?= (int) ($todayStats['total'] ?? 0) ?> agendamentos no dia</p>
        </div>
        <a class="button primary admin-agenda-link" href="/admin/agendamentos">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
            Ver agenda completa
        </a>
    </header>

    <div class="metric-grid four admin-status-grid" aria-label="Agendamentos de hoje por status">
        <article class="metric-card admin-status-card pending">
            <div><small>PENDENTES</small><strong><?= (int) ($todayStats['pending'] ?? 0) ?></strong><p>Aguardando início</p></div>
            <span aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
        </article>
        <article class="metric-card admin-status-card in-progress">
            <div><small>EM ATENDIMENTO</small><strong><?= (int) ($todayStats['in_progress'] ?? 0) ?></strong><p>Em execução agora</p></div>
            <span aria-hidden="true"><i class="fa-solid fa-scissors"></i></span>
        </article>
        <article class="metric-card admin-status-card completed">
            <div><small>CONCLUÍDOS</small><strong><?= (int) ($todayStats['completed'] ?? 0) ?></strong><p>Finalizados hoje</p></div>
            <span aria-hidden="true"><i class="fa-regular fa-circle-check"></i></span>
        </article>
        <article class="metric-card admin-status-card cancelled">
            <div><small>CANCELADOS</small><strong><?= (int) ($todayStats['cancelled'] ?? 0) ?></strong><p>Cancelados hoje</p></div>
            <span aria-hidden="true"><i class="fa-solid fa-ban"></i></span>
        </article>
    </div>

    <div class="admin-highlights-grid">
        <section class="panel admin-next-appointment" aria-labelledby="next-appointment-heading">
            <div class="admin-highlight-heading">
                <span class="admin-highlight-icon" aria-hidden="true"><i class="fa-solid fa-paw"></i></span>
                <div><h3 id="next-appointment-heading">Próximo atendimento</h3><p>O próximo agendamento pendente de hoje</p></div>
            </div>
            <?php if ($nextAppointment): ?>
                <?php $scheduledAt = (new DateTimeImmutable((string) $nextAppointment['scheduled_at']))->setTimezone($today->getTimezone()); ?>
                <div class="admin-next-appointment-content">
                    <div class="admin-next-pet">
                        <time datetime="<?= $e($scheduledAt->format(DateTimeInterface::ATOM)) ?>"><?= $e($scheduledAt->format('H:i')) ?></time>
                        <strong><?= $e($nextAppointment['pet_name']) ?></strong>
                    </div>
                    <p class="admin-next-service"><?= $e($nextAppointment['service_names']) ?></p>
                    <dl class="admin-next-meta">
                        <div><dt>Tutor</dt><dd><?= $e($nextAppointment['client_name']) ?></dd></div>
                        <div><dt>Duração prevista</dt><dd><?= $e($formatDuration((int) $nextAppointment['duration_minutes'])) ?></dd></div>
                        <div><dt>Profissional</dt><dd><?= $e($nextAppointment['staff_name'] ?: 'A definir') ?></dd></div>
                    </dl>
                </div>
            <?php else: ?>
                <div class="admin-next-empty"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i><p>Não há atendimentos pendentes restantes para hoje.</p></div>
            <?php endif; ?>
        </section>

        <aside class="panel admin-care-alert <?= $petsNeedingAttention > 0 ? 'has-alerts' : 'all-clear' ?>" aria-labelledby="care-alert-heading">
            <div class="admin-highlight-heading">
                <span class="admin-highlight-icon" aria-hidden="true"><i class="fa-solid <?= $petsNeedingAttention > 0 ? 'fa-triangle-exclamation' : 'fa-heart' ?>"></i></span>
                <div><h3 id="care-alert-heading">Cuidados especiais</h3><p>Informações relevantes para a equipe</p></div>
            </div>
            <?php if ($petsNeedingAttention > 0): ?>
                <strong class="admin-care-count"><?= (int) $petsNeedingAttention ?> <?= $petsNeedingAttention === 1 ? 'pet precisa' : 'pets precisam' ?> de atenção</strong>
                <p><?= $petsNeedingAttention === 1 ? 'Possui alergia ou necessidade especial' : 'Possuem alergias ou necessidades especiais' ?> em atendimentos de hoje.</p>
            <?php else: ?>
                <strong class="admin-care-count">Nenhum alerta registrado</strong>
                <p>Os atendimentos de hoje não possuem alergias ou necessidades especiais cadastradas.</p>
            <?php endif; ?>
            <a href="/admin/agendamentos" class="admin-care-link">Ver atendimentos <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </aside>
    </div>
</section>

<div class="charts-grid">
    <section class="panel chart-panel" aria-labelledby="appointments-chart-heading">
        <header class="chart-heading">
            <h2 id="appointments-chart-heading">Agendamentos por Período (Últimos 6 meses)</h2>
            <span class="chart-period">Mensal</span>
        </header>

        <?php if ($monthlyStats): ?>
            <div class="line-chart-wrap">
                <p class="chart-tooltip" data-chart-tooltip="appointments" role="status" aria-live="polite" hidden></p>
                <svg class="admin-line-chart" viewBox="-4 0 708 230" role="group" aria-label="Agendamentos por mês nos últimos seis meses">
                    <line class="chart-gridline" x1="0" y1="20" x2="700" y2="20" aria-hidden="true" />
                    <line class="chart-gridline" x1="0" y1="76.5" x2="700" y2="76.5" aria-hidden="true" />
                    <line class="chart-gridline" x1="0" y1="133" x2="700" y2="133" aria-hidden="true" />
                    <line class="chart-gridline" x1="0" y1="190" x2="700" y2="190" aria-hidden="true" />
                    <?php if ($chartPoints): ?>
                        <polyline class="chart-line" points="<?= $e($linePoints) ?>" aria-hidden="true" />
                        <?php foreach ($chartPoints as $point): ?>
                            <circle class="chart-point" cx="<?= number_format($point['x'], 2, '.', '') ?>" cy="<?= number_format($point['y'], 2, '.', '') ?>" r="5"
                                tabindex="0" role="img" data-chart-point data-chart-name="appointments"
                                data-chart-key="<?= $e($point['month']['key']) ?>"
                                data-chart-label="<?= $e($point['month']['full_label']) ?>"
                                data-chart-value="<?= (int) $point['month']['total'] ?>"
                                data-chart-detail="<?= $e($point['month']['full_label'] . ': ' . (int) $point['month']['total'] . ' agendamentos') ?>"
                                aria-label="<?= $e($point['month']['full_label'] . ': ' . (int) $point['month']['total'] . ' agendamentos') ?>">
                                <title><?= $e($point['month']['full_label'] . ': ' . (int) $point['month']['total'] . ' agendamentos') ?></title>
                            </circle>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php foreach ($chartPoints as $index => $point): ?>
                        <text class="chart-axis-label" x="<?= number_format($point['x'], 2, '.', '') ?>" y="220" text-anchor="<?= $index === 0 ? 'start' : ($index === $monthCount - 1 ? 'end' : 'middle') ?>"><?= $e($point['month']['label']) ?></text>
                    <?php endforeach; ?>
                </svg>
            </div>
        <?php else: ?>
            <div class="empty-state chart-empty">Não há meses disponíveis para exibir.</div>
        <?php endif; ?>
    </section>

    <section class="panel chart-panel service-chart-panel" aria-labelledby="services-chart-heading">
        <header class="chart-heading">
            <h2 id="services-chart-heading">Distribuição dos Serviços</h2>
        </header>

        <?php if ($serviceTotal > 0): ?>
            <p class="chart-tooltip" data-chart-tooltip="services" role="status" aria-live="polite" hidden></p>
            <div class="donut-chart-wrap">
                <svg class="admin-donut-chart" viewBox="0 0 200 200" role="group" aria-label="Distribuição de <?= (int) $serviceTotal ?> serviços. Use Tab para explorar as categorias.">
                    <?php foreach ($serviceDistribution as $index => $service): ?>
                        <?php
                        $fraction = $service['total'] / $serviceTotal;
                        $segmentLength = $fraction * $donutCircumference;
                        $color = $chartColors[$index % count($chartColors)];
                        $segmentDetail = $service['name'] . ': ' . $service['total'] . ' serviços (' . $service['percentage'] . '%)';
                        ?>
                        <circle class="donut-segment" cx="100" cy="100" r="72" fill="none"
                            stroke="<?= $color ?>" stroke-width="26"
                            stroke-dasharray="<?= number_format($segmentLength, 3, '.', '') ?> <?= number_format($donutCircumference - $segmentLength, 3, '.', '') ?>"
                            stroke-dashoffset="<?= number_format(-$donutOffset, 3, '.', '') ?>"
                            transform="rotate(-90 100 100)"
                            tabindex="0" role="img" data-chart-point data-chart-name="services"
                            data-chart-key="<?= $e($service['id']) ?>"
                            data-chart-label="<?= $e($service['name']) ?>"
                            data-chart-value="<?= (int) $service['total'] ?>"
                            data-chart-detail="<?= $e($segmentDetail) ?>"
                            aria-label="<?= $e($segmentDetail) ?>">
                            <title><?= $e($segmentDetail) ?></title>
                        </circle>
                        <?php $donutOffset += $segmentLength; ?>
                    <?php endforeach; ?>
                </svg>
                <div class="donut-center" aria-hidden="true">
                    <strong><?= (int) $serviceTotal ?></strong>
                    <span>Serviços</span>
                </div>
            </div>

            <ul class="service-chart-legend" aria-label="Categorias de serviços; selecione uma categoria para destacar sua fatia">
                <?php foreach ($serviceDistribution as $index => $service): ?>
                    <?php $color = $chartColors[$index % count($chartColors)]; ?>
                    <li>
                        <button type="button" class="service-legend-item" data-chart-point data-chart-name="services"
                            data-chart-key="<?= $e($service['id']) ?>"
                            data-chart-label="<?= $e($service['name']) ?>"
                            data-chart-value="<?= (int) $service['total'] ?>"
                            data-chart-detail="<?= $e($service['name'] . ': ' . (int) $service['total'] . ' serviços (' . (int) $service['percentage'] . '%)') ?>"
                            aria-label="<?= $e($service['name'] . ', ' . (int) $service['total'] . ' serviços, ' . (int) $service['percentage'] . ' por cento. Destacar no gráfico.') ?>">
                            <span class="legend-dot" style="--legend-color:<?= $color ?>" aria-hidden="true"></span>
                            <span class="legend-name"><?= $e($service['name']) ?> <small>(<?= (int) $service['percentage'] ?>%)</small></span>
                            <strong><?= (int) $service['total'] ?></strong>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state chart-empty">Ainda não há serviços contratados para distribuir.</div>
        <?php endif; ?>
    </section>
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

<script src="/assets/js/admin-dashboard.js?v=<?= hash_file('sha256', dirname(__DIR__, 4) . '/public/assets/js/admin-dashboard.js') ?>" defer></script>
