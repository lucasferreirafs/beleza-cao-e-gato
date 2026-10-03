<?php
$initials = implode('', array_map(static fn(string $word): string => mb_substr($word, 0, 1), array_slice(preg_split('/\s+/u', trim((string) $appointment['client_name'])), 0, 2)));
$phone = trim((string) ($appointment['client_phone'] ?? ''));
$email = trim((string) ($appointment['client_email'] ?? ''));
?>
<section class="staff-attendance-panel staff-attendance-owner" aria-labelledby="staff-attendance-owner-heading">
    <h2 id="staff-attendance-owner-heading">Proprietário</h2>
    <div class="staff-attendance-owner-identity">
        <span class="staff-attendance-initials" aria-hidden="true"><?= $e($initials ?: 'T') ?></span>
        <div><h3><?= $e($appointment['client_name']) ?></h3>
            <p>Telefone: <?php if ($phone !== ''): ?><a href="tel:<?= $e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= $e($phone) ?></a><?php else: ?>Não informado<?php endif; ?></p>
            <p>E-mail: <?php if ($email !== ''): ?><a href="mailto:<?= $e($email) ?>"><?= $e($email) ?></a><?php else: ?>Não informado<?php endif; ?></p>
        </div>
    </div>
</section>
