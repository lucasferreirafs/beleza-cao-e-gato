<?php
declare(strict_types=1);

namespace App\Support;

final class Navigation
{
    public static function home(string $role): string
    {
        return match ($role) { 'admin' => '/admin', 'staff' => '/staff', default => '/cliente' };
    }

    /** @return array<array{label: string, url: ?string}> */
    public static function breadcrumbs(string $path, string $role, string $title): array
    {
        $path = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');
        $home = self::home($role);
        $crumbs = [['label' => match ($role) { 'admin' => 'Administração', 'staff' => 'Área do colaborador', default => 'Início' }, 'url' => $home]];
        if ($path === $home || $path === '/dashboard') {
            $crumbs[0]['url'] = null;
            return $crumbs;
        }
        $segments = explode('/', trim($path, '/'));
        $section = $segments[1] ?? '';
        if ($role === 'staff' && $section === 'clientes' && ($segments[3] ?? '') === 'pets' && Uuid::parse($segments[2] ?? null)) {
            return [...$crumbs,
                ['label' => 'Clientes', 'url' => '/staff/clientes'],
                ['label' => 'Perfil do cliente', 'url' => '/staff/clientes/' . $segments[2]],
                ['label' => $title, 'url' => null],
            ];
        }
        $label = match ($section) {
            'pets' => $role === 'client' ? 'Meus pets' : 'Pets', 'agendamentos' => 'Agendamentos',
            'agendamento' => 'Agendamentos', 'perfil' => 'Meu Perfil', 'configuracoes' => 'Configurações',
            'servicos' => 'Serviços', 'clientes' => 'Clientes', 'funcionarios' => 'Funcionários',
            'agenda' => 'Minha Agenda', 'atendimentos' => 'Atendimentos',
            'relatorios' => 'Relatórios', default => $title,
        };
        $crumbs[] = ['label' => $label, 'url' => count($segments) > 2 ? $home . '/' . ($section === 'agendamento' ? 'agendamentos' : $section) : null];
        if ($section === 'agendamento') {
            $stepLabel = match ($segments[2] ?? '') {
                'servico', '1' => 'Serviços',
                'escolha-pet', '2' => 'Pet',
                'profissional', 'data-e-horario', '3' => 'Data e horário',
                'confirmacao', '4' => 'Confirmação',
                default => 'Agendamento',
            };
            $crumbs[] = ['label' => 'Novo agendamento · ' . $stepLabel, 'url' => null];
        } elseif (count($segments) > 2) {
            $crumbs[] = ['label' => $title, 'url' => null];
        }
        return $crumbs;
    }
}
