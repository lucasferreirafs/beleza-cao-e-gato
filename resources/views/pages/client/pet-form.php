<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn(string $key, mixed $default = ''): string => $e($pet[$key] ?? $default);
?>

<header class="page-heading">
    <div>
        <h1><?= $pet ? 'Editar Pet' : 'Cadastrar Novo Pet' ?></h1>
        <p>Insira as informações básicas e adicionais do pet.</p>
    </div>
</header>

<form class="panel pet-form" method="post" action="<?= $e($formAction) ?>">
    <?= \App\Security\Csrf::field() ?>

    <h2>1. Informações Básicas</h2>

    <div class="upload-row">
        <div class="photo-placeholder">▣</div>
        <span class="button teal" aria-hidden="true">Foto ilustrativa</span>
        <small>O upload de imagem será disponibilizado quando houver armazenamento configurado.</small>
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
            <input name="birth_date" type="date" value="<?= $value('birth_date') ?>" max="<?= date('Y-m-d') ?>">
        </label>

        <label>
            Peso aproximado (kg)
            <input name="weight" type="number" step=".1" min="0.1" max="9999" value="<?= $value('weight') ?>">
        </label>
    </div>

    <hr>

    <h2>2. Informações Adicionais</h2>

    <div class="form-grid">
        <label class="switch-label">
            <input type="checkbox" name="neutered" value="1" <?= !empty($pet['neutered']) ? 'checked' : '' ?>>
            Castrado?
        </label>

        <label class="full">
            Alergias
            <input name="allergies" value="<?= $value('allergies') ?>" maxlength="1000">
        </label>

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

        <label class="full">
            Observações Gerais
            <textarea name="notes" maxlength="2000"><?= $value('notes') ?></textarea>
        </label>
    </div>

    <footer class="form-actions">
        <a class="button ghost" href="/cliente/pets">Cancelar</a>
        <button class="button primary">Salvar Pet</button>
    </footer>
</form>
