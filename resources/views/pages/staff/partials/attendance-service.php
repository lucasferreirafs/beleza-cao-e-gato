<?php
$scheduled = new \DateTimeImmutable($appointment['scheduled_at']);
$minutes = (int) $appointment['duration_minutes'];
$duration = $minutes >= 60 ? intdiv($minutes, 60) . 'h' . ($minutes % 60 ? ' ' . ($minutes % 60) . 'min' : '') : $minutes . 'min';
$status = (string) $appointment['status'];
$nextStatus = $status === 'pending' ? 'in_progress' : ($status === 'in_progress' ? 'completed' : null);
?>
<section class="staff-attendance-panel staff-attendance-service" aria-labelledby="staff-attendance-service-heading">
    <h2 id="staff-attendance-service-heading">Serviço Contratado</h2>
    <dl class="staff-attendance-service-facts">
        <div><dt>Serviços</dt><dd><ul><?php foreach ($appointment['services'] as $service): ?><li><?= $e($service['name']) ?></li><?php endforeach; ?></ul></dd></div>
        <div><dt>Duração prevista</dt><dd><?= $e($duration) ?></dd></div>
        <div><dt>Horário agendado</dt><dd><time datetime="<?= $e($scheduled->format('c')) ?>"><?= $scheduled->format('d/m/Y · H:i') ?></time></dd></div>
        <div><dt>Valor total</dt><dd class="staff-attendance-price"><?= $e(\App\Support\PetHelper::formatPrice($appointment['price'])) ?></dd></div>
    </dl>
    <div class="staff-attendance-state"><h3>Status do atendimento</h3><span class="staff-attendance-badge <?= $e(\App\Support\StatusBadge::class($status)) ?>"><?= $e(\App\Support\AppointmentStatus::label($status)) ?></span></div>
    <?php if ($status !== 'cancelled'): ?>
        <ol class="staff-attendance-steps" aria-label="Etapas do atendimento">
            <?php foreach (['pending' => 'Pendente', 'in_progress' => 'Em atendimento', 'completed' => 'Concluído'] as $step => $label):
                $reached = array_search($status, ['pending', 'in_progress', 'completed'], true) >= array_search($step, ['pending', 'in_progress', 'completed'], true);
            ?><li class="<?= $reached ? 'is-reached' : '' ?> <?= $status === $step ? 'is-current' : '' ?>" <?= $status === $step ? 'aria-current="step"' : '' ?>><?= $e($label) ?></li><?php endforeach; ?>
        </ol>
    <?php endif; ?>
    <div class="staff-attendance-observation"><h3>Observações do agendamento</h3><p><?= $e(trim((string) ($appointment['notes'] ?? '')) ?: 'Nenhuma observação registrada.') ?></p></div>
    <?php if ($nextStatus !== null): ?>
        <form method="post" action="/staff/agendamentos/<?= (int) $appointment['id'] ?>/status" data-attendance-status-form>
            <?= \App\Security\Csrf::field() ?>
            <input type="hidden" name="source" value="atendimento">
            <input type="hidden" name="status" value="<?= $e($nextStatus) ?>">
            <button class="staff-attendance-action" type="submit"><?= $nextStatus === 'in_progress' ? 'Iniciar Atendimento' : 'Concluir Atendimento' ?></button>
            <p class="staff-attendance-feedback" data-attendance-feedback role="alert" hidden></p>
        </form>
    <?php endif; ?>
</section>
