<?php
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$usesAuthAnimations = in_array($pageClass ?? '', ['page-auth-login', 'page-auth-register'], true);
$authStylesheets = [
    'page-landing' => 'landing.css',
    'page-auth-login' => 'auth/login.css',
    'page-auth-recovery' => 'auth/recovery.css',
    'page-auth-register' => 'auth/register.css',
    'page-auth-reset' => 'auth/reset.css',
    'page-auth-verification' => 'auth/verification.css',
    'page-legal-terms' => 'legal/terms.css',
];
$authStylesheet = null;
foreach ($authStylesheets as $class => $stylesheet) {
    if (str_contains($pageClass ?? '', $class)) {
        $authStylesheet = $stylesheet;
        break;
    }
}
if ($authStylesheet === null && !str_starts_with($pageClass ?? '', 'page-errors-')) {
    $authStylesheet = 'auth.css';
}
?>
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
    <?php if ($authStylesheet !== null):
        $authStylesheetPath = dirname(__DIR__, 3) . '/public/assets/css/' . $authStylesheet;
    ?>
        <link rel="stylesheet" href="/assets/css/<?= $e($authStylesheet) ?>?v=<?= hash_file('sha256', $authStylesheetPath) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/errors/errors.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/errors/errors.css') ?>">
    <link rel="stylesheet" href="/assets/css/request-loading.css?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/css/request-loading.css') ?>">
</head>

<body class="auth-body <?= $pageClass ?? '' ?>"><?= $content ?>
    <?php require dirname(__DIR__) . '/partials/request-loading.php'; ?>
    <script src="/assets/js/app.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/app.js') ?>" defer></script>
    <?php if ($usesAuthAnimations): ?>
        <!-- Animações de entrada existem apenas nas telas de login e cadastro. -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
        <script src="/assets/js/auth-animations.js?v=<?= hash_file('sha256', dirname(__DIR__, 3) . '/public/assets/js/auth-animations.js') ?>" defer></script>
    <?php endif; ?>
</body>

</html>
