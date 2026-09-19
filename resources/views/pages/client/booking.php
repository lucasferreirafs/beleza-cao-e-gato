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


    <?php if (!empty($error)): ?>
        <div class="alert error" role="alert"><?= $e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/cliente/agendamento/<?= $step ?>">
        <?= \App\Security\Csrf::field() ?>

        <?php if ($step === 1): ?>
            <h2 class="step-title">Escolha o Pet</h2>
            <p class="step-subtitle">Selecione para qual pet você deseja realizar o agendamento.</p>

            <?php if (!$pets): ?>
                <div class="empty-state">
                    <p>Cadastre um pet antes de criar um agendamento.</p>
                    <a class="button primary" href="/cliente/pets/novo">
                        <i class="fa-regular fa-calendar"></i>
                        Cadastrar pet
                    </a>
                </div>
            <?php else: ?>
                <div class="selection-grid">
                    <?php foreach ($pets as $i => $pet): ?>
                        <label class="selection-card <?= (int) ($state['pet_id'] ?? $pets[0]['id']) === (int) $pet['id'] ? 'selected' : '' ?>">

                            <img src="/assets/images/<?= $e($photos[$pet['name']] ?? 'pet-luna.png') ?>" alt="<?= $e($pet['name']) ?>">
                            <input type="radio" name="pet_id" value="<?= (int) $pet['id'] ?>" <?= (int) ($state['pet_id'] ?? $pets[0]['id']) === (int) $pet['id'] ? 'checked' : '' ?> required>

                            <strong><?= $e($pet['name']) ?></strong>
                            <small><?= $e($pet['breed']) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <h2 class="step-title">Escolha o serviço</h2>
            <p class="step-subtitle">Selecione o procedimento estético desejado para <?= $e($selectedPet['name'] ?? 'o seu pet') ?>.</p>

            <div class="service-list">
                <?php foreach ($services as $service): ?>
                    <label class="service-card <?= (int) ($state['service_id'] ?? 0) === (int) $service['id'] ? 'selected' : '' ?>">
                        <input type="radio" name="service_id" value="<?= (int) $service['id'] ?>" <?= (int) ($state['service_id'] ?? 0) === (int) $service['id'] ? 'checked' : '' ?> required>
                        <div class="radio-indicator"></div>
                        <div class="service-info">
                            <strong><?= $e($service['name']) ?></strong>
                            <p><?= $e($service['description'] ?? '') ?></p>
                        </div>
                        <div class="service-meta">
                            <span class="duration">
                                <i class="fa-regular fa-clock"></i>
                                <?php 
                                $min = (int) $service['duration_minutes'];
                                echo $min >= 60 ? floor($min / 60) . 'h ' . ($min % 60 > 0 ? ($min % 60) . 'm' : '') : $min . ' min';
                                ?>
                            </span>
                            <span class="price">R$ <?= number_format((float) $service['price'], 2, ',', '.') ?></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

        <?php elseif ($step === 3): ?>
            <h2 class="step-title">Data e Horário</h2>
            <p class="step-subtitle">Escolha o melhor dia e horário para o atendimento.</p>

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
            <h2 class="step-title">Tem preferência por algum profissional?</h2>
            <p class="step-subtitle">A escolha é opcional. Caso não tenha preferência, atribuiremos o melhor profissional disponível.</p>

            <div class="service-list staff-list">
                <label class="service-card staff-card <?= empty($state['staff_id']) ? 'selected' : '' ?>">
                    <div class="staff-avatar default">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="service-info">
                        <strong>Sem preferência (recomendado)</strong>
                        <p>Atribuiremos o melhor profissional qualificado disponível para o seu pet no horário agendado.</p>
                    </div>
                    <input type="radio" name="staff_id" value="" <?= empty($state['staff_id']) ? 'checked' : '' ?>>
                    <div class="radio-indicator"></div>
                </label>

                <?php foreach ($staff as $member): ?>
                    <?php
                    $initials = mb_substr($member['name'], 0, 1) . mb_substr(explode(' ', $member['name'])[1] ?? '', 0, 1);
                    ?>
                    <label class="service-card staff-card <?= (int) ($state['staff_id'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>">
                        <div class="staff-avatar"><?= $e(strtoupper($initials)) ?></div>
                        <div class="service-info">
                            <strong><?= $e($member['name']) ?></strong>
                            <p>Profissional da unidade focado no bem-estar animal.</p>
                        </div>
                        <input type="radio" name="staff_id" value="<?= (int) $member['id'] ?>"
                               <?= (int) ($state['staff_id'] ?? 0) === (int) $member['id'] ? 'checked' : '' ?>>
                        <div class="radio-indicator"></div>
                    </label>
                <?php endforeach; ?>
            </div>

            <label class="full">
                Observações
                <textarea name="notes" maxlength="1000" placeholder="Cuidados ou informações importantes"><?= $e($state['notes'] ?? '') ?></textarea>
            </label>

        <?php else: ?>
            <h2 class="step-title">Confirme seu agendamento</h2>
            <p class="step-subtitle">Verifique as informações selecionadas e finalize a sua reserva</p>

            <div class="summary-card">
                <header class="summary-header">
                    <h3>Resumo do Agendamento</h3>
                    <span class="badge-success">Pré-Reserva</span>
                </header>

                <dl class="summary-list">
                    <div>
                        <dt>Pet</dt>
                        <dd>
                            <strong><?= $e($selectedPet['name'] ?? '-') ?></strong>
                            <?php if ($selectedPet): ?>
                                <small><?= $e($selectedPet['species'] ?? '') ?> • <?= $e($selectedPet['breed'] ?? '') ?></small>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Serviço</dt>
                        <dd>
                            <strong><?= $e($selectedService['name'] ?? '-') ?></strong>
                            <?php if ($selectedService): ?>
                                <?php $min = (int) $selectedService['duration_minutes']; ?>
                                <small>Duração estimada: <?= $min >= 60 ? floor($min / 60) . 'h ' . ($min % 60 > 0 ? ($min % 60) . 'm' : '') : $min . 'm' ?></small>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Data e Horário</dt>
                        <dd>
                            <strong>
                                <?php
                                $dateObj = !empty($state['date']) ? new DateTime($state['date']) : null;
                                if ($dateObj) {
                                    $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                    echo $dateObj->format('d') . ' de ' . $meses[(int)$dateObj->format('n') - 1] . ' de ' . $dateObj->format('Y');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </strong>
                            <small>Às <?= $e($state['time'] ?? '') ?></small>
                        </dd>
                    </div>
                    <div>
                        <dt>Profissional</dt>
                        <dd>
                            <strong><?= $e($selectedStaff['name'] ?? 'Sem preferência') ?></strong>
                            <small><?= empty($state['staff_id']) ? 'Será selecionado automaticamente' : 'Profissional escolhido pelo cliente' ?></small>
                        </dd>
                    </div>
                    <div>
                        <dt>Valor Total</dt>
                        <dd>
                            <strong class="price-highlight">R$ <?= number_format((float) ($selectedService['price'] ?? 0), 2, ',', '.') ?></strong>
                            <small>Pagamento realizado no pet shop</small>
                        </dd>
                    </div>
                </dl>
            </div>
        <?php endif; ?>

        <footer class="booking-actions">
            <?php if ($step > 1): ?>
                <a class="button ghost" href="/cliente/agendamento/<?= $step - 1 ?>">Voltar</a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <?php if (($step !== 1 || $pets) && ($step !== 3 || $slots)): ?>
                <button class="button primary <?= $step === 5 ? 'success' : '' ?>" type="submit">
                    <?= $step < 5 ? 'Próximo' : 'Confirmar Agendamento' ?>
                </button>
            <?php endif; ?>
        </footer>
    </form>
</section>
