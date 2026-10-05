<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$step = 3;
$date = '2030-01-07';
$time = $argv[1] ?? '';
$state = [];
$pets = $services = $selectedServices = [];
$staff = [['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Profissional QA']];
$slots = ['09:00', '10:00'];
$staffAvailability = [['id' => $staff[0]['id'], 'available' => $time === '09:00']];
$totalPrice = 50;
$totalDuration = 60;
require dirname(__DIR__, 2) . '/resources/views/pages/client/booking.php';
