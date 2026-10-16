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

        $headerName = ($layout === 'main') ? 'header.php' : "{$layout}_header.php";
        $footerName = ($layout === 'main') ? 'footer.php' : "{$layout}_footer.php";

        $layoutHeader = $viewsDir . 'layouts/' . $headerName;
        $layoutFooter = $viewsDir . 'layouts/' . $footerName;

        if (file_exists($layoutHeader)) {
            require $layoutHeader;
        }

        require $targetViewFile;

        if (file_exists($layoutFooter)) {
            require $layoutFooter;
        }
    }
}
