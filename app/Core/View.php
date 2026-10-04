<?php
declare(strict_types=1);
namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'app'): void
    {
        $root = dirname(__DIR__, 2) . '/resources/views/';
        $viewFile = $root . $template . '.php';

        if (!is_file($viewFile))
            throw new RuntimeException("View não encontrada: {$template}");

        $pageClass = 'page-' . str_replace(['pages/', '/'], ['', '-'], $template);
        if ($template === 'pages/staff/appointment-detail') $pageClass .= ' page-staff-page';
        if (in_array($template, ['pages/admin/client-profile', 'pages/admin/pet-profile', 'pages/admin/staff-profile'], true)) {
            // Reuse the admin shell styles shared by the management-list pages.
            $pageClass .= ' page-admin-list';
        }
        if (in_array($template, ['pages/client/settings', 'pages/errors/upload'], true) && $layout === 'app') {
            $pageClass .= ' page-client-profile';
        }

        extract($data, EXTR_SKIP);
        ob_start();
        
        require $viewFile;
        $content = (string) ob_get_clean();
        require $root . 'layouts/' . $layout . '.php';
    }
}
