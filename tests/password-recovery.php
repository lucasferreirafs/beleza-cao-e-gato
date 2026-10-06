<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Services\AccountService;

function verifyPasswordRecovery(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$_SESSION = ['_csrf' => str_repeat('c', 64), 'password_recovery_email' => 'cliente+<teste>@example.test'];
$_SERVER['REQUEST_URI'] = '/recuperar-senha';

ob_start();
View::render('pages/auth/recovery', [
    'title' => 'Recuperar senha',
    'success' => 'ALERTA ANTIGO',
    'error' => null,
], 'auth');
$recoveryHtml = (string) ob_get_clean();
verifyPasswordRecovery(!str_contains($recoveryHtml, 'ALERTA ANTIGO'), 'Alerta de sucesso antigo ainda aparece na recuperação.');

ob_start();
View::render('pages/auth/recovery-confirmation', [
    'title' => 'Verifique seu e-mail',
    'email' => $_SESSION['password_recovery_email'],
    'notice' => null,
    'error' => null,
    'devUrl' => null,
], 'auth');
$confirmationHtml = (string) ob_get_clean();
verifyPasswordRecovery(str_contains($confirmationHtml, 'page-auth-recovery'), 'Confirmação sem estilos do fluxo de recuperação.');
verifyPasswordRecovery(str_contains($confirmationHtml, 'cliente+&lt;teste&gt;@example.test'), 'E-mail não foi escapado para HTML.');
verifyPasswordRecovery(str_contains($confirmationHtml, 'Se houver uma conta associada a este endereço'), 'Texto não deve revelar a existência da conta.');
verifyPasswordRecovery(str_contains($confirmationHtml, 'action="/recuperar-senha/reenviar"')
    && str_contains($confirmationHtml, 'name="_token"'), 'Reenvio não tem rota/formulário protegido por CSRF.');

$_SERVER['REQUEST_URI'] = '/redefinir-senha';
ob_start();
View::render('pages/auth/reset', [
    'title' => 'Redefinir senha',
    'selector' => '00000000-0000-4000-8000-000000000001',
    'token' => str_repeat('a', 64),
    'validToken' => true,
    'error' => null,
], 'auth');
$resetHtml = (string) ob_get_clean();
verifyPasswordRecovery(str_contains($resetHtml, 'name="selector"') && str_contains($resetHtml, 'name="token"'), 'Formulário não leva seletor e segredo ao backend.');
verifyPasswordRecovery(str_contains($resetHtml, 'data-password-strength')
    && (bool) preg_match('/name="password_confirmation"[^>]*required/', $resetHtml), 'Redefinição sem senha forte e confirmação obrigatória.');

ob_start();
View::render('pages/auth/reset', [
    'title' => 'Redefinir senha', 'selector' => '', 'token' => '', 'validToken' => false,
    'error' => 'Este link é inválido, expirou ou já foi utilizado.',
], 'auth');
$invalidResetHtml = (string) ob_get_clean();
verifyPasswordRecovery(str_contains($invalidResetHtml, 'Solicitar novo link') && !str_contains($invalidResetHtml, 'name="password"'), 'Link inválido ainda mostra o formulário de senha.');

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
$routesProperty = new ReflectionProperty($router, 'routes');
$routes = $routesProperty->getValue($router);
$requiredRoutes = [
    'GET /recuperar-senha/confirmacao' => [],
    'POST /recuperar-senha/reenviar' => [CsrfMiddleware::class],
];
foreach ($requiredRoutes as $routeKey => $requiredMiddleware) {
    [$method, $path] = explode(' ', $routeKey, 2);
    $route = null;
    foreach ($routes as $candidate) {
        if ($candidate['method'] === $method && $candidate['path'] === $path) $route = $candidate;
    }
    verifyPasswordRecovery($route !== null, "Rota {$routeKey} ausente.");
    foreach ($requiredMiddleware as $middleware) {
        verifyPasswordRecovery(in_array($middleware, $route['middleware'], true), "Rota {$routeKey} sem CSRF.");
    }
}

$accountService = new AccountService();
verifyPasswordRecovery(!$accountService->isPasswordResetValid('invalid-selector', str_repeat('a', 64)), 'Seletor malformado não deve consultar ou aceitar token.');
verifyPasswordRecovery(!$accountService->resetPassword([
    'selector' => 'invalid-selector', 'token' => str_repeat('a', 64),
    'password' => 'SenhaForte123!', 'password_confirmation' => 'SenhaForte123!',
]), 'Token/seletor malformado não deve redefinir senha.');
try {
    $accountService->resetPassword([
        'selector' => 'invalid-selector', 'token' => str_repeat('a', 64),
        'password' => ['SenhaForte123!'], 'password_confirmation' => ['SenhaForte123!'],
    ]);
    throw new RuntimeException('Campos array não devem ser aceitos como senha.');
} catch (InvalidArgumentException) {
    // Form input is untrusted and must be scalar before validation.
}

echo "Password recovery: confirmation privacy, escaped email, resend CSRF, reset form, invalid token and routes OK.\n";
