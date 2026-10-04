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
try {
    $_SESSION = ['user' => ['id' => '00000000-0000-4000-8000-000000000001', 'name' => 'Admin QA', 'email' => 'admin@example.test', 'role' => 'admin']];
    $_SERVER['REQUEST_URI'] = '/admin/pets/00000000-0000-4000-8000-000000000002';
    $pet = [
        'id' => '00000000-0000-4000-8000-000000000002', 'name' => 'Luna', 'species' => 'Cachorro',
        'breed' => 'SRD', 'sex' => 'Fêmea', 'birth_date' => null, 'weight' => null,
        'coat_color' => null, 'neutered' => false, 'allergies' => '', 'special_needs' => '',
        'behavior' => '', 'notes' => '', 'photo_path' => null,
    ];
    ob_start();
    App\Core\View::render('pages/admin/pet-profile', [
        'title' => 'Perfil do pet', 'active' => 'pets', 'section' => 'pets', 'user' => $_SESSION['user'],
        'adminPetProfile' => true, 'fromPetDirectory' => true, 'petProfileUrl' => '/admin/pets/' . $pet['id'],
        'ownerProfileUrl' => '/admin/clientes/00000000-0000-4000-8000-000000000003', 'backUrl' => '/admin/pets',
        'returnQuery' => '', 'pet' => $pet, 'client' => ['id' => '00000000-0000-4000-8000-000000000003', 'name' => 'Tutor QA'],
    ]);
    $petProfileHtml = (string) ob_get_clean();
    if (!str_contains($petProfileHtml, 'class="app-body page-admin-pet-profile page-admin-list"')
        || !str_contains($petProfileHtml, '/assets/css/staff/clients.css?')) {
        $failed[] = 'admin-pet-profile-styles';
    }

    $_SERVER['REQUEST_URI'] = '/admin/clientes/00000000-0000-4000-8000-000000000003';
    ob_start();
    App\Core\View::render('pages/admin/client-profile', [
        'title' => 'Perfil do cliente', 'active' => 'clientes', 'section' => 'clientes', 'user' => $_SESSION['user'],
        'client' => [
            'id' => '00000000-0000-4000-8000-000000000003', 'name' => 'Tutor QA', 'email' => 'tutor@example.test',
            'phone' => null, 'birth_date' => null, 'status' => 'active', 'avatar_path' => null,
            'created_at' => null, 'cpf_masked' => null,
        ],
        'pets' => [],
    ]);
    $clientProfileHtml = (string) ob_get_clean();
    if (!str_contains($clientProfileHtml, 'class="app-body page-admin-client-profile page-admin-list"')
        || !str_contains($clientProfileHtml, '/assets/css/staff/clients.css?')) {
        $failed[] = 'admin-client-profile-styles';
    }

    $_SERVER['REQUEST_URI'] = '/admin/pets';
    ob_start();
    App\Core\View::render('pages/admin/list', [
        'title' => 'Pets', 'active' => 'pets', 'section' => 'pets', 'user' => $_SESSION['user'],
        'search' => '', 'total' => 1, 'page' => 1, 'pages' => 1, 'returnQuery' => '',
        'adminPetDirectory' => true, 'embedAdminPetDirectory' => true,
        'pets' => [['id' => '00000000-0000-4000-8000-000000000002', 'pet_name' => 'Luna', 'breed' => 'SRD', 'coat_color' => 'Dourado', 'owner_name' => 'Tutor QA']],
        'rows' => [],
    ]);
    $adminPetsHtml = (string) ob_get_clean();
    if (!str_contains($adminPetsHtml, 'action="/admin/pets"')
        || !str_contains($adminPetsHtml, 'data-admin-pet-card')
        || !str_contains($adminPetsHtml, 'aria-expanded="false"')
        || !str_contains($adminPetsHtml, 'data-admin-pet-actions hidden')
        || !str_contains($adminPetsHtml, 'Cor do pelo')
        || !str_contains($adminPetsHtml, 'Dourado')
        || !str_contains($adminPetsHtml, '>Ver detalhes')
        || !str_contains($adminPetsHtml, '>Editar</a>')) {
        $failed[] = 'admin-pet-expandable-list';
    }

    $_SERVER['REQUEST_URI'] = '/admin/funcionarios';
    ob_start();
    App\Core\View::render('pages/admin/list', [
        'title' => 'Funcionários', 'active' => 'funcionarios', 'section' => 'funcionarios',
        'user' => $_SESSION['user'], 'search' => 'João', 'total' => 1, 'page' => 1, 'pages' => 1,
        'employees' => [[
            'id' => '00000000-0000-4000-8000-000000000004', 'name' => 'João QA',
            'email' => 'joao@example.test', 'phone' => '(11) 99999-1234', 'status' => 'active',
        ]], 'rows' => [],
    ]);
    $adminStaffHtml = (string) ob_get_clean();
    if (!str_contains($adminStaffHtml, 'data-admin-client-card')
        || !str_contains($adminStaffHtml, 'João QA')
        || !str_contains($adminStaffHtml, 'joao@example.test')
        || !str_contains($adminStaffHtml, 'Ver perfil')
        || !str_contains($adminStaffHtml, 'Editar')
        || !str_contains($adminStaffHtml, 'Promover a administrador')
        || !str_contains($adminStaffHtml, 'name="_token"')) {
        $failed[] = 'admin-staff-directory';
    }

    $_SERVER['REQUEST_URI'] = '/admin/funcionarios/00000000-0000-4000-8000-000000000004';
    ob_start();
    App\Core\View::render('pages/admin/staff-profile', [
        'title' => 'Perfil do funcionário', 'active' => 'funcionarios', 'section' => 'funcionarios',
        'user' => $_SESSION['user'], 'staff' => [
            'id' => '00000000-0000-4000-8000-000000000004', 'name' => 'João QA',
            'email' => 'joao@example.test', 'phone' => null, 'birth_date' => null,
            'created_at' => null, 'status' => 'active', 'cpf_masked' => '***.***.***-4321',
        ],
    ]);
    $adminStaffProfileHtml = (string) ob_get_clean();
    if (!str_contains($adminStaffProfileHtml, 'page-admin-staff-profile page-admin-list')
        || !str_contains($adminStaffProfileHtml, '/assets/css/staff/clients.css?')
        || !str_contains($adminStaffProfileHtml, '***.***.***-4321')
        || !str_contains($adminStaffProfileHtml, 'Editar funcionário')) {
        $failed[] = 'admin-staff-profile';
    }

    ob_start();
    App\Core\View::render('pages/admin/form', [
        'title' => 'Editar Funcionário', 'active' => 'funcionarios', 'section' => 'funcionarios',
        'user' => $_SESSION['user'], 'record' => [
            'id' => '00000000-0000-4000-8000-000000000004', 'name' => 'João QA',
            'email' => 'joao@example.test', 'phone' => null, 'cpf' => '52998224725',
            'birth_date' => null,
        ], 'options' => ['clients' => []],
    ]);
    $adminStaffFormHtml = (string) ob_get_clean();
    if (!str_contains($adminStaffFormHtml, 'data-admin-password-required')
        || !str_contains($adminStaffFormHtml, 'data-admin-password-value')) {
        $failed[] = 'admin-staff-edit-password-confirmation';
    }

    $_SERVER['REQUEST_URI'] = '/admin/servicos';
    ob_start();
    App\Core\View::render('pages/admin/list', [
        'title' => 'Serviços', 'active' => 'servicos', 'section' => 'servicos',
        'user' => $_SESSION['user'], 'search' => '', 'statusFilter' => 'active',
        'total' => 7, 'page' => 1, 'pages' => 2, 'perPage' => 6,
        'services' => [[
            'id' => '00000000-0000-4000-8000-000000000005', 'name' => 'Banho de QA',
            'description' => 'Banho completo e higienização.', 'duration_minutes' => 60,
            'price' => '80.00', 'active' => true, 'appointment_count' => 12,
        ], [
            'id' => '00000000-0000-4000-8000-000000000006', 'name' => 'Tosa inativa QA',
            'description' => 'Serviço temporariamente desativado.', 'duration_minutes' => 30,
            'price' => '45.00', 'active' => 'f', 'appointment_count' => 0,
        ]], 'rows' => [],
    ]);
    $adminServicesHtml = (string) ob_get_clean();
    if (!str_contains($adminServicesHtml, 'Buscar por nome ou descrição...')
        || !str_contains($adminServicesHtml, 'value="active"')
        || !str_contains($adminServicesHtml, 'value="inactive"')
        || !str_contains($adminServicesHtml, 'Banho de QA')
        || !str_contains($adminServicesHtml, 'admin-service-edit')
        || !str_contains($adminServicesHtml, 'fa-pen-to-square')
        || !str_contains($adminServicesHtml, 'fa-ban')
        || !str_contains($adminServicesHtml, 'fa-circle-check')
        || !str_contains($adminServicesHtml, 'Desativado')
        || !str_contains($adminServicesHtml, 'Ativar</button>')
        || !str_contains($adminServicesHtml, '12')
        || !str_contains($adminServicesHtml, '/admin/servicos/00000000-0000-4000-8000-000000000005/status')
        || !str_contains($adminServicesHtml, 'data-admin-password-required')
        || !str_contains($adminServicesHtml, 'name="_token"')
        || !str_contains($adminServicesHtml, 'Página 1 de 2')) {
        $failed[] = 'admin-services-directory';
    }

    ob_start();
    App\Core\View::render('pages/admin/form', [
        'title' => 'Editar Serviço', 'active' => 'servicos', 'section' => 'servicos',
        'user' => $_SESSION['user'], 'record' => [
            'id' => '00000000-0000-4000-8000-000000000005', 'name' => 'Banho de QA',
            'description' => 'Banho completo e higienização.', 'duration_minutes' => 60, 'price' => '80.00',
        ], 'options' => ['clients' => []],
    ]);
    $adminServiceFormHtml = (string) ob_get_clean();
    if (!str_contains($adminServiceFormHtml, 'data-admin-password-required')
        || !str_contains($adminServiceFormHtml, 'data-admin-password-value')) {
        $failed[] = 'admin-service-edit-password-confirmation';
    }

    $_SERVER['REQUEST_URI'] = '/admin';
    ob_start();
    App\Core\View::render('pages/admin/dashboard', [
        'title' => 'Dashboard QA', 'active' => 'dashboard', 'user' => $_SESSION['user'],
        'todayStats' => ['total' => 4, 'pending' => 1, 'in_progress' => 1, 'completed' => 1, 'cancelled' => 1],
        'nextAppointment' => [
            'id' => '00000000-0000-4000-8000-000000000007', 'scheduled_at' => '2026-10-04T11:30:00-03:00',
            'duration_minutes' => 80, 'pet_name' => 'Lua <QA>', 'client_name' => 'Tutor QA',
            'staff_name' => 'Profissional QA', 'service_names' => 'Banho + Tosa',
        ],
        'petsNeedingAttention' => 2, 'recent' => [],
        'monthlyStats' => [], 'serviceDistribution' => [], 'serviceTotal' => 0,
    ]);
    $adminDashboardHtml = (string) ob_get_clean();
    if (!str_contains($adminDashboardHtml, 'PENDENTES')
        || !str_contains($adminDashboardHtml, 'EM ATENDIMENTO')
        || !str_contains($adminDashboardHtml, 'Próximo atendimento')
        || !str_contains($adminDashboardHtml, 'Lua &lt;QA&gt;')
        || !str_contains($adminDashboardHtml, 'Possuem alergias ou necessidades especiais')
        || !str_contains($adminDashboardHtml, 'Ver agenda completa')) {
        $failed[] = 'admin-dashboard-operational-overview';
    }

    ob_start();
    App\Core\View::render('pages/admin/dashboard', [
        'title' => 'Dashboard QA', 'active' => 'dashboard', 'user' => $_SESSION['user'],
        'todayStats' => ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0],
        'nextAppointment' => null, 'petsNeedingAttention' => 0, 'recent' => [],
        'monthlyStats' => [], 'serviceDistribution' => [], 'serviceTotal' => 0,
    ]);
    $emptyAdminDashboardHtml = (string) ob_get_clean();
    if (!str_contains($emptyAdminDashboardHtml, 'Não há atendimentos pendentes restantes para hoje.')
        || !str_contains($emptyAdminDashboardHtml, 'Nenhum alerta registrado')) {
        $failed[] = 'admin-dashboard-empty-state';
    }

    $staffProfileRoute = false;
    $promoteStaffRoute = false;
    $adminServiceStatusRoute = false;
    foreach ($property->getValue($router) as $route) {
        if ($route['path'] === '/admin/funcionarios/{id}' && $route['method'] === 'GET'
            && in_array(App\Middleware\AuthMiddleware::class, $route['middleware'], true)
            && in_array(App\Middleware\RoleMiddleware::class . ':admin', $route['middleware'], true)) $staffProfileRoute = true;
        if ($route['path'] === '/admin/funcionarios/{id}/promover-admin' && $route['method'] === 'POST'
            && in_array(App\Middleware\AuthMiddleware::class, $route['middleware'], true)
            && in_array(App\Middleware\RoleMiddleware::class . ':admin', $route['middleware'], true)
            && in_array(CsrfMiddleware::class, $route['middleware'], true)) $promoteStaffRoute = true;
        if ($route['path'] === '/admin/{section}/{id}/status' && $route['method'] === 'POST'
            && in_array(App\Middleware\AuthMiddleware::class, $route['middleware'], true)
            && in_array(App\Middleware\RoleMiddleware::class . ':admin', $route['middleware'], true)
            && in_array(CsrfMiddleware::class, $route['middleware'], true)) $adminServiceStatusRoute = true;
    }
    if (!$staffProfileRoute || !$promoteStaffRoute) $failed[] = 'admin-staff-routes';
    if (!$adminServiceStatusRoute) $failed[] = 'admin-service-status-route';
    $servicePageSize = (new ReflectionMethod(App\Repositories\ManagementRepository::class, 'adminServices'))
        ->getParameters()[3]->getDefaultValue();
    if ($servicePageSize !== 6) $failed[] = 'admin-service-page-size';

    $_SERVER['REQUEST_URI'] = '/admin/relatorios';
    $removedReportsRequest = new App\Core\Request();
    $removedReportsRequest->setRouteParams(['section' => 'relatorios']);
    http_response_code(200);
    ob_start();
    (new App\Controllers\AdminController())->page($removedReportsRequest);
    $removedReportsHtml = (string) ob_get_clean();
    if (http_response_code() !== 404 || !str_contains($removedReportsHtml, 'id="error-title"')) {
        $failed[] = 'admin-reports-removed-route';
    }
    http_response_code(200);
    if (str_contains($adminServicesHtml, '/admin/relatorios') || str_contains($adminServicesHtml, '>Relatórios</a>')) {
        $failed[] = 'admin-reports-sidebar-link';
    }
} catch (Throwable $e) {
    if (ob_get_level() > 0) ob_end_clean();
    $failed[] = 'admin-profile-render: ' . $e->getMessage();
}
if ($failed) {
    fwrite(STDERR, implode("\n", $failed));
    exit(1);
}
echo "Testes básicos OK\n";
