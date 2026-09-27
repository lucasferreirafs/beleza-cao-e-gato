<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn(string $key, mixed $default = ''): string => $e($pet[$key] ?? $default);
$allergyDescription = trim((string) ($pet['allergies'] ?? ''));
$hasAllergies = $allergyDescription !== '';
$petPhoto = !empty($pet['photo_path']) ? \App\Support\PetHelper::photo($pet) : '';
?>

<header class="page-heading">
    <div>
        <h1><?= $pet ? 'Editar Pet' : 'Cadastrar Novo Pet' ?></h1>
        <p>Insira as informações básicas e adicionais do pet.</p>
    </div>
</header>

<form class="panel pet-form" method="post" enctype="multipart/form-data" action="<?= $e($formAction) ?>" data-pet-form>
    <?= \App\Security\Csrf::field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

    <h2>1. Informações Básicas</h2>

    <div class="upload-row pet-photo-upload">
        <div class="photo-placeholder">
            <img data-pet-photo-preview <?= $petPhoto ? 'src="' . $e($petPhoto) . '"' : '' ?> alt="Foto de <?= $value('name', 'perfil do pet') ?>" <?= $petPhoto ? '' : 'hidden' ?>>
            <i class="fa-solid fa-camera" data-pet-photo-placeholder <?= $petPhoto ? 'hidden' : '' ?>></i>
        </div>
        <label class="button teal pet-photo-label" for="pet-photo"><i class="fa-solid fa-camera" aria-hidden="true"></i> <?= $petPhoto ? 'Trocar imagem' : 'Adicionar imagem' ?></label>
        <input class="pet-photo-input" id="pet-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <small id="pet-photo-help">JPG, PNG ou WebP, até 5 MB.</small>
        <small class="pet-photo-error" data-pet-photo-error role="alert" hidden></small>
    </div>

    <div class="form-grid">
        <label>
            Nome do Pet *
            <input name="name" value="<?= $value('name') ?>" maxlength="80" required>
        </label>

        <label>
            Espécie *
            <select name="species" required>
                <?php foreach (['Cachorro', 'Gato'] as $option): ?>
                    <option <?= $value('species', 'Cachorro') === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Raça *
            <input name="breed" value="<?= $value('breed') ?>" maxlength="100" required>
        </label>

        <fieldset>
            <legend>Sexo *</legend>
            <?php foreach (['Fêmea', 'Macho'] as $option): ?>
                <label class="radio">
                    <input type="radio" name="sex" value="<?= $option ?>" <?= $value('sex', 'Fêmea') === $option ? 'checked' : '' ?>>
                    <?= $option ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <label>
            Data de Nascimento
            <input type="text" name="birth_date" inputmode="numeric" placeholder="dd/mm/aaaa" value="<?= $value('birth_date') ? date('d/m/Y', strtotime($value('birth_date'))) : '' ?>">
        </label>

        <label>
            Peso aproximado (kg)
            <input name="weight" type="number" step=".1" min="0.1" max="9999" value="<?= $value('weight') ?>">
        </label>
    </div>

    <hr>

    <h2>2. Informações Adicionais</h2>

    <div class="form-grid">
        <fieldset class="pet-toggle-field">
            <legend>Castrado?</legend>
            <label class="pet-toggle"><input type="checkbox" name="neutered" value="1" <?= !empty($pet['neutered']) ? 'checked' : '' ?>><span class="pet-toggle-track" aria-hidden="true"></span><span>Sim, castrado</span></label>
        </fieldset>

        <fieldset class="full pet-allergies-field">
            <legend>Alergias</legend>
            <label class="pet-toggle">
                <input type="checkbox" name="has_allergies" value="1" data-allergies-toggle aria-controls="pet-allergy-details" aria-expanded="<?= $hasAllergies ? 'true' : 'false' ?>" <?= $hasAllergies ? 'checked' : '' ?>>
                <span class="pet-toggle-track" aria-hidden="true"></span><span>Sim, tem alergia</span>
            </label>
            <div id="pet-allergy-details" class="pet-allergy-details" <?= $hasAllergies ? '' : 'hidden' ?>>
                <label for="pet-allergy-description">Quais são as alergias?
                    <textarea id="pet-allergy-description" name="allergies_other" maxlength="1200" rows="3" placeholder="Descreva alimentos, medicamentos ou outras substâncias que causam alergia..." <?= $hasAllergies ? 'required' : 'disabled' ?>><?= $e($allergyDescription) ?></textarea>
                </label>
            </div>
        </fieldset>

        <label>
            Comportamento dominante
            <select name="behavior">
                <?php foreach (['Dócil e Brincalhão', 'Tranquilo', 'Curioso', 'Reativo'] as $option): ?>
                    <option <?= $value('behavior') === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Necessidades Especiais
            <input name="special_needs" value="<?= $value('special_needs') ?>" maxlength="1000">
        </label>

        <label>
            Cor do pelo
            <input name="coat_color" value="<?= $value('coat_color') ?>" maxlength="60" placeholder="Ex.: caramelo, preto e branco">
        </label>

        <label class="full">
            Observações Gerais
            <textarea name="notes" maxlength="2000"><?= $value('notes') ?></textarea>
        </label>
    </div>

    <footer class="form-actions">
        <a class="button ghost" href="/cliente/pets">Cancelar</a>
        <button class="button primary" type="submit">
            <i class="fa-solid fa-floppy-disk"></i>
            Salvar Pet
        </button>
    </footer>
</form>
