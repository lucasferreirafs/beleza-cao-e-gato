<?php

use App\Controllers\AuthController;

$router->get('/', [AuthController::class, 'landing']);
$router->get('/login', [AuthController::class,'loginForm']);