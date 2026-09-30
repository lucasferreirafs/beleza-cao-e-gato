<?php $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $e($title ?? 'Beleza Cão&Gato') ?> · Beleza Cão&Gato</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/errors/errors.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/css/errors/errors.css') ?>">
    <link rel="stylesheet" href="/assets/css/request-loading.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/css/request-loading.css') ?>">
</head>

<body class="auth-body <?= $pageClass ?? '' ?>"><?= $content ?>
    <?php require dirname(__DIR__) . '/partials/request-loading.php'; ?>
    <script src="/assets/js/app.js?v=<?= time() ?>" defer></script>
    <!-- GSAP via CDN — se falhar, auth-animations.js detecta e não executa (graceful degradation) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    <script src="/assets/js/auth-animations.js?v=<?= time() ?>" defer></script>
</body>

</html>
