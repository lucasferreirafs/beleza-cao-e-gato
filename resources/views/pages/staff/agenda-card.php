<?php
$status = $row['status'];
$label = \App\Support\AppointmentStatus::label($status);
$allergies = trim((string) ($row['pet_allergies'] ?? ''));
$needs = trim((string) ($row['pet_special_needs'] ?? ''));
$age = 'Não informada';
if (!empty($row['pet_birth_date'])) {
    $birth = new \DateTimeImmutable($row['pet_birth_date']);
    if ($birth <= $agenda['today']) {
        $difference = $birth->diff($agenda['today']);
        $age = $difference->y ? $difference->y . ' ano(s)' : $difference->m . ' mês(es)';
    }
}
?>
<details class="agenda-card agenda-status-<?= $e($status) ?>" data-appointment="<?= $e($row['id']) ?>">
    <summary>
        <strong class="agenda-pet-name"><?= $e($row['pet_name']) ?><?php if ($allergies !== '' || $needs !== ''): ?><span class="agenda-health-alert" aria-label="Alerta de saúde"> ⚠</span><?php endif; ?></strong>
        <span class="agenda-service-name"><?= $e($row['service_name']) ?></span>
        <span class="agenda-tutor">Tutor: <?= $e($row['client_name']) ?></span>
        <span class="agenda-duration"><?= $e($formatDuration((int) $row['duration_minutes'])) ?></span>
        <span class="agenda-status-label"><?= $e($label) ?></span>
        <i class="fa-solid fa-angle-down agenda-chevron agenda-chevron-down" aria-hidden="true"></i>
        <i class="fa-solid fa-angle-up agenda-chevron agenda-chevron-up" aria-hidden="true"></i>
    </summary>
    <div class="agenda-card-details">
        <p class="agenda-service-time">Horário previsto: <?= $item['start']->format('H:i') ?>–<?= $item['end']->format('H:i') ?></p>
        <?php if ($allergies !== '' || $needs !== ''): ?>
            <div class="agenda-care-alert" role="note">
                <?php if ($allergies !== ''): ?><p><strong>Alergias:</strong> <?= $e($allergies) ?></p><?php endif; ?>
                <?php if ($needs !== ''): ?><p><strong>Cuidados específicos:</strong> <?= $e($needs) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
        <dl class="agenda-pet-data">
            <?php foreach (['Espécie / raça' => trim(($row['pet_species'] ?? '') . ' / ' . ($row['pet_breed'] ?? ''), ' /'),
                'Sexo' => $row['pet_sex'] ?? '', 'Idade' => $age,
                'Peso' => isset($row['pet_weight']) ? $row['pet_weight'] . ' kg' : '', 'Cor do pelo' => $row['pet_coat_color'] ?? '',
                'Castrado' => in_array($row['pet_neutered'] ?? null, [true, 1, '1', 't'], true) ? 'Sim' : 'Não',
                'Comportamento' => $row['pet_behavior'] ?? '', 'Observações do pet' => $row['pet_notes'] ?? ''] as $field => $value): ?>
                <div><dt><?= $e($field) ?></dt><dd><?= $e($value !== '' ? $value : 'Não informado') ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <h3>Serviços deste atendimento</h3>
        <ul class="agenda-services">
            <?php foreach ($row['services'] as $service): ?><li><span><?= $e($service['name']) ?></span><strong><?= $e($formatDuration((int) $service['duration_minutes'])) ?></strong></li><?php endforeach; ?>
        </ul>
        <p class="agenda-observation"><strong>Observação do agendamento:</strong> <?= $e(trim((string) ($row['notes'] ?? '')) !== '' ? $row['notes'] : 'Nenhuma observação informada.') ?></p>
        <p class="agenda-contact"><strong>Tutor:</strong> <?= $e($row['client_name']) ?>
            <?php if (!empty($row['client_phone'])): ?><span>Telefone: <?= $e($row['client_phone']) ?></span><?php endif; ?>
            <?php if (!empty($row['client_email'])): ?><span>E-mail: <?= $e($row['client_email']) ?></span><?php endif; ?></p>
        <?php if (in_array($status, ['pending', 'in_progress'], true)): ?>
            <form method="post" action="/staff/agendamentos/<?= $e($row['id']) ?>/status" data-agenda-status-form>
                <?= \App\Security\Csrf::field() ?>
                <input type="hidden" name="source" value="agenda">
                <input type="hidden" name="date" value="<?= $agenda['selected']->format('Y-m-d') ?>">
                <input type="hidden" name="view" value="<?= $e($agenda['view']) ?>">
                <input type="hidden" name="status" value="<?= $status === 'pending' ? 'in_progress' : 'completed' ?>">
                <button type="submit" class="button primary"><?= $status === 'pending' ? 'Iniciar atendimento' : 'Concluir atendimento' ?></button>
                <p data-agenda-feedback role="status" hidden></p>
                <a href="<?= $e($url($agenda['selected'], $agenda['view'])) ?>" data-agenda-refresh hidden>Atualizar agenda</a>
            </form>
        <?php endif; ?>
    </div>
</details>
