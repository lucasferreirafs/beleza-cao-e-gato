<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

final class DashboardRepository
{
    public function client(int $userId): array
    {
        $pdo = Database::connection();
        $pets = (new PetRepository())->allForUser($userId);
        $appointments = (new AppointmentRepository())->allForUser($userId);
        $upcoming = current(
            array_filter(
                array_reverse($appointments),
                static fn(array $a): bool => strtotime($a['scheduled_at']) >= time() && !in_array($a['status'], ['cancelled', 'canceled'], true)
            )
        ) ?: null;

        $history = array_values(
            array_filter(
                $appointments,
                static fn(array $a): bool => in_array($a['status'], ['completed', 'done'], true)
            )
        );
        return compact('pets', 'appointments', 'upcoming', 'history');
    }

    public function admin(): array
    {
        $pdo = Database::connection();
        $counts = $pdo->query("
            SELECT (SELECT count(*) 
            FROM users 
            WHERE role='client') clients,
            (SELECT count(*) FROM pets) pets,
            (SELECT count(*) FROM users WHERE role='staff' AND status='active') staff,
            (SELECT count(*) FROM appointments WHERE scheduled_at::date=current_date) appointments_today
        ")->fetch();

        $repository = new AppointmentRepository();
        $recent = $repository->allForAdmin(true);
        $totals = [];
        foreach ($repository->allForAdmin() as $appointment) {
            foreach ($appointment['services'] as $service) {
                $id = (int) $service['id'];
                $totals[$id] ??= ['name' => $service['name'], 'total' => 0];
                $totals[$id]['total']++;
            }
        }
        $serviceStats = array_values($totals);
        usort($serviceStats, static fn(array $a, array $b): int => $b['total'] <=> $a['total'] ?: strcmp($a['name'], $b['name']));
        $serviceStats = array_slice($serviceStats, 0, 5);

        return compact('counts', 'recent', 'serviceStats');
    }

    public function staff(int $staffId): array
    {
        $appointments = (new AppointmentRepository())->allForStaff($staffId, true);
        usort($appointments, static fn(array $a, array $b): int => strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']));
        $counts = [
            'total' => count($appointments),
            'completed' => 0,
            'pending' => 0,
            'cancelled' => 0
        ];

        foreach ($appointments as $a) {
            if (in_array($a['status'], ['completed', 'done'], true))
                $counts['completed']++;
            elseif (in_array($a['status'], ['cancelled', 'canceled'], true))
                $counts['cancelled']++;
            else
                $counts['pending']++;
        }
        return compact('appointments', 'counts');
    }
}
