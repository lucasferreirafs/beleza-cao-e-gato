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

        extract($data, EXTR_SKIP);
        ob_start();
        
        require $viewFile;
        $content = (string) ob_get_clean();
        require $root . 'layouts/' . $layout . '.php';
    }
}
