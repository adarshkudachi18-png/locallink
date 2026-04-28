-- Manual fix for orders table - add missing columns
-- Run this in Railway MySQL console if automatic fix doesn't work

ALTER TABLE orders ADD COLUMN customer_mobile VARCHAR(20) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN delivery_status ENUM('pending','shipped','delivered','cancelled') DEFAULT 'pending';
