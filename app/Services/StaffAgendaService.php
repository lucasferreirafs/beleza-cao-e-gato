<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Repositories\{AppointmentRepository, ServiceRepository};
use DateTimeImmutable;
use DateTimeZone;

final class StaffAgendaService
{
    public static function selection(mixed $date, mixed $view): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone((string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo')));
        $selected = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $date, $today->getTimezone()) : false;
        if (!$selected || $selected->format('Y-m-d') !== $date || (int) $selected->format('Y') < 1900 || (int) $selected->format('Y') > 2100) $selected = $today;
        $view = $view === 'week' ? 'week' : 'day';
        $start = $view === 'week' ? $selected->modify('-' . ((int) $selected->format('N') - 1) . ' days') : $selected;
        $end = $start->modify($view === 'week' ? '+7 days' : '+1 day');
        return compact('today', 'selected', 'view', 'start', 'end');
    }

    public function data(int $staffId, mixed $date, mixed $view): array
    {
        $selection = self::selection($date, $view);
        $repository = new AppointmentRepository();
        $rows = $repository->staffAgenda($staffId, $selection['start'], $selection['end']);
        $services = (new ServiceRepository())->active();
        usort($services, static fn(array $a, array $b): int => (int) $a['duration_minutes'] <=> (int) $b['duration_minutes']);
        $shortest = $services[0] ?? null;
        $days = [];
        for ($day = $selection['start']; $day < $selection['end']; $day = $day->modify('+1 day')) {
            $daily = array_values(array_filter($rows, static fn(array $row): bool =>
                (new DateTimeImmutable($row['scheduled_at']))->setTimezone($day->getTimezone())->format('Y-m-d') === $day->format('Y-m-d')));
            $slots = $shortest ? $repository->availableSlots($day->format('Y-m-d'), [(int) $shortest['id']], $staffId) : [];
            $days[] = ['date' => $day, 'items' => self::timeline($day, $daily, $slots, (int) ($shortest['duration_minutes'] ?? 0)),
                'cancelled' => array_values(array_filter($daily, static fn(array $row): bool => $row['status'] === 'cancelled'))];
        }
        return $selection + ['days' => $days];
    }

    /** Flow layout lets expanded details push following appointments down without overlap. */
    public static function timeline(DateTimeImmutable $day, array $rows, array $slots, int $minimumDuration): array
    {
        $items = [];
        $cursor = $day->setTime(8, 0);
        $close = $day->setTime(18, 0);
        $gap = static function (DateTimeImmutable $from, DateTimeImmutable $until) use (&$items, $slots, $minimumDuration): void {
            while ($from < $until) {
                $end = min($until, $from->setTime((int) $from->format('H') + 1, 0));
                $available = array_values(array_filter($slots, static function (string $time) use ($from, $end, $until, $minimumDuration): bool {
                    $at = $from->setTime((int) substr($time, 0, 2), (int) substr($time, 3, 2));
                    return $at >= $from && $at < $end && $at->modify("+{$minimumDuration} minutes") <= $until;
                }));
                $items[] = ['start' => $from, 'end' => $end, 'row' => null, 'slots' => $available];
                $from = $end;
            }
        };
        usort($rows, static fn(array $a, array $b): int => strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']));
        foreach ($rows as $row) {
            if ($row['status'] === 'cancelled') continue;
            $start = (new DateTimeImmutable($row['scheduled_at']))->setTimezone($day->getTimezone());
            $end = $start->modify('+' . max(1, (int) $row['duration_minutes']) . ' minutes');
            if ($start > $cursor) $gap($cursor, min($start, $close));
            $items[] = ['start' => $start, 'end' => $end, 'row' => $row, 'slots' => []];
            $cursor = max($cursor, $end);
        }
        if ($cursor < $close) $gap($cursor, $close);
        return $items;
    }
}
