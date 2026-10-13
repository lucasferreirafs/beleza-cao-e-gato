<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$backSearch = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$backDate = is_string($_GET['date'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $_GET['date']) ? $_GET['date'] : '';
$backPage = filter_var($_GET['page'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
$backQuery = http_build_query(array_filter(['q' => $backSearch, 'date' => $backDate, 'page' => $backPage && $backPage > 1 ? $backPage : ''], static fn(mixed $v): bool => $v !== '' && $v !== null));
$backUrl = '/staff/atendimentos' . ($backQuery !== '' ? '?' . $backQuery : '');
?>
<div class="staff-attendance" data-attendance-detail>
    <a class="staff-attendance-back" href="<?= $e($backUrl) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Voltar aos atendimentos</a>
    <header class="staff-attendance-heading"><h1>Detalhes do Atendimento</h1><p>Informações do pet, proprietário e serviços deste agendamento.</p></header>
    <div class="staff-attendance-grid">
        <div class="staff-attendance-left">
            <?php require __DIR__ . '/partials/attendance-pet.php'; ?>
            <?php require __DIR__ . '/partials/attendance-owner.php'; ?>
        </div>
        <?php require __DIR__ . '/partials/attendance-service.php'; ?>
    </div>
</div>
<dialog class="staff-attendance-notice" data-attendance-notice aria-labelledby="staff-attendance-notice-title">
    <h2 id="staff-attendance-notice-title" data-attendance-notice-title>Atendimento atualizado</h2><p>Atualizando os detalhes…</p>
</dialog>
<script src="/assets/js/staff-attendance-detail.js?v=<?= hash_file('sha256', dirname(__DIR__, 4) . '/public/assets/js/staff-attendance-detail.js') ?>" defer></script>
