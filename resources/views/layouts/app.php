<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$user = \App\Core\Session::user() ?? ($user ?? []);
$homeUrl = \App\Support\Navigation::home($user['role'] ?? 'client');
$breadcrumbs = \App\Support\Navigation::breadcrumbs((new \App\Core\Request())->path(), $user['role'] ?? 'client', $title ?? 'Início');
$avatarUrl = !empty($user['avatar_path']) ? '/conta/foto?v=' . rawurlencode($user['avatar_path']) : null;
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
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/account.css">
    <link rel="stylesheet" href="/assets/css/errors/errors.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/css/errors/errors.css') ?>">
    <link rel="stylesheet" href="/assets/css/request-loading.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/css/request-loading.css') ?>">
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
                    <button class="notification-btn" aria-label="Notificações">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notification-dot"></span>
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
    <?php require dirname(__DIR__) . '/partials/request-loading.php'; ?>
    <script src="/assets/js/app.js?v=<?= time() ?>" defer></script>
</body>

</html>
