<?php
$suffix = $returnQuery !== '' ? '?' . $returnQuery : '';
$clientUrl = '/staff/clientes/' . $client['id'];
$phone = trim((string) $client['phone']);
$emailHref = filter_var($client['email'], FILTER_VALIDATE_EMAIL) ? 'mailto:' . rawurlencode($client['email']) : null;
?>
<div class="staff-clients">
    <a class="sc-back" href="/staff/clientes<?= $e($suffix) ?>"><i class="fa-solid fa-angle-left" aria-hidden="true"></i> Voltar para clientes</a>
    <header class="sc-profile-header panel">
        <span class="sc-avatar">
            <?php if ($client['avatar_path']): ?><img src="<?= $e($clientUrl . '/foto?v=' . rawurlencode($client['avatar_path'])) ?>" alt="" width="80" height="80" data-avatar-image><?php endif; ?>
            <span data-avatar-fallback <?= $client['avatar_path'] ? 'hidden' : '' ?>><?= $e(mb_substr($client['name'], 0, 1)) ?></span>
        </span>
        <div><p class="sc-eyebrow">Perfil do cliente</p><h1><?= $e($client['name']) ?></h1><?php if ($client['status'] !== 'active'): ?><span class="sc-account-state"><?= $client['status'] === 'blocked' ? 'Conta bloqueada' : 'Conta inativa' ?></span><?php endif; ?></div>
    </header>
    <div class="sc-profile-grid">
        <section class="panel sc-facts" aria-labelledby="client-contact"><h2 id="client-contact">Contato e dados básicos</h2>
            <dl>
                <div><dt>E-mail</dt><dd><?php if ($emailHref): ?><a href="<?= $e($emailHref) ?>"><?= $e($client['email']) ?></a><?php else: ?><?= $e($client['email']) ?><?php endif; ?></dd></div>
                <div><dt>Telefone</dt><dd><?php if ($phone !== ''): ?><a href="tel:<?= $e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= $e($phone) ?></a><?php else: ?>Não informado<?php endif; ?></dd></div>
                <div><dt>Data de nascimento</dt><dd><?= $e(\App\Support\PetHelper::formatDate($client['birth_date'])) ?></dd></div>
                <div><dt>Cliente desde</dt><dd><?= $e(\App\Support\PetHelper::formatDate($client['created_at'])) ?></dd></div>
            </dl>
            <div class="sc-contact-actions">
                <?php if ($phone !== ''): ?><a class="button secondary" href="tel:<?= $e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i> Ligar</a><?php endif; ?>
                <?php if ($emailHref): ?><a class="button secondary" href="<?= $e($emailHref) ?>"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Enviar e-mail</a><?php endif; ?>
            </div>
        </section>
        <section class="sc-pets-section" aria-labelledby="client-pets"><h2 id="client-pets">Pets de <?= $e($client['name']) ?> <span class="sc-count"><?= count($pets) ?></span></h2>
            <?php if (!$pets): ?><div class="panel sc-empty"><i class="fa-solid fa-paw" aria-hidden="true"></i><h3>Nenhum pet cadastrado</h3><p>Este cliente ainda não possui pets no sistema.</p></div>
            <?php else: ?><div class="sc-pets-grid"><?php foreach ($pets as $pet): require __DIR__ . '/partials/client-pet-card.php'; endforeach; ?></div><?php endif; ?>
        </section>
    </div>
</div>
