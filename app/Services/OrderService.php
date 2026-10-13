<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;

class OrderService extends BaseService
{
    private OrderRepository $orderRepository;

    public const ORDER_STATUS_PENDING = 'PENDING';
    public const ORDER_STATUS_CONFIRMED = 'CONFIRMED';
    public const ORDER_STATUS_PROCESSING = 'PROCESSING';
    public const ORDER_STATUS_SHIPPED = 'SHIPPED';
    public const ORDER_STATUS_OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    public const ORDER_STATUS_DELIVERED = 'DELIVERED';
    public const ORDER_STATUS_CANCELLED = 'CANCELLED';
    public const ORDER_STATUS_DELIVERY_FAILED = 'DELIVERY_FAILED';
    public const ORDER_STATUS_RETURNED = 'RETURNED';

    public function __construct(?OrderRepository $orderRepository = null)
    {
        $this->orderRepository = $orderRepository ?? new OrderRepository();
    }

    /**
     * Retrieve paginated order history for an authenticated customer
     */
    public function getUserOrders(int $userId, int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, min(50, $limit));

        $data = $this->orderRepository->getUserOrders($userId, $page, $limit);

        // Format presentation fields
        foreach ($data['orders'] as &$order) {
            $order['formatted_total'] = formatCurrency((float)$order['total_amount']);
            $order['formatted_date'] = date('M d, Y - h:i A', strtotime($order['created_at']));
            $order['status_meta'] = $this->getStatusMetadata($order['status']);
            $order['payment_meta'] = $this->getPaymentStatusMetadata($order['payment_status']);
        }
        unset($order);

        return $data;
    }

    /**
     * Retrieve single order details with strict server-side customer ownership check
     */
    public function getOrderDetails(string $orderNumber, int $userId): ?array
    {
        $order = $this->orderRepository->getOrderWithDetails($orderNumber, $userId);

        if (!$order) {
            return null;
        }

        // Format presentation fields
        $order['formatted_total'] = formatCurrency((float)$order['total_amount']);
        $order['formatted_shipping_fee'] = formatCurrency((float)$order['shipping_fee']);
        $order['formatted_subtotal'] = formatCurrency((float)$order['total_amount'] - (float)$order['shipping_fee']);
        $order['formatted_date'] = date('F j, Y - h:i A', strtotime($order['created_at']));
        $order['formatted_delivered_at'] = !empty($order['delivered_at']) 
            ? date('F j, Y - h:i A', strtotime($order['delivered_at'])) 
            : null;

        $order['status_meta'] = $this->getStatusMetadata($order['status']);
        $order['payment_meta'] = $this->getPaymentStatusMetadata($order['payment_status']);

        // Check customer cancellation eligibility
        $order['is_cancellable'] = in_array($order['status'], [self::ORDER_STATUS_PENDING, self::ORDER_STATUS_CONFIRMED], true);

        // Calculate delivery estimate
        $createdTimestamp = strtotime($order['created_at']);
        $order['estimated_delivery_start'] = date('M d, Y', strtotime('+5 weekdays', $createdTimestamp));
        $order['estimated_delivery_end'] = date('M d, Y', strtotime('+7 weekdays', $createdTimestamp));

        return $order;
    }

    /**
     * Build tracking timeline milestones and metadata
     */
    public function getOrderTracking(string $orderNumber, int $userId): ?array
    {
        $order = $this->getOrderDetails($orderNumber, $userId);
        if (!$order) {
            return null;
        }

        $standardStages = [
            self::ORDER_STATUS_PENDING => [
                'step' => 1,
                'title' => 'Pending Confirmation',
                'description' => 'Order placed with Cash on Delivery and queued for dispatch verification.',
                'icon' => 'fa-solid fa-clock-rotate-left'
            ],
            self::ORDER_STATUS_CONFIRMED => [
                'step' => 2,
                'title' => 'Order Confirmed',
                'description' => 'Delivery address and hardware inventory confirmed by warehouse logistics.',
                'icon' => 'fa-solid fa-file-circle-check'
            ],
            self::ORDER_STATUS_PROCESSING => [
                'step' => 3,
                'title' => 'Processing & Packaging',
                'description' => 'Hardware components retrieved and packed with anti-static electro-protection.',
                'icon' => 'fa-solid fa-box-open'
            ],
            self::ORDER_STATUS_SHIPPED => [
                'step' => 4,
                'title' => 'Shipped',
                'description' => 'Handed over to domestic courier service for secure regional transportation.',
                'icon' => 'fa-solid fa-truck-fast'
            ],
            self::ORDER_STATUS_OUT_FOR_DELIVERY => [
                'step' => 5,
                'title' => 'Out for Delivery',
                'description' => 'Courier delivery personnel assigned and currently in transit to your address.',
                'icon' => 'fa-solid fa-motorcycle'
            ],
            self::ORDER_STATUS_DELIVERED => [
                'step' => 6,
                'title' => 'Delivered',
                'description' => 'Package delivered to recipient and Cash on Delivery payment collected.',
                'icon' => 'fa-solid fa-circle-check'
            ]
        ];

        $currentStatus = $order['status'];
        $isCancelled = ($currentStatus === self::ORDER_STATUS_CANCELLED);
        $isDeliveryFailed = ($currentStatus === self::ORDER_STATUS_DELIVERY_FAILED);
        $isReturned = ($currentStatus === self::ORDER_STATUS_RETURNED);

        $stageOrder = array_keys($standardStages);
        $currentIndex = array_search($currentStatus, $stageOrder, true);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $milestones = [];
        foreach ($standardStages as $statusKey => $stageInfo) {
            $state = 'upcoming';
            $isCurrent = false;

            if ($isCancelled || $isDeliveryFailed || $isReturned) {
                // If order ended in exception state, only milestones prior to transition are completed
                $state = 'cancelled';
            } elseif ($currentIndex !== false && $currentIndex >= 0) {
                $stageIndex = array_search($statusKey, $stageOrder, true);
                if ($stageIndex < $currentIndex) {
                    $state = 'completed';
                } elseif ($stageIndex === $currentIndex) {
                    $state = 'current';
                    $isCurrent = true;
                }
            }

            $milestones[] = [
                'status' => $statusKey,
                'title' => $stageInfo['title'],
                'description' => $stageInfo['description'],
                'icon' => $stageInfo['icon'],
                'state' => $state,
                'is_current' => $isCurrent
            ];
        }

        // Progress percentage for progress bar
        $progressPct = 10;
        if (!$isCancelled && !$isDeliveryFailed && !$isReturned && $currentIndex !== false) {
            $progressPct = (int)round((($currentIndex + 1) / count($standardStages)) * 100);
        }

        return [
            'order' => $order,
            'current_status' => $currentStatus,
            'is_cancelled' => $isCancelled,
            'is_delivery_failed' => $isDeliveryFailed,
            'is_returned' => $isReturned,
            'milestones' => $milestones,
            'progress_percent' => $progressPct,
            'status_history' => $order['history'] ?? []
        ];
    }

    /**
     * Metadata helper for order status badges and labels
     */
    public function getStatusMetadata(string $status): array
    {
        $map = [
            self::ORDER_STATUS_PENDING => [
                'label' => 'Pending Confirmation',
                'class' => 'status-pending',
                'badge_style' => 'background: rgba(255, 215, 0, 0.15); color: #ffd700; border: 1px solid rgba(255, 215, 0, 0.4);',
                'icon' => 'fa-clock'
            ],
            self::ORDER_STATUS_CONFIRMED => [
                'label' => 'Confirmed',
                'class' => 'status-confirmed',
                'badge_style' => 'background: rgba(0, 245, 255, 0.15); color: var(--cyan); border: 1px solid var(--cyan);',
                'icon' => 'fa-clipboard-check'
            ],
            self::ORDER_STATUS_PROCESSING => [
                'label' => 'Processing',
                'class' => 'status-processing',
                'badge_style' => 'background: rgba(184, 41, 247, 0.15); color: #c084fc; border: 1px solid rgba(184, 41, 247, 0.4);',
                'icon' => 'fa-gears'
            ],
            self::ORDER_STATUS_SHIPPED => [
                'label' => 'Shipped',
                'class' => 'status-shipped',
                'badge_style' => 'background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4);',
                'icon' => 'fa-truck'
            ],
            self::ORDER_STATUS_OUT_FOR_DELIVERY => [
                'label' => 'Out for Delivery',
                'class' => 'status-out-for-delivery',
                'badge_style' => 'background: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.4);',
                'icon' => 'fa-motorcycle'
            ],
            self::ORDER_STATUS_DELIVERED => [
                'label' => 'Delivered',
                'class' => 'status-delivered',
                'badge_style' => 'background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4);',
                'icon' => 'fa-circle-check'
            ],
            self::ORDER_STATUS_CANCELLED => [
                'label' => 'Cancelled',
                'class' => 'status-cancelled',
                'badge_style' => 'background: rgba(255, 0, 85, 0.15); color: #ff3377; border: 1px solid rgba(255, 0, 85, 0.4);',
                'icon' => 'fa-ban'
            ],
            self::ORDER_STATUS_DELIVERY_FAILED => [
                'label' => 'Delivery Failed',
                'class' => 'status-failed',
                'badge_style' => 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);',
                'icon' => 'fa-triangle-exclamation'
            ],
            self::ORDER_STATUS_RETURNED => [
                'label' => 'Returned',
                'class' => 'status-returned',
                'badge_style' => 'background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.4);',
                'icon' => 'fa-arrow-rotate-left'
            ]
        ];

        return $map[$status] ?? [
            'label' => ucfirst(strtolower($status)),
            'class' => 'status-unknown',
            'badge_style' => 'background: rgba(255, 255, 255, 0.1); color: #fff;',
            'icon' => 'fa-circle-question'
        ];
    }

    /**
     * Metadata helper for payment status badges
     */
    public function getPaymentStatusMetadata(string $status): array
    {
        $map = [
            'PENDING' => [
                'label' => 'Payment Due upon Delivery',
                'badge_style' => 'background: rgba(255, 215, 0, 0.12); color: #ffd700; border: 1px solid rgba(255, 215, 0, 0.3);',
                'icon' => 'fa-hand-holding-dollar'
            ],
            'PAID' => [
                'label' => 'Paid (COD Collected)',
                'badge_style' => 'background: rgba(0, 255, 136, 0.12); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.3);',
                'icon' => 'fa-circle-check'
            ],
            'FAILED' => [
                'label' => 'Payment Failed',
                'badge_style' => 'background: rgba(255, 0, 85, 0.12); color: #ff3377; border: 1px solid rgba(255, 0, 85, 0.3);',
                'icon' => 'fa-circle-xmark'
            ]
        ];

        return $map[$status] ?? [
            'label' => ucfirst(strtolower($status)),
            'badge_style' => 'background: rgba(255, 255, 255, 0.1); color: #fff;',
            'icon' => 'fa-question'
        ];
    }
}
