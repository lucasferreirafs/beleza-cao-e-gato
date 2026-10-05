<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$stepNames = ['Serviços', 'Pet', 'Data e Horário', 'Confirmação'];
$stepSlugs = ['servico', 'escolha-pet', 'data-e-horario', 'confirmacao'];
$serviceIds = $state['service_ids'] ?? [];
$selectedPet = current(array_filter($pets, static fn(array $pet): bool => $pet['id'] === ($state['pet_id'] ?? null))) ?: null;
$selectedStaff = current(array_filter($staff, static fn(array $member): bool => $member['id'] === ($state['staff_id'] ?? null))) ?: null;
$availabilityById = [];
foreach ($staffAvailability as $availability) $availabilityById[$availability['id']] = $availability;
$selectedTimeAvailable = $time !== '' && in_array($time, $slots, true);
$formatDuration = static function (int $minutes): string {
    if ($minutes < 60) return $minutes . ' min';
    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    return $hours . 'h' . ($remaining ? ' ' . $remaining . ' min' : '');
};
?>

<section class="stepper" aria-label="Etapas do agendamento">
    <?php foreach ($stepNames as $index => $name): $number = $index + 1; ?>
        <span class="<?= $step === $number ? 'active' : ($step > $number ? 'done' : '') ?>">
            <b><?= $step > $number ? '✓' : $number ?></b>
            <small>PASSO 0<?= $number ?></small>
            <strong><?= $e($name) ?></strong>
        </span>
    <?php endforeach; ?>
</section>

<section class="panel booking">
    <?php if (!empty($error)): ?><div class="alert error" role="alert"><?= $e($error) ?></div><?php endif; ?>

    <form method="post" action="/cliente/agendamento/<?= $stepSlugs[$step - 1] ?>"
        data-booking-form data-availability-url="/cliente/agendamento/disponibilidade"
        <?php if ($step === 3): ?>data-availability-rendered="true" data-rendered-date="<?= $e($date) ?>" data-rendered-time="<?= $e($time) ?>"<?php endif; ?>>
        <?= \App\Security\Csrf::field() ?>

        <?php if ($step === 1): ?>
            <h2 class="step-title">Escolha os serviços</h2>
            <p class="step-subtitle">Você pode combinar serviços para o mesmo atendimento.</p>
            <div class="service-list booking-service-list">
                <?php foreach ($services as $service): $checked = in_array($service['id'], $serviceIds, true); ?>
                    <label class="service-card booking-service-card <?= $checked ? 'selected' : '' ?>">
                        <input type="checkbox" name="service_ids[]" value="<?= $e($service['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                        <span class="radio-indicator booking-service-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                        <span class="service-info"><strong><?= $e($service['name']) ?></strong><span><?= $e($service['description'] ?? '') ?></span></span>
                        <span class="service-meta">
                            <small><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $e($formatDuration((int) $service['duration_minutes'])) ?></small>
                            <strong>R$ <?= number_format((float) $service['price'], 2, ',', '.') ?></strong>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="booking-hint">Selecione pelo menos um serviço (máximo de <?= \App\Services\BookingService::MAX_SERVICES ?>).</small>

        <?php elseif ($step === 2): ?>
            <h2 class="step-title">Escolha o pet</h2>
            <p class="step-subtitle">Selecione para qual pet serão feitos os serviços.</p>
            <?php if (!$pets): ?>
                <div class="empty-state"><p>Cadastre um pet antes de criar um agendamento.</p><a class="button primary" href="/cliente/pets/novo">Cadastrar pet</a></div>
            <?php else: ?>
                <div class="selection-grid">
                    <?php foreach ($pets as $pet): $checked = ($state['pet_id'] ?? null) === $pet['id']; ?>
                        <label class="selection-card <?= $checked ? 'selected' : '' ?>">
                            <img src="<?= $e(\App\Support\PetHelper::photo($pet)) ?>" alt="<?= $e($pet['name']) ?>" loading="lazy" decoding="async">
                            <input type="radio" name="pet_id" value="<?= $e($pet['id']) ?>" <?= $checked ? 'checked' : '' ?> required>
                            <strong><?= $e($pet['name']) ?></strong><small><?= $e($pet['breed']) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($step === 3): ?>
            <h2 class="step-title">Data, horário e profissional</h2>
            <p class="step-subtitle">Escolha o horário para consultar a disponibilidade da equipe e, se quiser, selecione um profissional.</p>
            <div class="form-grid booking-date-grid">
                <label>Data
                    <input type="date" name="date" value="<?= $e($date) ?>" min="<?= date('Y-m-d') ?>" data-booking-date required>
                </label>
                <label>Horário
                    <select name="time" data-booking-time required>
                        <option value="">Selecione</option>
                        <?php foreach ($slots as $slot): ?>
                            <option value="<?= $e($slot) ?>" <?= $time === $slot ? 'selected' : '' ?>><?= $e($slot) ?></option>
                        <?php endforeach; ?>
                        <?php if ($time !== '' && !$selectedTimeAvailable): ?>
                            <option value="<?= $e($time) ?>" selected disabled><?= $e($time) ?> · Indisponível</option>
                        <?php endif; ?>
                    </select>
                    <small data-slot-status role="status"><?= $slots ? '' : 'Nenhum horário disponível nesta data.' ?></small>
                </label>
            </div>
            <section class="staff-slot-status" aria-labelledby="staff-slot-title" data-staff-slot-status
                data-selected-staff="<?= $e($state['staff_id'] ?? '') ?>" <?= $time === '' ? 'hidden' : '' ?>>
                <h3 id="staff-slot-title">Profissionais neste horário</h3>
                <p data-staff-slot-message role="status">
                    <?php if ($time !== '' && !$selectedTimeAvailable): ?>Este horário não está mais disponível. Escolha outro.<?php endif; ?>
                </p>
                <div class="staff-list" role="radiogroup" aria-label="Profissionais disponíveis para este horário" data-slot-staff-list>
                    <label class="service-card staff-card staff-card--preference <?= empty($state['staff_id']) && $selectedTimeAvailable ? 'selected' : '' ?>">
                        <span class="staff-avatar default"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                        <span class="service-info"><strong>Sem preferência (recomendado)</strong><span>Atribuiremos o melhor profissional qualificado disponível para o seu pet no horário agendado.</span></span>
                        <input type="radio" name="staff_id" value="" <?= empty($state['staff_id']) && $selectedTimeAvailable ? 'checked' : '' ?> <?= $selectedTimeAvailable ? '' : 'disabled' ?>>
                        <span class="radio-indicator" aria-hidden="true"></span>
                    </label>
                    <?php foreach ($staff as $member):
                        $memberId = $member['id'];
                        $isAvailable = isset($availabilityById[$memberId]) && in_array($availabilityById[$memberId]['available'], [true, 1, '1', 't', 'true'], true);
                        $isSelected = ($state['staff_id'] ?? null) === $memberId;
                    ?>
                        <label class="service-card staff-card staff-card--person <?= $isSelected && $isAvailable ? 'selected' : '' ?> <?= $isAvailable ? '' : 'unavailable' ?>" data-staff-option data-staff-id="<?= $e($memberId) ?>">
                            <span class="staff-avatar">
                                <?php if (!empty($member['avatar_path'])): ?><img src="/cliente/profissionais/<?= $e($memberId) ?>/foto?v=<?= $e(rawurlencode((string) $member['avatar_path'])) ?>" alt="" loading="lazy" decoding="async">
                                <?php else: ?><i class="fa-solid fa-user" aria-hidden="true"></i><?php endif; ?>
                            </span>
                            <span class="staff-name"><?= $e($member['name']) ?></span>
                            <span class="staff-unavailable-badge" <?= $isAvailable ? 'hidden' : '' ?>>Indisponível</span>
                            <input type="radio" name="staff_id" value="<?= $e($memberId) ?>" <?= $isSelected && $isAvailable ? 'checked' : '' ?> <?= $isAvailable ? '' : 'disabled' ?>>
                            <span class="radio-indicator" aria-hidden="true"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
            <p class="booking-buffer-note">Mantemos 30 minutos de intervalo entre atendimentos do mesmo profissional ou pet.</p>
            <label class="booking-notes">Observações
                <textarea name="notes" maxlength="1000" rows="3" placeholder="Cuidados ou informações importantes"><?= $e($state['notes'] ?? '') ?></textarea>
            </label>

        <?php else: ?>
            <h2 class="step-title">Confirme seu agendamento</h2>
            <p class="step-subtitle">Confira os serviços, o valor total e as informações antes de reservar.</p>
            <div class="summary-card">
                <header class="summary-header"><h3>Resumo do Agendamento</h3><span class="badge-success">Pré-reserva</span></header>
                <dl class="summary-list">
                    <div><dt>Pet</dt><dd><strong><?= $e($selectedPet['name'] ?? '—') ?></strong>
                        <?php if ($selectedPet): ?><small><?= $e($selectedPet['species']) ?> · <?= $e($selectedPet['breed']) ?></small><?php endif; ?>
                    </dd></div>
                    <div><dt>Serviços</dt><dd>
                        <?php foreach ($selectedServices as $service): ?>
                            <strong><?= $e($service['name']) ?> <span>R$ <?= number_format((float) $service['price'], 2, ',', '.') ?></span></strong>
                        <?php endforeach; ?>
                        <small>Duração total: <?= $e($formatDuration($totalDuration)) ?></small>
                    </dd></div>
                    <div><dt>Data e horário</dt><dd><strong><?= $e(date('d/m/Y', strtotime($state['date'] ?? ''))) ?> às <?= $e($state['time'] ?? '') ?></strong></dd></div>
                    <div><dt>Profissional</dt><dd><strong><?= $e($selectedStaff['name'] ?? 'Sem preferência') ?></strong>
                        <small><?= empty($state['staff_id']) ? 'Será atribuído um profissional disponível.' : 'Profissional selecionado.' ?></small>
                    </dd></div>
                    <?php if (($state['notes'] ?? '') !== ''): ?><div><dt>Observações</dt><dd><strong class="summary-notes"><?= nl2br($e($state['notes'])) ?></strong></dd></div><?php endif; ?>
                    <div><dt>Valor total</dt><dd><strong class="price-highlight">R$ <?= number_format($totalPrice, 2, ',', '.') ?></strong><small>Pagamento realizado no pet shop</small></dd></div>
                </dl>
            </div>
        <?php endif; ?>

        <footer class="booking-actions">
            <?php if ($step > 1): ?><a class="button ghost" href="/cliente/agendamento/<?= $stepSlugs[$step - 2] ?>">Voltar</a><?php else: ?><span></span><?php endif; ?>
            <?php if ($step !== 2 || $pets): ?>
                <button class="button primary <?= $step === 4 ? 'success' : '' ?>" type="submit"><?= $step < 4 ? 'Próximo' : 'Confirmar Agendamento' ?></button>
            <?php endif; ?>
        </footer>
    </form>
</section>
