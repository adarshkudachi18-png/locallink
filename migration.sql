-- Migration for Local Link - Add new columns
-- Run this against existing database

-- Add mobile_number to users table
ALTER TABLE users ADD COLUMN mobile_number VARCHAR(20) DEFAULT NULL AFTER email;

-- Add OTP columns to users table
ALTER TABLE users ADD COLUMN otp VARCHAR(6) DEFAULT NULL AFTER reset_token_expiry;
ALTER TABLE users ADD COLUMN otp_expiry DATETIME DEFAULT NULL AFTER otp;
ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER otp_expiry;

-- Add customer_mobile and delivery_status to orders table
ALTER TABLE orders ADD COLUMN customer_mobile VARCHAR(20) DEFAULT NULL AFTER status;
ALTER TABLE orders ADD COLUMN delivery_status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending' AFTER customer_mobile;
