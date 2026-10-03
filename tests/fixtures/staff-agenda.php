<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$_SERVER['REQUEST_URI'] = '/staff/agenda';
$_SESSION = ['user' => ['id' => 1, 'name' => 'Profissional QA', 'role' => 'staff']];
$agenda = \App\Services\StaffAgendaService::selection('2030-10-01', $argv[1] ?? 'day');
$long = ($argv[2] ?? '') === 'long';
$row = ['id' => 1, 'scheduled_at' => '2030-10-01 08:00:00-03:00', 'pet_name' => $long ? str_repeat('NomeLongo', 12) : 'Luna',
    'service_name' => 'Banho + Tosa Premium', 'client_name' => 'Maria Eduarda Silva', 'client_phone' => '(11) 99999-0000',
    'client_email' => 'qa@example.invalid', 'duration_minutes' => 80, 'status' => 'pending', 'pet_species' => 'Cachorro', 'pet_breed' => 'Golden',
    'pet_weight' => '20.5', 'pet_coat_color' => 'Caramelo', 'pet_allergies' => 'Shampoo perfumado', 'pet_special_needs' => 'Secar com cuidado',
    'notes' => '<script>alert(1)</script>', 'services' => [['name' => 'Banho', 'duration_minutes' => 40], ['name' => 'Tosa Premium', 'duration_minutes' => 40]]];
$agenda['days'] = [];
for ($day = $agenda['start']; $day < $agenda['end']; $day = $day->modify('+1 day')) {
    $daily = [array_replace($row, ['scheduled_at' => $day->setTime(8, 0)->format('c')]),
        array_replace($row, ['id' => 2, 'status' => 'in_progress', 'scheduled_at' => $day->setTime(14, 0)->format('c')]),
        array_replace($row, ['id' => 3, 'status' => 'completed', 'scheduled_at' => $day->setTime(16, 0)->format('c')])];
    $agenda['days'][] = ['date' => $day, 'items' => \App\Services\StaffAgendaService::timeline($day, $daily, ['10:00', '10:30', '11:00', '12:00'], 15),
        'cancelled' => [array_replace($row, ['id' => 4, 'status' => 'cancelled'])]];
}
\App\Core\View::render('pages/staff/page', ['title' => 'Minha Agenda', 'section' => 'agenda', 'active' => 'agenda', 'agenda' => $agenda]);
