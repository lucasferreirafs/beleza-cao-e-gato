<?php
declare(strict_types=1);

use App\Support\PetHelper;
use App\Support\StatusBadge;

$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$petName = (string) ($pet['name'] ?? 'Pet');
$species = (string) ($pet['species'] ?? 'Pet');
$breed = (string) ($pet['breed'] ?? '');
$sex = (string) ($pet['sex'] ?? 'Fêmea');
$age = PetHelper::age($pet['birth_date'] ?? null);
$photo = PetHelper::photo($pet);
$genderLabel = PetHelper::genderLabel($sex);
$genderClass = PetHelper::genderBadgeClass($sex);

$subtitleParts = array_filter([
    $species,
    $breed,
    $age
]);
$subtitle = implode(' • ', $subtitleParts);

$castratedLabel = PetHelper::castratedLabel($sex);
$isCastrated = !empty($pet['neutered']) ? 'Sim' : 'Não';
$weightFormatted = PetHelper::formatWeight($pet['weight'] ?? null);
$birthFormatted = PetHelper::formatDate($pet['birth_date'] ?? null);

$coatColor = trim((string) ($pet['coat_color'] ?? '')) !== ''
    ? (string) $pet['coat_color']
    : 'Não informada';

$allergies = !empty($pet['allergies']) 
    ? (string) $pet['allergies'] 
    : 'Nenhuma registrada';

$specialNeeds = !empty($pet['special_needs']) 
    ? (string) $pet['special_needs'] 
    : 'Nenhuma';

$careNotes = !empty($pet['notes'])
    ? (string) $pet['notes']
    : (!empty($pet['special_needs'])
        ? (string) $pet['special_needs']
        : (!empty($pet['behavior'])
            ? 'Comportamento: ' . (string) $pet['behavior']
            : 'Nenhuma observação de cuidado registrada para este pet.'));
?>

<div class="pet-profile-container">
    <!-- 1. Header do Pet -->
    <header class="pet-profile-header">
        <div class="pet-profile-identity">
            <div class="pet-profile-avatar-wrap">
                <img class="pet-profile-avatar" src="<?= $e($photo) ?>" alt="<?= $e($petName) ?>">
            </div>

            <div class="pet-profile-meta">
                <div class="pet-profile-name-row">
                    <h1 class="pet-profile-name"><?= $e($petName) ?></h1>
                    <span class="pet-gender-badge <?= $genderClass ?>"><?= $e($genderLabel) ?></span>
                </div>

                <p class="pet-profile-subtitle">
                    <?= $e($subtitle) ?>
                </p>
            </div>
        </div>

        <div class="pet-profile-header-actions">
            <a href="/cliente/pets/<?= $e($pet['id']) ?>/editar" class="pet-profile-btn-outline" title="Editar informações do pet">
                <i class="fa-solid fa-pen"></i>
                <span>Editar Pet</span>
            </a>
        </div>
    </header>

    <!-- 2. Grid de Informações Básicas (6 Cards) -->
    <section class="pet-profile-grid" aria-label="Informações Básicas do Pet">
        <div class="pet-stat-card">
            <span class="pet-stat-label">Nascimento</span>
            <span class="pet-stat-value"><?= $e($birthFormatted) ?></span>
        </div>

        <div class="pet-stat-card">
            <span class="pet-stat-label">Peso</span>
            <span class="pet-stat-value"><?= $e($weightFormatted) ?></span>
        </div>

        <div class="pet-stat-card">
            <span class="pet-stat-label">Cor do Pelo</span>
            <span class="pet-stat-value"><?= $e($coatColor) ?></span>
        </div>

        <div class="pet-stat-card">
            <span class="pet-stat-label"><?= $e($castratedLabel) ?></span>
            <span class="pet-stat-value"><?= $e($isCastrated) ?></span>
        </div>

        <div class="pet-stat-card">
            <span class="pet-stat-label">Alergias</span>
            <span class="pet-stat-value"><?= $e($allergies) ?></span>
        </div>

        <div class="pet-stat-card">
            <span class="pet-stat-label">Necessidades Especiais</span>
            <span class="pet-stat-value"><?= $e($specialNeeds) ?></span>
        </div>
    </section>

    <!-- 3. Observações de Cuidado -->
    <article class="pet-profile-card" aria-labelledby="care-observations-title">
        <header class="pet-profile-card-header">
            <h2 class="pet-profile-card-title" id="care-observations-title">Observações de Cuidado</h2>
        </header>

        <p class="pet-care-text">
            <?= nl2br($e($careNotes)) ?>
        </p>
    </article>

    <!-- 4. Histórico de Atendimentos -->
    <article class="pet-profile-card" aria-labelledby="history-appointments-title">
        <header class="pet-profile-card-header">
            <h2 class="pet-profile-card-title" id="history-appointments-title">Histórico de Atendimentos</h2>
        </header>

        <?php if (!empty($appointments)): ?>
            <div class="pet-table-wrap">
                <table class="pet-history-table">
                    <thead>
                        <tr>
                            <th scope="col">Data</th>
                            <th scope="col">Serviço</th>
                            <th scope="col">Funcionário</th>
                            <th scope="col">Valor</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $item): 
                            $rawStatus = (string) ($item['status'] ?? 'pending');
                            $statusLabel = PetHelper::statusLabel($rawStatus);
                            $statusBadgeClass = StatusBadge::class($rawStatus);
                            $staffDisplay = !empty($item['staff_name']) ? (string) $item['staff_name'] : 'Sem preferência';
                            $dateDisplay = !empty($item['scheduled_at']) ? date('d/m/Y', strtotime((string) $item['scheduled_at'])) : '-';
                            $serviceDisplay = (string) ($item['service_name'] ?? 'Serviço');
                            $priceDisplay = PetHelper::formatPrice($item['price'] ?? 0);
                        ?>
                            <tr>
                                <td class="pet-cell-date"><?= $e($dateDisplay) ?></td>
                                <td class="pet-cell-service"><?= $e($serviceDisplay) ?></td>
                                <td class="pet-cell-staff"><?= $e($staffDisplay) ?></td>
                                <td class="pet-cell-price"><?= $e($priceDisplay) ?></td>
                                <td>
                                    <span class="pet-status-pill <?= $e($statusBadgeClass) ?>">
                                        <?= $e($statusLabel) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="pet-history-empty">
                <div class="pet-history-empty-icon" aria-hidden="true">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h3>Nenhum atendimento registrado</h3>
                <p>Quando <?= $e($petName) ?> realizar banho, tosa ou outro serviço, o histórico completo de atendimentos aparecerá aqui.</p>
                <a href="/cliente/agendamento/servico" class="button primary">
                    <i class="fa-solid fa-plus"></i>
                    Agendar Atendimento
                </a>
            </div>
        <?php endif; ?>
    </article>
</div>
