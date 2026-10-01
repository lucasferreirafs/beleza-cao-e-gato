<?php
declare(strict_types=1);

// Render the real view and layout with synthetic data; no database or session files.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$scenario = $argv[1] ?? 'normal';
$_SERVER['REQUEST_URI'] = '/cliente';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION = ['user' => ['id' => 1, 'name' => 'Cliente QA', 'role' => 'client']];
$pets = [['id' => 1, 'name' => $scenario === 'long' ? str_repeat('PetSemEspacos', 8) : 'Joca',
    'species' => 'Cachorro', 'breed' => 'Golden Retriever', 'sex' => 'Macho']];
$upcoming = ['id' => 1, 'pet_id' => 1, 'pet_name' => $pets[0]['name'],
    'service_name' => $scenario === 'long' ? str_repeat('Banho + Tosa Completa + Hidratação ', 4) : 'Banho + Tosa Completa',
    'scheduled_at' => '2030-10-01 11:00:00', 'staff_name' => 'João da Silva Oliveira', 'status' => 'pending'];
$history = [];
$count = $scenario === 'single' ? 1 : ($scenario === 'pair' ? 2 : 5);
for ($index = 0; $index < $count; $index++) {
    $history[] = array_replace($upcoming, ['status' => 'completed', 'scheduled_at' => '2030-09-' . (25 - $index) . ' 11:00:00']);
}
if ($scenario === 'empty') { $pets = []; $upcoming = null; $history = []; }
\App\Core\View::render('pages/client/dashboard', compact('pets', 'upcoming', 'history') + ['title' => 'Dashboard', 'active' => 'dashboard']);
