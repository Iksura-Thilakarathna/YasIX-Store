<?php

namespace App\Controllers;

use App\Services\CartService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class CartController extends BaseController
{
    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    public function index(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $cart = $this->cartService->getCart($userId);

        View::render('cart/index', [
            'title' => 'Shopping Cart - YasIX Cyber Store',
            'cart' => $cart,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    public function add(): void
    {
        Session::start();

        if (!Session::has('user_id')) {
            if ($this->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'redirect' => '/login',
                    'message' => 'Please log in to add items to your cart.'
                ]);
                exit;
            }
            Session::setFlash('error', 'Please log in to add items to your cart.');
            header('Location: ' . getenv('APP_URL') . '/login');
            exit;
        }

        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CSRF::verifyToken($token)) {
            if ($this->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
                exit;
            }
            Session::setFlash('error', 'Security token mismatch. Please try again.');
            header('Location: ' . getenv('APP_URL') . '/products');
            exit;
        }

        $userId = (int)Session::get('user_id');
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $result = $this->cartService->addToCart($userId, $productId, $quantity);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('error', $result['message']);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? (getenv('APP_URL') . '/cart');
        header('Location: ' . $referer);
        exit;
    }

    public function update(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/cart');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        $result = $this->cartService->updateQuantity($userId, $productId, $quantity);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('error', $result['message']);
        }

        header('Location: ' . getenv('APP_URL') . '/cart');
        exit;
    }

    public function remove(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/cart');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $result = $this->cartService->removeFromCart($userId, $productId);

        Session::setFlash('success', $result['message']);
        header('Location: ' . getenv('APP_URL') . '/cart');
        exit;
    }

    public function clear(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/cart');
            exit;
        }

        $result = $this->cartService->clearCart($userId);
        Session::setFlash('success', $result['message']);
        header('Location: ' . getenv('APP_URL') . '/cart');
        exit;
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }
}
