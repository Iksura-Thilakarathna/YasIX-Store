<?php

namespace App\Controllers;

class ErrorController extends BaseController
{
    public function notFound(): void
    {
        http_response_code(404);
        $this->render('404', [
            'title' => '404 - Page Not Found | YasIX Store'
        ], 'main');
    }
}
