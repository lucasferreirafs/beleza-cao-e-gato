<?php
declare(strict_types=1);

// Repositories and rendering are simulated; middleware/controller/session are real.
// No database connection, HTTP request or session file is created.
namespace App\Repositories {
    final class UserRepository
    {
        public static array $row = [];
        public static int $queries = 0;
        public function findById(string $id): ?array
        {
            self::$queries++;
            return self::$row['id'] === $id ? self::$row : null;
        }
    }
}
namespace App\Core {
    final class View
    {
        public static array $data = [];
        public static function render(string $template, array $data = [], string $layout = 'app'): void
        {
            self::$data = $data;
        }
    }
}
namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';
    use App\Controllers\ProfileController;
    use App\Core\Request;
    use App\Core\View;
    use App\Middleware\AuthMiddleware;
    use App\Repositories\UserRepository;

    function verify(bool $condition, string $message): void
    {
        if (!$condition) throw new \RuntimeException($message);
    }

    foreach (['client', 'staff', 'admin'] as $role) {
        // Even with the bypass flag enabled, production must refresh normally.
        putenv('APP_ENV=production');
        putenv('AUTH_BYPASS=true');
        UserRepository::$queries = 0;
        UserRepository::$row = [
            'id' => '11111111-1111-4111-8111-111111111111', 'role' => $role,
            'status' => 'active', 'auth_version' => 1, 'name' => 'Nome atualizado',
            'email' => 'atual@example.invalid', 'phone' => '11999999999',
            'cpf' => '52998224725', 'birth_date' => '2000-01-01',
        ];
        $_SESSION = ['user' => array_replace(UserRepository::$row, ['name' => 'Nome antigo']), 'last_activity' => time()];
        $request = new Request();
        (new AuthMiddleware())->handle($request);
        (new ProfileController())->show($request);
        verify(UserRepository::$queries === 1, 'Perfil deve compartilhar a consulta do middleware: ' . $role);
        verify(View::$data['profile'] === UserRepository::$row, 'Perfil deve receber todos os dados atualizados');

        putenv('APP_ENV=development');
        $_SESSION['user']['name'] = 'Nome antigo';
        UserRepository::$queries = 0;
        (new AuthMiddleware())->handle($request);
        (new ProfileController())->show($request);
        verify(UserRepository::$queries === 1, 'Bypass deve manter consulta completa');
        verify(View::$data['profile'] === UserRepository::$row, 'Bypass nao pode mostrar perfil antigo');
    }
    echo "OK: perfil usa uma consulta de usuario para cliente, funcionario e administrador; bypass preservado.\n";
}
