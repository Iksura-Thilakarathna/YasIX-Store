<?php

namespace App\Helpers;

use Exception;

class View
{
    public static function render(string $viewPath, array $data = [], ?string $layout = 'main'): void
    {
        extract($data);
        $viewsDir = dirname(__DIR__, 2) . '/views/';
        $targetViewFile = $viewsDir . trim($viewPath, '/') . '.php';

        if (!file_exists($targetViewFile)) {
            throw new Exception("View file not found: {$targetViewFile}");
        }

        if ($layout === null) {
            require $targetViewFile;
            return;
        }

        $layoutHeader = $viewsDir . 'layouts/header.php';
        $layoutFooter = $viewsDir . 'layouts/footer.php';

        if (file_exists($layoutHeader)) {
            require $layoutHeader;
        }

        require $targetViewFile;

        if (file_exists($layoutFooter)) {
            require $layoutFooter;
        }
    }
}
