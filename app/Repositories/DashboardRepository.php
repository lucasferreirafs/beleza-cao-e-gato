<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use App\Core\Env;
use DateTimeImmutable;
use DateTimeZone;

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
                static fn(array $a): bool => strtotime($a['scheduled_at']) >= time() && $a['status'] === 'pending'
            )
        ) ?: null;

        $history = array_values(
            array_filter(
                $appointments,
                static fn(array $a): bool => $a['status'] === 'completed'
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

    public function staff(int $staffId, string $period = 'today'): array
    {
        $period = $period === 'week' ? 'week' : 'today';
        $repository = new AppointmentRepository();
        $appointments = $repository->pendingTodayForStaff($staffId);
        $inProgressAppointments = $repository->inProgressTodayForStaff($staffId);
        usort($appointments, static fn(array $a, array $b): int => strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']));
        $nextAppointment = $repository->nextPendingForStaff($staffId);
        $pdo = Database::connection();
        $now = (new DateTimeImmutable($pdo->query('SELECT clock_timestamp()')->fetchColumn()))
            ->setTimezone(new DateTimeZone((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo')));
        $periodStart = $now->setTime(0, 0);
        if ($period === 'week') $periodStart = $periodStart->modify('-' . ((int) $now->format('N') - 1) . ' days');
        $periodEnd = $periodStart->modify($period === 'week' ? '+7 days' : '+1 day');
        $query = $pdo->prepare("SELECT count(*) total,
            count(*) FILTER (WHERE status='completed') completed,
            count(*) FILTER (WHERE status='pending') pending,
            count(*) FILTER (WHERE status='cancelled') cancelled
            FROM appointments WHERE staff_id=:staff AND scheduled_at>=:start AND scheduled_at<:end");
        $query->execute(['staff' => $staffId, 'start' => $periodStart->format('c'), 'end' => $periodEnd->format('c')]);
        $counts = array_map('intval', $query->fetch());
        return compact('appointments', 'inProgressAppointments', 'counts', 'nextAppointment', 'period', 'periodStart', 'periodEnd', 'now');
    }
}
