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
        $label = match ($section) {
            'pets' => $role === 'client' ? 'Meus pets' : 'Pets', 'agendamentos' => 'Agendamentos',
            'agendamento' => 'Agendamentos', 'perfil' => 'Meu Perfil', 'configuracoes' => 'Configurações',
            'servicos' => 'Serviços', 'clientes' => 'Clientes', 'funcionarios' => 'Funcionários',
            'agenda' => 'Minha Agenda', 'atendimentos' => 'Atendimentos', 'historico' => 'Histórico',
            'relatorios' => 'Relatórios', default => $title,
        };
        $crumbs[] = ['label' => $label, 'url' => count($segments) > 2 ? $home . '/' . ($section === 'agendamento' ? 'agendamentos' : $section) : null];
        if ($section === 'agendamento') {
            $crumbs[] = ['label' => 'Novo agendamento · Etapa ' . max(1, min(5, (int) ($segments[2] ?? 1))), 'url' => null];
        } elseif (count($segments) > 2) {
            $crumbs[] = ['label' => $title, 'url' => null];
        }
        return $crumbs;
    }
}
