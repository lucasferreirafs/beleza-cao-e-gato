<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, View};

final class LegalController
{
    public function terms(Request $request): void
    {
        View::render(
            'pages/legal/terms',
            ['title' => 'Termos de Uso'],
            'auth'
        );
    }
}
