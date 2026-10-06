<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\PasswordService;

function verifyPasswordChange(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$_SESSION = [
    'user' => ['id' => '00000000-0000-4000-8000-000000000001', 'name' => 'QA Cliente', 'role' => 'client'],
    '_csrf' => str_repeat('a', 64),
];
$_SERVER['REQUEST_URI'] = '/conta/alterar-senha';
ob_start();
View::render('pages/account/change-password', [
    'title' => 'Alterar senha', 'active' => 'profile', 'user' => $_SESSION['user'],
]);
$html = (string) ob_get_clean();
verifyPasswordChange((bool) preg_match('/<input[^>]*name="current_password"[^>]*required/', $html), 'Campo da senha atual obrigatório.');
verifyPasswordChange(str_contains($html, 'name="new_password"') && str_contains($html, 'data-password-strength'), 'Nova senha sem medidor de força.');
verifyPasswordChange((bool) preg_match('/<input[^>]*name="password_confirmation"[^>]*required/', $html), 'Confirmação obrigatória.');
verifyPasswordChange(str_contains($html, 'data-password-change-form') && str_contains($html, 'name="_token"'), 'Formulário sem integração de comparação/CSRF.');
verifyPasswordChange(str_contains($html, '/assets/css/account/change-password.css?'), 'CSS da página não carregado.');
verifyPasswordChange(str_contains($html, 'page-client-profile'), 'View de alteração de senha sem classe de layout do cliente.');
verifyPasswordChange(substr_count($html, 'data-password-toggle') === 3, 'Os três campos devem ter controles para mostrar/ocultar senha.');

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
$routesProperty = new ReflectionProperty($router, 'routes');
$routes = $routesProperty->getValue($router);
$get = $post = null;
foreach ($routes as $route) {
    if ($route['path'] !== '/conta/alterar-senha') continue;
    if ($route['method'] === 'GET') $get = $route;
    if ($route['method'] === 'POST') $post = $route;
}
verifyPasswordChange($get !== null && in_array(AuthMiddleware::class, $get['middleware'], true), 'GET deve exigir autenticação.');
verifyPasswordChange($post !== null && in_array(AuthMiddleware::class, $post['middleware'], true)
    && in_array(CsrfMiddleware::class, $post['middleware'], true), 'POST deve exigir autenticação e CSRF.');

$service = new PasswordService();
$invalidInputs = [
    [],
    ['current_password' => [], 'new_password' => 'SenhaForte123!', 'password_confirmation' => 'SenhaForte123!'],
    ['current_password' => 'atual', 'new_password' => '', 'password_confirmation' => ''],
    ['current_password' => 'atual', 'new_password' => 'fraca', 'password_confirmation' => 'fraca'],
    ['current_password' => 'atual', 'new_password' => 'SenhaForte123!', 'password_confirmation' => 'OutraSenha123!'],
];
foreach ($invalidInputs as $input) {
    try {
        $service->change('00000000-0000-4000-8000-000000000001', $input);
        throw new RuntimeException('Dados inválidos passaram para a persistência.');
    } catch (InvalidArgumentException) {
        // All these cases must fail before opening a database connection.
    }
}

echo "Password change: markup, route protection, required fields, strength and confirmation checks OK.\n";
