<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;
use App\Core\Env;
use DateTimeImmutable;
use DateTimeZone;

final class DashboardRepository
{
    public function client(string $userId): array
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
        $repository = new AppointmentRepository();
        $recent = $repository->allForAdmin(true);
        $timezone = new DateTimeZone((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
        $now = new DateTimeImmutable('now', $timezone);
        $todayStart = $now->setTime(0, 0);
        $tomorrow = $todayStart->modify('+1 day');
        $todayParams = [
            'start' => $todayStart->format('c'),
            'end' => $tomorrow->format('c'),
        ];

        $dailyStatsQuery = $pdo->prepare("SELECT
                count(*) AS total,
                count(*) FILTER (WHERE status='pending') AS pending,
                count(*) FILTER (WHERE status='in_progress') AS in_progress,
                count(*) FILTER (WHERE status='completed') AS completed,
                count(*) FILTER (WHERE status='cancelled') AS cancelled
            FROM appointments
            WHERE scheduled_at >= :start AND scheduled_at < :end");
        $dailyStatsQuery->execute($todayParams);
        $todayStats = array_map('intval', $dailyStatsQuery->fetch() ?: []);
        $todayStats += ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];

        $attentionQuery = $pdo->prepare("SELECT count(DISTINCT p.id)
            FROM appointments a
            JOIN pets p ON p.id=a.pet_id
            WHERE a.scheduled_at >= :start AND a.scheduled_at < :end
                AND a.status IN ('pending', 'in_progress')
                AND (NULLIF(btrim(p.allergies), '') IS NOT NULL
                    OR NULLIF(btrim(p.special_needs), '') IS NOT NULL)");
        $attentionQuery->execute($todayParams);
        $petsNeedingAttention = (int) $attentionQuery->fetchColumn();

        $storageCapabilities = (new AppointmentStorage($pdo))->capabilities();
        $serviceItemsAvailable = $storageCapabilities['items'];
        $durationExpression = $storageCapabilities['duration']
            ? 'a.duration_minutes'
            : ($serviceItemsAvailable ? 'COALESCE(items.duration_minutes, service.duration_minutes)' : 'service.duration_minutes');
        $serviceNames = $serviceItemsAvailable
            ? "COALESCE(NULLIF(items.service_names, ''), service.name)"
            : 'service.name';
        $serviceJoin = $serviceItemsAvailable
            ? "LEFT JOIN (
                    SELECT appointment_id,
                        string_agg(service_name, ' + ' ORDER BY position) AS service_names,
                        sum(duration_minutes) AS duration_minutes
                    FROM appointment_services
                    GROUP BY appointment_id
                ) items ON items.appointment_id=a.id"
            : '';
        $nextAppointmentQuery = $pdo->prepare("SELECT a.id, a.scheduled_at, {$durationExpression} AS duration_minutes,
                p.name AS pet_name, c.name AS client_name, staff.name AS staff_name,
                {$serviceNames} AS service_names
            FROM appointments a
            JOIN pets p ON p.id=a.pet_id
            JOIN users c ON c.id=p.owner_id
            JOIN services service ON service.id=a.service_id
            LEFT JOIN users staff ON staff.id=a.staff_id
            {$serviceJoin}
            WHERE a.status='pending' AND a.scheduled_at >= :now AND a.scheduled_at < :end
            ORDER BY a.scheduled_at ASC, a.id ASC
            LIMIT 1");
        $nextAppointmentQuery->execute([
            'now' => $now->format('c'),
            'end' => $tomorrow->format('c'),
        ]);
        $nextAppointment = $nextAppointmentQuery->fetch() ?: null;

        $currentMonth = (new DateTimeImmutable('now', $timezone))->modify('first day of this month')->setTime(0, 0);
        $rangeStart = $currentMonth->modify('-5 months');
        $rangeEnd = $currentMonth->modify('+1 month');

        $monthlyQuery = $pdo->prepare("SELECT date_trunc('month', scheduled_at) AS month_bucket, count(*) AS total
            FROM appointments
            WHERE scheduled_at >= :start AND scheduled_at < :end
            GROUP BY month_bucket
            ORDER BY month_bucket");
        $monthlyQuery->execute([
            'start' => $rangeStart->format('c'),
            'end' => $rangeEnd->format('c'),
        ]);
        $monthlyCounts = [];
        foreach ($monthlyQuery->fetchAll() as $row) {
            $month = (new DateTimeImmutable((string) $row['month_bucket'], $timezone))->setTimezone($timezone)->format('Y-m');
            $monthlyCounts[$month] = (int) $row['total'];
        }

        $monthLabels = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
        ];
        $monthlyStats = [];
        for ($month = $rangeStart; $month < $rangeEnd; $month = $month->modify('+1 month')) {
            $key = $month->format('Y-m');
            $monthlyStats[] = [
                'key' => $key,
                'label' => $monthLabels[(int) $month->format('n')],
                'full_label' => $monthLabels[(int) $month->format('n')] . ' ' . $month->format('Y'),
                'total' => $monthlyCounts[$key] ?? 0,
            ];
        }

        // Prefer the service snapshots in appointment_services, falling back to
        // appointments.service_id only for historical rows without item records.
        $hasServiceItems = (new AppointmentStorage($pdo))->capabilities()['items'];
        $serviceSql = $hasServiceItems
            ? "WITH service_occurrences AS (
                    SELECT items.service_id, items.service_name AS name
                    FROM appointment_services items
                    UNION ALL
                    SELECT appointment.service_id, service.name
                    FROM appointments appointment
                    JOIN services service ON service.id = appointment.service_id
                    WHERE NOT EXISTS (
                        SELECT 1 FROM appointment_services items
                        WHERE items.appointment_id = appointment.id
                    )
                )
                SELECT service_id, name, count(*) AS total
                FROM service_occurrences
                GROUP BY service_id, name
                ORDER BY total DESC, name ASC"
            : "SELECT appointment.service_id, service.name, count(*) AS total
                FROM appointments appointment
                JOIN services service ON service.id = appointment.service_id
                GROUP BY appointment.service_id, service.name
                ORDER BY total DESC, service.name ASC";
        $serviceQuery = $pdo->query($serviceSql);
        $serviceRows = array_map(static fn(array $row): array => [
            'id' => (string) $row['service_id'],
            'name' => (string) $row['name'],
            'total' => (int) $row['total'],
        ], $serviceQuery->fetchAll());

        $serviceTotal = array_sum(array_column($serviceRows, 'total'));
        $serviceDistribution = array_slice($serviceRows, 0, 3);
        $otherServicesTotal = $serviceTotal - array_sum(array_column($serviceDistribution, 'total'));
        if ($otherServicesTotal > 0) {
            $serviceDistribution[] = ['id' => 'other', 'name' => 'Outros', 'total' => $otherServicesTotal];
        }

        // Largest-remainder rounding ensures visible percentages add up to 100%.
        if ($serviceTotal > 0) {
            $remainders = [];
            $allocated = 0;
            foreach ($serviceDistribution as $index => $service) {
                $exact = $service['total'] * 100 / $serviceTotal;
                $serviceDistribution[$index]['percentage'] = (int) floor($exact);
                $allocated += $serviceDistribution[$index]['percentage'];
                $remainders[$index] = $exact - floor($exact);
            }
            arsort($remainders);
            foreach (array_slice(array_keys($remainders), 0, 100 - $allocated) as $index) {
                $serviceDistribution[$index]['percentage']++;
            }
        }

        return compact('todayStats', 'nextAppointment', 'petsNeedingAttention', 'recent', 'monthlyStats', 'serviceDistribution', 'serviceTotal');
    }

    public function staff(string $staffId, string $period = 'today'): array
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
