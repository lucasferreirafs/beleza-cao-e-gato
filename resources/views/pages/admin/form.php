<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$value = static fn(string $key, mixed $default = ''): string => $e($record[$key] ?? $default);
$action = '/admin/' . $section . (!empty($record['id']) ? '/' . rawurlencode((string) $record['id']) : '');
?>

<header class="page-heading">
    <div>
        <h1><?= $e($title) ?></h1>
        <p>Os campos são validados no servidor antes da persistência.</p>
    </div>
</header>

<form class="panel pet-form" method="post" action="<?= $e($action) ?>" <?= in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true) && !empty($record['id']) ? 'data-admin-password-required data-password-title="Confirmar edição" data-password-message="Digite sua senha de administrador para salvar as alterações deste cadastro."' : '' ?>>
    <?= \App\Security\Csrf::field() ?>
    <?php if (in_array($section, ['clientes', 'pets', 'funcionarios', 'servicos'], true) && !empty($record['id'])): ?>
        <input type="password" name="admin_password" autocomplete="current-password" hidden disabled data-admin-password-value>
        <noscript><label>Senha de administrador<input type="password" name="admin_password" autocomplete="current-password" required></label></noscript>
    <?php endif; ?>

    <?php if (in_array($section, ['clientes', 'funcionarios'], true)): ?>
        <div class="form-grid">
            <label>Nome completo<input name="name" value="<?= $value('name') ?>" maxlength="120" required></label>
            <label>E-mail<input type="email" name="email" value="<?= $value('email') ?>" maxlength="190" required></label>
            <label>Telefone<input name="phone" value="<?= $value('phone') ?>"></label>
            <?php if ($section !== 'clientes'): ?><label>CPF<input name="cpf" value="<?= $value('cpf') ?>" maxlength="14"></label><?php endif; ?>
            <label>Data de nascimento<input type="date" name="birth_date" value="<?= $value('birth_date') ?>"></label>
            <label>
                <?= $record ? 'Nova senha (opcional)' : 'Senha provisória' ?>
                <input type="password" name="password" autocomplete="new-password" <?= $record ? '' : 'required' ?>>
            </label>
        </div>

    <?php elseif ($section === 'servicos'): ?>
        <div class="form-grid">
            <label>Nome do serviço<input name="name" value="<?= $value('name') ?>" required></label>
            <label>Duração em minutos<input type="number" name="duration_minutes" value="<?= $value('duration_minutes', 45) ?>" min="1" max="1440" required></label>
            <label>Preço<input type="number" name="price" value="<?= $value('price') ?>" min="0" step="0.01" required></label>
            <label class="full">Descrição<textarea name="description" maxlength="2000" required><?= $value('description') ?></textarea></label>
        </div>

    <?php elseif ($section === 'pets'): ?>
        <div class="form-grid">
            <label>
                Cliente
                <select name="owner_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($options['clients'] as $client): ?>
                        <option value="<?= $e($client['id']) ?>" <?= ($record['owner_id'] ?? null) === $client['id'] ? 'selected' : '' ?>>
                            <?= $e($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Nome<input name="name" value="<?= $value('name') ?>" required></label>

            <label>
                Espécie
                <select name="species">
                    <?php foreach (['Cachorro', 'Gato'] as $option): ?>
                        <option <?= $value('species', 'Cachorro') === $option ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Raça<input name="breed" value="<?= $value('breed') ?>" required></label>
            <label>Cor do pelo<input name="coat_color" value="<?= $value('coat_color') ?>" maxlength="60"></label>

            <label>
                Sexo
                <select name="sex">
                    <?php foreach (['Fêmea', 'Macho'] as $option): ?>
                        <option <?= $value('sex', 'Fêmea') === $option ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Nascimento<input type="date" name="birth_date" value="<?= $value('birth_date') ?>"></label>
            <label>Peso<input type="number" name="weight" value="<?= $value('weight') ?>" min="0.1" step="0.1"></label>

            <label class="switch-label">
                <input type="checkbox" name="neutered" value="1" <?= !empty($record['neutered']) ? 'checked' : '' ?>>
                Castrado
            </label>

            <label class="full">Alergias<input name="allergies" value="<?= $value('allergies') ?>"></label>
            <label>Comportamento<input name="behavior" value="<?= $value('behavior') ?>"></label>
            <label>Necessidades especiais<input name="special_needs" value="<?= $value('special_needs') ?>"></label>
            <label class="full">Observações<textarea name="notes"><?= $value('notes') ?></textarea></label>
        </div>
    <?php endif; ?>

    <footer class="form-actions">
        <a class="button ghost" href="/admin/<?= $e($section) ?>">Cancelar</a>
        <button class="button primary">Salvar</button>
    </footer>
</form>
