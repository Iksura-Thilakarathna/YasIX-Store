-- YasIX Store - Day 5 Schema Migration
-- Order Tracking, Status History, Delivery Snapshot, and Warranty Enhancements

USE `yasix_store`;

-- 1. Modify orders table: Expand status ENUM and add delivered_at
ALTER TABLE `orders` 
  MODIFY COLUMN `status` ENUM('PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'OUT_FOR_DELIVERY', 'DELIVERED', 'CANCELLED', 'DELIVERY_FAILED', 'RETURNED') DEFAULT 'PENDING';

-- Conditionally add delivered_at column if not exists
SET @exist_delivered_at := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME = 'delivered_at'
);

SET @sql_add_delivered_at := IF(
  @exist_delivered_at = 0,
  'ALTER TABLE `orders` ADD COLUMN `delivered_at` DATETIME NULL AFTER `payment_status`',
  'SELECT "delivered_at column already exists"'
);
PREPARE stmt FROM @sql_add_delivered_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Modify order_items table: Add purchase-time warranty_months snapshot
SET @exist_warranty_months := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'order_items' 
    AND COLUMN_NAME = 'warranty_months'
);

SET @sql_add_warranty_months := IF(
  @exist_warranty_months = 0,
  'ALTER TABLE `order_items` ADD COLUMN `warranty_months` INT NOT NULL DEFAULT 0 AFTER `subtotal`',
  'SELECT "warranty_months column already exists"'
);
PREPARE stmt FROM @sql_add_warranty_months;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill historical order_items warranty_months from products
UPDATE `order_items` oi
JOIN `products` p ON oi.product_id = p.id
SET oi.warranty_months = p.warranty_months
WHERE oi.warranty_months = 0;

-- 3. Create order_status_history table for auditing lifecycle transitions
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `previous_status` VARCHAR(50) NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `changed_by_user_id` INT NULL,
  `actor_role` VARCHAR(50) DEFAULT 'CUSTOMER',
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_status_history_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Enhance warranty_claims table with claim_reason, admin_decision, resolution_details and CLOSED status
SET @exist_claim_reason := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'warranty_claims' 
    AND COLUMN_NAME = 'claim_reason'
);
SET @sql_add_claim_reason := IF(
  @exist_claim_reason = 0,
  'ALTER TABLE `warranty_claims` ADD COLUMN `claim_reason` VARCHAR(100) NOT NULL DEFAULT \'Other\' AFTER `claim_number`',
  'SELECT "claim_reason already exists"'
);
PREPARE stmt FROM @sql_add_claim_reason;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_admin_decision := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'warranty_claims' 
    AND COLUMN_NAME = 'admin_decision'
);
SET @sql_add_admin_decision := IF(
  @exist_admin_decision = 0,
  'ALTER TABLE `warranty_claims` ADD COLUMN `admin_decision` VARCHAR(50) DEFAULT \'PENDING\' AFTER `status`',
  'SELECT "admin_decision already exists"'
);
PREPARE stmt FROM @sql_add_admin_decision;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_resolution_details := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'warranty_claims' 
    AND COLUMN_NAME = 'resolution_details'
);
SET @sql_add_resolution_details := IF(
  @exist_resolution_details = 0,
  'ALTER TABLE `warranty_claims` ADD COLUMN `resolution_details` TEXT NULL AFTER `admin_decision`',
  'SELECT "resolution_details already exists"'
);
PREPARE stmt FROM @sql_add_resolution_details;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `warranty_claims`
  MODIFY COLUMN `status` ENUM('SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED', 'RESOLVED', 'CLOSED') DEFAULT 'SUBMITTED';
