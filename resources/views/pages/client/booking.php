<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$photos = ['Luna' => 'pet-luna.png', 'Mel' => 'pet-mel.png', 'Toddy' => 'pet-toddy.png'];
$selectedPet = current(array_filter($pets, static fn(array $p): bool => (int) $p['id'] === (int) ($state['pet_id'] ?? 0))) ?: null;
$selectedService = current(array_filter($services, static fn(array $s): bool => (int) $s['id'] === (int) ($state['service_id'] ?? 0))) ?: null;
$selectedStaff = current(array_filter($staff, static fn(array $u): bool => (int) $u['id'] === (int) ($state['staff_id'] ?? 0))) ?: null;
?>

<section class="stepper">
    <?php foreach (['Pet', 'Serviço', 'Data e Horário', 'Profissional', 'Confirmação'] as $i => $name): ?>
        <span class="<?= $step === ($i + 1) ? 'active' : ($step > $i + 1 ? 'done' : '') ?>">
            <b><?= $step > $i + 1 ? '✓' : $i + 1 ?></b>
            <small>PASSO 0<?= $i + 1 ?></small>
            <strong><?= $e($name) ?></strong>
        </span>
    <?php endforeach; ?>
</section>

<section class="panel booking">
    <h1>Novo Agendamento</h1>

    <?php if (!empty($error)): ?>
        <div class="alert error" role="alert"><?= $e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/cliente/agendamento/<?= $step ?>">
        <?= \App\Security\Csrf::field() ?>

        <?php if ($step === 1): ?>
            <p>Escolha o pet para o atendimento</p>

            <?php if (!$pets): ?>
                <div class="empty-state">
                    <p>Cadastre um pet antes de criar um agendamento.</p>
                    <a class="button primary" href="/cliente/pets/novo">Cadastrar pet</a>
                </div>
            <?php else: ?>
                <div class="selection-grid">
                    <?php foreach ($pets as $i => $pet): ?>
                        <label class="selection-card <?= (int) ($state['pet_id'] ?? $pets[0]['id']) === (int) $pet['id'] ? 'selected' : '' ?>">
                            <img src="/assets/images/<?= $e($photos[$pet['name']] ?? 'pet-luna.png') ?>"
                                 alt="<?= $e($pet['name']) ?>">
                            <input type="radio" name="pet_id" value="<?= (int) $pet['id'] ?>"
                                   <?= (int) ($state['pet_id'] ?? $pets[0]['id']) === (int) $pet['id'] ? 'checked' : '' ?> required>
                            <strong><?= $e($pet['name']) ?></strong>
                            <small><?= $e($pet['breed']) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <p>Selecione o serviço desejado</p>

            <div class="selection-grid">
                <?php foreach ($services as $service): ?>
                    <label class="selection-card <?= (int) ($state['service_id'] ?? 0) === (int) $service['id'] ? 'selected' : '' ?>">
                        <input type="radio" name="service_id" value="<?= (int) $service['id'] ?>"
                               <?= (int) ($state['service_id'] ?? 0) === (int) $service['id'] ? 'checked' : '' ?> required>
                        <strong><?= $e($service['name']) ?></strong>
                        <small>
                            <?= (int) $service['duration_minutes'] ?> min •
                            R$ <?= number_format((float) $service['price'], 2, ',', '.') ?>
                        </small>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php elseif ($step === 3): ?>
            <p>Escolha uma data e um horário disponível</p>

            <div class="form-grid">
                <label>
                    Data
                    <input type="date" name="date" value="<?= $e($date) ?>"
                           min="<?= date('Y-m-d') ?>" data-booking-date required>
                </label>

                <label>
                    Horário
                    <select name="time" required>
                        <option value="">Selecione</option>
                        <?php foreach ($slots as $slot): ?>
                            <option value="<?= $e($slot) ?>" <?= ($state['time'] ?? '') === $slot ? 'selected' : '' ?>>
                                <?= $e($slot) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$slots): ?>
                        <small>Nenhum horário disponível nesta data.</small>
                    <?php endif; ?>
                </label>
            </div>

        <?php elseif ($step === 4): ?>
            <p>Escolha o profissional ou deixe sem preferência</p>

            <div class="selection-grid">
                <label class="selection-card <?= empty($state['staff_id']) ? 'selected' : '' ?>">
                    <input type="radio" name="staff_id" value="" <?= empty($state['staff_id']) ? 'checked' : '' ?>>
                    <strong>Sem preferência</strong>
                    <small>Primeiro profissional disponível</small>
                </label>

                <?php foreach ($staff as $member): ?>
                    <label class="selection-card <?= (int) ($state['staff_id'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>">
                        <input type="radio" name="staff_id" value="<?= (int) $member['id'] ?>"
                               <?= (int) ($state['staff_id'] ?? 0) === (int) $member['id'] ? 'checked' : '' ?>>
                        <strong><?= $e($member['name']) ?></strong>
                        <small>Profissional da unidade</small>
                    </label>
                <?php endforeach; ?>
            </div>

            <label class="full">
                Observações
                <textarea name="notes" maxlength="1000" placeholder="Cuidados ou informações importantes"><?= $e($state['notes'] ?? '') ?></textarea>
            </label>

        <?php else: ?>
            <p>Revise os dados antes de confirmar.</p>

            <dl class="summary-list">
                <div>
                    <dt>Pet</dt>
                    <dd><?= $e($selectedPet['name'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt>Serviço</dt>
                    <dd><?= $e($selectedService['name'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt>Data</dt>
                    <dd><?= !empty($state['date']) ? date('d/m/Y', strtotime($state['date'])) . ' às ' . $e($state['time']) : '-' ?></dd>
                </div>
                <div>
                    <dt>Profissional</dt>
                    <dd><?= $e($selectedStaff['name'] ?? 'Primeiro disponível') ?></dd>
                </div>
                <div>
                    <dt>Total</dt>
                    <dd>R$ <?= number_format((float) ($selectedService['price'] ?? 0), 2, ',', '.') ?></dd>
                </div>
            </dl>
        <?php endif; ?>

        <footer class="booking-actions">
            <?php if ($step > 1): ?>
                <a class="button secondary" href="/cliente/agendamento/<?= $step - 1 ?>">Voltar</a>
            <?php endif; ?>

            <?php if (($step !== 1 || $pets) && ($step !== 3 || $slots)): ?>
                <button class="button primary" type="submit">
                    <?= $step < 5 ? 'Próximo Passo' : 'Confirmar Agendamento' ?>
                </button>
            <?php endif; ?>
        </footer>
    </form>
</section>
