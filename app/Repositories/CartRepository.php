<?php

namespace App\Repositories;

use PDO;

class CartRepository extends BaseRepository
{
    protected string $table = 'carts';

    public function getOrCreateCart(int $userId): int
    {
        $sql = "SELECT id FROM carts WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $cartId = $stmt->fetchColumn();

        if ($cartId) {
            return (int)$cartId;
        }

        $insertSql = "INSERT INTO carts (user_id, created_at, updated_at) VALUES (:user_id, NOW(), NOW())";
        $insertStmt = $this->db->prepare($insertSql);
        $insertStmt->execute(['user_id' => $userId]);

        return (int)$this->db->lastInsertId();
    }

    public function getCartByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM carts WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cart ?: null;
    }

    public function getCartItems(int $cartId): array
    {
        $sql = "SELECT ci.id AS cart_item_id, ci.cart_id, ci.product_id, ci.quantity,
                       p.name AS product_name, p.slug AS product_slug, p.brand,
                       p.price AS current_price, p.stock_quantity, p.status AS product_status,
                       p.weight, p.package_length, p.package_width, p.package_height,
                       p.warranty_months, p.condition,
                       c.name AS category_name, c.icon_class AS category_icon,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE ci.cart_id = :cart_id
                ORDER BY ci.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cart_id' => $cartId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCartItem(int $cartId, int $productId): ?array
    {
        $sql = "SELECT * FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cart_id' => $cartId,
            'product_id' => $productId
        ]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    public function addItem(int $cartId, int $productId, int $quantity): bool
    {
        $existing = $this->getCartItem($cartId, $productId);

        if ($existing) {
            $newQuantity = (int)$existing['quantity'] + $quantity;
            $sql = "UPDATE cart_items SET quantity = :quantity, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'quantity' => $newQuantity,
                'id' => $existing['id']
            ]);
        }

        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, created_at, updated_at) 
                VALUES (:cart_id, :product_id, :quantity, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }

    public function updateQuantity(int $cartId, int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $productId);
        }

        $sql = "UPDATE cart_items SET quantity = :quantity, updated_at = NOW() 
                WHERE cart_id = :cart_id AND product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'quantity' => $quantity,
            'cart_id' => $cartId,
            'product_id' => $productId
        ]);
    }

    public function removeItem(int $cartId, int $productId): bool
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'cart_id' => $cartId,
            'product_id' => $productId
        ]);
    }

    public function clearCart(int $cartId): bool
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['cart_id' => $cartId]);
    }

    public function getItemCount(int $cartId): int
    {
        $sql = "SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cart_id' => $cartId]);
        return (int)$stmt->fetchColumn();
    }
}
