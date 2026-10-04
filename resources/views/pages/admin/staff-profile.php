<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabel = match ($staff['status']) {
    'active' => 'Ativo',
    'blocked' => 'Bloqueado',
    'pending_verification' => 'Aguardando verificação',
    default => 'Inativo',
};
$phone = trim((string) ($staff['phone'] ?? ''));
$emailHref = filter_var($staff['email'], FILTER_VALIDATE_EMAIL) ? 'mailto:' . rawurlencode($staff['email']) : null;
?>
<div class="staff-clients">
    <a class="sc-back" href="/admin/funcionarios"><i class="fa-solid fa-angle-left" aria-hidden="true"></i> Voltar para funcionários</a>
    <header class="sc-profile-header panel">
        <span class="sc-avatar"><span aria-hidden="true"><?= $e(mb_substr($staff['name'], 0, 1)) ?></span></span>
        <div>
            <p class="sc-eyebrow">Perfil do funcionário</p>
            <h1><?= $e($staff['name']) ?></h1>
            <span class="badge <?= $e(\App\Support\StatusBadge::class($staff['status'])) ?>"><?= $e($statusLabel) ?></span>
        </div>
        <div class="admin-client-profile-actions">
            <a class="button ghost" href="/admin/funcionarios/<?= $e(rawurlencode((string) $staff['id'])) ?>/editar">Editar funcionário</a>
        </div>
    </header>

    <section class="panel sc-facts" aria-labelledby="staff-contact">
        <h2 id="staff-contact">Contato e dados básicos</h2>
        <dl>
            <div><dt>E-mail</dt><dd><?php if ($emailHref): ?><a href="<?= $e($emailHref) ?>"><?= $e($staff['email']) ?></a><?php else: ?><?= $e($staff['email']) ?><?php endif; ?></dd></div>
            <div><dt>Telefone</dt><dd><?php if ($phone !== ''): ?><a href="tel:<?= $e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= $e($phone) ?></a><?php else: ?>Não informado<?php endif; ?></dd></div>
            <div><dt>Data de nascimento</dt><dd><?= $e(\App\Support\PetHelper::formatDate($staff['birth_date'])) ?></dd></div>
            <div><dt>Na equipe desde</dt><dd><?= $e(\App\Support\PetHelper::formatDate($staff['created_at'])) ?></dd></div>
            <div><dt>CPF</dt><dd><?= $e($staff['cpf_masked'] ?? 'Não informado') ?></dd></div>
        </dl>
        <div class="sc-contact-actions">
            <?php if ($phone !== ''): ?><a class="button secondary" href="tel:<?= $e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i> Ligar</a><?php endif; ?>
            <?php if ($emailHref): ?><a class="button secondary" href="<?= $e($emailHref) ?>"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Enviar e-mail</a><?php endif; ?>
        </div>
    </section>
</div>
