<?php
use App\Controllers\{AdminController, AuthController, ClientController, DashboardController, LegalController, ProfileController, StaffController};
use App\Middleware\{AuthMiddleware, CsrfMiddleware, RoleMiddleware};

$auth = AuthMiddleware::class;
$csrf = CsrfMiddleware::class;

// GET - AUTENTICAÇÃO
$router->get('/', [AuthController::class, 'landing']);
$router->get('/termos', [LegalController::class, 'terms']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->get('/cadastro', [AuthController::class, 'registerForm']);
$router->get('/recuperar-senha', [AuthController::class, 'recovery']);
$router->get('/recuperar-senha/confirmacao', [AuthController::class, 'recoveryConfirmation']);
$router->get('/verificar-email', [AuthController::class, 'verification']);
$router->get('/redefinir-senha', [AuthController::class, 'resetForm']);


// POST - AUTENTICAÇÃO
$router->post('/login', [AuthController::class, 'login'], [$csrf]);
$router->post('/logout', [AuthController::class, 'logout'], [$auth, $csrf]);
$router->post('/cadastro', [AuthController::class, 'register'], [$csrf]);
$router->post('/recuperar-senha', [AuthController::class, 'sendRecovery'], [$csrf]);
$router->post('/recuperar-senha/reenviar', [AuthController::class, 'resendRecovery'], [$csrf]);
$router->post('/verificar-email', [AuthController::class, 'verify'], [$csrf]);
$router->post('/verificar-email/reenviar', [AuthController::class, 'resendVerification'], [$csrf]);
$router->post('/redefinir-senha', [AuthController::class, 'resetPassword'], [$csrf]);
$router->post('/api/password-strength', [AuthController::class, 'passwordStrength'], [$csrf]);

// Perfil e configurações pessoais, antes das rotas genéricas de admin/staff.
foreach (['cliente' => 'client', 'staff' => 'staff', 'admin' => 'admin'] as $prefix => $profileRole) {
    $profileMiddleware = [$auth, RoleMiddleware::class . ':' . $profileRole];
    $router->get('/' . $prefix . '/perfil', [ProfileController::class, 'show'], $profileMiddleware);
    $router->post('/' . $prefix . '/perfil', [ProfileController::class, 'update'], [...$profileMiddleware, $csrf]);
    $router->get('/' . $prefix . '/configuracoes', [ProfileController::class, 'settings'], $profileMiddleware);
}
$router->get('/conta/foto', [ProfileController::class, 'photo'], [$auth]);
$router->get('/conta/alterar-senha', [ProfileController::class, 'changePasswordForm'], [$auth]);
$router->post('/conta/alterar-senha', [ProfileController::class, 'changePassword'], [$auth, $csrf]);
$router->post('/conta/revogar-acessos', [ProfileController::class, 'revokeRemembered'], [$auth, $csrf]);

// GET - DASHBOARD
$router->get('/dashboard', [DashboardController::class, 'index'], [$auth]);
$router->get('/cliente', [DashboardController::class, 'client'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/staff', [DashboardController::class, 'staff'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/admin', [DashboardController::class, 'admin'], [$auth, RoleMiddleware::class . ':admin']);

// GET - CLIENTE
$router->get('/cliente/pets', [ClientController::class, 'pets'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/pets/novo', [ClientController::class, 'petForm'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/pets/{id}/editar', [ClientController::class, 'editPet'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/pets/{id}/foto', [ClientController::class, 'petPhoto'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/profissionais/{id}/foto', [ClientController::class, 'staffPhoto'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/pets/{id}', [ClientController::class, 'petProfile'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/servicos', [ClientController::class, 'services'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/agendamentos', [ClientController::class, 'appointments'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/agendamento/disponibilidade', [ClientController::class, 'bookingAvailability'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/cliente/agendamento/{step}', [ClientController::class, 'booking'], [$auth, RoleMiddleware::class . ':client']);

// POST - CLIENTE
$router->post('/cliente/pets', [ClientController::class, 'storePet'], [$auth, RoleMiddleware::class . ':client', $csrf]);
$router->post('/cliente/pets/{id}', [ClientController::class, 'updatePet'], [$auth, RoleMiddleware::class . ':client', $csrf]);
$router->post('/cliente/agendamento/{step}', [ClientController::class, 'saveBookingStep'], [$auth, RoleMiddleware::class . ':client', $csrf]);
$router->post('/cliente/agendamentos/{id}/cancelar', [ClientController::class, 'cancelAppointment'], [$auth, RoleMiddleware::class . ':client', $csrf]);

// DELETE - CLIENTE
$router->delete('/cliente/pets/{id}', [ClientController::class, 'deletePet'], [$auth, RoleMiddleware::class . ':client', $csrf]);

// GET - FUNCIONARIO
$router->get('/staff/pets', [StaffController::class, 'petsDirectory'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/pets/{petId}/foto', [StaffController::class, 'petPhoto'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/pets/{petId}', [StaffController::class, 'petProfile'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/clientes', [StaffController::class, 'clients'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/clientes/{clientId}/foto', [StaffController::class, 'clientPhoto'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/clientes/{clientId}/pets/{petId}/foto', [StaffController::class, 'clientPetPhoto'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/clientes/{clientId}/pets/{petId}', [StaffController::class, 'clientPet'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/clientes/{clientId}', [StaffController::class, 'clientProfile'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/atendimentos/{id}/foto', [StaffController::class, 'appointmentPetPhoto'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/atendimentos/{id}', [StaffController::class, 'appointmentDetail'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/staff/{section}', [StaffController::class, 'page'], [$auth, RoleMiddleware::class . ':staff']);

// POST - FUNCIONARIO
$router->post('/staff/agendamentos/{id}/status', [StaffController::class, 'appointmentStatus'], [$auth, RoleMiddleware::class . ':staff', $csrf]);

// GET - ADMIN
$router->get('/admin/clientes/{id}/foto', [AdminController::class, 'clientPhoto'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/clientes/{id}/pets/{petId}/foto', [AdminController::class, 'clientPetPhoto'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/clientes/{id}', [AdminController::class, 'clientProfile'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/funcionarios/{id}', [AdminController::class, 'staffProfile'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/pets/{id}/foto', [AdminController::class, 'petPhoto'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/pets/{id}', [AdminController::class, 'petProfile'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/{section}/novo', [AdminController::class, 'form'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/{section}/{id}/editar', [AdminController::class, 'form'], [$auth, RoleMiddleware::class . ':admin']);
$router->get('/admin/{section}', [AdminController::class, 'page'], [$auth, RoleMiddleware::class . ':admin']);

// POST - ADMIN
$router->post('/admin/clientes/{id}/promover', [AdminController::class, 'promoteClient'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
$router->post('/admin/funcionarios/{id}/promover-admin', [AdminController::class, 'promoteStaff'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
$router->post('/admin/clientes/{id}/excluir', [AdminController::class, 'deleteClient'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
$router->post('/admin/pets/{id}/excluir', [AdminController::class, 'deletePet'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
$router->post('/admin/{section}', [AdminController::class, 'save'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
$router->post('/admin/{section}/{id}', [AdminController::class, 'save'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
$router->post('/admin/{section}/{id}/status', [AdminController::class, 'status'], [$auth, RoleMiddleware::class . ':admin', $csrf]);
