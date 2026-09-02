<?php

use App\Controllers\AuthController;

// GET
$router->get('/', [AuthController::class, 'landing']);
$router->get('/login', [AuthController::class,'loginForm']);
$router->get('/cadastro', [AuthController::class, 'registerForm']);


// POST


// DELETE