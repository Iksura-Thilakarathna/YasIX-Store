<?php

namespace App\Services;

use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

class CartService extends BaseService
{
    private CartRepository $cartRepository;
    private ProductRepository $productRepository;

    public function __construct(
        ?CartRepository $cartRepository = null,
        ?ProductRepository $productRepository = null
    ) {
        $this->cartRepository = $cartRepository ?? new CartRepository();
        $this->productRepository = $productRepository ?? new ProductRepository();
    }

    public function getCart(int $userId): array
    {
        $cartId = $this->cartRepository->getOrCreateCart($userId);
        $rawItems = $this->cartRepository->getCartItems($cartId);

        $items = [];
        $subtotal = 0.00;
        $totalItems = 0;
        $hasUnavailable = false;

        foreach ($rawItems as $item) {
            $unitPrice = (float)$item['current_price'];
            $quantity = (int)$item['quantity'];
            $stockQuantity = (int)$item['stock_quantity'];
            $isAvailable = ($item['product_status'] === 'AVAILABLE' && $stockQuantity > 0);

            // Cap display quantity to stock if currently exceeded
            $effectiveQuantity = $quantity;
            if ($quantity > $stockQuantity && $stockQuantity > 0) {
                $effectiveQuantity = $stockQuantity;
                // Auto-sync cart item quantity to available stock
                $this->cartRepository->updateQuantity($cartId, (int)$item['product_id'], $effectiveQuantity);
            }

            if (!$isAvailable) {
                $hasUnavailable = true;
            }

            $lineTotal = $unitPrice * $effectiveQuantity;
            $subtotal += $lineTotal;
            $totalItems += $effectiveQuantity;

            $items[] = [
                'cart_item_id' => $item['cart_item_id'],
                'product_id' => (int)$item['product_id'],
                'product_name' => $item['product_name'],
                'product_slug' => $item['product_slug'],
                'brand' => $item['brand'],
                'unit_price' => $unitPrice,
                'formatted_price' => formatCurrency($unitPrice),
                'quantity' => $effectiveQuantity,
                'stock_quantity' => $stockQuantity,
                'line_total' => $lineTotal,
                'formatted_line_total' => formatCurrency($lineTotal),
                'is_available' => $isAvailable,
                'product_status' => $item['product_status'],
                'weight' => (float)$item['weight'],
                'package_length' => (float)$item['package_length'],
                'package_width' => (float)$item['package_width'],
                'package_height' => (float)$item['package_height'],
                'warranty_months' => (int)$item['warranty_months'],
                'condition' => $item['condition'],
                'category_name' => $item['category_name'],
                'category_icon' => $item['category_icon'] ?? 'fa-solid fa-microchip',
                'primary_image' => $item['primary_image'] ?? null
            ];
        }

        return [
            'cart_id' => $cartId,
            'items' => $items,
            'item_count' => $totalItems,
            'subtotal' => $subtotal,
            'formatted_subtotal' => formatCurrency($subtotal),
            'has_unavailable' => $hasUnavailable,
            'is_empty' => empty($items)
        ];
    }

    public function addToCart(int $userId, int $productId, int $quantity = 1): array
    {
        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Quantity must be at least 1 unit.'
            ];
        }

        $product = $this->productRepository->findByIdWithDetails($productId);

        if (!$product || $product['status'] !== 'AVAILABLE') {
            return [
                'success' => false,
                'message' => 'This product is currently unavailable for purchase.'
            ];
        }

        $stock = (int)$product['stock_quantity'];
        if ($stock <= 0) {
            return [
                'success' => false,
                'message' => 'Sorry, this item is out of stock.'
            ];
        }

        $cartId = $this->cartRepository->getOrCreateCart($userId);
        $existing = $this->cartRepository->getCartItem($cartId, $productId);
        $currentInCart = $existing ? (int)$existing['quantity'] : 0;
        $newTotal = $currentInCart + $quantity;

        if ($newTotal > $stock) {
            return [
                'success' => false,
                'message' => "Only {$stock} units available in stock. You already have {$currentInCart} in your cart."
            ];
        }

        $this->cartRepository->addItem($cartId, $productId, $quantity);

        return [
            'success' => true,
            'message' => "Added {$product['name']} to your cyber cart.",
            'cart_count' => $this->cartRepository->getItemCount($cartId)
        ];
    }

    public function updateQuantity(int $userId, int $productId, int $quantity): array
    {
        $cartId = $this->cartRepository->getOrCreateCart($userId);

        if ($quantity <= 0) {
            $this->cartRepository->removeItem($cartId, $productId);
            return [
                'success' => true,
                'message' => 'Item removed from your cart.',
                'cart_count' => $this->cartRepository->getItemCount($cartId)
            ];
        }

        $product = $this->productRepository->findByIdWithDetails($productId);

        if (!$product) {
            $this->cartRepository->removeItem($cartId, $productId);
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }

        $stock = (int)$product['stock_quantity'];
        if ($quantity > $stock) {
            // Cap at available stock
            $this->cartRepository->updateQuantity($cartId, $productId, $stock);
            return [
                'success' => false,
                'message' => "Quantity adjusted to maximum available stock ({$stock} units).",
                'cart_count' => $this->cartRepository->getItemCount($cartId)
            ];
        }

        $this->cartRepository->updateQuantity($cartId, $productId, $quantity);

        return [
            'success' => true,
            'message' => 'Cart updated successfully.',
            'cart_count' => $this->cartRepository->getItemCount($cartId)
        ];
    }

    public function removeFromCart(int $userId, int $productId): array
    {
        $cartId = $this->cartRepository->getOrCreateCart($userId);
        $this->cartRepository->removeItem($cartId, $productId);

        return [
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => $this->cartRepository->getItemCount($cartId)
        ];
    }

    public function clearCart(int $userId): array
    {
        $cartId = $this->cartRepository->getOrCreateCart($userId);
        $this->cartRepository->clearCart($cartId);

        return [
            'success' => true,
            'message' => 'Your cart has been cleared.',
            'cart_count' => 0
        ];
    }

    public function getCartCount(int $userId): int
    {
        $cart = $this->cartRepository->getCartByUserId($userId);
        if (!$cart) {
            return 0;
        }
        return $this->cartRepository->getItemCount((int)$cart['id']);
    }
}
