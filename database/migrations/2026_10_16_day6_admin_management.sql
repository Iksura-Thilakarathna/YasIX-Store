-- YasIX Store - Day 6 Schema Migration
-- Admin Management: Inventory Audit Logs, Category Status, Payment Collections, and Warranty Review Notes

USE `yasix_store`;

-- 1. Create inventory_logs table for auditable stock adjustments
CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NULL,
  `change_quantity` INT NOT NULL,
  `previous_stock` INT NOT NULL,
  `new_stock` INT NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_inventory_product` (`product_id`),
  INDEX `idx_inventory_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Enhance categories table: Add status ENUM
SET @exist_cat_status := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'categories' 
    AND COLUMN_NAME = 'status'
);
SET @sql_add_cat_status := IF(
  @exist_cat_status = 0,
  'ALTER TABLE `categories` ADD COLUMN `status` ENUM(\'ACTIVE\', \'INACTIVE\') DEFAULT \'ACTIVE\' AFTER `icon_class`',
  'SELECT "categories.status column already exists"'
);
PREPARE stmt FROM @sql_add_cat_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Enhance products table: Expand status ENUM to include INACTIVE
ALTER TABLE `products`
  MODIFY COLUMN `status` ENUM('AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED', 'INACTIVE') DEFAULT 'AVAILABLE';

-- 4. Enhance payments table: Add collection audit fields
SET @exist_collected_at := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'payments' 
    AND COLUMN_NAME = 'collected_at'
);
SET @sql_add_collected_at := IF(
  @exist_collected_at = 0,
  'ALTER TABLE `payments` ADD COLUMN `collected_at` DATETIME NULL AFTER `status`',
  'SELECT "payments.collected_at already exists"'
);
PREPARE stmt FROM @sql_add_collected_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_collected_by := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'payments' 
    AND COLUMN_NAME = 'collected_by_user_id'
);
SET @sql_add_collected_by := IF(
  @exist_collected_by = 0,
  'ALTER TABLE `payments` ADD COLUMN `collected_by_user_id` INT NULL AFTER `collected_at`, ADD CONSTRAINT `fk_payments_collected_by` FOREIGN KEY (`collected_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL',
  'SELECT "payments.collected_by_user_id already exists"'
);
PREPARE stmt FROM @sql_add_collected_by;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_payment_notes := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'payments' 
    AND COLUMN_NAME = 'notes'
);
SET @sql_add_payment_notes := IF(
  @exist_payment_notes = 0,
  'ALTER TABLE `payments` ADD COLUMN `notes` VARCHAR(255) NULL AFTER `collected_by_user_id`',
  'SELECT "payments.notes already exists"'
);
PREPARE stmt FROM @sql_add_payment_notes;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Enhance warranty_claims table: Add internal notes and reviewer
SET @exist_internal_notes := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'warranty_claims' 
    AND COLUMN_NAME = 'internal_notes'
);
SET @sql_add_internal_notes := IF(
  @exist_internal_notes = 0,
  'ALTER TABLE `warranty_claims` ADD COLUMN `internal_notes` TEXT NULL AFTER `resolution_details`',
  'SELECT "warranty_claims.internal_notes already exists"'
);
PREPARE stmt FROM @sql_add_internal_notes;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_reviewed_by := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'warranty_claims' 
    AND COLUMN_NAME = 'reviewed_by_user_id'
);
SET @sql_add_reviewed_by := IF(
  @exist_reviewed_by = 0,
  'ALTER TABLE `warranty_claims` ADD COLUMN `reviewed_by_user_id` INT NULL AFTER `internal_notes`, ADD CONSTRAINT `fk_warranty_claims_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL',
  'SELECT "warranty_claims.reviewed_by_user_id already exists"'
);
PREPARE stmt FROM @sql_add_reviewed_by;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_resolved_at := (
  SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'warranty_claims' 
    AND COLUMN_NAME = 'resolved_at'
);
SET @sql_add_resolved_at := IF(
  @exist_resolved_at = 0,
  'ALTER TABLE `warranty_claims` ADD COLUMN `resolved_at` DATETIME NULL AFTER `reviewed_by_user_id`',
  'SELECT "warranty_claims.resolved_at already exists"'
);
PREPARE stmt FROM @sql_add_resolved_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
