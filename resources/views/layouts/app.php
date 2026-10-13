<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$user = \App\Core\Session::user() ?? ($user ?? []);
$homeUrl = \App\Support\Navigation::home($user['role'] ?? 'client');
$breadcrumbs = \App\Support\Navigation::breadcrumbs((new \App\Core\Request())->path(), $user['role'] ?? 'client', $title ?? 'Início');
$avatarUrl = !empty($user['avatar_path']) ? '/conta/foto?v=' . rawurlencode($user['avatar_path']) : null;
$pageStylesheets = [];
$role = $user['role'] ?? 'client';
$currentPageClass = $pageClass ?? '';

if ($role === 'admin') {
    if (str_contains($currentPageClass, 'page-admin-dashboard')) {
        $pageStylesheets[] = 'admin/dashboard.css';
    } elseif (str_contains($currentPageClass, 'page-admin-form')) {
        $pageStylesheets[] = 'admin/form.css';
    } elseif (str_contains($currentPageClass, 'page-admin-list')) {
        $pageStylesheets[] = 'admin/list.css';
    }
} elseif ($role === 'staff') {
    if (str_contains($currentPageClass, 'page-staff-dashboard')) {
        $pageStylesheets[] = 'staff/dashboard.css';
    } elseif (str_contains($currentPageClass, 'page-staff-page')) {
        $pageStylesheets[] = 'staff/page.css';
    }
} else {
    $clientStylesheets = [
        'page-client-dashboard' => 'client/dashboard.css',
        'page-client-appointments' => 'client/appointments.css',
        'page-client-booking' => 'client/booking.css',
        'page-client-pet-form' => 'client/pet-form.css',
        'page-client-pet-profile' => 'client/pet-profile.css',
        'page-client-pets' => 'client/pets.css',
        'page-client-profile' => 'client/profile.css',
        'page-client-services' => 'client/services.css',
    ];

    foreach ($clientStylesheets as $class => $stylesheet) {
        if (str_contains($currentPageClass, $class)) {
            $pageStylesheets[] = $stylesheet;
            break;
        }
    }
}

// The full stylesheet is a safety net for any new app view not yet mapped above.
if ($pageStylesheets === []) {
    $pageStylesheets[] = 'main.css';
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $e($title ?? 'Painel') ?> · Beleza Cão&Gato</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php foreach ($pageStylesheets as $stylesheet):
        $stylesheetPath = dirname(__DIR__, 3) . '/public/assets/css/' . $stylesheet;
        $stylesheetVersion = is_file($stylesheetPath) ? hash_file('sha256', $stylesheetPath) : '';
    ?>
        <link rel="stylesheet" href="/assets/css/<?= $e($stylesheet) ?>?v=<?= $stylesheetVersion ?>">
    <?php endforeach; ?>
    <?php if (str_contains($currentPageClass, 'page-account-change-password')): ?>
        <link rel="stylesheet" href="/assets/css/account/change-password.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/account/change-password.css') ?>">
    <?php endif; ?>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <link rel="stylesheet" href="/assets/css/admin/password-confirmation.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/admin/password-confirmation.css') ?>">
    <?php endif; ?>
    <?php if (str_contains($pageClass ?? '', 'page-admin-client-profile') || str_contains($pageClass ?? '', 'page-admin-pet-profile') || str_contains($pageClass ?? '', 'page-admin-staff-profile') || (($pageClass ?? '') === 'page-admin-list' && ($section ?? '') === 'pets')): ?>
        <link rel="stylesheet" href="/assets/css/staff/clients.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/staff/clients.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/account.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/account.css') ?>">
    <?php if (($pageClass ?? '') === 'page-staff-page' && in_array(($section ?? ''), ['clientes', 'pets'], true)): ?>
        <link rel="stylesheet" href="/assets/css/staff/clients.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/staff/clients.css') ?>">
    <?php endif; ?>
    <?php if (($pageClass ?? '') === 'page-staff-page' && ($section ?? '') === 'agenda'): ?>
        <link rel="stylesheet" href="/assets/css/staff/agenda.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/staff/agenda.css') ?>">
    <?php endif; ?>
    <?php if (($section ?? '') === 'atendimentos' && str_contains($pageClass ?? '', 'page-staff-page')): ?>
        <link rel="stylesheet" href="/assets/css/staff/appointments.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/staff/appointments.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/errors/errors.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/errors/errors.css') ?>">
    <link rel="stylesheet" href="/assets/css/request-loading.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/request-loading.css') ?>">
    <link rel="stylesheet" href="/assets/css/pagination.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/pagination.css') ?>">
    <script src="/assets/js/pagination-ui.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/pagination-ui.js') ?>" defer></script>
</head>

<body class="app-body <?= $pageClass ?? '' ?>">
    <div class="shell"><?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="workspace">
            <header class="topbar">
                <button type="button" class="mobile-menu" data-sidebar-toggle aria-label="Abrir menu" aria-controls="app-sidebar" aria-expanded="false">☰</button>

                <nav class="breadcrumb" aria-label="Localização atual"><ol>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <li><?php if ($crumb['url'] !== null): ?>
                            <a href="<?= $e($crumb['url']) ?>"><?= $e($crumb['label']) ?></a>
                        <?php else: ?><strong aria-current="page"><?= $e($crumb['label']) ?></strong><?php endif; ?></li>
                    <?php endforeach; ?>
                </ol></nav>

                <div class="top-user">
                    <button type="button" class="notification-btn" data-notification-toggle aria-label="Notificações" aria-haspopup="true" aria-expanded="false" aria-controls="notification-popover">
                        <i class="fa-solid fa-bell" aria-hidden="true"></i>
                        <span class="notification-dot" aria-hidden="true"></span>
                    </button>

                    <div class="header-separator"></div>

                    <button type="button" class="user-info" data-profile-toggle aria-haspopup="dialog" aria-controls="profile-menu" aria-expanded="false" aria-label="Abrir menu do perfil">
                        <span class="user-text">
                            <strong><?= $e($user['name'] ?? 'Usuário') ?></strong>
                            <small><?= $e(match ($user['role'] ?? 'client') { 'admin' => 'Administrador', 'staff' => 'Funcionário', default => 'Cliente'}) ?></small>
                        </span>

                        <span class="avatar">
                            <?php if ($avatarUrl): ?><img src="<?= $e($avatarUrl) ?>" alt="" data-avatar-image><?php endif; ?>
                            <span <?= $avatarUrl ? 'hidden' : '' ?> data-avatar-fallback><?= $e(mb_substr($user['name'] ?? 'U', 0, 1)) ?></span>
                        </span>

                        <i class="fa-solid fa-chevron-down user-chevron" aria-hidden="true"></i>
                    </button>
                </div>
            </header>

            <section class="notification-popover" id="notification-popover" data-notification-popover aria-labelledby="notification-title" hidden>
                <div class="notification-popover__heading">
                    <span class="notification-popover__icon" aria-hidden="true"><i class="fa-solid fa-bell"></i></span>
                    <h2 id="notification-title">Notificações</h2>
                </div>
                <p>Ainda estamos preparando esse espaço. Em breve, você poderá acompanhar novidades e avisos importantes por aqui. <span aria-hidden="true">🐾</span></p>
                <p class="notification-popover__note">Disponível em uma próxima atualização.</p>
            </section>

            <main class="main">
                <?php if ($m = \App\Core\Session::flash('success')): ?>
                    <div class="alert success" role="status">
                        <?= $e($m) ?>
                    </div>
                <?php endif; ?>

                <?php if ($m = \App\Core\Session::flash('error')): ?>
                    <div class="alert error" role="alert">
                        <?= $e($m) ?>
                    </div>
                <?php endif; ?><?= $content ?>
            </main>
        </div>
    </div>
    <dialog id="profile-menu" class="profile-menu" aria-labelledby="profile-menu-title">
        <div class="profile-menu-heading"><strong id="profile-menu-title">Minha conta</strong><button type="button" data-profile-close aria-label="Fechar menu do perfil">×</button></div>
        <nav aria-label="Perfil">
            <a href="<?= $e($homeUrl) ?>/perfil"><i class="fa-solid fa-user" aria-hidden="true"></i> Meu Perfil</a>
            <a href="<?= $e($homeUrl) ?>/pets"><i class="fa-solid fa-paw" aria-hidden="true"></i> <?= ($user['role'] ?? 'client') === 'client' ? 'Meus pets' : 'Pets' ?></a>
            <a href="<?= $e($homeUrl) ?>/configuracoes"><i class="fa-solid fa-gear" aria-hidden="true"></i> Configurações</a>
        </nav>
        <form method="post" action="/logout"><?= \App\Security\Csrf::field() ?><button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sair da Conta</button></form>
    </dialog>
    <button type="button" class="sidebar-backdrop" data-sidebar-backdrop aria-label="Fechar menu de navegação" hidden></button>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <?php require dirname(__DIR__) . '/partials/admin-password-confirmation.php'; ?>
        <script src="/assets/js/admin-password-confirmation.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/admin-password-confirmation.js') ?>" defer></script>
    <?php endif; ?>
    <?php require dirname(__DIR__) . '/partials/request-loading.php'; ?>
    <script src="/assets/js/app.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/app.js') ?>" defer></script>
    <?php if (str_contains($currentPageClass, 'page-client-booking')): ?>
        <script src="/assets/js/client-booking.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/client-booking.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (str_contains($currentPageClass, 'page-client-appointments') || ($role === 'admin' && str_contains($currentPageClass, 'page-admin-list'))): ?>
        <script src="/assets/js/table-filters.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/table-filters.js') ?>" defer></script>
    <?php endif; ?>
</body>

</html>
