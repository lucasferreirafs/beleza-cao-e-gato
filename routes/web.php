<?php

use App\Controllers\{AuthController, DashboardController};
use App\Middleware\{AuthMiddleware, RoleMiddleware};

$auth = AuthMiddleware::class;

// GET - AUTENTICAÇÃO
$router->get('/', [AuthController::class, 'landing']);
$router->get('/login', [AuthController::class,'loginForm']);
$router->get('/cadastro', [AuthController::class, 'registerForm']);
$router->get('/recuperar-senha', [AuthController::class, 'recovery']);
$router->get('/verificar-email', [AuthController::class, 'verification']);

// GET - DASHBOARD
$router->get('/dashboard', [DashboardController::class, 'index'], [$auth]);
$router->get('/cliente', [DashboardController::class, 'client'], [$auth, RoleMiddleware::class . ':client']);
$router->get('/staff', [DashboardController::class, 'staff'], [$auth, RoleMiddleware::class . ':staff']);
$router->get('/admin', [DashboardController::class, 'admin'], [$auth, RoleMiddleware::class . ':admin']);


// POST


// DELETE