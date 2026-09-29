<?php
require_once __DIR__ . '/../models/Shop.php';

class ShopController {
    private function requireAuth(): int {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }
        return (int)$_SESSION['user_id'];
    }

    private function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data);
    }

    // GET /shop/items[?category=slug]
    public function index(): void {
        $categorySlug = $_GET['category'] ?? null;
        $model        = new ShopItem();
        $items        = $model->getAll($categorySlug);
        $categories   = $model->getCategories();
        $this->json(['success' => true, 'items' => $items, 'categories' => $categories]);
    }

    // GET /shop/inventory
    public function inventory(): void {
        $userId   = $this->requireAuth();
        $model    = new ShopItem();
        $items    = $model->getUserInventory($userId);
        $equipped = $model->getEquipped($userId);
        $this->json(['success' => true, 'inventory' => $items, 'equipped' => $equipped]);
    }

    // GET /shop/equipped — returns all equipped slots keyed by category_slug
    public function equipped(): void {
        $userId   = $this->requireAuth();
        $model    = new ShopItem();
        $equipped = $model->getEquipped($userId);
        $this->json(['success' => true, 'equipped' => $equipped]);
    }

    // POST /shop/:id/purchase
    public function purchase(int $itemId): void {
        $userId = $this->requireAuth();
        $model  = new ShopItem();
        $result = $model->purchase($userId, $itemId);
        $this->json($result, $result['success'] ? 200 : 400);
    }

    // POST /shop/:id/equip
    public function equip(int $itemId): void {
        $userId = $this->requireAuth();
        $model  = new ShopItem();
        $result = $model->equip($userId, $itemId);
        $this->json($result, $result['success'] ? 200 : 400);
    }

    // POST /shop/unequip/:slot
    public function unequip(string $slot): void {
        $userId = $this->requireAuth();
        $model  = new ShopItem();
        $result = $model->unequip($userId, $slot);
        $this->json($result);
    }
}
