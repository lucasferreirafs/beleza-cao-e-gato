<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\Env;
use App\Security\Csrf;
use App\Validation\Validator;
use App\Core\Router;
use App\Middleware\CsrfMiddleware;
Env::load(dirname(__DIR__) . '/.env');
$failed = [];
if (!preg_match('/--orange-500:\s*#e8612d/i', (string) file_get_contents(dirname(__DIR__) . '/public/assets/css/auth/login.css')))
    $failed[] = 'token';
try {
    if (Validator::email('  TEST@example.com ') !== 'test@example.com') $failed[] = 'email-normalization';
    if (Validator::cpf('529.982.247-25') !== '52998224725') $failed[] = 'cpf';
    Validator::password('Senha123!');
    $_SESSION = [];
    if (Csrf::validate('') || Csrf::validate(null)) $failed[] = 'csrf-empty-session';
    $csrf = Csrf::token();
    if (!Csrf::validate($csrf) || Csrf::validate('invalid')) $failed[] = 'csrf';
} catch (Throwable $e) {
    $failed[] = 'security: ' . $e->getMessage();
}
try {
    $router = new Router();
    require dirname(__DIR__) . '/routes/web.php';
    $property = new ReflectionProperty($router, 'routes');
    $property->setAccessible(true);
    foreach ($property->getValue($router) as $route) {
        [$controller, $method] = $route['handler'];
        if (!class_exists($controller) || !method_exists($controller, $method)) $failed[] = 'route-handler:' . $route['path'];
        foreach ($route['middleware'] as $definition) {
            $middleware = explode(':', $definition, 2)[0];
            if (!class_exists($middleware)) $failed[] = 'route-middleware:' . $route['path'];
        }
        if (in_array($route['method'], ['POST','PUT','PATCH','DELETE'], true) && !in_array(CsrfMiddleware::class, $route['middleware'], true)) $failed[] = 'route-csrf:' . $route['path'];
    }
} catch (Throwable $e) {
    $failed[] = 'routes: ' . $e->getMessage();
}
if ($failed) {
    fwrite(STDERR, implode("\n", $failed));
    exit(1);
}
echo "Testes básicos OK\n";
