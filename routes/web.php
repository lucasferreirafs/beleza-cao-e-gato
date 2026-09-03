<?php

use App\Controllers\AuthController;

// GET
$router->get('/', [AuthController::class, 'landing']);
$router->get('/login', [AuthController::class,'loginForm']);
$router->get('/cadastro', [AuthController::class, 'registerForm']);
$router->get('/recuperar-senha', [AuthController::class, 'recovery']);
$router->get('/verificar-email', [AuthController::class, 'verification']);


// POST


// DELETE