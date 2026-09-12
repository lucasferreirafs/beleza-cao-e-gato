<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
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
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>
    <div class="shell"><?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="workspace">
            <header class="topbar">
                <button class="mobile-menu" data-sidebar-toggle aria-label="Abrir menu">☰</button>

                <div class="breadcrumb">
                    <?= $e(($user['role'] ?? 'client') === 'admin' ? 'Admin' : 'Home') ?>

                    <span>/</span>
                    <strong><?= $e($title ?? 'Dashboard') ?></strong>
                </div>

                <div class="top-user">
                    <span class="notification" aria-label="Notificações">♧
                        <i></i>
                    </span>
                    
                    <span class="avatar">
                        <?= $e(mb_substr($user['name'] ?? 'U', 0, 1)) ?>
                    </span>
                    
                    <span>
                        <strong><?= $e($user['name'] ?? 'Usuário') ?></strong>
                        <small>
                            <?= $e(match ($user['role'] ?? 'client') { 'admin' => 'Unidade Matriz', 'staff' => 'Esteticista Sênior', default => 'Cliente Premium'}) ?>
                        </small>
                    </span>
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
    <script src="/assets/js/app.js" defer></script>
</body>

</html>
