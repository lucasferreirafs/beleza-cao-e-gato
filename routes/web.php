<?php

use App\Controllers\AuthController;

$router->get('/', [AuthController::class, 'landing']);