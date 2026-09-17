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

        $recent = $pdo->query('
            SELECT a.id,a.scheduled_at,a.status,p.name pet_name,c.name client_name,s.name service_name,st.name staff_name 
            FROM appointments a 
            JOIN pets p 
            ON p.id=a.pet_id 
            JOIN users c ON c.id=p.owner_id 
            JOIN services s 
            ON s.id=a.service_id 
            LEFT JOIN users st 
            ON st.id=a.staff_id 
            ORDER BY a.created_at DESC 
            LIMIT 8
        ')->fetchAll();

        $serviceStats = $pdo->query('
            SELECT s.name,count(a.id) total 
            FROM services s 
            LEFT JOIN appointments a 
            ON a.service_id=s.id 
            GROUP BY s.id,s.name 
            ORDER BY total DESC,s.name 
            LIMIT 5
        ')->fetchAll();

        return compact('counts', 'recent', 'serviceStats');
    }

    public function staff(int $staffId): array
    {
        $s = Database::connection()->prepare("
            SELECT a.id,a.scheduled_at,a.status,a.price,p.name pet_name,c.name client_name,sv.name service_name 
            FROM appointments a 
            JOIN pets p 
            ON p.id=a.pet_id 
            JOIN users c 
            ON c.id=p.owner_id 
            JOIN services sv 
            ON sv.id=a.service_id 
            WHERE a.staff_id=:staff 
            AND a.scheduled_at::date=current_date 
            ORDER BY a.scheduled_at
        ");

        $s->execute(['staff' => $staffId]);
        $appointments = $s->fetchAll();
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
