<?php
$url = static fn(\DateTimeImmutable $date, string $view): string => '/staff/agenda?' . http_build_query(['date' => $date->format('Y-m-d'), 'view' => $view]);
$weekdays = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$step = $agenda['view'] === 'week' ? 7 : 1;
$formatDuration = static fn(int $minutes): string => intdiv($minutes, 60) > 0
    ? intdiv($minutes, 60) . 'h' . ($minutes % 60 ? ' ' . ($minutes % 60) . 'min' : '') : $minutes . 'min';
?>
<div class="staff-agenda" data-staff-agenda>
    <header class="agenda-heading">
        <div><h1>Minha Agenda</h1><p>Seus horários, atendimentos e cuidados de cada pet.</p></div>
        <div class="agenda-controls">
            <nav class="agenda-view-switch" aria-label="Visualização da agenda">
                <?php foreach (['day' => 'Dia', 'week' => 'Semana'] as $mode => $label): ?>
                    <a href="<?= $e($url($agenda['selected'], $mode)) ?>" <?= $agenda['view'] === $mode ? 'aria-current="page"' : '' ?>><?= $label ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="agenda-date-controls">
                <a class="agenda-arrow" href="<?= $e($url($agenda['selected']->modify("-{$step} days"), $agenda['view'])) ?>" aria-label="<?= $step === 7 ? 'Semana anterior' : 'Dia anterior' ?>">‹</a>
                <form method="get" action="/staff/agenda" class="agenda-date-form">
                    <input type="hidden" name="view" value="<?= $e($agenda['view']) ?>">
                    <label for="agenda-date" class="agenda-sr-only">Data da agenda</label>
                    <input id="agenda-date" name="date" type="date" min="1900-01-01" max="2100-12-31" value="<?= $agenda['selected']->format('Y-m-d') ?>" required>
                    <button type="submit">Ir</button>
                </form>
                <a class="agenda-arrow" href="<?= $e($url($agenda['selected']->modify("+{$step} days"), $agenda['view'])) ?>" aria-label="<?= $step === 7 ? 'Próxima semana' : 'Próximo dia' ?>">›</a>
            </div>
            <a class="agenda-today" href="<?= $e($url($agenda['today'], $agenda['view'])) ?>">Hoje</a>
        </div>
    </header>
    <div class="agenda-legend" aria-label="Legenda dos atendimentos">
        <span class="agenda-status-pending">Pendente</span><span class="agenda-status-in_progress">Em andamento</span>
        <span class="agenda-status-completed">Concluído</span><span class="agenda-status-cancelled">Cancelado</span>
    </div>
    <?php if ($agenda['view'] === 'week'): ?>
        <p class="agenda-period">Semana de <?= $agenda['start']->format('d/m/Y') ?> a <?= $agenda['end']->modify('-1 day')->format('d/m/Y') ?></p>
    <?php endif; ?>
    <div class="agenda-days">
        <?php foreach ($agenda['days'] as $day): ?>
            <section class="panel agenda-day" aria-labelledby="day-<?= $day['date']->format('Y-m-d') ?>">
                <header class="agenda-day-heading">
                    <div><h2 id="day-<?= $day['date']->format('Y-m-d') ?>"><?= $agenda['view'] === 'day' ? 'Linha do Tempo Diária' : $e(ucfirst($weekdays[(int) $day['date']->format('w')])) ?></h2>
                        <p><?= $day['date']->format('d/m/Y') ?><?= $day['date'] == $agenda['today'] ? ' · Hoje' : '' ?></p></div>
                    <?php if ($agenda['view'] === 'week'): ?><a href="<?= $e($url($day['date'], 'day')) ?>">Ver dia</a><?php endif; ?>
                </header>
                <ol class="agenda-timeline">
                    <?php foreach ($day['items'] as $item): $row = $item['row']; ?>
                        <li class="agenda-time-row" style="--agenda-block-height: <?= max(64, min(360, (int) (($item['end']->getTimestamp() - $item['start']->getTimestamp()) / 60))) ?>px">
                            <div class="agenda-time"><time datetime="<?= $e($item['start']->format('c')) ?>"><?= $item['start']->format('H:i') ?></time>
                                <small>até <?= $item['end']->format('H:i') ?></small></div>
                            <?php if ($row): require __DIR__ . '/agenda-card.php'; else: ?>
                                <div class="agenda-gap <?= $item['slots'] ? 'is-free' : '' ?>">
                                    <span><?= $item['slots'] ? 'Disponível para agendamento' : ($day['date'] < $agenda['today'] ? 'Sem atendimento neste intervalo' : 'Sem início disponível neste intervalo') ?></span>
                                    <?php if ($item['slots']): ?><small>Inícios: <?= $e(implode(' · ', $item['slots'])) ?> · conforme duração dos serviços</small><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <li class="agenda-closing"><time>18:00</time><span>Encerramento da agenda</span></li>
                </ol>
                <?php if ($day['cancelled']): ?>
                    <div class="agenda-cancelled"><h3>Cancelados — horários liberados</h3>
                        <?php foreach ($day['cancelled'] as $row):
                            $item = ['start' => (new \DateTimeImmutable($row['scheduled_at']))->setTimezone($agenda['today']->getTimezone())];
                            $item['end'] = $item['start']->modify('+' . (int) $row['duration_minutes'] . ' minutes');
                            require __DIR__ . '/agenda-card.php';
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<dialog class="agenda-notice" data-agenda-notice aria-labelledby="agenda-notice-title">
    <h2 id="agenda-notice-title" data-agenda-notice-title>Atendimento atualizado</h2><p>Atualizando sua agenda…</p>
</dialog>
<script src="/assets/js/staff-agenda.js?v=<?= (int) filemtime(dirname(__DIR__, 4) . '/public/assets/js/staff-agenda.js') ?>" defer></script>
